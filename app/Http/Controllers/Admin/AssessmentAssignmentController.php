<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * B2: manajemen assignment instrumen per program (D1: admin + operations).
 * Tambah add-on / round baru, ubah window (dalam periode program), tutup
 * assignment. MCMI-3 selalu ditolak (D5 — hanya per-person grant).
 */
class AssessmentAssignmentController extends Controller
{
    private const ADDON_CODES = ['bigfive', 'disc', 'ergonomic', 'phq9', 'gad7', 'cbi', 'bfitrader', 'disc24'];

    public function index(CompanyWellbeingProgram $program)
    {
        $program->load(['perusahaan', 'wellbeingPackage']);

        $assignments = AssessmentAssignment::where('assignable_type', $program->getMorphClass())
            ->where('assignable_id', $program->id)
            ->withCount('submissions as completers_count')
            ->orderBy('instrument_code')->orderBy('round')
            ->get();

        return view('admin.assessment-assignments.index', [
            'program' => $program,
            'assignments' => $assignments,
            'addonCodes' => self::ADDON_CODES,
        ]);
    }

    public function store(Request $request, CompanyWellbeingProgram $program)
    {
        // H1: normalisasi kode — "MCMI3" tidak boleh lolos gate klinis.
        $request->merge(['instrument_code' => strtolower(trim((string) $request->input('instrument_code')))]);
        $data = $request->validate([
            'instrument_code' => [
                'required', 'string', 'max:32',
                \Illuminate\Validation\Rule::exists('assessment_instruments', 'code'),
                function ($attribute, $value, $fail) {
                    if (in_array($value, AssessmentAssignment::CLINICAL, true)
                        || \App\Models\AssessmentInstrument::where('code', $value)->value('domain') === 'clinical') {
                        $fail('Instrumen klinis (MCMI-III) hanya bisa diberikan per karyawan lewat grant dengan indikasi klinis, bukan massal.');
                    }
                },
            ],
            'source' => ['required', 'string', 'in:core,addon'],
            'addon_price' => ['required_if:source,addon', 'nullable', 'numeric', 'min:0', 'max:999999999'],
            'round' => ['required', 'integer', 'min:1', 'max:12'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
        ]);

        if ($data['source'] === 'addon' && !in_array($data['instrument_code'], self::ADDON_CODES, true)) {
            throw ValidationException::withMessages([
                'instrument_code' => 'Add-on hanya untuk instrumen berbayar: bigfive, disc, ergonomic, phq9, gad7, cbi, bfitrader, disc24.',
            ]);
        }

        // Window harus dalam periode program.
        [$inPeriod, $message] = $this->windowInPeriod($program, $data['opens_at'], $data['closes_at']);
        if (!$inPeriod) {
            throw ValidationException::withMessages(['closes_at' => $message]);
        }

        // Unique (assignable, code, audience, round) — friendly error.
        $exists = AssessmentAssignment::where('assignable_type', $program->getMorphClass())
            ->where('assignable_id', $program->id)
            ->where('instrument_code', $data['instrument_code'])
            ->where('audience', AssessmentAssignment::AUDIENCE_EMPLOYEE)
            ->where('round', $data['round'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'instrument_code' => "Assignment {$data['instrument_code']} round {$data['round']} sudah ada untuk program ini.",
            ]);
        }

        $assignment = AssessmentAssignment::create([
            'assignable_type' => $program->getMorphClass(),
            'assignable_id' => $program->id,
            'instrument_code' => $data['instrument_code'],
            'audience' => AssessmentAssignment::AUDIENCE_EMPLOYEE,
            'source' => $data['source'],
            'addon_price' => $data['source'] === 'addon' ? $data['addon_price'] : null,
            'round' => $data['round'],
            'opens_at' => $data['opens_at'],
            'closes_at' => $data['closes_at'],
            'status' => 'active',
            'assigned_by' => auth()->id(),
        ]);

        AdminAction::record(auth()->id(), 'assessment_assignment.created', $assignment, [
            'instrument_code' => $assignment->instrument_code,
            'round' => $assignment->round,
            'source' => $assignment->source,
        ]);

        return redirect()->route('admin.programs.assessments', $program)
            ->with('success', "Assignment {$assignment->instrument_code} R{$assignment->round} ditambahkan.");
    }

    public function update(Request $request, CompanyWellbeingProgram $program, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_type === $program->getMorphClass()
            && (int) $assignment->assignable_id === $program->id, 404);

        $data = $request->validate([
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
        ]);

        [$inPeriod, $message] = $this->windowInPeriod($program, $data['opens_at'], $data['closes_at']);
        if (!$inPeriod) {
            throw ValidationException::withMessages(['closes_at' => $message]);
        }

        $assignment->update([
            'opens_at' => $data['opens_at'],
            'closes_at' => $data['closes_at'],
        ]);

        AdminAction::record(auth()->id(), 'assessment_assignment.window_updated', $assignment, [
            'instrument_code' => $assignment->instrument_code,
            'round' => $assignment->round,
            'opens_at' => $data['opens_at'],
            'closes_at' => $data['closes_at'],
        ]);

        return redirect()->route('admin.programs.assessments', $program)
            ->with('success', "Window {$assignment->instrument_code} R{$assignment->round} diperbarui.");
    }

    public function close(CompanyWellbeingProgram $program, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_type === $program->getMorphClass()
            && (int) $assignment->assignable_id === $program->id, 404);

        $assignment->update(['status' => 'closed']);

        AdminAction::record(auth()->id(), 'assessment_assignment.closed', $assignment, [
            'instrument_code' => $assignment->instrument_code,
            'round' => $assignment->round,
        ]);

        return redirect()->route('admin.programs.assessments', $program)
            ->with('success', "Assignment {$assignment->instrument_code} R{$assignment->round} ditutup.");
    }

    /** D6: window harus dalam periode program. */
    private function windowInPeriod(CompanyWellbeingProgram $program, string $opens, string $closes): array
    {
        if ($opens < $program->starts_at->toDateString() || $closes > $program->ends_at->toDateString()) {
            return [false, "Window harus dalam periode program ({$program->starts_at->format('d M Y')} s.d. {$program->ends_at->format('d M Y')})."];
        }

        return [true, null];
    }
}
