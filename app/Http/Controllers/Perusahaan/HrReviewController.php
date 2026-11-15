<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\PerusahaanSession;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;

/**
 * Antrean review HR (F1.1): request konsultasi employee masuk status
 * 'scheduled' + scheduled_at NULL + consultant_id NULL = menunggu review HR.
 * HR approve → consultant di-assign (konsultan tetap ACC jadwal via schedulePerusahaan,
 * pattern lama). HR reject → 'cancelled' + cancel_reason (pakai nilai enum existing;
 * CHECK constraint tidak mengizinkan 'rejected' — lihat migrasi 2026_10_21).
 */
class HrReviewController extends Controller
{
    /** Sesi menunggu review HR milik tenant aktif. */
    private function pendingQuery()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403);

        return PerusahaanSession::with(['employee', 'consultant.user'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('status', 'scheduled')
            ->whereNull('scheduled_at')
            ->whereNull('consultant_id');
    }

    private function resolvePending(PerusahaanSession $session): PerusahaanSession
    {
        $found = $this->pendingQuery()->where('id', $session->id)->first();
        abort_unless($found, 422, 'Request tidak menunggu review HR.');

        return $found;
    }

    public function index(Request $r)
    {
        $requests = $this->pendingQuery()
            ->when($r->string('type')->trim()->toString(), fn ($q, $t) => $q->where('konsultan_type', $t))
            ->orderBy('id')
            ->get();

        $perusahaan = PerusahaanContext::current();

        return view('perusahaan.hr-review', [
            'perusahaan' => $perusahaan,
            'requests' => $requests,
            'pools' => $requests->groupBy('konsultan_type')
                ->map(fn ($g) => ConsultantPool::poolFor('perusahaan', $perusahaan->id)
                    ->where('type', $g->first()->konsultan_type)->values())
                ->filter(fn ($p) => $p->isNotEmpty()),
            'activeFilter' => $r->string('type')->trim()->toString(),
        ]);
    }

    /** Approve: assign consultant (dipilih HR, atau pool terdekat beban). */
    public function approve(Request $r, PerusahaanSession $session)
    {
        $pending = $this->resolvePending($session);
        $r->validate(['consultant_id' => ['nullable', 'integer']]);

        $pool = ConsultantPool::poolFor('perusahaan', $pending->perusahaan_id)
            ->where('type', $pending->konsultan_type)->values();
        abort_if($pool->isEmpty(), 422, 'Tidak ada konsultan tipe ini di program kamu.');

        if ($r->filled('consultant_id')) {
            abort_unless(ConsultantPool::validChoice('perusahaan', $pending->perusahaan_id,
                $r->integer('consultant_id'), $pending->konsultan_type), 422, 'Konsultan tidak ada di pool program.');
            $assigned = $pool->firstWhere('id', $r->integer('consultant_id'));
        } else {
            $assigned = $pool->sortBy(fn ($c) => PerusahaanSession::where('consultant_id', $c['id'])
                ->where('status', 'scheduled')->count())->first();
        }

        $pending->update(['consultant_id' => $assigned['id']]);

        AdminAction::record($r->user()->id, 'hr_review.approve', $pending, [
            'consultant_id' => $assigned['id'],
        ]);

        return back()->with('success', 'Request disetujui — ' . $assigned['name'] . ' ditugaskan. Konsultan tinggal set jadwal.');
    }

    /** Reject: cancelled + alasan (terlihat ke employee di riwayat). */
    public function reject(Request $r, PerusahaanSession $session)
    {
        $pending = $this->resolvePending($session);
        $r->validate(['reason' => ['required', 'string', 'max:1000']]);

        $pending->update([
            'status' => 'cancelled',
            'cancel_reason' => '[HR] ' . $r->reason,
            'cancelled_at' => now(),
        ]);

        // Reason = free-text HR, sudah terlihat ke employee di cancel_reason —
        // dicatat apa adanya (tidak menambah eksposur PII baru).
        AdminAction::record($r->user()->id, 'hr_review.reject', $pending, [
            'reason' => $r->reason,
        ]);

        return back()->with('success', 'Request ditolak. Karyawan bisa mengirim request baru.');
    }
}
