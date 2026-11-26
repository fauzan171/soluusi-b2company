<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Services\Klinik\HapPervormaService;
use App\Support\RevenueShare;

/**
 * wv3: progress Checkpoint 40 Konsultasi per klinik (research2-infra §5).
 * Satu query completedCount (definisi sama dengan payout engine HAP) —
 * progress bar + badge "Checkpoint tercapai" pada count >= 40.
 *
 * wv5: kolom "Bagi hasil maks." — rate cap S&K Klinik §5 dari median harga
 * sesi terverifikasi + estimasi (rate × sum). View-only, payout engine HAP
 * tidak tersentuh.
 */
class KlinikCheckpointController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::orderBy('name')->get()->map(function (Klinik $klinik) {
            $counts = HapPervormaService::completedCounts($klinik);
            $share = $this->revenueShare($klinik, $counts['windowStart']);

            return (object) [
                'id' => $klinik->id,
                'name' => $klinik->name,
                'brand' => $klinik->brand,
                'is_active' => $klinik->is_active,
                'completed' => $counts['sinceMou'],
                'progress' => min(100, (int) round(($counts['sinceMou'] / 40) * 100)),
                'reached' => $counts['sinceMou'] >= 40,
                'shareRate' => $share['rate'],
                'estimatedShare' => $share['estimate'],
                'verifiedSum' => $share['sum'],
            ];
        });

        return view('admin.klinik-checkpoints', ['kliniks' => $kliniks]);
    }

    /**
     * Rate cap §5 + estimasi bagi hasil dari sesi terverifikasi klinik
     * (window start sama dengan HapPervormaService::completedCounts:
     * mou_at ?? created_at). Harga sesi = package_price, fallback amount
     * saat package_price = 0. Median → tier; estimasi = rate × sum.
     */
    private function revenueShare(Klinik $klinik, $windowStart): array
    {
        // SQLite tidak punya native MEDIAN — ambil harga terurut lalu
        // ambil nilai tengah di PHP (satu query agregat per klinik).
        $prices = ConsultationSession::where('klinik_id', $klinik->id)
            ->whereNotNull('completion_verified_at')
            ->where('completion_verified_at', '>=', $windowStart)
            ->where('payment_status', '!=', ConsultationSession::PAYMENT_STATUS_REFUNDED)
            ->orderBy(\DB::raw('CASE WHEN package_price > 0 THEN package_price ELSE amount END'))
            ->select(\DB::raw('CASE WHEN package_price > 0 THEN package_price ELSE amount END AS effective_price'))
            ->pluck('effective_price')
            ->map(fn ($p) => (float) $p)
            ->values()
            ->all();

        if ($prices === []) {
            return ['rate' => null, 'estimate' => null, 'sum' => 0.0];
        }

        $median = $prices[intdiv(count($prices), 2)];
        if (count($prices) % 2 === 0) {
            $median = ($prices[count($prices) / 2 - 1] + $prices[count($prices) / 2]) / 2;
        }

        $rate = RevenueShare::rate((int) round($median));
        $sum = array_sum($prices);

        return [
            'rate' => $rate,
            'estimate' => $rate !== null ? $rate * $sum : null,
            'sum' => $sum,
        ];
    }
}
