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

        // wv4 feature A: Risk & Priority Map — agregat risiko per area
        // (departemen), threshold 10. Label "area prioritas", bukan
        // "karyawan high-risk" (R10: no individual flagging).
        $metrics['areaRisk'] = $this->areaRisk($perusahaan);

        return view('perusahaan.wellbeing', ['perusahaan' => $perusahaan] + $metrics);
    }

    private function areaRisk($perusahaan)
    {
        [$month, $start, $end] = \App\Support\AggregatePrivacy::month();
        $assessed = \App\Models\Perusahaan\PerusahaanAssessment::where('perusahaan_id', $perusahaan->id)
            ->where('taken_at', '>=', $start)->where('taken_at', '<', $end)
            ->whereNotNull('employee_id')
            ->get()
            ->groupBy('employee_id')->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());

        $byEmployee = \App\Models\Perusahaan\PerusahaanEmployee::where('perusahaan_id', $perusahaan->id)
            ->get(['id', 'departement_id'])
            ->groupBy('departement_id');

        $rows = $byEmployee->map(function ($members, $departementId) use ($assessed) {
            $scores = $assessed->whereIn('employee_id', $members->pluck('id'))->pluck('score');
            // R10: suppress area < 10 responden.
            if ($scores->count() < 10) {
                return ['name' => $members->first()->departement?->name ?? 'Tanpa departemen', 'n' => $scores->count(), 'avg' => null];
            }

            return [
                'name' => $members->first()->departement?->name ?? 'Tanpa departemen',
                'n' => $scores->count(),
                'avg' => round($scores->avg(), 1),
            ];
        })->values()->sortByDesc(fn ($r) => $r['avg'] ?? PHP_FLOAT_MAX)->values();

        return ['month' => $month, 'rows' => $rows];
    }
}
