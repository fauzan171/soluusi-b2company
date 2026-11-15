<?php

namespace App\Console\Commands;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\SekolahSession;
use App\Models\SessionSlotProposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredSessions extends Command
{
    protected $signature = 'sessions:cancel-expired';

    protected $description = 'Auto-cancel scheduled B2B sessions past the no check-in window and restore quota.';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('booking.auto_cancel_hours', 24));
        $pendingCutoff = now()->subHours((int) config('booking.pending_cancel_hours', 72));
        $total = 0;

        foreach (['perusahaan' => PerusahaanSession::class, 'sekolah' => SekolahSession::class] as $vertical => $model) {
            $total += $this->sweepExpired($vertical, $model, $cutoff);
            $total += $this->sweepStalePendingRequests($vertical, $model, $pendingCutoff);
        }

        $this->info("Total: {$total} session(s) auto-cancelled.");

        return self::SUCCESS;
    }

    /**
     * Sesi terjadwal melewati window check-in → auto-cancel, kuota restore.
     * Re-fetch di dalam transaction dengan lockForUpdate: sesi yang check-in tepat
     * sebelum update tidak tersentuh (race closed).
     */
    private function sweepExpired(string $vertical, string $model, \DateTimeInterface $cutoff): int
    {
        return DB::transaction(function () use ($vertical, $model, $cutoff) {
            $sessions = $model::where('status', 'scheduled')
                ->whereNull('checked_in_at')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', $cutoff)
                ->lockForUpdate()
                ->get();

            // Filter ulang in-memory setelah lock: status bisa berubah antara select dan lock.
            $sessions = $sessions->filter(fn ($s) => $s->status === 'scheduled' && $s->checked_in_at === null);

            if ($sessions->isEmpty()) {
                return 0;
            }

            foreach ($sessions as $session) {
                // Sweep bukan late-cancel — kuota selalu di-restore.
                \App\Support\SessionStateMachine::cancel($session, 'Auto-cancelled: no check-in');
            }

            $ids = $sessions->pluck('id');
            DB::table('booking_claims')->where('owner_type', $vertical)->whereIn('owner_id', $ids)->delete();
            // Proposal hanya milik perusahaan_sessions — jangan sentuh saat sweep sekolah
            // (id numerik bisa tabrakan lintas vertikal).
            if ($vertical === 'perusahaan') {
                $this->rejectPendingProposals($ids);
            }

            $this->info("{$vertical}: {$sessions->count()} session(s) auto-cancelled (no check-in).");

            return $sessions->count();
        });
    }

    /**
     * Permintaan menunggu review HR (scheduled + scheduled_at NULL + consultant NULL)
     * yang macet > pending_cancel_hours → auto-cancel + restore kuota + reject proposal.
     */
    private function sweepStalePendingRequests(string $vertical, string $model, \DateTimeInterface $cutoff): int
    {
        $hours = (int) config('booking.pending_cancel_hours', 72);
        $reason = "[HR] Permintaan kedaluwarsa (tidak diproses dalam {$hours} jam)";

        return DB::transaction(function () use ($vertical, $model, $cutoff, $reason) {
            $sessions = $model::where('status', 'scheduled')
                ->whereNull('scheduled_at')
                ->whereNull('consultant_id')
                ->where('created_at', '<', $cutoff)
                ->lockForUpdate()
                ->get();

            $sessions = $sessions->filter(fn ($s) => $s->status === 'scheduled'
                && $s->scheduled_at === null && $s->consultant_id === null);

            if ($sessions->isEmpty()) {
                return 0;
            }

            foreach ($sessions as $session) {
                \App\Support\SessionStateMachine::cancel($session, $reason);
            }

            $ids = $sessions->pluck('id');
            DB::table('booking_claims')->where('owner_type', $vertical)->whereIn('owner_id', $ids)->delete();
            if ($vertical === 'perusahaan') {
                $this->rejectPendingProposals($ids);
            }

            $this->info("{$vertical}: {$sessions->count()} pending request(s) auto-cancelled (stale review).");

            return $sessions->count();
        });
    }

    /** Proposal slot yang masih pending untuk sesi cancelled → rejected (tidak yatim). */
    private function rejectPendingProposals($sessionIds): void
    {
        if ($sessionIds->isEmpty()) {
            return;
        }

        SessionSlotProposal::whereIn('session_id', $sessionIds)
            ->where('status', SessionSlotProposal::STATUS_PENDING)
            ->update(['status' => SessionSlotProposal::STATUS_REJECTED]);
    }
}
