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
     * @return array{score:int, band:array{label:string, note:string}, escalated:bool, dimensions:?array<int, array{key:string, label:string, score:int, band:array{label:string, note:string}}>}
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
            'dimensions' => self::dimensionScores($instrument, $items, $answers),
        ];
    }

    /**
     * wv1: subskor per dimensi (DASS-21/Big Five/DISC). Item dimensi dijumlah
     * (reverse ikut), dikali multiplier (DASS-21 ×2), band per dimensi.
     * Instrumen tanpa dimensions → null (single-score, perilaku lama).
     *
     * @param  array<int, array{key:string, label:string, score:int, band:array{label:string, note:string}}>|null
     */
    private static function dimensionScores(AssessmentInstrument $instrument, $items, array $answers): ?array
    {
        $defs = $instrument->dimensions;
        if (!$defs) {
            return null;
        }

        $byCode = collect($items)->keyBy('code');

        $out = [];
        foreach ($defs as $key => $def) {
            $sum = 0;
            foreach ($def['items'] ?? [] as $itemCode) {
                $item = $byCode[$itemCode] ?? null;
                if (!$item) {
                    continue;
                }
                $bound = (int) ($item->weight ?? self::defaultMax($item));
                $value = (int) ($answers[$itemCode] ?? 0);
                $sum += $item->reverse ? $bound - $value : $value;
            }
            $dimScore = (int) ($sum * ($def['multiplier'] ?? 1));
            $out[] = [
                'key' => $key,
                'label' => $def['label'] ?? $key,
                'score' => $dimScore,
                'band' => $instrument->dimensionBandFor($key, $dimScore),
            ];
        }
        return $out;
    }

    /**
     * HR agregat per instrumen (n≥5 suppression via AggregatePrivacy::safe()).
     * Band distribusi diambil dari latest assessment per karyawan —
     * tidak pernah identitas individu; eskalasi hanya COUNT.
     * wv1 (R10/K14): threshold perusahaan 10 — sekuat data laporan program.
     *
     * B4: optional $assignment — scope ke rows assignment tsb + legacy rows
     * (NULL assessment_assignment_id, instrument_code match) di tenant yang sama.
     * Tanpa $assignment → perilaku lama (semua rows kode tsb).
     *
     * @return array{assessed:int, bands:?array<string,int>, escalatedCount:?int, mean:?float}
     */
    public static function aggregate($perusahaan, AssessmentInstrument $instrument, $assignment = null): array
    {
        $rows = $perusahaan->assessments()
            ->where('instrument_code', $instrument->code)
            ->where('status', 'completed');

        if ($assignment !== null) {
            $rows = $rows->where(function ($q) use ($assignment) {
                $q->where('assessment_assignment_id', $assignment->id);
                // row tanpa assignment_id hanya milik assignment legacy (bukan program/round).
                if ($assignment->source === \App\Models\AssessmentAssignment::SOURCE_LEGACY) {
                    $q->orWhereNull('assessment_assignment_id');
                }
            });
        }

        $latest = $rows->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => $group->sortByDesc('taken_at')->first());

        $bandCounts = [];
        foreach ($latest as $row) {
            $label = $instrument->bandLabelFor((int) $row->score);
            $bandCounts[$label] = ($bandCounts[$label] ?? 0) + 1;
        }

        $assessed = $latest->count();
        if (! AggregatePrivacy::safe(array_values($bandCounts), 10)) {
            return ['assessed' => $assessed, 'bands' => null, 'escalatedCount' => null, 'mean' => null];
        }

        return [
            'assessed' => $assessed,
            'bands' => $bandCounts,
            // M1: eskalasi 1-9 bisa re-identifikasi → tampil hanya 0 atau ≥10.
            'escalatedCount' => ($esc = $latest->where('escalated', true)->count()) === 0 || $esc >= 10 ? $esc : null,
            'mean' => round($latest->avg('score'), 1),
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
