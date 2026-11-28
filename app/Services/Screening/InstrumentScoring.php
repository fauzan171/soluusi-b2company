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
 * - disc_forced : DISC 24 (ac1) — forced-choice ipsatif: jawaban {DSnp, DSnk}
 *   per item; letter P/K dari config/assessment-disc.php key, count→line via
 *   conv p/k, diff=P−K→conv diff; combo line-positif dicocokkan ke 40 profil
 *   types. score = key profil (1-40), band = archetype/desc (dari bands
 *   instrumen), dimensions = line D/I/S/C.
 */
class InstrumentScoring
{
    /**
     * @param  array<string, int>  $answers  kode item => nilai mentah
     * @return array{score:int, band:array{label:string, note:string}, escalated:bool, dimensions:?array<int, array{key:string, label:string, score:int, band:array{label:string, note:string}}>}
     */
    public static function score(AssessmentInstrument $instrument, array $answers): array
    {
        if ($instrument->scoring_type === 'disc_forced') {
            return self::scoreDiscForced($instrument, $answers);
        }

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
     * ac1: DISC 24 forced-choice (ipsatif). Tabel dari config/assessment-disc.php.
     * Per item n: letterP = key[n].P[answers["DSnp"]], letterK = key[n].K[answers["DSnk"]]
     * ("*" netral di-skip). P-count/K-count per huruf → line via conv p/k
     * (count clamp 0-20); diff = P−K per huruf → conv diff[diff+22].
     * Combo = SET huruf line-positif (>0) — dicocokkan exact ke types.combo
     * (strip spasi); fallback: profil dengan huruf positif terbanyak.
     */
    private static function scoreDiscForced(AssessmentInstrument $instrument, array $answers): array
    {
        $cfg = config('assessment-disc');
        $letters = ['D', 'I', 'S', 'C'];

        $pCount = array_fill_keys($letters, 0);
        $kCount = array_fill_keys($letters, 0);

        $items = self::items($instrument);
        $ord = 0;
        foreach ($items as $item) {
            $n = (string) $item->ord;
            $key = $cfg['key'][$n] ?? null;
            if (!$key) {
                continue;
            }
            $ord++;
            $pIdx = (int) ($answers[$item->code.'p'] ?? 0);
            $kIdx = (int) ($answers[$item->code.'k'] ?? 0);
            $letterP = $key['P'][$pIdx] ?? '*';
            $letterK = $key['K'][$kIdx] ?? '*';
            if ($letterP !== '*' && isset($pCount[$letterP])) {
                $pCount[$letterP]++;
            }
            if ($letterK !== '*' && isset($kCount[$letterK])) {
                $kCount[$letterK]++;
            }
        }

        // Line per huruf: p/k count→line, diff=P−K (clamp -22..22) → line.
        $line = [];
        foreach ($letters as $i => $letter) {
            $pLine = $cfg['conv']['p'][min(20, max(0, $pCount[$letter]))][$i];
            $kLine = $cfg['conv']['k'][min(20, max(0, $kCount[$letter]))][$i];
            $diff = (int) round($pLine - $kLine);
            $diffIdx = min(44, max(0, $diff + 22));
            $line[$letter] = (float) $cfg['conv']['diff'][$diffIdx][$i];
        }

        // Combo = huruf dengan line positif.
        $positive = array_keys(array_filter($line, fn ($v) => $v > 0));
        $combo = implode('', $positive);

        $type = self::matchDiscType($cfg['types'], $combo);
        $score = (int) $type['key'];

        $dimensions = [];
        foreach ($letters as $letter) {
            $dimensions[] = [
                'key' => $letter,
                'label' => $instrument->dimensions[$letter]['label'] ?? $letter,
                'score' => (int) round($line[$letter]),
                'band' => ['label' => ($line[$letter] > 0 ? 'Tinggi' : ($line[$letter] < 0 ? 'Rendah' : 'Sedang')), 'note' => ''],
            ];
        }

        return [
            'score' => $score,
            'band' => [
                'label' => $instrument->bandLabelFor($score),
                'note' => $instrument->bandNoteFor($score),
            ],
            'escalated' => false,
            'dimensions' => $dimensions,
        ];
    }

    /** Combo string (tanpa spasi) → profil; fallback huruf terbanyak. */
    private static function matchDiscType(array $types, string $combo): array
    {
        $combo = str_replace(' ', '', $combo);
        $comboSet = str_split($combo);
        sort($comboSet);
        foreach ($types as $t) {
            foreach (explode('/', $t['combo']) as $alt) {
                $altSet = str_split(str_replace(' ', '', $alt));
                sort($altSet);
                if ($comboSet === $altSet && $comboSet !== []) {
                    return $t;
                }
            }
        }
        // fallback: profil dengan overlap huruf terbanyak.
        $best = $types[0];
        $bestOverlap = -1;
        foreach ($types as $t) {
            $altLetters = array_unique(str_split(str_replace(['/', ' '], '', $t['combo'])));
            $overlap = count(array_intersect($comboSet, $altLetters));
            if ($overlap > $bestOverlap) {
                $bestOverlap = $overlap;
                $best = $t;
            }
        }
        return $best;
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

    /** Instrumen di mana skor item tinggi = lebih menekan (top items bermakna). */
    public const SYMPTOM_INSTRUMENTS = ['pss10', 'cbi', 'dass21', 'phq9', 'gad7'];

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
            return ['assessed' => $assessed, 'bands' => null, 'escalatedCount' => null, 'mean' => null, 'topItems' => null, 'freeTextCount' => null];
        }

        return [
            'assessed' => $assessed,
            'bands' => $bandCounts,
            // M1: eskalasi 1-9 bisa re-identifikasi → tampil hanya 0 atau ≥10.
            'escalatedCount' => ($esc = $latest->where('escalated', true)->count()) === 0 || $esc >= 10 ? $esc : null,
            'mean' => round($latest->avg('score'), 1),
            // T3: top-3 item mean tertinggi (sama gate n≥10) + count pengisi free_text.
            // Hanya instrumen stres/gejala (skor tinggi = lebih berat); WHO-5/kepribadian tidak.
            'topItems' => in_array($instrument->code, self::SYMPTOM_INSTRUMENTS, true)
                ? self::topItems($instrument, $latest)
                : null,
            // sama dgn M1: 1-9 bisa re-identifikasi → tampil hanya 0 atau ≥10.
            'freeTextCount' => ($ft = $latest->whereNotNull('free_text')->count()) === 0 || $ft >= 10 ? $ft : null,
        ];
    }

    /**
     * T3: top-3 item mean skor tertinggi (jawaban dinormalkan reverse supaya
     * "semakin tinggi = semakin menekan"). Agregat — tanpa identitas individu.
     *
     * @param  \Illuminate\Support\Collection  $latest  rows latest per employee
     * @return array<int, array{code:string, text:string, mean:float}>|null
     */
    private static function topItems(AssessmentInstrument $instrument, $latest): ?array
    {
        $items = self::items($instrument);
        if ($items->isEmpty()) {
            return null;
        }

        $rows = $latest->pluck('answers', 'employee_id')->filter();
        if ($rows->isEmpty()) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            $bound = (int) ($item->weight ?? self::defaultMax($item));
            if ($bound <= 0) {
                continue;
            }
            $sum = 0;
            $n = 0;
            foreach ($rows as $answers) {
                if (! is_array($answers) || ! array_key_exists($item->code, $answers)) {
                    continue;
                }
                $value = (int) $answers[$item->code];
                $sum += $item->reverse ? $bound - $value : $value;
                $n++;
            }
            if ($n >= 10) { // gate n per item, bukan hanya per band
                $out[] = ['code' => $item->code, 'text' => $item->text, 'mean' => round($sum / $n, 1)];
            }
        }

        return collect($out)->sortByDesc('mean')->take(3)->values()->all();
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
