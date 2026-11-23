<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Http\Request;

/**
 * Pengerjaan assessment multi-instrumen (W4, K12/K13). 1 instrumen per sesi.
 * Path WHO-5 lama (EmployeeController::assessmentStore) tetap jalan —
 * controller ini engine baru definition-driven, tidak menyentuh Sekolah.
 * wv1: domain gate per program aktif (research2-infra §1) + grant MCMI per employee.
 */
class AssessmentController extends Controller
{
    /**
     * wv1: resolve instrumen dengan gate domain program aktif + grant.
     * - inactive → 404 (kecuali MCMI-style grant eksplisit employee ini).
     * - program aktif → domain instrumen harus ∈ snapshot assessment_domains
     *   (domain null = instrumen umum, selalu lolos).
     */
    private function resolveInstrument(PerusahaanEmployee $employee, string $code): AssessmentInstrument
    {
        $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();

        $granted = InstrumentGrant::where('employee_id', $employee->id)
            ->where('instrument_code', $instrument->code)->exists();

        abort_unless($instrument->is_active || $granted, 404);

        if ($program = \App\Services\WellbeingQuotaService::activeProgram($employee->perusahaan)) {
            abort_unless($granted || $instrument->allowedForProgram($program->assessment_domains), 404);
        }

        return $instrument;
    }

    /** Daftar instrumen aktif + riwayat milik sendiri (untuk kartu retake 30 hari). */
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        $program = \App\Services\WellbeingQuotaService::activeProgram($employee->perusahaan);
        $domains = $program?->assessment_domains;
        $grantedCodes = InstrumentGrant::where('employee_id', $employee->id)
            ->pluck('instrument_code')->all();

        $instruments = AssessmentInstrument::where('is_active', true)->orWhereIn('code', $grantedCodes)
            ->orderBy('id')->get()
            ->filter(fn ($instrument) => in_array($instrument->code, $grantedCodes)
                || $instrument->allowedForProgram($domains))
            ->map(function ($instrument) use ($employee) {
                $latest = $employee->assessments()
                    ->where('instrument_code', $instrument->code)
                    ->latest('taken_at')->first();
                $instrument->latest_score = $latest?->score;
                $instrument->latest_band = $latest ? $instrument->bandLabelFor((int) $latest->score) : null;
                $instrument->latest_taken = $latest?->taken_at;
                $instrument->needs_retake = !$latest || $latest->taken_at->lt(now()->subDays(30));
                return $instrument;
            });

        return view('perusahaan.assessment-instruments', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'instruments' => $instruments,
        ]);
    }

    /** Form satu instrumen (1 instrumen per sesi tampil). */
    public function form(string $code)
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        $instrument = $this->resolveInstrument($employee, $code);

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
        $instrument = $this->resolveInstrument($employee, $code);
        $items = InstrumentScoring::items($instrument);

        $rules = ['answers' => ['required', 'array', 'size:'.$items->count()]];
        foreach ($items as $item) {
            $rules["answers.{$item->code}"] = ['required', 'integer', 'min:0', 'max:'.(int) $item->weight];
        }
        $r->validate($rules);

        $answers = collect($r->input('answers'))->only($items->pluck('code'))->all();
        $result = InstrumentScoring::score($instrument, $answers);

        // WB: R3 cap — program wellbeing aktif membatasi peserta assessment
        // distinct employee (retake employee sama tidak menambah count).
        // FIX (review wb REV#3): scope ke window program (taken_at >= starts_at)
        // — assessment pra-program tidak dihitung.
        if ($program = \App\Services\WellbeingQuotaService::activeProgram($employee->perusahaan)
            and $program->assessment_max_participants !== null) {
            // QA8-F1: lock row program — cek cap + create ter-serialisasi
            // (dua submit paralel tidak bisa lolos sisa 1 slot).
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
