<?php

namespace App\Services\Screening;

use App\Models\AssessmentInstrument;
use App\Models\ScreeningQuestion;
use App\Support\AggregatePrivacy;
use Illuminate\Support\Collection;

/**
 * Engine skor instrumen assessment definition-driven (W4, K12).
 * Semua skala/bobots/band dibaca dari AssessmentInstrument + ScreeningQuestion;
 * instrumen baru tidak menyentuh file ini.
 *
 * Scoring type:
 * - sum      : PHQ-9 / GAD-7 — total mentah (0-27 / 0-21).
 * - sum_x4   : WHO-5 — sum 0-25 × 4 → 0-100 (parity formula path lama).
 * - mean_x25 : CBI — mean item (0-4) × 25 → 0-100.
 */
class InstrumentScoring
{
    /**
     * @param  array<string, int>  $answers  kode item => nilai mentah
     * @return array{score:int, band:array{label:string, note:string}, escalated:bool}
     */
    public static function score(AssessmentInstrument $instrument, array $answers): array
    {
        $items = self::items($instrument);
        $raw = 0;
        $count = 0;

        foreach ($items as $item) {
            $bound = (int) ($item->weight ?? self::defaultMax($item));
            if ($bound <= 0) {
                continue;
            }
            $count++;
            $value = (int) ($answers[$item->code] ?? 0);
            $raw += $item->reverse ? $bound - $value : $value;
        }

        $score = match ($instrument->scoring_type) {
            'sum_x4' => (int) ($raw * 4),
            'mean_x25' => (int) round($raw / max(1, $count) * 25),
            default => $raw,
        };

        $escalated = collect($instrument->escalate_items ?? [])
            ->contains(fn ($code) => (int) ($answers[$code] ?? 0) > 0);

        return [
            'score' => $score,
            'band' => [
                'label' => $instrument->bandLabelFor($score),
                'note' => $instrument->bandNoteFor($score),
            ],
            'escalated' => $escalated,
        ];
    }

    /**
     * HR agregat per instrumen (n≥5 suppression via AggregatePrivacy::safe()).
     * Band distribusi diambil dari latest assessment per karyawan —
     * tidak pernah identitas individu; eskalasi hanya COUNT.
     *
     * @return array{assessed:int, bands:?array<string,int>, escalatedCount:?int}
     */
    public static function aggregate($perusahaan, AssessmentInstrument $instrument): array
    {
        $latest = $perusahaan->assessments()
            ->where('instrument_code', $instrument->code)
            ->where('status', 'completed')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());

        $bandCounts = [];
        foreach ($latest as $row) {
            $label = $instrument->bandLabelFor((int) $row->score);
            $bandCounts[$label] = ($bandCounts[$label] ?? 0) + 1;
        }

        $assessed = $latest->count();
        if (! AggregatePrivacy::safe(array_values($bandCounts))) {
            return ['assessed' => $assessed, 'bands' => null, 'escalatedCount' => null];
        }

        return [
            'assessed' => $assessed,
            'bands' => $bandCounts,
            'escalatedCount' => (int) $latest->where('escalated', true)->count(),
        ];
    }

    /** @return Collection<ScreeningQuestion> */
    public static function items(AssessmentInstrument $instrument): Collection
    {
        return ScreeningQuestion::where('vertical', 'perusahaan')
            ->where('instrument_code', $instrument->code)
            ->orderBy('ord')
            ->get();
    }

    /** Fallback bound bila weight NULL (skala likert umum dari type). */
    private static function defaultMax(ScreeningQuestion $q): int
    {
        return match ($q->type) {
            'freq0-5' => 5,
            'freq0-4' => 4,
            'freq0-3' => 3,
            default => 0,
        };
    }
}
