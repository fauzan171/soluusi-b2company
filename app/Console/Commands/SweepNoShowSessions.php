<?php

namespace App\Console\Commands;

use App\Models\Perusahaan\PerusahaanSession;
use App\Services\QuotaService;
use Illuminate\Console\Command;

/**
 * W2 — sweeper no-show (MASTER-PLAN K2): sesi scheduled yang scheduled_at-nya
 * lewat > 24 jam tanpa pernah dimulai → status no_show + ledger forfeited
 * (hangus TERHITUNG terpakai). Sesi tanpa scheduled_at (menunggu ACC konsultan)
 * tidak pernah di-sweep.
 *
 * Dijadwalkan di Kernel::schedule tiap jam; prod butuh `schedule:run`
 * aktif (systemd timer / cron — lihat deploy note W8).
 */
class SweepNoShowSessions extends Command
{
    protected $signature = 'sessions:sweep-no-show';
    protected $description = 'Tandai sesi terjadwal lewat >24 jam sebagai no_show + ledger forfeited';

    public function handle(): int
    {
        $stale = PerusahaanSession::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now()->subHours(24))
            ->whereNull('checked_in_at')
            ->whereNull('completed_at')
            ->get();

        $swept = 0;
        foreach ($stale as $session) {
            // WB: routing — sesi wellbeing → hangus di ledger wellbeing saja;
            // else QuotaService legacy (path lama identik).
            if (\App\Services\WellbeingQuotaService::reservedLedgerFor($session)) {
                \App\Services\WellbeingQuotaService::forfeit($session, 'no_show: lewat jadwal > 24 jam (sweeper)');
            } else {
                QuotaService::forfeit($session, 'no_show: lewat jadwal > 24 jam (sweeper)');
            }
            $swept++;
        }

        $this->info("No-show swept: {$swept} dari {$stale->count()} kandidat.");

        return self::SUCCESS;
    }
}
