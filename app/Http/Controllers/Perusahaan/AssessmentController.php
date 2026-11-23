<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Services\AssessmentAccess;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/**
 * Pengerjaan assessment multi-instrumen (W4, K12/K13). 1 instrumen per sesi.
 * B1: akses assignment-driven (D2) — resolveInstrument gate domain lama diganti
 * AssessmentAccess::resolveForEmployee (assignment terbuka) + InstrumentGrant
 * (clinical bypass D5). Satu submission per (assignment, employee) per window
 * (D6, unique index). Legacy WHO-5 path dihapus (D8) — route redirect ke index.
 */
class AssessmentController extends Controller
{
    /** Label domain instrumen untuk kartu index. */
    private const DOMAIN_LABELS = [
        'mh' => 'Mental Health', 'wellbeing' => 'Kesejahteraan', 'nutrition' => 'Gizi',
        'ergonomic' => 'Ergonomi', 'clinical' => 'Klinis',
    ];

    /**
     * B1: resolve instrumen employee — assignment terbuka atau grant klinis.
     * Tanpa keduanya → 404 (bug "semua instrumen terlihat" mati, D2).
     *
     * @return array{0: AssessmentInstrument, 1: ?AssessmentAssignment}
     */
    private function resolveInstrument(PerusahaanEmployee $employee, string $code): array
    {
        $assignment = AssessmentAccess::resolveForEmployee($employee, $code);
        $granted = in_array($code, AssessmentAccess::grantedCodes($employee), true);

        if ($assignment === null && ! $granted) {
            abort(404);
        }

        $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();

        // instrumen non-aktif hanya boleh via grant (MCMI-style, wv1).
        abort_unless($instrument->is_active || $granted, 404);

        return [$instrument, $assignment];
    }

    /** Redirect kalau employee sudah submit utk assignment ini (D6). */
    private function submittedRedirect(PerusahaanEmployee $employee, AssessmentAssignment $assignment): ?\Illuminate\Http\RedirectResponse
    {
        if (! AssessmentAccess::hasSubmitted($assignment, $employee->id, 'perusahaan')) {
            return null;
        }

        $row = PerusahaanAssessment::where('assessment_assignment_id', $assignment->id)
            ->where('employee_id', $employee->id)->first();

        return $row
            ? redirect()->route('perusahaan.assessment.result', $row)
                ->with('info', 'Asesmen putaran ini sudah Anda kerjakan.')
            : redirect()->route('perusahaan.assessment.instruments')
                ->with('info', 'Asesmen putaran ini sudah Anda kerjakan.');
    }

    /** Daftar instrumen dari assignment terbuka + grant; riwayat milik sendiri. */
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();

        // kartu assignment: instrument eager-load utk name/domain/is_addon.
        $assignments = AssessmentAccess::forEmployee($employee)
            ->load('instrument')
            ->map(function (AssessmentAssignment $a) use ($employee) {
                $instrument = $a->instrument;
                if (! $instrument) {
                    return null;
                }
                $latest = $employee->assessments()
                    ->where('instrument_code', $a->instrument_code)
                    ->where('assessment_assignment_id', $a->id)
                    ->latest('taken_at')->first();
                $a->card_name = $instrument->name;
                $a->card_domain = self::DOMAIN_LABELS[$instrument->domain] ?? 'Umum';
                $a->card_done_at = $latest?->taken_at;
                return $a;
            })
            ->filter()
            ->values();

        // grant klinis (MCMI-style) di luar assignment (D5).
        $grants = collect(AssessmentAccess::grantedCodes($employee))
            ->map(fn (string $code) => AssessmentInstrument::where('code', $code)->first())
            ->filter(fn ($i) => $i && ($i->is_active || true))
            ->values();

        $history = $employee->assessments()
            ->whereNotNull('instrument_code')
            ->with('instrument')
            ->orderByDesc('taken_at')
            ->limit(20)
            ->get()
            ->map(function (PerusahaanAssessment $row) {
                $row->history_name = $row->instrument?->name
                    ?? AssessmentInstrument::where('code', $row->instrument_code)->value('name')
                    ?? $row->instrument_code;
                $row->history_band = $row->instrument
                    ? $row->instrument->bandLabelFor((int) $row->score)
                    : $row->band();
                return $row;
            });

        return view('perusahaan.assessment-instruments', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'assignments' => $assignments,
            'grants' => $grants,
            'history' => $history,
        ]);
    }

    /** Form satu instrumen (1 instrumen per sesi tampil). */
    public function form(string $code)
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        [$instrument, $assignment] = $this->resolveInstrument($employee, $code);

        // sudah submit utk putaran ini → langsung ke hasil (D6, no retake).
        if ($assignment && $redirect = $this->submittedRedirect($employee, $assignment)) {
            return $redirect;
        }

        return view('perusahaan.assessment-instrument', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'instrument' => $instrument,
            'items' => InstrumentScoring::items($instrument),
        ]);
    }

    /** Simpan jawaban → skor via engine; raw answers + ScreeningAnswer rows. */
    public function store(Request $r, string $code)
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        [$instrument, $assignment] = $this->resolveInstrument($employee, $code);
        $items = InstrumentScoring::items($instrument);

        $rules = ['answers' => ['required', 'array', 'size:'.$items->count()]];
        foreach ($items as $item) {
            $rules["answers.{$item->code}"] = ['required', 'integer', 'min:0', 'max:'.(int) $item->weight];
        }
        $r->validate($rules);

        // sudah submit utk putaran ini → tanpa row baru (D6).
        if ($assignment && $redirect = $this->submittedRedirect($employee, $assignment)) {
            return $redirect;
        }

        $answers = collect($r->input('answers'))->only($items->pluck('code'))->all();
        $result = InstrumentScoring::score($instrument, $answers);

        try {
            // QA8-F1: lock + create dalam satu transaksi — cap Discovery
            // ter-serialisasi, unique (assignment, employee) ditangkap.
            $row = \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $instrument, $assignment, $answers, $result) {
                // WB: R3 cap — program wellbeing aktif membatasi peserta assessment
                // distinct employee (retake employee sama tidak menambah count).
                // Scope ke window program (taken_at >= starts_at).
                if ($program = \App\Services\WellbeingQuotaService::activeProgram($employee->perusahaan)
                    and $program->assessment_max_participants !== null) {
                    \App\Models\CompanyWellbeingProgram::whereKey($program->id)->lockForUpdate()->first();
                    $scoped = PerusahaanAssessment::where('perusahaan_id', $employee->perusahaan_id)
                        ->where('taken_at', '>=', $program->starts_at);
                    $distinct = (clone $scoped)->distinct('employee_id')->count('employee_id');
                    $already = (clone $scoped)->where('employee_id', $employee->id)->exists();
                    abort_if($distinct >= $program->assessment_max_participants && ! $already,
                        403, 'Kuota peserta assessment penuh. Hubungi HR untuk info program.');
                }

                $row = PerusahaanAssessment::create([
                    'perusahaan_id' => $employee->perusahaan_id,
                    'employee_id' => $employee->id,
                    'departement_id' => $employee->departement_id,
                    'instrument_code' => $instrument->code,
                    'answers' => $answers,
                    'score' => $result['score'],
                    'escalated' => $result['escalated'],
                    'section' => 'mental_health',
                    'status' => 'completed',
                    'taken_at' => now(),
                    'assessment_assignment_id' => $assignment?->id,
                ]);

                foreach ($answers as $qCode => $value) {
                    \App\Models\ScreeningAnswer::create([
                        'assessable_type' => PerusahaanAssessment::class,
                        'assessable_id' => $row->id,
                        'question_code' => $qCode,
                        'value' => (string) $value,
                        'answered_at' => now(),
                    ]);
                }

                return $row;
            });
        } catch (UniqueConstraintViolationException) {
            // double-submit race: unique (assignment, employee) — sudah dikerjakan.
            return redirect()->route('perusahaan.assessment.instruments')
                ->with('info', 'Asesmen putaran ini sudah Anda kerjakan.');
        }

        return redirect()->route('perusahaan.assessment.result', $row)
            ->with('success', 'Terima kasih! Hasil asesmen kamu sudah tersimpan.');
    }

    /** Hasil individual — hanya pemilik (employee) yang boleh. */
    public function result(PerusahaanAssessment $assessment)
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        abort_unless($assessment->employee_id === $employee->id, 403);

        $instrument = AssessmentInstrument::where('code', $assessment->instrument_code)->first();

        // wv1: subskor dimensi (DASS-21/Big Five/DISC) dari jawaban tersimpan.
        $dimensions = null;
        if ($instrument?->dimensions) {
            $scoring = InstrumentScoring::score($instrument, $assessment->answers ?? []);
            $dimensions = $scoring['dimensions'];
        }

        return view('perusahaan.assessment-result', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'assessment' => $assessment,
            'instrument' => $instrument,
            'bandLabel' => $instrument ? $instrument->bandLabelFor((int) $assessment->score) : $assessment->band(),
            'bandNote' => $instrument ? $instrument->bandNoteFor((int) $assessment->score) : '',
            'escalated' => (bool) $assessment->escalated,
            'dimensions' => $dimensions,
        ]);
    }
}
