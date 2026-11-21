<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetrics;
use App\Services\ProgramAggregate;
use App\Support\PerusahaanContext;

/**
 * Kesejahteraan agregat — halaman detail semua chart dari card dashboard HR.
 */
class WellbeingController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $metrics = DashboardMetrics::build($perusahaan, 'perusahaan');
        $monthly = ProgramAggregate::monthly($perusahaan, 'perusahaan');
        $metrics['totalAssessed'] = $monthly['totalAssessed'] ?? 0;
        $metrics['byType'] = $monthly['byType'] ?? collect();
        $metrics['topics'] = $monthly['topics'] ?? collect();
        $metrics['riskDist'] = $monthly['riskDist'] ?? null;
        $metrics['riskSuppressed'] = $monthly['riskSuppressed'] ?? false;
        $metrics['groups'] = $monthly['groups'] ?? collect();
        $metrics['serviceShare'] = $metrics['byType']->map(fn ($c, $t) => round($c / max(1, $metrics['doneMonth']) * 100))->all();

        return view('perusahaan.wellbeing', ['perusahaan' => $perusahaan] + $metrics);
    }
}
