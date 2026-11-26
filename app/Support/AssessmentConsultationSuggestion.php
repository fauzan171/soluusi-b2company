<?php

namespace App\Support;

use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\PerusahaanAssessment;

/**
 * A4: saran konsultasi deskriptif dari hasil asesmen terbaru — BUKAN diagnosis.
 * Jumlah sesi (documented constants, band label → saran):
 *   Berisiko Tinggi / Berat / Sangat Berat / Tinggi        → 4+ sesi (high)
 *   Perlu Perhatian / Sedang / Perlu Intervensi             → 2–3 sesi (moderate)
 *   lainnya (Sehat / Minimal / Rendah / Ringan)             → 1 sesi rutin (low)
 * Tipe konsultan dari domain instrumen: mh→psikolog, nutrition→nutrisionis,
 * ergonomic→fisioterapis, lainnya→psikolog.
 */
class AssessmentConsultationSuggestion
{
    /** Band labels yang menandakan kebutuhan pendampingan intensif. */
    private const HIGH_BANDS = ['Berisiko Tinggi', 'Berat', 'Sangat Berat', 'Tinggi'];

    /** Band labels menengah. */
    private const MODERATE_BANDS = ['Perlu Perhatian', 'Sedang', 'Perlu Intervensi'];

    private const SESSIONS = [
        'high' => '4+ sesi',
        'moderate' => '2–3 sesi',
        'low' => '1 sesi',
    ];

    private const CONSULTANT_BY_DOMAIN = [
        'mh' => 'psikolog',
        'nutrition' => 'nutrisionis',
        'ergonomic' => 'fisioterapis',
        'wellbeing' => 'psikolog',
    ];

    /** @return array{level: string, sessions: string, consultant: string, note: string}|null */
    public static function from(PerusahaanAssessment $assessment): ?array
    {
        $instrument = AssessmentInstrument::where('code', $assessment->instrument_code)->first();
        if (! $instrument) {
            return null;
        }

        $band = $instrument->bandLabelFor((int) $assessment->score);

        $level = in_array($band, self::HIGH_BANDS, true) ? 'high'
            : (in_array($band, self::MODERATE_BANDS, true) ? 'moderate' : 'low');

        return [
            'level' => $level,
            'sessions' => self::SESSIONS[$level],
            'consultant' => self::CONSULTANT_BY_DOMAIN[$instrument->domain ?? 'mh'] ?? 'psikolog',
            'note' => \App\Support\DomainRecommendation::for($instrument->domain, $band),
        ];
    }
}
