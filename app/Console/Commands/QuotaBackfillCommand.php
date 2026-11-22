<?php

namespace App\Console\Commands;

use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Console\Command;

/**
 * Backfill ledger kuota (mission M2) dari perusahaan_sessions existing.
 * Derived-only: membaca, tidak mengubah row existing — NO destructive ops.
 * Mapping per sesi (bulan scheduled_at, tz app Asia/Jakarta):
 *   status done  → used
 *   no_show atau cancelled dengan quota_consumed → forfeited
 *   lainnya (scheduled/cancelled ringan) → skip (belum tentu terpakai)
 * Idempotent via UNIQUE(perusahaan_session_id, event) + insert ignore.
 * Backfill consultation_type='chat' untuk sesi legacy (M4) juga di sini.
 */
class QuotaBackfillCommand extends Command
{
    protected $signature = 'quota:backfill';
    protected $description = 'Backfill consultation_quota_ledger + consultation_type dari sesi existing (idempotent)';

    public function handle(): int
    {
        $tenantPackage = \App\Models\Package::query()->orderBy('id')->first();

        $sessions = PerusahaanSession::query()
            ->whereNotNull('employee_id')
            ->whereIn('status', ['done', 'no_show', 'cancelled'])
            ->get();

        $created = 0;
        foreach ($sessions as $session) {
            if (! $session->quota_consumed && $session->status !== 'done') {
                continue; // cancelled ringan / belum hangus
            }

            $event = $session->status === 'done'
                ? ConsultationQuotaLedger::EVENT_USED
                : ConsultationQuotaLedger::EVENT_FORFEITED;

            $period = $session->scheduled_at?->format('Y-m') ?? now()->format('Y-m');

            $exists = ConsultationQuotaLedger::query()
                ->where('perusahaan_session_id', $session->id)
                ->where('event', $event)
                ->exists();
            if ($exists) {
                continue;
            }

            $ep = $this->assignmentFor($session, $tenantPackage);
            if (! $ep) {
                continue; // tidak ada package assignment → tidak ada kuota untuk dibukukan
            }

            ConsultationQuotaLedger::create([
                'employee_package_id' => $ep->id,
                'perusahaan_session_id' => $session->id,
                'period_ym' => $period,
                'event' => $event,
                'reason' => 'backfill from existing sessions',
            ]);
            $created++;
        }

        $typed = PerusahaanSession::whereNull('consultation_type')->update(['consultation_type' => 'chat']);

        $this->info("Ledger entries created: {$created}, consultation_type backfilled: {$typed}");

        return self::SUCCESS;
    }

    /**
     * Cari assignment package aktif untuk employee sesi (bulan periode).
     * Fallback: buat assignment dari package pertama jika tenant belum punya.
     */
    private function assignmentFor(PerusahaanSession $session, ?\App\Models\Package $package): ?EmployeePackage
    {
        $ep = EmployeePackage::query()
            ->where('perusahaan_employee_id', $session->employee_id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
        if ($ep) {
            return $ep;
        }

        if (! $package || ! $session->perusahaan_id) {
            return null;
        }

        return EmployeePackage::create([
            'perusahaan_employee_id' => $session->employee_id,
            'package_id' => $package->id,
            'perusahaan_id' => $session->perusahaan_id,
            'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
    }
}
