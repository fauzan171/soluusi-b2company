<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Sekolah\Sekolah;
use Illuminate\Http\Request;

/**
 * B3: assignment instrumen per sekolah oleh admin/ops (D2). Gate minor-safety:
 * audience student → hanya STUDENT_ALLOWED (D9); mcmi3 selalu ditolak (D5).
 */
class SekolahAssessmentAssignmentController extends Controller
{
    public function store(Request $r, Sekolah $sekolah)
    {
        $r->merge(['instrument_code' => strtolower(trim((string) $r->input('instrument_code')))]);
        $data = $r->validate([
            'instrument_code' => ['required', 'string', 'max:32'],
            'audience' => ['required', 'in:student,staff'],
            'round' => ['nullable', 'integer', 'min:1', 'max:99'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
        ]);

        $instrument = AssessmentInstrument::where('code', $data['instrument_code'])->first();
        abort_unless($instrument, 404);

        // D5: MCMI-III tidak pernah mass-assign.
        if (in_array($data['instrument_code'], AssessmentAssignment::CLINICAL, true) || $instrument->domain === 'clinical') {
            abort(422, 'Instrumen klinis (MCMI-III) hanya via grant per-person, tidak bisa mass-assign.');
        }
        // D9: minor safety.
        if ($data['audience'] === AssessmentAssignment::AUDIENCE_STUDENT
            && ! in_array($data['instrument_code'], AssessmentAssignment::STUDENT_ALLOWED, true)) {
            abort(422, 'Instrumen ini belum tersedia untuk siswa (butuh alur safeguarding)');
        }

        $duplicate = AssessmentAssignment::where('assignable_type', $sekolah->getMorphClass())
            ->where('assignable_id', $sekolah->id)->where('instrument_code', $data['instrument_code'])
            ->where('audience', $data['audience'])->where('round', $data['round'] ?? 1)->exists();
        if ($duplicate) {
            return back()->withErrors(['round' => 'Putaran ini sudah ada untuk instrumen & audience tersebut.'])->withInput();
        }

        $assignment = AssessmentAssignment::create([
            'assignable_type' => $sekolah->getMorphClass(),
            'assignable_id' => $sekolah->id,
            'instrument_code' => $data['instrument_code'],
            'audience' => $data['audience'],
            'source' => AssessmentAssignment::SOURCE_CORE,
            'round' => $data['round'] ?? 1,
            'opens_at' => $data['opens_at'] ?? today(),
            'closes_at' => $data['closes_at'] ?? null,
            'status' => 'active',
            'assigned_by' => auth()->id(),
        ]);

        AdminAction::record(auth()->id(), 'sekolah.assignment.create', $sekolah, [
            'assignment_id' => $assignment->id,
            'instrument_code' => $assignment->instrument_code,
            'audience' => $assignment->audience,
        ]);

        return redirect()->route('admin.tenants.sekolahs.show', $sekolah)
            ->with('success', "Assignment {$assignment->instrument_code} ({$assignment->audience}) dibuat.");
    }

    /** Tutup assignment (status=closed) — window berakhir lebih awal. */
    public function close(Request $r, Sekolah $sekolah, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_id === $sekolah->id
            && $assignment->assignable_type === $sekolah->getMorphClass(), 403);

        $assignment->update(['status' => 'closed']);
        AdminAction::record(auth()->id(), 'sekolah.assignment.close', $sekolah, ['assignment_id' => $assignment->id]);

        return back()->with('success', 'Assignment ditutup.');
    }

    public function reopen(Request $r, Sekolah $sekolah, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_id === $sekolah->id
            && $assignment->assignable_type === $sekolah->getMorphClass(), 403);

        $assignment->update(['status' => 'active']);
        AdminAction::record(auth()->id(), 'sekolah.assignment.reopen', $sekolah, ['assignment_id' => $assignment->id]);

        return back()->with('success', 'Assignment dibuka kembali.');
    }

    /** Edit window/round (audience & code tetap — bikin assignment baru bila butuh). */
    public function update(Request $r, Sekolah $sekolah, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_id === $sekolah->id
            && $assignment->assignable_type === $sekolah->getMorphClass(), 403);

        $data = $r->validate([
            'round' => ['required', 'integer', 'min:1', 'max:99'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
        ]);

        try {
            $assignment->update($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['round' => 'Putaran ini sudah ada untuk instrumen & audience tersebut.']);
        }
        AdminAction::record(auth()->id(), 'sekolah.assignment.update', $sekolah, ['assignment_id' => $assignment->id] + $data);

        return back()->with('success', 'Assignment diperbarui.');
    }
}
