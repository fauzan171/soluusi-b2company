<?php

namespace App\Console\Commands;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\SekolahSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredSessions extends Command
{
    protected $signature = 'sessions:cancel-expired';

    protected $description = 'Auto-cancel scheduled B2B sessions past the no check-in window and restore quota.';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('session.auto_cancel_hours', 24));
        $total = 0;

        foreach (['perusahaan' => PerusahaanSession::class, 'sekolah' => SekolahSession::class] as $vertical => $model) {
            $ids = $model::where('status', 'scheduled')
                ->whereNull('checked_in_at')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', $cutoff)
                ->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            $model::whereIn('id', $ids)->update([
                'status' => 'cancelled',
                'quota_consumed' => false,
                'cancel_reason' => 'Auto-cancelled: no check-in',
                'cancelled_at' => now(),
            ]);
            DB::table('booking_claims')->where('owner_type', $vertical)->whereIn('owner_id', $ids)->delete();

            $total += $ids->count();
            $this->info("{$vertical}: {$ids->count()} session(s) auto-cancelled.");
        }

        $this->info("Total: {$total} session(s) auto-cancelled.");

        return self::SUCCESS;
    }
}
