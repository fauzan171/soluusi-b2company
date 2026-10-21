<?php

namespace App\Services\Screening;

use Illuminate\Support\Collection;

/**
 * Mesin skor screening multi-domain — port dari kiosk TVS `functions/_lib/scoring.ts`.
 *
 * Tiga domain dinormalkan ke 0–100 supaya band bisa dibandingkan antar domain.
 * Nilai mentah tidak pernah ditimpa: reverse-score hanya dihitung di sini.
 * Rekomendasi konsultasi = MAXIMUM antar domain, bukan rata-rata — satu temuan
 * terburuk yang menentukan kebutuhan tindak lanjut (kiosk sdd.md §6.2).
 */
class ScoringService
{
    /** Band per domain: 0–24 hijau, 25–49 kuning, 50–74 oranye, 75–100 merah. */
    public const DEFAULT_BANDS = [
        ['max' => 24, 'color' => 'low', 'consult' => 0],
        ['max' => 49, 'color' => 'medium', 'consult' => 1],
        ['max' => 74, 'color' => 'high', 'consult' => 2],
        ['max' => 100, 'color' => 'critical', 'consult' => 3],
    ];

    /** Bobot maksimum per tipe pertanyaan yang ikut skor. */
    private const TYPE_MAX = [
        'freq0-4' => 4,
        'pain4' => 3,
        'yesno' => 1,
        'scale1-10' => 10,
        // 'single' & 'multi' tidak punya skala ordinal seragam → bobot dari mapping khusus
    ];

    /**
     * Hitung skor satu domain dari jawaban.
     *
     * @param  string  $section  mental_health | nutrition_lifestyle | ergonomic
     * @param  array<string, mixed>  $answers  code => nilai mentah
     * @param  Collection  $questions  baris screening_questions domain ini
     * @return array{score:int,max:int,raw:int}
     */
    public static function scoreSection(string $section, array $answers, Collection $questions): array
    {
        $raw = 0;
        $max = 0;

        foreach ($questions as $q) {
            $bound = self::typeMax($q);
            if ($bound <= 0) {
                continue;   // pertanyaan konteks (Q29/Q30/Q32/Q34) — tidak masuk skor
            }
            // Penyebut = bobot SELURUH soal ter-skor di domain ini, bukan hanya yang
            // terjawab. Kalau penyebut ikut mengecil, menjawab satu soal saja akan
            // menghasilkan 100% — pemeriksaan jadi bisa diloloskan dengan mengosongkan
            // jawaban. Soal kosong = kontribusi nol.
            $max += $bound;

            if (! array_key_exists($q->code, $answers) || $answers[$q->code] === null || $answers[$q->code] === '') {
                continue;
            }
            $value = (int) $answers[$q->code];
            $raw += $q->reverse ? $bound - $value : $value;
        }

        if ($max === 0) {
            return ['score' => 0, 'max' => 0, 'raw' => 0];
        }

        return [
            'score' => (int) round($raw / $max * 100),
            'max' => $max,
            'raw' => $raw,
        ];
    }

    /** Batas atas nilai untuk satu pertanyaan; 0 = tidak diskor. */
    private static function typeMax(object $q): int
    {
        // Nutrition Q15–Q20 bertipe `single`/`yesno` tapi ikut skor lewat bobot tetap.
        static $nutrition = ['Q15' => 2, 'Q16' => 2, 'Q17' => 2, 'Q18' => 2, 'Q19' => 1, 'Q20' => 1];
        if (isset($nutrition[$q->code])) {
            return $nutrition[$q->code];
        }

        return self::TYPE_MAX[$q->type] ?? 0;
    }

    /** Band untuk satu skor; band menerima skor <= max-nya. */
    public static function bandFor(int $score, array $bands = self::DEFAULT_BANDS): array
    {
        foreach ($bands as $band) {
            if ($score <= $band['max']) {
                return $band;
            }
        }

        return end($bands) ?: ['max' => 100, 'color' => 'critical', 'consult' => 3];
    }

    /**
     * Rekomendasi karyawan: konsultasi & warna terburuk antar domain.
     *
     * @param  array<string, int>  $scores  section => skor 0–100
     * @return array{consult:int,color:string,section:?string}
     */
    public static function recommend(array $scores, array $bands = self::DEFAULT_BANDS): array
    {
        $rank = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];
        $consult = 0;
        $color = 'low';
        $worst = null;

        foreach ($scores as $section => $score) {
            $band = self::bandFor((int) $score, $bands);
            if ($band['consult'] > $consult) {
                $consult = $band['consult'];
            }
            if (($rank[$band['color']] ?? 0) > ($rank[$color] ?? 0)) {
                $color = $band['color'];
                $worst = $section;
            }
        }

        return ['consult' => $consult, 'color' => $color, 'section' => $worst];
    }

    /** Label Indonesia untuk band — dipakai di UI & laporan. */
    public static function colorLabel(string $color): string
    {
        return match ($color) {
            'low' => 'Hijau',
            'medium' => 'Kuning',
            'high' => 'Oranye',
            'critical' => 'Merah',
            default => ucfirst($color),
        };
    }
}