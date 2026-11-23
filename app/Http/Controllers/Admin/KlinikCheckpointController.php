<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Klinik\Klinik;
use App\Services\Klinik\HapPervormaService;

/**
 * wv3: progress Checkpoint 40 Konsultasi per klinik (research2-infra §5).
 * Satu query completedCount (definisi sama dengan payout engine HAP) —
 * progress bar + badge "Checkpoint tercapai" pada count >= 40.
 */
class KlinikCheckpointController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::orderBy('name')->get()->map(function (Klinik $klinik) {
            $counts = HapPervormaService::completedCounts($klinik);

            return (object) [
                'id' => $klinik->id,
                'name' => $klinik->name,
                'brand' => $klinik->brand,
                'is_active' => $klinik->is_active,
                'completed' => $counts['sinceMou'],
                'progress' => min(100, (int) round(($counts['sinceMou'] / 40) * 100)),
                'reached' => $counts['sinceMou'] >= 40,
            ];
        });

        return view('admin.klinik-checkpoints', ['kliniks' => $kliniks]);
    }
}
