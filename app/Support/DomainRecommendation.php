<?php

namespace App\Support;

/**
 * wv1: teks rekomendasi per domain instrumen (My Recommendations, hasil
 * assessment). Teks generik non-klinis; domain clinical tidak dipakai di sini
 * (MCMI-III hanya via psikolog).
 */
class DomainRecommendation
{
    private const TEXT = [
        'mh' => [
            ' Sedang' => 'pertimbangkan konsultasi dengan psikolog melalui fitur booking untuk memahami kondisi ini lebih awal.',
            'Berat' => 'konsultasi dengan psikolog sangat disarankan — jadwalkan sesi melalui fitur booking.',
            'Sangat Berat' => 'segera bicara dengan profesional. Hubungi konsultan lewat fitur booking atau hotline krisis 119 ext. 8.',
            'Tinggi' => 'kelola beban dengan jeda teratur, dan pertimbangkan konsultasi bila terus berlanjut.',
            'Perlu Perhatian' => 'pantau kondisi ini dan pertimbangkan konsultasi bila berlanjut lebih dari 2 minggu.',
            'Perlu Intervensi' => 'perubahan kebiasaan bertahap + konsultasi profesional disarankan.',
        ],
        'nutrition' => [
            'Perlu Perhatian' => 'mulai perbaiki pola makan bertahap: sarapan teratur, tambah sayur/buah, kurangi minuman manis. Konsultasi gizi membantu.',
            'Perlu Intervensi' => 'konsultasi gizi disarankan untuk menyusun pola makan yang realistis dengan jam kerja kamu.',
        ],
        'ergonomic' => [
            'Sedang' => 'atur ulang stasiun kerja (layar sejajar mata, kaki menapak), lakukan peregangan 5 menit tiap 1 jam.',
            'Berat' => 'evaluasi ergonomi stasiun kerja + konsultasi medis untuk nyeri yang mengganggu aktivitas.',
            'Tinggi' => 'sesuaikan postur dan stasiun kerja; bila nyeri menetap, konsultasi medis disarankan.',
            'Perlu Perhatian' => 'perbaiki postur duduk dan lakukan peregangan rutin.',
            'Perlu Intervensi' => 'evaluasi stasiun kerja dan konsultasi bila keluhan menetap.',
        ],
        'wellbeing' => [
            'Perlu Perhatian' => 'jaga rutinitas tidur dan aktivitas fisik; pantau kembali dalam 2 minggu.',
            'Tinggi' => 'prioritaskan pemulihan: tidur cukup, jeda kerja, dan aktivitas yang menenangkan.',
            'Perlu Intervensi' => 'pertimbangkan konsultasi untuk menyusun rutinitas pemulihan.',
        ],
    ];

    public static function for(?string $domain, string $bandLabel): string
    {
        foreach (self::TEXT[$domain ?? 'mh'] ?? self::TEXT['mh'] as $band => $text) {
            if ($bandLabel === trim($band)) {
                return $text;
            }
        }
        // Fallback band berat umum.
        return 'pertimbangkan konsultasi melalui fitur booking untuk membahas hasil ini.';
    }
}
