<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\PerusahaanContext;

class ReportController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        return view('perusahaan.report', ['perusahaan' => $perusahaan] + ProgramAggregate::monthly($perusahaan, 'perusahaan'));
    }

    /**
     * B4: laporan asesmen per assignment program aktif (atau legacy perusahaan).
     * Completion vs headcount selalu tampil; distribusi band hanya saat n≥10
     * tiap band (AggregatePrivacy::safe); trend round hanya growth/impact
     * dengan tiap round n≥10 (D7). Tanpa identitas individu (D10).
     */
    public function assessment()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
        $assignable = $program
            ? $program
            : $perusahaan;

        $assignments = \App\Models\AssessmentAssignment::query()
            ->where('assignable_type', $assignable->getMorphClass())
            ->where('assignable_id', $assignable->id)
            ->where('audience', \App\Models\AssessmentAssignment::AUDIENCE_EMPLOYEE)
            // closed tetap tampil: hasil putaran lama dibutuhkan utk trend.
            ->orderBy('instrument_code')->orderBy('round')
            ->get();

        // Headcount dari kolom (bukan jumlah baris employee — karyawan tanpa
        // akun/asesmen tetap dihitung sebagai populasi).
        $registered = (int) ($perusahaan->employee_count ?: $perusahaan->employees()->count());

        $sections = $assignments->groupBy('instrument_code')->map(function ($group, $code) use ($perusahaan, $registered, $program) {
            $instrument = \App\Models\AssessmentInstrument::where('code', $code)->first();
            if (! $instrument || ! $instrument->is_active) {
                return null;
            }

            // Rows assignment tsb + legacy rows NULL id (D8 parity — data lama tetap terhitung).
            $completers = \App\Models\Perusahaan\PerusahaanAssessment::query()
                ->where('perusahaan_id', $perusahaan->id)
                ->where('status', 'completed')
                ->where(function ($q) use ($group, $perusahaan) {
                    $q->whereIn('assessment_assignment_id', $group->pluck('id'))
                        ->orWhere(function ($legacy) use ($perusahaan, $group) {
                            // row tanpa assignment_id hanya milik assignment legacy.
                            $legacy->whereRaw($group->contains('source', \App\Models\AssessmentAssignment::SOURCE_LEGACY) ? '1 = 1' : '1 = 0')
                                ->whereNull('assessment_assignment_id')
                                ->where('perusahaan_id', $perusahaan->id)
                                ->whereIn('instrument_code', $group->pluck('instrument_code'));
                        });
                })
                ->distinct('employee_id')
                ->count('employee_id');

            $rows = [];
            foreach ($group as $assignment) {
                $agg = \App\Services\Screening\InstrumentScoring::aggregate($perusahaan, $instrument, $assignment);
                $rows[] = [
                    'assignment' => $assignment,
                    'round' => $assignment->round,
                    'aggregate' => $agg,
                ];
            }

            $trend = null;
            $trendSuppressed = false;
            if ($program && in_array($program->wellbeingPackage?->code, ['growth', 'impact'], true) && $group->count() > 1) {
                $rounds = collect($rows)->filter(fn ($r) => $r['aggregate']['mean'] !== null);
                if ($rounds->count() === count($rows)) {
                    $trend = $rounds->map(fn ($r) => ['round' => $r['round'], 'mean' => $r['aggregate']['mean']])->values()->all();
                } else {
                    $trendSuppressed = true;
                }
            }

            return [
                'code' => $code,
                'instrument' => $instrument,
                'assignment' => $group->first(),
                'rounds' => $rows,
                'completers' => $completers,
                'registered' => $registered,
                'pct' => $registered > 0 ? round($completers / $registered * 100) : 0,
                'trend' => $trend,
                'trendSuppressed' => $trendSuppressed,
            ];
        })->filter()->values();

        return view('perusahaan.report-assessment', [
            'perusahaan' => $perusahaan,
            'program' => $program,
            'sections' => $sections,
        ]);
    }
}
