<?php

namespace App\Services;

use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use App\Services\Klinik\HapPervormaService;
use Carbon\Carbon;

/**
 * C3 — Pervorma (commission associate) generalize 3 vertikal, driver-based.
 *
 * Driver map: hap → klinik (delegasi penuh ke HapPervormaService — engine
 * existing dengan rate per-klinik + window 90 hari; JANGAN duplikasi logikanya
 * di sini), sap → sekolah, cap → perusahaan. Rates/triggers live di
 * config/pervorma.php (C2) — jangan hardcode.
 *
 * Trigger (task.md C3): klinik = verify sesi (HapPervormaService dipanggil
 * dari SessionVerificationController seperti sekarang); sekolah = sesi done;
 * perusahaan = sesi done + tenant activated (mou_at terisi). Stage-1 CAP
 * trigger string 'mou_at' = cair saat tenant activated, tanpa hitungan sesi.
 *
 * KNOWN GAP (ponytail): driver 'sap' saat ini tidak punya call site natural —
 * SekolahSession punya kolom completed_at tapi tidak ada controller/command
 * yang men-set-nya (flow sekolah belum punya tombol/flow "tandai sesi done").
 * SAP stage tetap idempotent dan akan cair begitu completed_at mulai diisi.
 * Upgrade path: tambahkan mark-done endpoint di portal BK + sweep, lalu
 * panggil PervormaService::evaluate('sap', $session->sekolah) di situ.
 *
 * Idempotent: unique(tenant_id, stage) di tiap tabel + firstOrCreate — evaluasi
 * berulang tidak membuat row ganda.
 */
class PervormaService
{
    /**
     * Evaluasi pervorma untuk satu tenant. $session hanya dipakai driver hap
     * (penandaan hap_pervorma_stage per sesi); sap/cap cukup tenant.
     *
     * @param  string  $vertical  'hap' | 'sap' | 'cap'
     * @return array row pervorma yang BARU tercipta (bukan yang sudah ada)
     */
    public static function evaluate(string $vertical, Klinik|Perusahaan|Sekolah $tenant, ?ConsultationSession $session = null): array
    {
        return match ($vertical) {
            // Driver hap = HapPervormaService (existing callers tidak berubah;
            // service ini delegasi ke sana supaya SATU engine HAP).
            'hap' => $session ? HapPervormaService::evaluate($session) : [],
            'sap' => self::evaluateSessionCount($tenant, SekolahPervorma::class, 'sekolah_id', config('pervorma.sap.stages')),
            'cap' => self::evaluateSessionCount($tenant, PerusahaanPervorma::class, 'perusahaan_id', config('pervorma.cap.stages')),
            default => throw new \InvalidArgumentException("Unknown pervorma vertical: {$vertical}"),
        };
    }

    /**
     * Driver generik sesi-count (sap + cap). Stage dengan trigger integer =
     * jumlah sesi done (completed_at terisi); trigger string 'mou_at' = tenant
     * activated.
     */
    protected static function evaluateSessionCount(Perusahaan|Sekolah $tenant, string $model, string $tenantKey, array $stages): array
    {
        $doneCount = $tenant->sessions()->whereNotNull('completed_at')->count();
        $activated = $tenant->mou_at !== null;

        $created = [];
        foreach ($stages as $stage => $def) {
            $reached = $def['trigger'] === 'mou_at'
                ? $activated
                : $doneCount >= (int) $def['trigger'];
            if (!$reached) {
                continue;
            }
            // firstOrCreate → idempotent, satu stage cair sekali per tenant.
            $pervorma = $model::firstOrCreate(
                [$tenantKey => $tenant->id, 'stage' => $stage],
                [
                    'associate_id' => $tenant->associate_id,
                    'amount' => $def['amount'],
                    'status' => 'due',
                    'due_at' => Carbon::now()->addWeekdays(7), // SLA payout 7 hari kerja
                ]
            );
            if ($pervorma->wasRecentlyCreated) {
                $created[] = $pervorma;
            }
        }

        return $created;
    }
}
