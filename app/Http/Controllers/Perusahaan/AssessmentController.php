<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Http\Request;

/**
 * Pengerjaan assessment multi-instrumen (W4, K12/K13). 1 instrumen per sesi.
 * Path WHO-5 lama (EmployeeController::assessmentStore) tetap jalan —
 * controller ini engine baru definition-driven, tidak menyentuh Sekolah.
 */
class AssessmentController extends Controller
{
    /** Daftar instrumen aktif + riwayat milik sendiri (untuk kartu retake 30 hari). */
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        $instruments = AssessmentInstrument::where('is_active', true)->orderBy('id')->get()
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
        $instrument = AssessmentInstrument::where('code', $code)->where('is_active', true)->firstOrFail();

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
        $instrument = AssessmentInstrument::where('code', $code)->where('is_active', true)->firstOrFail();
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
        if ($program = \App\Services\WellbeingQuotaService::activeProgram($employee->perusahaan)
            and $program->assessment_max_participants !== null) {
            $distinct = PerusahaanAssessment::where('perusahaan_id', $employee->perusahaan_id)
                ->distinct('employee_id')->count('employee_id');
            $already = PerusahaanAssessment::where('perusahaan_id', $employee->perusahaan_id)
                ->where('employee_id', $employee->id)->exists();
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

        return view('perusahaan.assessment-result', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'assessment' => $assessment,
            'instrument' => $instrument,
            'bandLabel' => $instrument ? $instrument->bandLabelFor((int) $assessment->score) : $assessment->band(),
            'bandNote' => $instrument ? $instrument->bandNoteFor((int) $assessment->score) : '',
            'escalated' => (bool) $assessment->escalated,
        ]);
    }
}
