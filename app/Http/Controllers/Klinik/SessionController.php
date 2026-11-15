<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Schedule;
use App\Services\Klinik\HapPervormaService;
use Illuminate\Http\Request;

/**
 * Session & dual-confirm (Build-NEW #4 + #11, M4, Spec §9).
 * Konsultan mark-done → customer confirm (guest via status page) → verified →
 * release (HAP stage eval). Fallback 24h tanpa confirm → flag ops review.
 */
class SessionController extends Controller
{
    /** Konsultan: daftar sesi miliknya. */
    public function index()
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant, 403, 'Bukan konsultan.');

        $sessions = ConsultationSession::where('consultant_id', $consultant->id)
            ->with(['schedule', 'consultantPackage', 'klinik'])
            ->orderByDesc('order_at')
            ->paginate(20);

        // Sesi karyawan perusahaan (CAP) yang ditugaskan ke konsultan ini — room chat.
        $companySessions = \App\Models\Perusahaan\PerusahaanSession::where('consultant_id', $consultant->id)
            ->with('employee.user')
            ->orderByDesc('scheduled_at')
            ->limit(50)
            ->get();

        return view('consultant.sessions', compact('sessions', 'consultant', 'companySessions'));
    }

    /** Konsultan check-in (pre-session window). */
    public function checkIn(ConsultationSession $session)
    {
        $this->authorize('checkIn', $session);
        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak dalam status scheduled.');

        $session->konsultan_checked_in_at = now();
        $session->save();

        return back()->with('success', 'Check-in berhasil.');
    }

    /** Konsultan mark-done (dual-confirm step 1). Notes = confidential. */
    public function markDone(Request $request, ConsultationSession $session)
    {
        $this->authorize('markDone', $session);
        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak bisa ditandai selesai.');

        $request->validate([
            'note' => 'nullable|string',      // clinical notes — konsultan + audit only
            'solution' => 'nullable|string',  // ringkasan yang boleh dibagikan ke customer
        ]);

        $session->note = $request->note;
        $session->solution = $request->solution;
        $session->konsultan_done_at = now();
        $session->save();

        $this->tryVerify($session);

        return back()->with('success', 'Sesi ditandai selesai. Menunggu konfirmasi customer (maks 24 jam).');
    }

    /** Customer confirm (guest via token status page) — dual-confirm step 2. */
    public static function customerConfirm(ConsultationSession $session): void
    {
        $session->customer_confirmed_at = now();
        $session->save();

        (new static)->tryVerify($session);
    }

    /** Verify completion bila kedua pihak sudah confirm → release. */
    protected function tryVerify(ConsultationSession $session): void
    {
        if ($session->completion_verified_at || !$session->konsultan_done_at || !$session->customer_confirmed_at) {
            return;
        }

        $session->completion_verified_at = now();
        $session->status = 'done';
        $session->completed_at = now();
        $session->save();

        // Release: HAP 3-stage pervorma evaluation (M6). Consultant fee = flat via
        // ConsultantFee::feeFor — payout batch Phase 2 (§15.3 DECIDED).
        HapPervormaService::evaluate($session);
    }

    /** Konsultan set jadwal untuk request konsultasi CAP (scheduled_at masih null). */
    public function schedulePerusahaan(Request $r, \App\Models\Perusahaan\PerusahaanSession $session)
    {
        $this->authorize('view', $session);
        $consultant = auth()->user()->consultants()->first();
        abort_unless($session->status === 'scheduled' && !$session->scheduled_at, 422, 'Sesi sudah dijadwalkan.');

        $r->validate(['schedule_id' => 'required|integer|exists:schedules,id']);

        $slot = Schedule::where('id', $r->schedule_id)
            ->where('consultant_id', $consultant->id)
            ->where('started_at', '>', now())
            ->whereDoesntHave('perusahaanSessions', fn ($q) => $q->whereIn('status', ['scheduled', 'done']))
            ->whereDoesntHave('consultationSession', fn ($q) => $q->whereIn('status', ['scheduled', 'done']))
            ->firstOrFail();

        $session->update(['schedule_id' => $slot->id]);
        // Lewat state machine: set schedule_version=1 + ended_at (konsisten dengan flow confirmSlot).
        \App\Support\SessionStateMachine::schedule($session, $slot->started_at, $slot->ended_at);
        return back()->with('success', 'Jadwal di-set.');
    }
}
