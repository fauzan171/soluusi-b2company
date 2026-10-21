<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bank pertanyaan "My Health Index Assessment" (Soluusi × TVS) sebagai data.
 *
 * Diadopsi dari kiosk TVS `pertanyaan.md` / `0002_seed.sql`. Disimpan per vertical
 * supaya segmen perusahaan dan sekolah bisa punya konten berbeda tanpa menyentuh
 * mesin skor. Idempoten: re-run tidak menggandakan (updateOrInsert pada vertical+code).
 */
class ScreeningQuestionSeeder extends Seeder
{
    /** [code, section, text, type, options[], reverse, ord] */
    private const QUESTIONS = [
        // Mental Health Q5–Q13 (reverse: Q8, Q9, Q11, Q12 — item positif)
        ['Q5', 'mental_health', 'Merasa kesal karena sesuatu terjadi secara tak terduga', 'freq0-4', null, false, 5],
        ['Q6', 'mental_health', 'Merasa tidak mampu mengendalikan hal-hal penting dalam hidup Anda', 'freq0-4', null, false, 6],
        ['Q7', 'mental_health', 'Merasa gugup dan stres', 'freq0-4', null, false, 7],
        ['Q8', 'mental_health', 'Merasa percaya diri terhadap kemampuan Anda dalam menangani masalah pribadi', 'freq0-4', null, true, 8],
        ['Q9', 'mental_health', 'Merasa bahwa segala sesuatunya berjalan sesuai dengan keinginan Anda', 'freq0-4', null, true, 9],
        ['Q10', 'mental_health', 'Merasa tidak mampu mengatasi semua hal yang harus Anda lakukan', 'freq0-4', null, false, 10],
        ['Q11', 'mental_health', 'Mampu mengendalikan hal-hal yang membuat Anda jengkel dalam hidup', 'freq0-4', null, true, 11],
        ['Q12', 'mental_health', 'Merasa mampu mengendalikan keadaan', 'freq0-4', null, true, 12],
        ['Q13', 'mental_health', 'Merasa marah karena hal-hal yang terjadi di luar kendali Anda', 'freq0-4', null, false, 13],

        // Nutrition & Lifestyle Q15–Q20
        ['Q15', 'nutrition_lifestyle', 'Seberapa sering Anda mengonsumsi alkohol rata-rata?', 'single', ['Tidak mengonsumsi alkohol', 'Kurang dari 3 kali per minggu', 'Lebih dari 3 kali per minggu'], false, 15],
        ['Q16', 'nutrition_lifestyle', 'Berapa banyak rokok yang Anda hisap dalam sehari?', 'single', ['Tidak merokok', '5–9 batang per hari', 'Lebih dari 10 batang per hari'], false, 16],
        ['Q17', 'nutrition_lifestyle', 'Tingkat aktivitas fisik yang paling menggambarkan rutinitas Anda', 'single', ['Ringan', 'Sedang', 'Berat'], true, 17],
        ['Q18', 'nutrition_lifestyle', 'Seberapa sering Anda melakukan aktivitas fisik?', 'single', ['Tidak beraktivitas fisik', 'Kurang dari 3 hari per minggu', 'Lebih dari 3 hari per minggu'], true, 18],
        ['Q19', 'nutrition_lifestyle', 'Apakah Anda memiliki riwayat keluarga (orang tua dan/atau kakek-nenek) dengan penyakit diabetes, hipertensi, jantung, kanker, atau Alzheimer?', 'yesno', null, false, 19],
        ['Q20', 'nutrition_lifestyle', 'Apakah Anda memiliki alergi? (terhadap makanan, obat-obatan, debu, dll.)', 'yesno', null, false, 20],

        // Ergonomic Q21–Q28 (nyeri per bagian tubuh)
        ['Q21', 'ergonomic', 'Leher (Neck)', 'pain4', null, false, 21],
        ['Q22', 'ergonomic', 'Bahu (Shoulder)', 'pain4', null, false, 22],
        ['Q23', 'ergonomic', 'Punggung (Back)', 'pain4', null, false, 23],
        ['Q24', 'ergonomic', 'Pergelangan Tangan (Wrist)', 'pain4', null, false, 24],
        ['Q25', 'ergonomic', 'Lutut (Knee)', 'pain4', null, false, 25],
        ['Q26', 'ergonomic', 'Betis (Calf)', 'pain4', null, false, 26],
        ['Q27', 'ergonomic', 'Pergelangan Kaki (Ankle)', 'pain4', null, false, 27],
        ['Q28', 'ergonomic', 'Telapak Kaki (Feet)', 'pain4', null, false, 28],

        // Ergonomic Q29–Q34 (konteks; hanya Q31 yang masuk skor)
        ['Q29', 'ergonomic', 'Bagaimana Anda menggambarkan durasi nyeri yang dirasakan?', 'single', ['Akut (mendadak dan berat)', 'Intermiten (muncul dan hilang secara berkala)', 'Kronis (berlangsung lama)'], false, 29],
        ['Q30', 'ergonomic', 'Aktivitas apa saja yang memperparah rasa nyeri Anda?', 'multi', ['Berjalan', 'Duduk', 'Berdiri', 'Membungkuk', 'Mengangkat beban, mendorong dan menarik', 'Other'], false, 30],
        ['Q31', 'ergonomic', 'Nilai tingkat kesulitan yang Anda alami dalam melakukan aktivitas sehari-hari', 'scale1-10', null, false, 31],
        ['Q32', 'ergonomic', 'Apakah Anda memiliki cedera atau pernah menjalani operasi sebelumnya yang mungkin berkaitan dengan kondisi Anda saat ini?', 'yesno', null, false, 32],
        ['Q33', 'ergonomic', 'Nilai tingkat nyeri yang Anda rasakan saat ini', 'pain4', null, false, 33],
        ['Q34', 'ergonomic', 'Apakah Anda memiliki kondisi medis lainnya?', 'yesno', null, false, 34],
    ];

    public function run(): void
    {
        foreach (['perusahaan', 'sekolah'] as $vertical) {
            foreach (self::QUESTIONS as [$code, $section, $text, $type, $options, $reverse, $ord]) {
                DB::table('screening_questions')->updateOrInsert(
                    ['vertical' => $vertical, 'code' => $code],
                    [
                        'section' => $section,
                        'text' => $text,
                        'type' => $type,
                        'options' => $options === null ? null : json_encode($options),
                        'reverse' => $reverse,
                        'ord' => $ord,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}