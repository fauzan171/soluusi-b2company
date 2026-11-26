<?php

namespace Database\Seeders;

use App\Models\AssessmentInstrument;
use App\Models\ScreeningQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Instrumen assessment sebagai data (W4, K12/K13, research-c).
 * WHO-5/PHQ-9/GAD-7 = teks Indonesia standar; CBI = back-translation Indonesia.
 * Item hidup di screening_questions (vertical perusahaan, kolom M6 instrument_code/weight).
 * Idempotent: updateOrCreate per code; item updateOrInsert per (vertical, code).
 */
class AssessmentInstrumentSeeder extends Seeder
{
    private const FREQ0_5 = ['Tidak pernah', 'Sebagian kecil waktu', 'Kurang dari setengah waktu', 'Lebih dari setengah waktu', 'Sebagian besar waktu', 'Sepanjang waktu'];
    private const FREQ0_3 = ['Tidak sama sekali', 'Beberapa hari', 'Lebih dari setengah hari', 'Hampir setiap hari'];
    private const CBI_OPTS = ['Tidak pernah', 'Jarang', 'Kadang-kadang', 'Sering', 'Selalu'];
    private const DASS_OPTS = ['Tidak sesuai sama sekali', 'Sesuai sebagian waktu', 'Sesuai sebagian besar waktu', 'Sesuai sangat banyak/hampir selalu'];
    private const PSS_OPTS = ['Tidak pernah', 'Hampir tidak pernah', 'Kadang-kadang', 'Cukup sering', 'Sangat sering'];
    private const AGREE0_4 = ['Sangat tidak setuju', 'Tidak setuju', 'Netral', 'Setuju', 'Sangat setuju'];
    private const NUTR_OPTS = ['Tidak pernah', 'Jarang', 'Kadang-kadang', 'Sering', 'Selalu'];
    private const PAIN_OPTS = ['Tidak ada nyeri', 'Nyeri ringan', 'Nyeri sedang', 'Nyeri berat'];

    public function run(): void
    {
        $this->who5();
        $this->phq9();
        $this->gad7();
        $this->cbi();
        // wv1 (research2-infra §1): library tambahan.
        $this->dass21();
        $this->pss10();
        $this->bigFive();
        $this->disc();
        $this->nutrition();
        $this->ergonomic();
        $this->mcmi3();
    }

    private function upsertInstrument(array $def): AssessmentInstrument
    {
        return AssessmentInstrument::updateOrCreate(['code' => $def['code']], $def);
    }

    /** [code, text, type, options, reverse, ord, weight] */
    private function upsertItems(string $instrumentCode, array $items, string $section = 'mental_health'): void
    {
        foreach ($items as [$code, $text, $type, $options, $reverse, $ord, $weight]) {
            ScreeningQuestion::updateOrCreate(
                ['vertical' => 'perusahaan', 'code' => $code],
                [
                    'section' => $section,
                    'instrument_code' => $instrumentCode,
                    'version' => '1',
                    'weight' => $weight,
                    'text' => $text,
                    'type' => $type,
                    'options' => $options,
                    'reverse' => $reverse,
                    'ord' => $ord,
                ]
            );
        }
    }

    private function who5(): void
    {
        $this->upsertInstrument([
            'code' => 'who5',
            'domain' => 'wellbeing',
            'name' => 'WHO-5 Well-Being Index',
            'version' => '1',
            'scoring_type' => 'sum_x4',
            'bands' => [
                ['max' => 49, 'label' => 'Berisiko Tinggi', 'note' => 'Kesejahteraan rendah — sangat disarankan konsultasi.'],
                ['max' => 74, 'label' => 'Perlu Perhatian', 'note' => 'Kesejahteraan menurun — pantau dan pertimbangkan konsultasi.'],
                ['max' => 100, 'label' => 'Sehat', 'note' => 'Kesejahteraan baik.'],
            ],
            'interpretation' => 'Skrining kesejahteraan 2 minggu terakhir, bukan diagnosis.',
            'license_note' => 'WHO — bebas hak cipta, terjemahan resmi tersedia.',
            'escalate_items' => null,
            'is_active' => true,
        ]);

        $items = [
            ['W1', 'Saya merasa ceria dan senang', 'freq0-5', self::FREQ0_5, false, 1, 5],
            ['W2', 'Saya merasa tenang dan rileks', 'freq0-5', self::FREQ0_5, false, 2, 5],
            ['W3', 'Saya merasa aktif dan bersemangat', 'freq0-5', self::FREQ0_5, false, 3, 5],
            ['W4', 'Saya bangun tidur dengan perasaan segar dan beristirahat', 'freq0-5', self::FREQ0_5, false, 4, 5],
            ['W5', 'Kehidupan sehari-hari saya dipenuhi hal yang menarik', 'freq0-5', self::FREQ0_5, false, 5, 5],
        ];
        $this->upsertItems('who5', $items);
    }

    private function phq9(): void
    {
        $this->upsertInstrument([
            'code' => 'phq9',
            'domain' => 'mh',
            'name' => 'PHQ-9 (Skrining Depresi)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 4, 'label' => 'Minimal', 'note' => 'Tidak ada gejala bermakna.'],
                ['max' => 9, 'label' => 'Ringan', 'note' => 'Gejala ringan — pemantauan cukup.'],
                ['max' => 14, 'label' => 'Sedang', 'note' => 'Gejala sedang — pertimbangkan konsultasi.'],
                ['max' => 19, 'label' => 'Agak Berat', 'note' => 'Gejala agak berat — konsultasi disarankan.'],
                ['max' => 27, 'label' => 'Berat', 'note' => 'Gejala berat — konsultasi segera.'],
            ],
            'interpretation' => 'Skrining depresi 2 minggu terakhir, bukan diagnosis.',
            'license_note' => 'Pfizer/Spitzer — bebas dipakai, tanpa izin (phqscreeners.com).',
            'escalate_items' => ['P9'],
            'is_active' => true,
        ]);

        $items = [
            ['P1', 'Kurang tertarik atau kurang senang dalam melakukan hal-hal', 'freq0-3', self::FREQ0_3, false, 1, 3],
            ['P2', 'Merasa murung, sedih, atau putus asa', 'freq0-3', self::FREQ0_3, false, 2, 3],
            ['P3', 'Sulit tidur, tidur tidak nyenyak, atau terlalu banyak tidur', 'freq0-3', self::FREQ0_3, false, 3, 3],
            ['P4', 'Merasa lelah atau kekurangan energi', 'freq0-3', self::FREQ0_3, false, 4, 3],
            ['P5', 'Kurang nafsu makan atau makan berlebihan', 'freq0-3', self::FREQ0_3, false, 5, 3],
            ['P6', 'Tidak puas dengan diri sendiri, atau merasa gagal atau mengecewakan diri/keluarga', 'freq0-3', self::FREQ0_3, false, 6, 3],
            ['P7', 'Sulit berkonsentrasi pada sesuatu, misalnya membaca atau menonton televisi', 'freq0-3', self::FREQ0_3, false, 7, 3],
            ['P8', 'Bergerak atau berbicara sangat lambat, atau sebaliknya gelisah sehingga sulit diam', 'freq0-3', self::FREQ0_3, false, 8, 3],
            ['P9', 'Muncul pikiran bahwa lebih baik mati, atau menyakiti diri sendiri dengan cara tertentu', 'freq0-3', self::FREQ0_3, false, 9, 3],
        ];
        $this->upsertItems('phq9', $items);
    }

    private function gad7(): void
    {
        $this->upsertInstrument([
            'code' => 'gad7',
            'domain' => 'mh',
            'name' => 'GAD-7 (Skrining Kecemasan)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 4, 'label' => 'Minimal', 'note' => 'Kecemasan minimal.'],
                ['max' => 9, 'label' => 'Ringan', 'note' => 'Kecemasan ringan.'],
                ['max' => 14, 'label' => 'Sedang', 'note' => 'Kecemasan sedang — pertimbangkan konsultasi.'],
                ['max' => 21, 'label' => 'Berat', 'note' => 'Kecemasan berat — konsultasi disarankan.'],
            ],
            'interpretation' => 'Skrining kecemasan 2 minggu terakhir, bukan diagnosis.',
            'license_note' => 'Pfizer/Spitzer — bebas dipakai, tanpa izin.',
            'escalate_items' => null,
            'is_active' => true,
        ]);

        $items = [
            ['G1', 'Merasa gugup, cemas, atau tegang', 'freq0-3', self::FREQ0_3, false, 1, 3],
            ['G2', 'Tidak bisa berhenti mengkhawatirkan sesuatu atau sulit mengendalikan kekhawatiran', 'freq0-3', self::FREQ0_3, false, 2, 3],
            ['G3', 'Terlalu banyak mengkhawatirkan berbagai hal', 'freq0-3', self::FREQ0_3, false, 3, 3],
            ['G4', 'Sulit rileks atau sulit istirahat', 'freq0-3', self::FREQ0_3, false, 4, 3],
            ['G5', 'Sangat gelisah sehingga sulit duduk tenang', 'freq0-3', self::FREQ0_3, false, 5, 3],
            ['G6', 'Mudah tersinggung atau mudah kesal', 'freq0-3', self::FREQ0_3, false, 6, 3],
            ['G7', 'Merasa takut seolah-olah sesuatu yang buruk mungkin terjadi', 'freq0-3', self::FREQ0_3, false, 7, 3],
        ];
        $this->upsertItems('gad7', $items);
    }

    private function cbi(): void
    {
        $this->upsertInstrument([
            'code' => 'cbi',
            'domain' => 'mh',
            'name' => 'CBI (Copenhagen Burnout Inventory)',
            'version' => '1',
            'scoring_type' => 'mean_x25',
            'bands' => [
                ['max' => 24, 'label' => 'Rendah', 'note' => 'Burnout rendah.'],
                ['max' => 49, 'label' => 'Ringan', 'note' => 'Burnout ringan — pantau beban kerja.'],
                ['max' => 74, 'label' => 'Sedang', 'note' => 'Burnout sedang — evaluasi beban kerja.'],
                ['max' => 100, 'label' => 'Tinggi', 'note' => 'Burnout tinggi — intervensi disarankan.'],
            ],
            'interpretation' => 'Skrining burnout (Kristensen et al. 2005), bukan diagnosis.',
            'license_note' => 'Public domain — sitasi Kristensen et al. 2005, Work & Stress 19:192-207.',
            'escalate_items' => null,
            'is_active' => true,
        ]);

        $items = [
            ['C1', 'Seberapa sering Anda merasa lelah?', 'freq0-4', self::CBI_OPTS, false, 1, 4],
            ['C2', 'Seberapa sering Anda secara fisik kelelahan?', 'freq0-4', self::CBI_OPTS, false, 2, 4],
            ['C3', 'Seberapa sering Anda secara emosional lelah?', 'freq0-4', self::CBI_OPTS, false, 3, 4],
            ['C4', 'Seberapa sering Anda berpikir "Saya tidak dapat menanggung ini lagi"?', 'freq0-4', self::CBI_OPTS, false, 4, 4],
            ['C5', 'Seberapa sering Anda merasa lelah saat bangun pagi untuk menghadapi hari kerja baru?', 'freq0-4', self::CBI_OPTS, false, 5, 4],
            ['C6', 'Seberapa sering Anda merasa lelah selama jam kerja?', 'freq0-4', self::CBI_OPTS, false, 6, 4],
            ['C7', 'Apakah Anda punya energi untuk keluarga dan teman di luar jam kerja?', 'freq0-4', self::CBI_OPTS, true, 7, 4],
            ['C8', 'Apakah pekerjaan Anda terasa bermakna?', 'freq0-4', self::CBI_OPTS, false, 8, 4],
            ['C9', 'Apakah Anda ingin berhenti dari pekerjaan sekarang, tapi merasa tidak bisa?', 'freq0-4', self::CBI_OPTS, false, 9, 4],
            ['C10', 'Apakah Anda kadang berpikir "Saya ingin lari dari semuanya"?', 'freq0-4', self::CBI_OPTS, false, 10, 4],
            ['C11', 'Apakah pekerjaan Anda melelahkan Anda secara emosional?', 'freq0-4', self::CBI_OPTS, false, 11, 4],
            ['C12', 'Apakah pekerjaan Anda melelahkan Anda secara fisik?', 'freq0-4', self::CBI_OPTS, false, 12, 4],
            ['C13', 'Apakah Anda prihatin bahwa pekerjaan Anda melelahkan Anda secara mental?', 'freq0-4', self::CBI_OPTS, false, 13, 4],
            ['C14', 'Apakah Anda merasa pekerjaan Anda sangat menegangkan?', 'freq0-4', self::CBI_OPTS, false, 14, 4],
            ['C15', 'Apakah Anda lelah dengan pekerjaan Anda?', 'freq0-4', self::CBI_OPTS, false, 15, 4],
            ['C16', 'Seberapa sering Anda kehabisan energi saat bekerja?', 'freq0-4', self::CBI_OPTS, false, 16, 4],
            ['C17', 'Apakah Anda menceritakan pekerjaan Anda melelahkan?', 'freq0-4', self::CBI_OPTS, false, 17, 4],
            ['C18', 'Apakah Anda tak punya energi untuk rekan kerja?', 'freq0-4', self::CBI_OPTS, false, 18, 4],
            ['C19', 'Seberapa sering pekerjaan membuat Anda frustrasi?', 'freq0-4', self::CBI_OPTS, false, 19, 4],
        ];
        $this->upsertItems('cbi', $items);
    }

    // --- wv1: DASS-21 — 3 subscale (public domain) ---------------------------

    private function dass21(): void
    {
        $this->upsertInstrument([
            'code' => 'dass21',
            'domain' => 'mh',
            'name' => 'DASS-21 (Depresi, Kecemasan, Stres)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 24, 'label' => 'Normal', 'note' => 'Tidak ada gejala bermakna.'],
                ['max' => 45, 'label' => 'Ringan', 'note' => 'Gejala ringan — pemantauan cukup.'],
                ['max' => 63, 'label' => 'Sedang', 'note' => 'Gejala sedang — pertimbangkan konsultasi.'],
                ['max' => 63, 'label' => 'Berat', 'note' => 'Gejala berat — konsultasi segera.'],
            ],
            'dimensions' => [
                'depression' => ['label' => 'Depresi', 'items' => ['D3', 'D5', 'D10', 'D13', 'D16', 'D17', 'D21'],
                    'multiplier' => 2, 'bands' => [
                        ['max' => 9, 'label' => 'Normal', 'note' => 'Tidak ada gejala depresi bermakna.'],
                        ['max' => 13, 'label' => 'Ringan', 'note' => 'Gejala ringan.'],
                        ['max' => 18, 'label' => 'Sedang', 'note' => 'Gejala sedang — pertimbangkan konsultasi.'],
                        ['max' => 21, 'label' => 'Berat', 'note' => 'Gejala berat — konsultasi disarankan.'],
                        ['max' => 42, 'label' => 'Sangat Berat', 'note' => 'Gejala sangat berat — konsultasi segera.'],
                    ]],
                'anxiety' => ['label' => 'Kecemasan', 'items' => ['D2', 'D4', 'D7', 'D9', 'D15', 'D19', 'D20'],
                    'multiplier' => 2, 'bands' => [
                        ['max' => 7, 'label' => 'Normal', 'note' => 'Tidak ada gejala kecemasan bermakna.'],
                        ['max' => 9, 'label' => 'Ringan', 'note' => 'Gejala ringan.'],
                        ['max' => 14, 'label' => 'Sedang', 'note' => 'Gejala sedang — pertimbangkan konsultasi.'],
                        ['max' => 17, 'label' => 'Berat', 'note' => 'Gejala berat — konsultasi disarankan.'],
                        ['max' => 42, 'label' => 'Sangat Berat', 'note' => 'Gejala sangat berat — konsultasi segera.'],
                    ]],
                'stress' => ['label' => 'Stres', 'items' => ['D1', 'D6', 'D8', 'D11', 'D12', 'D14', 'D18'],
                    'multiplier' => 2, 'bands' => [
                        ['max' => 14, 'label' => 'Normal', 'note' => 'Tidak ada gejala stres bermakna.'],
                        ['max' => 18, 'label' => 'Ringan', 'note' => 'Gejala ringan.'],
                        ['max' => 25, 'label' => 'Sedang', 'note' => 'Gejala sedang — pertimbangkan konsultasi.'],
                        ['max' => 33, 'label' => 'Berat', 'note' => 'Gejala berat — konsultasi disarankan.'],
                        ['max' => 42, 'label' => 'Sangat Berat', 'note' => 'Gejala sangat berat — konsultasi segera.'],
                    ]],
            ],
            'interpretation' => 'Skrining depresi/kecemasan/stres 1 minggu terakhir (subska×2 vs norma DASS-42), bukan diagnosis.',
            'license_note' => 'Lovibond & Lovibond 1995 — public domain, tanpa izin.',
            'escalate_items' => null, // DASS-21 tidak punya item eskalasi mandatori; PHQ-9 tetap jalur eskalasi.
            'is_active' => true,
        ]);

        // Terjemahan standar Indonesia DASS-21 (public domain).
        $items = [
            ['D1', 'Saya merasa sulit untuk menenangkan diri sendiri', 'freq0-3', self::DASS_OPTS, false, 1, 3],
            ['D2', 'Saya sadar mulut saya kering', 'freq0-3', self::DASS_OPTS, false, 2, 3],
            ['D3', 'Saya sama sekali tidak bisa merasakan perasaan positif sama sekali', 'freq0-3', self::DASS_OPTS, false, 3, 3],
            ['D4', 'Saya mengalami kesulitan bernapas (napas cepat, sedikit tidak cukup)', 'freq0-3', self::DASS_OPTS, false, 4, 3],
            ['D5', 'Saya sama sekali tidak bisa merasakan semangat untuk melakukan sesuatu', 'freq0-3', self::DASS_OPTS, false, 5, 3],
            ['D6', 'Saya cenderung bereaksi berlebihan terhadap situasi', 'freq0-3', self::DASS_OPTS, false, 6, 3],
            ['D7', 'Saya merasa gemetar (misalnya tangan)', 'freq0-3', self::DASS_OPTS, false, 7, 3],
            ['D8', 'Saya merasa sulit untuk rileks', 'freq0-3', self::DASS_OPTS, false, 8, 3],
            ['D9', 'Saya khawatir situasi panik membuat saya melakukan hal memalukan', 'freq0-3', self::DASS_OPTS, false, 9, 3],
            ['D10', 'Saya merasa tidak punya harapan', 'freq0-3', self::DASS_OPTS, false, 10, 3],
            ['D11', 'Saya merasa gelisah', 'freq0-3', self::DASS_OPTS, false, 11, 3],
            ['D12', 'Saya merasa sulit untuk berinisiatif melakukan sesuatu', 'freq0-3', self::DASS_OPTS, false, 12, 3],
            ['D13', 'Saya cenderung murung dan sedih', 'freq0-3', self::DASS_OPTS, false, 13, 3],
            ['D14', 'Saya merasa tidak toleran terhadap gangguan untuk apa yang saya lakukan', 'freq0-3', self::DASS_OPTS, false, 14, 3],
            ['D15', 'Saya merasa hampir panik', 'freq0-3', self::DASS_OPTS, false, 15, 3],
            ['D16', 'Saya tidak bisa merasa antusias terhadap apa pun', 'freq0-3', self::DASS_OPTS, false, 16, 3],
            ['D17', 'Saya merasa tidak berharga sebagai manusia', 'freq0-3', self::DASS_OPTS, false, 17, 3],
            ['D18', 'Saya merasa tidak sabar', 'freq0-3', self::DASS_OPTS, false, 18, 3],
            ['D19', 'Saya merasa perubahan mood drastis', 'freq0-3', self::DASS_OPTS, false, 19, 3],
            ['D20', 'Saya khawatir kemungkinan tersesat dalam pikiran saat bekerja/mengemudi', 'freq0-3', self::DASS_OPTS, false, 20, 3],
            ['D21', 'Saya merasa gugup', 'freq0-3', self::DASS_OPTS, false, 21, 3],
        ];
        $this->upsertItems('dass21', $items);
    }

    // --- wv1: PSS-10 — sum 0-40, reverse 4,5,7,8 ----------------------------

    private function pss10(): void
    {
        $this->upsertInstrument([
            'code' => 'pss10',
            'domain' => 'mh',
            'name' => 'PSS-10 (Perceived Stress Scale)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 13, 'label' => 'Rendah', 'note' => 'Stres persepsi rendah.'],
                ['max' => 26, 'label' => 'Sedang', 'note' => 'Stres persepsi sedang — kelola beban dan istirahat.'],
                ['max' => 40, 'label' => 'Tinggi', 'note' => 'Stres persepsi tinggi — pertimbangkan konsultasi.'],
            ],
            'interpretation' => 'Skrining persepsi stres 1 bulan terakhir, bukan diagnosis (tidak ada cutoff resmi).',
            'license_note' => 'Cohen 1983 — skala asli bebas pakai; izin translasi via MAPI Trust.',
            'escalate_items' => null,
            'is_active' => true,
        ]);

        // Terjemahan bebas (public wording), reverse item 4,5,7,8.
        $items = [
            ['PS1', 'Saya pernah terganggu oleh sesuatu yang terjadi secara tak terduga', 'freq0-4', self::PSS_OPTS, false, 1, 4],
            ['PS2', 'Saya merasa tidak mampu mengendalikan hal-hal penting dalam hidup saya', 'freq0-4', self::PSS_OPTS, false, 2, 4],
            ['PS3', 'Saya merasa gugup dan stres', 'freq0-4', self::PSS_OPTS, false, 3, 4],
            ['PS4', 'Saya merasa percaya diri tentang kemampuan menangani masalah pribadi saya', 'freq0-4', self::PSS_OPTS, true, 4, 4],
            ['PS5', 'Saya merasa segala sesuatunya berjalan sesuai keinginan saya', 'freq0-4', self::PSS_OPTS, true, 5, 4],
            ['PS6', 'Saya merasa tidak mampu mengatasi semua hal yang harus saya lakukan', 'freq0-4', self::PSS_OPTS, false, 6, 4],
            ['PS7', 'Saya mampu mengendalikan hal-hal yang menjengkelkan dalam hidup saya', 'freq0-4', self::PSS_OPTS, true, 7, 4],
            ['PS8', 'Saya merasa menguasai keadaan', 'freq0-4', self::PSS_OPTS, true, 8, 4],
            ['PS9', 'Saya marah tentang hal-hal di luar kendali saya', 'freq0-4', self::PSS_OPTS, false, 9, 4],
            ['PS10', 'Saya merasa kesulitan menumpuk begitu banyak hal yang harus saya atasi', 'freq0-4', self::PSS_OPTS, false, 10, 4],
        ];
        $this->upsertItems('pss10', $items);
    }

    // --- wv1: Big Five mini (15 item, 3 per dimensi) --------------------------

    private function bigFive(): void
    {
        $this->upsertInstrument([
            'code' => 'bigfive',
            'domain' => null,
            'name' => 'Big Five (Kepribadian)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 60, 'label' => 'Selesai', 'note' => 'Profil kepribadian per dimensi di bawah.'],
            ],
            'dimensions' => [
                'openness' => ['label' => 'Keterbukaan', 'items' => ['BF1', 'BF2', 'BF3'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung praktis dan konvensional.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Seimbang antara eksplorasi dan rutinitas.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Terbuka pada pengalaman dan ide baru.']]],
                'conscientiousness' => ['label' => 'Ketelitian', 'items' => ['BF4', 'BF5', 'BF6'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung fleksibel dan spontan.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Terorganisasi cukup baik.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Terorganisasi dan dapat diandalkan.']]],
                'extraversion' => ['label' => 'Ekstraversi', 'items' => ['BF7', 'BF8', 'BF9'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung tenang dan reflektif.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Nyaman baik sendiri maupun sosial.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Energi tinggi dalam interaksi sosial.']]],
                'agreeableness' => ['label' => 'Keramahan', 'items' => ['BF10', 'BF11', 'BF12'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung analitis dan kompetitif.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Kooperatif cukup baik.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Empatik dan kooperatif.']]],
                'neuroticism' => ['label' => 'Stabilitas Emosi', 'items' => ['BF13', 'BF14', 'BF15'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Stabil secara emosional.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Reaktivitas emosi sedang.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Reaktif terhadap stres — teknik relaksasi membantu.']]],
            ],
            'interpretation' => 'Preferensi kepribadian kerja (bukan kemampuan/validitas klinis).',
            'license_note' => 'Item IPIP-NEO-style — public domain (ipip.ori.org).',
            'escalate_items' => null,
            'is_active' => true,
            // wv1: add-on berbayar — label saja, tidak mengubah akses.
            'is_addon' => true,
        ]);

        // 0-4 agreement; reverse item per dimensi (BF2, BF6, BF8, BF12, BF15).
        $items = [
            ['BF1', 'Saya punya imajinasi yang kaya', 'freq0-4', self::AGREE0_4, false, 1, 4],
            ['BF2', 'Saya menyukai hal-hal praktis dan nyata', 'freq0-4', self::AGREE0_4, true, 2, 4],
            ['BF3', 'Saya tertarik pada ide-ide abstrak', 'freq0-4', self::AGREE0_4, false, 3, 4],
            ['BF4', 'Saya menyelesaikan tugas sampai tuntas', 'freq0-4', self::AGREE0_4, false, 4, 4],
            ['BF5', 'Saya suka merencanakan dengan rapi', 'freq0-4', self::AGREE0_4, false, 5, 4],
            ['BF6', 'Saya sering meninggalkan barang berantakan', 'freq0-4', self::AGREE0_4, true, 6, 4],
            ['BF7', 'Saya merasa nyaman memulai percakapan dengan orang baru', 'freq0-4', self::AGREE0_4, false, 7, 4],
            ['BF8', 'Saya lebih suka menyendiri daripada berkumpul ramai', 'freq0-4', self::AGREE0_4, true, 8, 4],
            ['BF9', 'Saya mudah bergaul dengan banyak orang', 'freq0-4', self::AGREE0_4, false, 9, 4],
            ['BF10', 'Saya mudah merasakan apa yang dirasakan orang lain', 'freq0-4', self::AGREE0_4, false, 10, 4],
            ['BF11', 'Saya menikmati membantu orang lain', 'freq0-4', self::AGREE0_4, false, 11, 4],
            ['BF12', 'Saya mudah tersinggung terhadap kritik', 'freq0-4', self::AGREE0_4, true, 12, 4],
            ['BF13', 'Saya sering merasa cemas', 'freq0-4', self::AGREE0_4, false, 13, 4],
            ['BF14', 'Saya mudah cemas saat menghadapi masalah', 'freq0-4', self::AGREE0_4, false, 14, 4],
            ['BF15', 'Saya tetap tenang di bawah tekanan', 'freq0-4', self::AGREE0_4, true, 15, 4],
        ];
        $this->upsertItems('bigfive', $items);
    }

    // --- wv1: DISC mini (12 item, 3 per dimensi) ------------------------------

    private function disc(): void
    {
        $this->upsertInstrument([
            'code' => 'disc',
            'domain' => null,
            'name' => 'DISC (Gaya Perilaku Kerja)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 48, 'label' => 'Selesai', 'note' => 'Profil gaya perilaku per dimensi di bawah.'],
            ],
            'dimensions' => [
                'dominance' => ['label' => 'Dominance', 'items' => ['DI1', 'DI2', 'DI3'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung konsensus dan hati-hati.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Tegas sesuai konteks.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Tegas, berorientasi hasil, cepat mengambil keputusan.']]],
                'influence' => ['label' => 'Influence', 'items' => ['DI4', 'DI5', 'DI6'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung reflektif dan berbasis data.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Komunikasi mudah.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Energik, persuasif, nyaman di depan banyak orang.']]],
                'steadiness' => ['label' => 'Steadiness', 'items' => ['DI7', 'DI8', 'DI9'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung menyukai variasi dan kecepatan.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Seimbang antara stabilitas dan perubahan.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Sabar, konsisten, menjadi penyeimbang tim.']]],
                'compliance' => ['label' => 'Compliance', 'items' => ['DI10', 'DI11', 'DI12'],
                    'bands' => [['max' => 4, 'label' => 'Rendah', 'note' => 'Cenderung independen dari prosedur.'], ['max' => 8, 'label' => 'Sedang', 'note' => 'Mengikuti aturan sesuai konteks.'], ['max' => 12, 'label' => 'Tinggi', 'note' => 'Teliti, menghargai standar dan kualitas.']]],
            ],
            'interpretation' => 'Preferensi gaya perilaku kerja, bukan penilaian kemampuan.',
            'license_note' => 'Model DISC (Marston 1928) — istilah tidak diasapi; item ringkas milik sendiri.',
            'escalate_items' => null,
            'is_active' => true,
            // wv1: add-on berbayar — label saja, tidak mengubah akses.
            'is_addon' => true,
        ]);

        $items = [
            ['DI1', 'Saya langsung mengambil keputusan ketika dibutuhkan', 'freq0-4', self::AGREE0_4, false, 1, 4],
            ['DI2', 'Saya nyaman memimpin dan mengarahkan orang lain', 'freq0-4', self::AGREE0_4, false, 2, 4],
            ['DI3', 'Saya menyukai tantangan yang menuntut hasil cepat', 'freq0-4', self::AGREE0_4, false, 3, 4],
            ['DI4', 'Saya mudah membangun hubungan dengan rekan baru', 'freq0-4', self::AGREE0_4, false, 4, 4],
            ['DI5', 'Saya menikmati presentasi di depan banyak orang', 'freq0-4', self::AGREE0_4, false, 5, 4],
            ['DI6', 'Saya mudah meyakinkan orang lain tentang gagasan saya', 'freq0-4', self::AGREE0_4, false, 6, 4],
            ['DI7', 'Saya menghargai suasana kerja yang stabil dan tenang', 'freq0-4', self::AGREE0_4, false, 7, 4],
            ['DI8', 'Saya sabar saat menghadapi perubahan mendadak', 'freq0-4', self::AGREE0_4, false, 8, 4],
            ['DI9', 'Saya lebih suka menyelesaikan pekerjaan bertahap dan konsisten', 'freq0-4', self::AGREE0_4, false, 9, 4],
            ['DI10', 'Saya memeriksa pekerjaan saya lebih dari sekali sebelum dianggap selesai', 'freq0-4', self::AGREE0_4, false, 10, 4],
            ['DI11', 'Saya merasa data dan fakta lebih penting daripada intuisi', 'freq0-4', self::AGREE0_4, false, 11, 4],
            ['DI12', 'Saya mengikuti prosedur kerja yang sudah disepakati', 'freq0-4', self::AGREE0_4, false, 12, 4],
        ];
        $this->upsertItems('disc', $items);
    }

    // --- wv1: Nutrition (8 item ringkas) -------------------------------------

    private function nutrition(): void
    {
        $this->upsertInstrument([
            'code' => 'nutrition',
            'domain' => 'nutrition',
            'name' => 'Gizi & Pola Makan',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 7, 'label' => 'Baik', 'note' => 'Kebiasaan gizi sehat.'],
                ['max' => 19, 'label' => 'Perlu Perhatian', 'note' => 'Ada kebiasaan yang perlu diperbaiki — konsultasi gizi membantu.'],
                ['max' => 32, 'label' => 'Perlu Intervensi', 'note' => 'Kebiasaan berisiko — konsultasi gizi disarankan.'],
            ],
            'interpretation' => 'Skrining kebiasaan gizi & pola makan 1 bulan terakhir, bukan diagnosis medis.',
            'license_note' => 'Item ringkas public-domain style (kuesioner gizi kerja umum).',
            'escalate_items' => null,
            'is_active' => true,
        ]);

        $items = [
            ['N1', 'Saya makan buah dan/atau sayur setiap hari', 'freq0-4', self::NUTR_OPTS, true, 1, 4],
            ['N2', 'Saya sarapan sebelum bekerja', 'freq0-4', self::NUTR_OPTS, true, 2, 4],
            ['N3', 'Saya mengonsumsi minuman manis (teh kemasan, soda, bobba) lebih dari sekali sehari', 'freq0-4', self::NUTR_OPTS, false, 3, 4],
            ['N4', 'Saya makan makanan cepat saji/gorengan 3 kali atau lebih per minggu', 'freq0-4', self::NUTR_OPTS, false, 4, 4],
            ['N5', 'Saya minum air putih minimal 8 gelas per hari', 'freq0-4', self::NUTR_OPTS, true, 5, 4],
            ['N6', 'Saya makan camilan asin atau kemasan setiap hari', 'freq0-4', self::NUTR_OPTS, false, 6, 4],
            ['N7', 'Porsi makan saya teratur (tidak melewatkan jam makan)', 'freq0-4', self::NUTR_OPTS, true, 7, 4],
            ['N8', 'Saya makan berlebihan saat stres atau lembur', 'freq0-4', self::NUTR_OPTS, false, 8, 4],
        ];
        $this->upsertItems('nutrition', $items, 'nutrition_lifestyle');
    }

    // --- wv1: Ergonomic (8 titik nyeri) --------------------------------------

    private function ergonomic(): void
    {
        $this->upsertInstrument([
            'code' => 'ergonomic',
            'domain' => 'ergonomic',
            'name' => 'Ergonomi & Nyeri Kerja',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 2, 'label' => 'Baik', 'note' => 'Tidak ada keluhan nyeri bermakna.'],
                ['max' => 15, 'label' => 'Ringan', 'note' => 'Keluhan ringan — perhatikan postur dan peregangan.'],
                ['max' => 32, 'label' => 'Sedang', 'note' => 'Keluhan sedang — evaluasi stasiun kerja disarankan.'],
                ['max' => 32, 'label' => 'Berat', 'note' => 'Keluhan berat — konsultasi medis/ergonomi disarankan.'],
            ],
            'interpretation' => 'Skrining nyeri muskuloskeletal akibat kerja, bukan diagnosis medis.',
            'license_note' => 'Adaptasi Cornell Musculoskeletal Discomfort Questionnaire-style — free untuk riset/pemakaian internal.',
            'escalate_items' => ['ER7', 'ER8'],
            'is_active' => true,
            // wv1: add-on berbayar — label saja, tidak mengubah akses.
            'is_addon' => true,
        ]);

        // Nyeri per area (0-3), eskalasi punggung/pundak berat.
        $items = [
            ['ER1', 'Nyeri pada leher', 'pain3', self::PAIN_OPTS, false, 1, 3],
            ['ER2', 'Nyeri pada bahu', 'pain3', self::PAIN_OPTS, false, 2, 3],
            ['ER3', 'Nyeri pada punggung atas', 'pain3', self::PAIN_OPTS, false, 3, 3],
            ['ER4', 'Nyeri pada punggung bawah/pinggang', 'pain3', self::PAIN_OPTS, false, 4, 3],
            ['ER5', 'Nyeri pada pergelangan tangan/jari', 'pain3', self::PAIN_OPTS, false, 5, 3],
            ['ER6', 'Nyeri pada kaki/lutut', 'pain3', self::PAIN_OPTS, false, 6, 3],
            ['ER7', 'Nyeri pada punggung bawah sampai mengganggu tidur', 'pain3', self::PAIN_OPTS, false, 7, 3],
            ['ER8', 'Nyeri/kesemutan pada tangan saat bangun tidur', 'pain3', self::PAIN_OPTS, false, 8, 3],
        ];
        $this->upsertItems('ergonomic', $items, 'ergonomic');
    }

    // --- wv1: MCMI-III stub — clinical, inactive, grant-only -----------------

    private function mcmi3(): void
    {
        // Stub: instrumen Category-A (psikolog klinis berlisensi), TIDAK mass-test.
        // Item penuh & skoring hanya dikembangkan saat ada indikasi klinis + grant.
        $this->upsertInstrument([
            'code' => 'mcmi3',
            'domain' => 'clinical',
            'name' => 'MCMI-III (Klinis — via indikasi)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 0, 'label' => 'Menunggu Psikolog', 'note' => 'Dikerjakan hanya dengan pendampingan psikolog klinis Soluusi.'],
            ],
            'interpretation' => 'Instrumen klinis Category-A — hanya atas indikasi klinis, bukan skrining massal.',
            'license_note' => 'Millon (Pearson) — lisensi berbayar, dikelola psikolog klinis berlisensi.',
            'escalate_items' => null,
            'is_active' => false,
        ]);
    }
}
