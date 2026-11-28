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
        // ac1: instrumen xlsx klien (BFI-15 Trader, DISC-24 forced-choice, MCMI-III items).
        $this->bfiTrader();
        $this->disc24();
        $this->mcmi3Items();
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

    // --- ac1: BFI-15 Trader (xlsx klien) — add-on kepribadian ------------------

    private function bfiTrader(): void
    {
        $this->upsertInstrument([
            'code' => 'bfitrader',
            'domain' => null,
            'name' => 'Big Five Trader (Kepribadian)',
            'version' => '1',
            'scoring_type' => 'sum',
            'bands' => [
                ['max' => 60, 'label' => 'Selesai', 'note' => 'Profil kepribadian trader per dimensi di bawah.'],
            ],
            'dimensions' => [
            'extraversion' => ['label' => 'Extraversion', 'items' => ['BFT1', 'BFT6', 'BFT11'],
                'bands' => [
                    ['max' => 8, 'label' => 'Rendah', 'note' => 'Si Influencer Market — Kamu aktif di komunitas, grup Telegram, dan Twitter (X). Kamu suka berbagi opini, memberi analisa, dan kadang FOMO karena tekanan sosial. Kamu bisa jadi cepat mengambil peluang, tapi juga mudah terpancing emosi. Hati-hati: kecepatan bisa jadi keunggulan, tapi dalam crypto, noise sering menyesatkan. Gunakan energimu untuk membangun jaringan sambil tetap menjaga sistem trading yang disiplin.'],
                    ['max' => 11, 'label' => 'Sedang', 'note' => 'Si Influencer Market — Kamu aktif di komunitas, grup Telegram, dan Twitter (X). Kamu suka berbagi opini, memberi analisa, dan kadang FOMO karena tekanan sosial. Kamu bisa jadi cepat mengambil peluang, tapi juga mudah terpancing emosi. Hati-hati: kecepatan bisa jadi keunggulan, tapi dalam crypto, noise sering menyesatkan. Gunakan energimu untuk membangun jaringan sambil tetap menjaga sistem trading yang disiplin.'],
                    ['max' => 15, 'label' => 'Tinggi', 'note' => 'Si Influencer Market — Kamu aktif di komunitas, grup Telegram, dan Twitter (X). Kamu suka berbagi opini, memberi analisa, dan kadang FOMO karena tekanan sosial. Kamu bisa jadi cepat mengambil peluang, tapi juga mudah terpancing emosi. Hati-hati: kecepatan bisa jadi keunggulan, tapi dalam crypto, noise sering menyesatkan. Gunakan energimu untuk membangun jaringan sambil tetap menjaga sistem trading yang disiplin.'],
                ]],
            'agreeableness' => ['label' => 'Agreeableness', 'items' => ['BFT2', 'BFT7', 'BFT12'],
                'bands' => [
                    ['max' => 8, 'label' => 'Rendah', 'note' => 'Si Paling Kalem — Kamu menghargai harmoni, bahkan dalam pasar yang kacau. Kamu sering bertanya pendapat orang lain sebelum masuk posisi dan cenderung ragu-ragu. Kamu bisa menjadi sangat loyal terhadap satu proyek atau tim, bahkan ketika metrik tidak mendukung. Kamu perlu belajar lebih asertif dan percaya diri mengambil keputusan sendiri, karena di dunia crypto, keberanian dan waktu kadang lebih penting daripada keraguan yang sopan.'],
                    ['max' => 11, 'label' => 'Sedang', 'note' => 'Si Paling Kalem — Kamu menghargai harmoni, bahkan dalam pasar yang kacau. Kamu sering bertanya pendapat orang lain sebelum masuk posisi dan cenderung ragu-ragu. Kamu bisa menjadi sangat loyal terhadap satu proyek atau tim, bahkan ketika metrik tidak mendukung. Kamu perlu belajar lebih asertif dan percaya diri mengambil keputusan sendiri, karena di dunia crypto, keberanian dan waktu kadang lebih penting daripada keraguan yang sopan.'],
                    ['max' => 15, 'label' => 'Tinggi', 'note' => 'Si Paling Kalem — Kamu menghargai harmoni, bahkan dalam pasar yang kacau. Kamu sering bertanya pendapat orang lain sebelum masuk posisi dan cenderung ragu-ragu. Kamu bisa menjadi sangat loyal terhadap satu proyek atau tim, bahkan ketika metrik tidak mendukung. Kamu perlu belajar lebih asertif dan percaya diri mengambil keputusan sendiri, karena di dunia crypto, keberanian dan waktu kadang lebih penting daripada keraguan yang sopan.'],
                ]],
            'conscientiousness' => ['label' => 'Conscientiousness', 'items' => ['BFT3', 'BFT8', 'BFT13'],
                'bands' => [
                    ['max' => 8, 'label' => 'Rendah', 'note' => 'Si Analis Strategis — Kamu adalah trader yang membuat jurnal, punya plan entry/exit jelas, dan menghitung risiko dengan hati-hati. Kamu lebih nyaman dengan teknikal dan manajemen modal daripada hype. Kamu biasanya tidak FOMO, tapi justru terlalu hati-hati saat pasar memberi peluang besar. Belajarlah sedikit lebih fleksibel. Strategi kamu sudah kuat, tinggal tambahkan sedikit agresivitas di momen yang tepat.'],
                    ['max' => 11, 'label' => 'Sedang', 'note' => 'Si Analis Strategis — Kamu adalah trader yang membuat jurnal, punya plan entry/exit jelas, dan menghitung risiko dengan hati-hati. Kamu lebih nyaman dengan teknikal dan manajemen modal daripada hype. Kamu biasanya tidak FOMO, tapi justru terlalu hati-hati saat pasar memberi peluang besar. Belajarlah sedikit lebih fleksibel. Strategi kamu sudah kuat, tinggal tambahkan sedikit agresivitas di momen yang tepat.'],
                    ['max' => 15, 'label' => 'Tinggi', 'note' => 'Si Analis Strategis — Kamu adalah trader yang membuat jurnal, punya plan entry/exit jelas, dan menghitung risiko dengan hati-hati. Kamu lebih nyaman dengan teknikal dan manajemen modal daripada hype. Kamu biasanya tidak FOMO, tapi justru terlalu hati-hati saat pasar memberi peluang besar. Belajarlah sedikit lebih fleksibel. Strategi kamu sudah kuat, tinggal tambahkan sedikit agresivitas di momen yang tepat.'],
                ]],
            'neuroticism' => ['label' => 'Negative Emotionality', 'items' => ['BFT4', 'BFT9', 'BFT14'],
                'bands' => [
                    ['max' => 8, 'label' => 'Rendah', 'note' => 'Si Paling Waspada — Kamu mudah cemas melihat candle merah, bisa panik saat rugi, dan terlalu sering mengecek chart. Kamu mungkin lebih sering cut loss terlalu cepat atau tidak entry karena takut salah. Di dunia crypto yang super volatil, perasaanmu bisa naik-turun seperti harga token. Kuncinya: bangun sistem dan batas risiko, lalu latih mental agar tetap tenang. Mungkin kamu bukan scalper, tapi bisa sukses jadi swing trader dengan strategi defensif yang kuat.'],
                    ['max' => 11, 'label' => 'Sedang', 'note' => 'Si Paling Waspada — Kamu mudah cemas melihat candle merah, bisa panik saat rugi, dan terlalu sering mengecek chart. Kamu mungkin lebih sering cut loss terlalu cepat atau tidak entry karena takut salah. Di dunia crypto yang super volatil, perasaanmu bisa naik-turun seperti harga token. Kuncinya: bangun sistem dan batas risiko, lalu latih mental agar tetap tenang. Mungkin kamu bukan scalper, tapi bisa sukses jadi swing trader dengan strategi defensif yang kuat.'],
                    ['max' => 15, 'label' => 'Tinggi', 'note' => 'Si Paling Waspada — Kamu mudah cemas melihat candle merah, bisa panik saat rugi, dan terlalu sering mengecek chart. Kamu mungkin lebih sering cut loss terlalu cepat atau tidak entry karena takut salah. Di dunia crypto yang super volatil, perasaanmu bisa naik-turun seperti harga token. Kuncinya: bangun sistem dan batas risiko, lalu latih mental agar tetap tenang. Mungkin kamu bukan scalper, tapi bisa sukses jadi swing trader dengan strategi defensif yang kuat.'],
                ]],
            'openmindedness' => ['label' => 'Open-Mindedness', 'items' => ['BFT5', 'BFT10', 'BFT15'],
                'bands' => [
                    ['max' => 8, 'label' => 'Rendah', 'note' => 'Si Inovator Blockchain — Kamu suka eksplorasi dan merasa hidup saat menemukan proyek baru di dunia crypto, entah itu altcoin, DeFi, atau NFT yang sedang naik daun. Kamu cepat belajar dan bisa jadi early adopter, namun kadang berpindah terlalu cepat dari satu proyek ke proyek lain. Kamu butuh keseimbangan antara rasa ingin tahu dan disiplin karena di crypto, volatilitas bisa menghargai keberanian, tapi bisa juga menghukum ketidaksabaran.'],
                    ['max' => 11, 'label' => 'Sedang', 'note' => 'Si Inovator Blockchain — Kamu suka eksplorasi dan merasa hidup saat menemukan proyek baru di dunia crypto, entah itu altcoin, DeFi, atau NFT yang sedang naik daun. Kamu cepat belajar dan bisa jadi early adopter, namun kadang berpindah terlalu cepat dari satu proyek ke proyek lain. Kamu butuh keseimbangan antara rasa ingin tahu dan disiplin karena di crypto, volatilitas bisa menghargai keberanian, tapi bisa juga menghukum ketidaksabaran.'],
                    ['max' => 15, 'label' => 'Tinggi', 'note' => 'Si Inovator Blockchain — Kamu suka eksplorasi dan merasa hidup saat menemukan proyek baru di dunia crypto, entah itu altcoin, DeFi, atau NFT yang sedang naik daun. Kamu cepat belajar dan bisa jadi early adopter, namun kadang berpindah terlalu cepat dari satu proyek ke proyek lain. Kamu butuh keseimbangan antara rasa ingin tahu dan disiplin karena di crypto, volatilitas bisa menghargai keberanian, tapi bisa juga menghukum ketidaksabaran.'],
                ]],

            ],
            'interpretation' => 'Profil Big Five 15 item (BFI-15) dengan band narasi khusus konteks trading/kewirausahaan.',
            'license_note' => 'BFI-15 (Soto & John) — item bebas pakai untuk riset; band narasi klien.',
            'escalate_items' => null,
            'is_active' => true,
            'is_addon' => true,
        ]);

        // 15 item, 3 per dimensi; reverse item 3, 7, 8, 10, 14.
        $items = [
            ['BFT1', 'Cenderung diam ketika bersama dengan orang lain', 'freq0-4', self::AGREE0_4, false, 1, 4],
            ['BFT2', 'Berbelas kasih, suka menolong.', 'freq0-4', self::AGREE0_4, false, 2, 4],
            ['BFT3', 'Cenderung tidak terorganisir, tidak sistematis dalam beraktivitas', 'freq0-4', self::AGREE0_4, true, 3, 4],
            ['BFT4', 'Sering khawatir', 'freq0-4', self::AGREE0_4, false, 4, 4],
            ['BFT5', 'Mengagumi seni, musik, atau sastra', 'freq0-4', self::AGREE0_4, false, 5, 4],
            ['BFT6', 'Bersikap menguasai, bertindak seperti pemimpin', 'freq0-4', self::AGREE0_4, false, 6, 4],
            ['BFT7', 'Terkadang berkata kasar dengan orang lain', 'freq0-4', self::AGREE0_4, true, 7, 4],
            ['BFT8', 'Sulit memulai pekerjaan tugas', 'freq0-4', self::AGREE0_4, true, 8, 4],
            ['BFT9', 'Sering mengalami depresi, merasa sedih', 'freq0-4', self::AGREE0_4, false, 9, 4],
            ['BFT10', 'Tidak suka berpikir abstrak (mendalam), lebih suka mencari solusi konkret (praktis)', 'freq0-4', self::AGREE0_4, true, 10, 4],
            ['BFT11', 'Penuh tenaga dan semangat', 'freq0-4', self::AGREE0_4, false, 11, 4],
            ['BFT12', 'Berbaik sangka, berprasangka baik kepada orang lain', 'freq0-4', self::AGREE0_4, false, 12, 4],
            ['BFT13', 'Selalu dapat diandalkan', 'freq0-4', self::AGREE0_4, false, 13, 4],
            ['BFT14', 'Stabil secara emosional, tidak mudah marah', 'freq0-4', self::AGREE0_4, true, 14, 4],
            ['BFT15', 'Orisinal, mampu menghasilkan ide baru', 'freq0-4', self::AGREE0_4, false, 15, 4],

        ];
        $this->upsertItems('bfitrader', $items);
    }

    // --- ac1: DISC 24 forced-choice (xlsx klien) — add-on gaya perilaku -------
    // Tabel skoring (key/conv/types) hidup di config/assessment-disc.php;
    // engine match arm 'disc_forced' (InstrumentScoring).

    private function disc24(): void
    {
        $this->upsertInstrument([
            'code' => 'disc24',
            'domain' => null,
            'name' => 'DISC 24 (Gaya Perilaku)',
            'version' => '1',
            'scoring_type' => 'disc_forced',
            'bands' => [
                ['max' => 1, 'label' => 'LOGICAL THINKER', 'note' => 'Seorang yang praktis, cakap dan unik. Ia orang yang mampu menilai diri sendiri dan kritis terhadap dirinya dan orang lain. Ia menyukai hal yang detil dan logis; secara alamiah ia sangat analitis. Karena menyimpan informasi, ia meneliti isu berulang-ulang kali. Ia cenderung malu dan tertutup; ia hati-hati dalam membuat keputusan yang berdasarkan pada logika, bukan emosi, selalu menggunakan pertanyaan "bagaimana dan mengapa". Ia mengerjakan sesuatu dengan sistematis dan akurat. Ia rapi dan terorganisir sebab ia merasa bahwa keadaan berantakan sama dengan mutu yang rendah; demikian juga, rapi dan teratur merupakan mutu yang tinggi. Sangat teliti dalam segala sesuatu seperti halnya dalam pekerjaan dan penggunaan waktunya. Ia merencanakan dan mengorganisir semua sisi kehidupannya. Kelambanan sangat mengganggunya dan tak dapat ditolerir. Karir: Planner (any function), Engineer (Installation, Technical), Technical/Research (Chemist Technician), Academic, Statistician, Government Worker, IT Management, Prison Officer, Quality Controller.'],
                ['max' => 2, 'label' => 'ESTABLISHER', 'note' => 'Memiliki rasa ego yang tinggi dan cenderung invidualis dengan standard yang sangat tinggi. Ia lebih suka menganalisa masalah sendirian daripada bersama orang lain. Rasa egoisnya yang kuat membuatnya tidak nyaman di bawah kendali orang lain; ia lebih suka menjadi "boss" dan menetapkan standard tinggi baik untuk dirinya maupun orang lain. Ia menghindari sesuatu yang biasa-biasa dan cenderung mencari tantangan yang baru. Ia menyukai petualangan dan kadang-kadang beralih ke dalam petualangan baru sebelum mempertimbangkannya secara menyeluruh. Mampu memimpin situasi dan orang lain dalam rangka mencapai sasarannya; ia ingin selalu unggul dalam persaingan dengan taruhan apapun. Karir: Attorney, Researcher, Sales Representative, Planning Consultant, Transport Personnel, Production (Director, Manager, Supervisor), Technologist, Strategic Planning, Trouble Shooting, Marketing Services, Consultant, Engineering (Director, Manager, Supervisor) and Self-Employment.'],
                ['max' => 3, 'label' => 'DESIGNER', 'note' => 'Seorang yang sangat berorientasi pada tugas dan sensitif pada permasalahan. Ia lebih mempedulikan tugas yang ada dibanding orang-orang di sekitarnya, termasuk perasaan mereka. Sangat kukuh/keras dan mempunyai pendekatan yang efektif dalam pemecahan masalah. Oleh karena sifat alamiah dan keinginannya akan hasil yang terukur, Akan tampak dingin, tidak berperasaan dan menjaga jarak. Ia membuat keputusan berdasar pada fakta, bukan emosi. Cenderung pendiam dan tidak mudah percaya. Karir: Engineering (Management, Research, Design), Research (R&amp;D), Planning, Chemist, Accountancy, Specialist, Finance, Technician, Quality Control, Production Planning/Management, Design Engineer, Bookkeeper, Chemist Technician, Safety Officer, Librarian.'],
                ['max' => 4, 'label' => 'NEGOTIATOR', 'note' => 'Merupakan seorang pemimpin integratif yang bekerja dengan dan melalui orang lain.  Ia ramah, memiliki perhatian yang tinggi akan orang dan juga mempunyai kemampuan untuk memperoleh hormat dan penghargaan dari berbagai tipe orang.  Melakukan pekerjaannya dengan cara yang bersahabat, baik dalam mencapai sasarannya maupun meyakinkan pandangannya kepada orang lain.  Ia tidak begitu memperhatikan hal-hal kecil.  Kadang bertindak sesuai dengan kata hati/impulsif, terlalu antusias dan sangat banyak bicara.  Ia terlalu berlebihan menilai kemampuannya dalam memotivasi atau mengubah perilaku orang lain.  Mencari kebebasan dari rutinitas, menginginkan otoritas/wewenang dan juga prestise.  Ia menginginkan aktivitas yang bervariasi dan bekerja lebih efisien jika data-data analitis disediakan oleh orang lain.  Menginginkan penugasan yang mengutamakan mobilitas dan tantangan. Karir: Sales and Marketing (Directing, Manager, Person), Public Relations, Recruitment Consultant, Politician, Director, Self-Employed, Hotelier, Travel Agent, Trainer, Hospitality, Lawyer, Solicitor, Motivators, Team Leader, Politician, Trainer, Lecturer, Theatrical Agent, General Management and Leading People, Attorney.'],
                ['max' => 5, 'label' => 'CONFIDENT &amp; DETERMINED', 'note' => 'Sangat berorientasi terhadap tugas dan juga menyukai orang.  Ia sangat baik dalam menarik orang/recruiting.  Seorang yang bersahabat, tetapi menyukai keadaan di mana tugas-tugas harus dilakukan dengan benar.  Ia kadang-kadang tampak dingin dan mendominasi.  Ia juga bisa sangat fokus pada tugas dan melupakan orang-orang di sekitarnya.  Sangat mengharapkan orang-orang terlibat dalam proyeknya, tetapi tidak memperdulikan apa yang diinginkan oleh orang-orang itu.  Ia perlu mendengar dan memikirkan  apa yang menjadi keinginan orang di sekitarnya, khususnya kesempatan untuk mencoba.  Ia sangat membutuhkan persetujuan sosial seperti halnya ia sangat mempercayai orang lain.  Karena itu, ia kadang-kadang berlebihan dalam menilai orang dan kemampuannya.  Ia tampak tidak konsisten dan tidak karuan karena ketidakmampuannya berkonsentrasi dan fokus dalam waktu yang lama.  Perlu belajar untuk secara sungguh-sungguh mendengarkan orang-orang di sekitarnya dari pada selalu berpikir apa yang ingin dikatakan.  Ia mempunyai kemampuan logika yang tinggi ketika ia mau menggunakannya. Karir: Specialist/Technical Selling (Computer, Finance, Engineer and others, Chef, Technical/Capital Equipment Selling), Financial (Manager, Specialist), Computer Hardware Sales, Engineering (Manager, Designer, Buyer, Draughtsman), Project Engineer, Sales Engineer, Consultant, Trainer, Lecturer, Hotelier, Insurance, Mortgage and Finance Sales, Teacher, Travel Agent, Personnel and Marketing Services.'],
                ['max' => 6, 'label' => 'REFORMER', 'note' => 'Seorang yang bersahabat dan sosial; ia juga suka mengendalikan situasi dan menjadi pemimpin.  Ia menyelesaikan tugasnya melalui keterampilan sosialnya; ia peduli dan menerima orang lain.  Ia berkonsentrasi pada tugas yang ada di tangannya sampai selesai dan akan minta bantuan orang lain jika perlu.  Ia menyadari keterbatasannya dan meminta bantuan jika memerlukannya.  Ia disukai dan orang ingin menolongnya.  Senang membagi kebanggaannya dengan kelompok; ia seorang team player tetapi juga team leader.  Menginginkan popularitas dan pengakuan. Karir: Hotelier, Customer Service, Complaints Manager, Recruiting Agent, Sales (Manager/Person), Marketing Services, Public Relations, Politician, Computer Software Sales, Lecturer, Engineering and Production (Manager/Supervisor).'],
                ['max' => 7, 'label' => 'MOTIVATOR', 'note' => 'Seorang yang menampilkan gaya bersemangat ketika termotivasi pada sasaran.  Ia lebih suka memimpin atau melibatkan diri, walaupun ia juga mau melayani sebagai pembantu.  Ia membutuhkan pengakuan dan penghargaan serta senang pada peran pendukung.  Ia peduli kepada orang-orang di sekitarnya dan akan mempertimbangkan perasaan orang lain dalam proses pengambilan keputusan.  Menampilkan keterampilan berhubungan dan berkomunikasi dengan sangat baik.  Ia akan berusaha keras menyelesaikan tugas dengan cepat dan efisien. Karir: Hotelier, Community Counseling, Customer Service, Complaints Manager, Community Work, Recruitment Consultant, Hospitality, Teacher, Telemarketing, Production Manager, Complaints Manager, Recruiting Agent, Sales (Manager/Person), Marketing Services, Public Relations, Politician, Call Centre Manager, Lecturer, Engineering and Production (Manager/Supervisor).'],
                ['max' => 8, 'label' => 'INQUIRER', 'note' => 'Seorang yang sabar, terkontrol dan suka menggali fakta dan jalan keluar.  Ia tenang dan ramah.  Ia merencanakan pekerjaan dengan hati-hati, tetapi agresif, menanyakan sesuatu serta mengumpulkan data pendukung.  Kemudian ia bekerja dengan konsisten dengan arahan yang benar.  Menjadi individu yang penuh perhatian, rendah hati, dan ia berhubungan baik dengan hampir semua orang.  Seorang yang konsisten dan suka menolong. People skill darinya melebihi orientasi tugasnya. Karir: Directing, Managing or Supervising (in Engineering, Accountancy, Research and Development and Computing disciplines), Research Manager, Scientific Work, Accountant, Administration, Project Engineer, Draughtsman, Designer, Analyst, Finance, Chemist, Technical Service Support, Flight Attendant, Technician, Service Engineer, Service Manager, Security Specialist.'],
                ['max' => 9, 'label' => 'PENGAMBIL KEPUTUSAN', 'note' => 'Tidak basa-basi dan tegas, ia cenderung merupakan seorang invidualis yang kuat. Ia berpandangan jauh ke depan, progresif dan mau berkompetisi untuk mencapai sasaran. DI seorang yang selalu ingin tahu dan mempunyai minat dengan cakupan yang luas. Ia seorang yang logis, kritis dan tajam dalam memecahkan masalah. Sering kali ia tampak imajinatif. Ia mempunyai kemampuan memimpinan yang baik. Ia kadang tampak keras kepala atau dingin karena orientasi dan prioritasnya pada tugas cenderung melebihi orientasi terhadap sesama. Ia mencanangkan standard tinggi pada dirinya dan akan sangat kritis ketika standard ini tidak dicapai. Ia juga menempatkan standard tinggi pada orang-orang di sekitarnya, serta mengutamakan kesempurnaan. Ia menginginkan otoritas yang jelas dan menyukai tugas-tugas baru. Karir: General Management (Directing/Managing/Supervising, Public Relations, Business Management, Conflict Resolution, Industrial Relations, Business Consultant, Trouble Shooting, Sales and Sales Management, Marketing, Promoting, Production (Director, Manager, Supervisor), Consultancy, Publishing, Sales Executive, Promotional Work, Brokers, Self-Employment, Advertising, Lecturing, Dealing/Broking.'],
                ['max' => 10, 'label' => 'DIRECTOR', 'note' => 'Fokus pada penyelesaian pekerjaan dan menunjukkan penghargaan yang tinggi kepada orang lain.  Ia memiliki kemampuan untuk menggerakkan orang dan pekerjaan dikarenakan keterampilannya berpikir ke depan dan hubungan antar manusia.  Tidak berorientasi detil, ia fokus pada target secara keseluruhan dengan menyerahkan hal detil kepada orang lain.  Enerjik dan sosial, ia mampu memotivasi orang lain sambil menyelesaikan pekerjaannya.  Ia menampilkan rasa percaya diri dan mampu meyakinkan orang lain.  Sekali ia memutuskan sesuatu, ia akan terus mengerjakannya dan bertahan sampai selesai. Karir: Engineering and Production (Directing, Managing, Supervising), Sales, Sales Management, Service Manager, Distribution, Public Relations, Office Management, Account Manager, Customer Service, Retail Manager, IT, Lecturer, Logistics, Manager-General, National Accounts Manager, Teacher, Projects Manager.'],
                ['max' => 11, 'label' => 'SELF-MOTIVATED', 'note' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik. Karir: Engineering and Production (Directing, Managing, Supervising), Project Management, Researcher, Chemist (R&amp;D), Planner, Engineering (R&amp;D), Systems Analyst, Commercial Planner, Computer Engineer, Programmer, IT, Other computer-related disciplines, Technical Trouble Shooting and Directing, Lawyer, Solicitor, Development Engineer, Work Study, Barrister, Attorney.'],
                ['max' => 12, 'label' => 'MEDIATOR', 'note' => 'Merupakan individu yang berorientasi pada orang, ia mampu menggabungkan ketepatan dan loyalitas.  Ia cenderung peka dan mempunyai standard yang tinggi.  Ia menginginkan stabilitas dan berorientasi terhadap sasaran.  Ia menginginkan pengakuan sosial dan perhatian pribadi.  Ia bersahabat, antusias, informal, banyak bicara, dan mungkin sangat mencemaskan apa yang dipikirkan oleh orang lain.  Ia menolak agresi, dan mengharapkan suasana harmonis.  Ia cenderung cukup cerdas dalam berbagai hal. Ia merupakan pencari fakta yang sangat baik dan akan membuat keputusan yang baik setelah mengumpulkan fakta dan data pendukung. Karir: Engineering and Production (Supervisor, Installer, Technician, Service and Design), Research (Supervisor, Chemist, Lab. Technician), Trainer, Finance (Supervisor, Accountant, Advisor), Public Relations, Administration, Office Administrator, Market Analyst, System Analyst, Programmer, Selling (Technical/Service).'],
                ['max' => 13, 'label' => 'PRACTITIONER', 'note' => 'Merupakan individu yang berorientasi pada orang, ia mampu menggabungkan ketepatan dan loyalitas.  Ia cenderung peka dan mempunyai standard yang tinggi.  Ia menginginkan stabilitas dan berorientasi terhadap sasaran.  Ia menginginkan pengakuan sosial dan perhatian pribadi.  Bersahabat, antusias, informal, banyak bicara, dan mungkin sangat mencemaskan apa yang dipikirkan oleh orang lain.  Ia menolak agresi dan mengharapkan suasana harmonis.  Ia cenderung cukup cerdas dalam berbagai hal. Ia merupakan pencari fakta yang sangat baik dan akan membuat keputusan yang baik setelah mengumpulkan fakta dan data pendukung. Karir: Engineering and Production (Supervisor, Installer, Technician, Service and Design), Research (Supervisor, Chemist), Trainer, Finance (Manager, Supervisor, Accountant, Advisor), Public Relations-Administration, Purchasing, Chemist Research, Office Administrator, Computer Programmer, Market Analyst, System Analyst, Programmer, Research and Development Supervisor, Laboratory Technician, Legal, Selling (Technical/Service).'],
                ['max' => 14, 'label' => 'RESPONSIVE &amp; THOUGHTFUL', 'note' => 'Merupakan individu yang berorientasi pada orang dan lancar berkomunikasi serta loyal.  Ia cenderung sensitif dan mempunyai standard yang tinggi.  Keputusannya dibuat berdasarkan fakta dan data pendukung.  Ia sepertinya tidak bisa diam.  Ia perlu untuk lebih terus terang dan jangan terlalu subyektif.  Ia butuh pengakuan sosial dan perhatian pribadi; ia dapat cepat akrab dengan orang lain.  Ia bersahabat, antusias, informal, banyak bicara dan terlalu khawatir terhadap apa yang dipikirkan orang.  Ia menguasai banyak hal.  Ia ingin diterima sebagai anggota kelompok dan ingin mengetahui secara pasti apa yang diharapkan darinya sebelum ia memulai proyek baru. Karir: Actors, Chef, Personnel, Welfare, Broadcasting, Training, Attorney, Teaching, Accounting, Technical Instructor, Accounting-General, Accounts Supervisor, Customer Services, Public Relations, Artist, Hotelier, Demonstrator, Florist/Floral Designer, Engineering (Sales, Service, Project, Draughtsman, Designer), Graphic Designer, Specialist (Soft/Services), Selling, Purchasing, Singers, Technical Instructor, Personnel Management, Politician, Supervising (Engineering, Production, Accounts), Administration Work, Sales Engineer, Secretarial, Industrial Relations Specialist.'],
                ['max' => 15, 'label' => 'SPECIALIST', 'note' => 'Merupakan individu konsisten yang berusaha menjaga lingkungan/suasana yang tidak berubah.  Ia bekerja dengan baik bersama orang-orang dengan berbagai kepribadian karena perilakunya yang terkendali dan rendah hati.  Sabar, loyal dan suka menolong.  Persahabatan dikembangkannya dengan lambat dan selektif.  Ia tidak bosan dengan rutinitas dan sangat baik bekerja dengan petunjuk dan peraturan yang jelas. Ia mengharapkan bantuan dan supervisi pada saat mengawali proyek baru.  Ia butuh waktu untuk menyesuaikan diri dengan perubahan dan sungkan menjalankan "cara-cara lama mengerjakan sesuatu".  Ia akan menghindari konfrontasi dan berusaha sekuat tenaga memendam perasaannya. Karir: Administrative Work, Engineering and Production areas (Sales, Services, Project, Painter, Plumber, Draughtsman, Designer, Operative), Chef, Accounting, Telemarketing/Tele-Sales, Research and Development, Administrator, Florist/Floral Designer, Retail-General, Sales-General, Accounting-General, Service-General, Landscape Gardener.'],
                ['max' => 16, 'label' => 'PERFECTIONIST', 'note' => 'Berpikir sistematis dan cenderung mengikuti prosedur dalam kehidupan pribadi dan pekerjaannya.  Teratur dan memiliki perencanaan yang baik, ia teliti dan fokus pada detil.  Bertindak dengan penuh kebijaksanaan, diplomatis dan jarang menentang rekan kerjanya dengan sengaja.  Ia sangat berhati-hati, sungguh-sungguh mengharapkan akurasi dan standard tinggi dalam pekerjaannya.  Ia cenderung terjebak dalam hal detil, khususnya jika harus memutuskan.  Menginginkan adanya petunjuk standard pelaksanaan kerja dan tanpa perubahan mendadak. Karir: Researcher (Technician, Chemist, Quality Control), Engineer (Project, Draughtsman, Armed Forces, Designer), Statistician, Surveyor, Optician, Medical Specialist, Health Care, IT Management, Planner, Technical Writing, Production, Dentist, Quality Control, Planning, Dental Technician, Accounting, Computer Programmer, Psychologist, Surgeon, Architect, Medical Specialist.'],
                ['max' => 17, 'label' => 'PEACEMAKER, RESPECTFULL &amp; ACCURATE', 'note' => 'Ia adalah orang yang baik secara alamiah dan sangat berorientasi detil.  Ia peduli dengan orang-orang di sekitarnya dan mempunyai kualitas yang membuatnya sangat teliti dalam penyelesaian tugas.  Ia mempertimbangkan sekelilingnya dengan hati-hati sebelum membuat keputusan untuk melihat pengaruhnya pada mereka; saat tertentu ia terlalu hati-hati.  Jika ia merasa seseorang memanfaatkan situasi, ia akan memperlambat kerjanya sehingga dapat mengamati apa yang sedang berlangsung di sekitarnya. Karir: Office (Manager, Supervisor, Person), Chief Clerk, General Administrator, Production Supervisor, Planner, Accountant, Research and Development, Flight Attendant, Engineering (Project Manager, Supervisor, Technician), Computer Programmer, Draughtsman, Soft/Service Selling, Doctor, Cashier, Receptionist, Data Entry, Planner, Word Processing, Property Manager, Database Administrator, Health Care, Statistician, Nursing-Administration, Company Secretary, System Analyst, Programmer, Statistician, Accounting-General, Security Specialist.'],
                ['max' => 18, 'label' => 'CHALLENGER', 'note' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik. Karir: Engineering (Management, Research, Design), Actuaries, Research (R&amp;D), Planning, Chemist, Hospital Supervisor, Industrial Marketing, Investment Banking, Medical Administrator, Mortgage Brokers, Accountancy, Fund Management, Specialist Finance, Quality Control and Specialist work in any area where knowledge and experience is available, Production, Financial Services, Technical Management, Project Leader, Matron, Strategic Planning, Industrial Marketing.'],
                ['max' => 19, 'label' => 'CHANCELLOR', 'note' => 'Ia menggabungkan antara kesenangan dengan pekerjaan/bisnis ketika melakukan sesuatu. Ia kelihatan menyukai hubungan dengan sesama tetapi juga dapat mengerjakan hal-hal detil. Ia ingin melakukan segala sesuatu dengan tepat, dan ia akan menyelesaikan tugasnya untuk meyakinkan ketepatan dan kelengkapannya. Seorang yang ramah secara alami dan menikmati interaksi dengan sesama, akan tetapi ia akan juga menilai orang dan tugas secara hati-hati; persahabatannya akan bergeser sesuai dengan dorongan hatinya pada orang lain di sekitarnya. Ia sering melalaikan perencanaan yang seksama dan akan beralih ke pada proyek-proyek baru tanpa pertimbangan yang menyeluruh. Karir: Technical/Scientific (Directing, Management, Supervision), Engineering, Finance, Production Planning, Personnel Disciplines, Self-Employment, Credit Manager, Planner, Fund Management, Computer Hardware/Software Sales, IT, Business Consultant, Banking, Logistics, Lecturing, Work Study, Film Director, Transport, Consultancy, Industrial Relations and Computers (Selling, Software, Systems Analyst) and General Manager.'],
                ['max' => 20, 'label' => 'DIRECTOR', 'note' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik. Karir: Engineering and Production (Directing, Managing, Supervising), Sales, Sales Management, Service Manager, Distribution, Public Relations, Creative Designer, Office Management, Chief Engineer, Business Consultant, Chief Financial Officer, Customer Service, National Accounts Manager, Chief Accountant, Lecturer, Projects Manager, Research Planning, Human Resources, Scientific Work, Security Specialist, Solicitor, Planner, Production Administrator.'],
                ['max' => 21, 'label' => 'Director', 'note' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik. Karir: Engineering and Production (Directing, Managing, Supervising), Sales, Sales Management, Service Manager, Distribution, Public Relations, Creative Designer, Office Management, Chief Engineer, Business Consultant, Chief Financial Officer, Customer Service, National Accounts Manager, Chief Accountant, Lecturer, Projects Manager, Research Planning, Human Resources, Scientific Work, Security Specialist, Solicitor, Planner, Production Administrator.'],
                ['max' => 22, 'label' => 'CHALLENGER', 'note' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik. Karir: Technical/Scientific (Directing, Management, Supervision), Engineering, Finance, Production Planning, Personnel Disciplines, Self-Employment, Credit Manager, Planner, Lecturing, Work Study, Transport, Consultancy, Industrial Relations and Computers (Selling, Software, Systems Analyst) and General Manager.'],
                ['max' => 23, 'label' => 'CHALLENGER', 'note' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik. Karir: Engineering, Production and Finance (Directing, Administrating, Managing and Managing Specialist Work), Scientific, Research Planning, Personnel, Trouble Shooting, Credit Control, Chief Accountant, Accountant, Chief Engineer, Work Study, Consultancy, Designer, Draughtsman, Project Work, Security Specialist, Doctor, Attorney.'],
                ['max' => 24, 'label' => 'COMMUNICATOR', 'note' => 'Merupakan seorang yang antusias dan optimistik, ia lebih suka mencapai sasarannya melalui orang lain. Ia suka berhubungan dengan sesamanya - ia bahkan suka mengadakan “pesta” atau kegiatan untuk berkumpul, dan ini menunjukkan kepribadiannya yang ramah. Ia tidak suka bekerja sendirian dan cenderung bersama dengan orang lain dalam menyelesaikan proyek.  Perhatian dan fokusnya tidak sebaik apa yang dia inginkan -  maka ia membutuhkan energi yang besar untuk mampu bergerak cepat dari satu hal ke hal berikutnya tanpa penundaan.  Ia sangat menonjol dalam keterampilan berkomunikasi, dan ini merupakan salah satu kekuatan yang paling sering digunakan.  Ia memiliki kemampuan untuk memotivasi dan memberi semangat dengan kata-katanya, dan ia dikenal sebagai individu yang inspirasional. Ketika ia harus memusatkan perhatiannya pada tugas, Ia akan menjadi tidak akurat dan bahkan tidak terorganisir.  Tetapi ia akan memusatkan perhatian kepada yang harus ia senangkan, karena ia enggan sekali untuk menolak.  Ia menginginkan pengakuan sosial dan takut akan penolakan.  Ia mudah menemukan teman dan berusaha menciptakan suasana yang menyenangkan.  Ia membutuhkan seorang manajer atau supervisor untuk menentukan batas waktu yang jelas dalam pekerjaannya, ia lebih suka menggunakan gaya manajemen partisipatif yang dibangun berdasarkan hubungan yang kuat. Karir: Promoting, Demonstrating, Canvassing, Marketing Services, Public Relations, Lecturing, Advertising, Publican, Publishing, Hospitality, Retail-General, Human Resources, Journalist, Singers, Technical Writing, Tour Guide, Promotional Work, Hotelier, Dancers, Host, Actors, Travel Agent, Politician, and very soft selling.'],
                ['max' => 25, 'label' => 'ADVISOR', 'note' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan. Karir: Personnel, Welfare, Training, Hotelier, Promoting, Travel Agent, Lecturing, Upmarket/Speciality Sales, Soft/Service Selling, Beauty Therapist, Psychologist, Nursing, Human Resources, Retail-Specialist, Veterinarian, Social Work, Personal Assistant, Personnel-HR, Coach, Mentor.'],
                ['max' => 26, 'label' => 'ASSESSOR', 'note' => 'Merupakan seorang yang ramah dan suka berteman; ia merasa nyaman walaupun dengan orang asing. Ia dapat mengembangkan hubungan baru dengan mudah, dan pada umumnya dapat mengendalikan diri sampai pada tingkat dimana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia seorang yang sangat sosial, menunjukkan kepedulian dan persahabatan ketika sedang melakukan tugas-tugas di tangannya. Ia cenderung perfeksionis secara alamiah, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan.  Ia berkeinginan mempromosikan tugas-tugas orang lain, juga kepunyaannya.  Kadang-kadang ia salah menilai kemampuan orang lain dikarenakan pandangan-pandangannya yang optimis. Karir: Teaching, Training, Inventing, Specialist Selling (Engineering, Finance or any area involving capital equipment), Project Engineer, Finance, Service Engineer or Supervising within a Technical/Specialist Area, Public Relations, Environmentalist, Marketing, Conference Organiser, Estate Agent.'],
                ['max' => 27, 'label' => 'ASSESSOR', 'note' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik. Karir: Specialist/Technical Selling (Computer, Finance, Engineer and others, Technical/Capital Equipment Selling), Financial (Manager, Specialist), Engineering (Manager, Designer, Buyer, Draughtsman), Project Engineer, Sales Engineer, Consultant, Trainer, Lecturer, Hotelier, Travel Agent, Personnel and Marketing Services.'],
                ['max' => 28, 'label' => 'RESPONSIVE &amp; THOUGHTFUL', 'note' => 'Merupakan individu yang berorientasi pada orang dan lancar berkomunikasi serta loyal.  Ia cenderung sensitif dan mempunyai standard yang tinggi.  Keputusannya dibuat berdasarkan fakta dan data pendukung.  Ia sepertinya tidak bisa diam.  Ia perlu untuk lebih terus terang dan jangan terlalu subyektif.  Ia butuh pengakuan sosial dan perhatian pribadi; ia dapat cepat akrab dengan orang lain.  Ia bersahabat, antusias, informal, banyak bicara dan terlalu khawatir terhadap apa yang dipikirkan orang.  Ia menguasai banyak hal.  Ia ingin diterima sebagai anggota kelompok dan ingin mengetahui secara pasti apa yang diharapkan darinya sebelum ia memulai proyek baru. Karir: Personnel, Welfare, Training, Attorney, Teaching, Accounting, Technical Instructor, Customer Services, Public Relations, Artist, Hotelier, Demonstrator, Engineering (Sales, Service, Project, Draughtsman, Designer), Specialist (Soft/Services), Selling, Purchasing, Supervising (Engineering, Production, Accounts), Administration Work, Secretarial, Industrial Relations Specialist.'],
                ['max' => 29, 'label' => 'SELF-MOTIVATED', 'note' => 'Merupakan seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan juga ingin memberikan bantuan dan dukungan.  Secara internal termotivasi oleh target pribadi, Ia menyukai orang-orang, tetapi juga mempunyai kemampuan untuk berorientasi pada pekerjaannya pada saat dibutuhkan.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya memiliki kontribusi akan keberhasilannya.  Keuletannya setelah memulai pekerjaan, ia akan berusaha keras untuk mendapatkan sasarannya.  Seorang yang bebas, ia orang yang cermat dan memiliki tindak lanjut yang baik.  Ia bisa menjadi tidak ramah walaupun ia pada dasarnya ia yang berorientasi pada orang; dan pada situasi yang tidak membuatnya nyaman, ia lebih suka mendukung pemimpinnya dari pada keterlibatannya dengan situasi. Karir: Investigator, Researcher, Accountant, Engineering, Production/Engineering Supervisor, Computer Specialist, Architect, Transport/Warehouse Supervisor, Credit Controller, DP Supervisor, Computer Specialist, Research and Development, Private Investigator, Quality Controller, Engineering (Designer, Draughtsman, Project Engineer), Sales and Service Engineer, Property Manager, Attorney, Administration Manager'],
                ['max' => 30, 'label' => 'ADVISOR', 'note' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" yang sebenarnya dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan. Karir: Personnel Welfare, Training, Hotelier, Promoting, Travel Agent, Lecturing, Child Care, Charitable Organizations, Soft or Service Selling, Psychologist, Therapist, Nurse, Personal Assistant, Hospitality Manager, Social Work, Student Services, Upmarket/Speciality Sales.'],
                ['max' => 31, 'label' => 'DIRECTOR', 'note' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik. Karir: Engineering and Production (Supervision), Service Selling, Distribution and Warehouse Supervision/Manager, Office Management, Customer Service, System Analyst, Radio Announcer, Technical Writing, Telemarketing, TV Presenter, Project Engineer, Film Producer, Programmer, Sales/Service Engineer, Accounting, Draughtsman, Project Engineer.'],
                ['max' => 32, 'label' => 'ADVISOR', 'note' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" yang sebenarnya dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan. Karir: Engineering and Production (Supervision), Service Selling, Distribution and Warehouse Supervision, Office Management, Customer Service, System Analyst, Programmer, Sales/Service Engineer, Accounting, Draughtsman, Project Engineer.'],
                ['max' => 33, 'label' => 'ADVOCATE', 'note' => 'Merupakan orang yang stabil, individu yang ramah yang berusaha keras membangun hubungan yang positif di tempat kerja dan di rumah.  Ia dapat menjadi sangat berorientasi detil ketika situasi membutuhkan; tetapi secara keseluruhan ia cenderung individualis, independen dan sedikit perhatian terhadap detil.  Sekali dia membuat keputusan, sangat sulit mengubah pendiriannya.  Ia menyukai hubungan dengan orang dan cenderung mendukung pihak yang lemah.  Ia akan mengambil posisi berlawanan dengan ketidaksepakatan dan merasa frustrasi jika sesuatu tidak sejalan dengannya.  Ia ingin diterima sebagai anggota tim, dan ia menginginkan orang lain menyukainya.  Ia cukup sulit membuat keputusan sampai parameter wewenang secara jelas ditentukan, dan ia mungkin cenderung tidak sungguh-sungguh jika dipaksa membuat keputusan ketika ia tidak ingin melakukannya.  Ia menginginkan orang lain yang membuat keputusan, khususnya jika ada orang yang sangat ia hargai dan hormati.  Ia cenderung moderat, cermat dan dapat diandalkan. Karir: Personnel Welfare, Training, Teaching, Attorney, Accounting, Technical Instructor, Customer Service, Public Relations, Artist, Hotelier, Demonstrator, Engineer (Sales, Service, Project, Draughtsman, Designer), Specialist (Soft/Service), Selling, Purchasing, Supervising (Engineering, Production, Accounts) Administrative Work, Secretarial.'],
                ['max' => 34, 'label' => 'INQUIRER', 'note' => 'Seorang yang baik secara alamiah dan sangat berorientasi detil.  Ia peduli dengan orang-orang di sekitarnya dan mempunyai kualitas yang membuatnya sangat teliti dalam penyelesaian tugas.  Ia mempertimbangkan sekelilingnya dengan hati-hati sebelum membuat keputusan untuk melihat pengaruhnya pada mereka; saat tertentu ia terlalu hati-hati.  Jika ia merasa seseorang memanfaatkan situasi, ia akan memperlambat kerjanya sehingga dapat mengamati apa yang sedang berlangsung di sekitarnya. Karir: Directing, Managing or Supervising (in Engineering, Accountancy, Research and Development and Computing disciplines), Accountant, Project Engineer, Draughtsman, Designer, Analyst, Chemist, Technician, Service Engineer, Manager, Security Specialist.'],
                ['max' => 35, 'label' => 'ADVOCATE', 'note' => 'Merupakan orang yang stabil, individu yang ramah yang berusaha keras membangun hubungan yang positif di tempat kerja dan di rumah.  Ia dapat menjadi sangat berorientasi detil ketika situasi membutuhkan; tetapi secara keseluruhan ia cenderung individualis, independen dan sedikit perhatian terhadap detil.  Sekali dia membuat keputusan, sangat sulit mengubah pendiriannya.  Ia menyukai hubungan dengan orang dan cenderung mendukung pihak yang lemah.  Ia akan mengambil posisi berlawanan dengan ketidaksepakatan dan merasa frustrasi jika sesuatu tidak sejalan dengannya.  Ia ingin diterima sebagai anggota tim, dan ia menginginkan orang lain menyukainya.  Ia cukup sulit membuat keputusan sampai parameter wewenang secara jelas ditentukan, dan ia mungkin cenderung tidak sungguh-sungguh jika dipaksa membuat keputusan ketika ia tidak ingin melakukannya.  Ia menginginkan orang lain yang membuat keputusan, khususnya jika ada orang yang sangat ia hargai dan hormati.  Ia cenderung moderat, cermat dan dapat diandalkan. Karir: Personnel Welfare, Administrator, Advisers, Training, Teaching, Attorney, Accounting, Counseling, Technical Instructor, Customer Service, Accounting-General, Public Relations, Accounts Supervisor, Artist, Hotelier, Demonstrator, Engineer (Sales, Service, Project, Draughtsman, Designer), Specialist (Soft/Service), Selling, Purchasing, Sales Engineer, Legal, Negotiator, Student Service, Photographer, Physiotherapist, Project Engineer, Vocational Education, Supervising (Engineering, Production, Accounts) Administrative Work, Demonstrator, Secretarial, Hospitality Manager.'],
                ['max' => 36, 'label' => 'ASSESSOR', 'note' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik. Karir: Sales (Technical/Specialist), Public Relations, Lecturer, Academic, Personnel Administration, Purchasing, Travel Agent, Training, Teaching, Real Estate Agent, Hospitality Administration, Sales-Technical, Hotelier, Project Engineer, Service Engineer.'],
                ['max' => 37, 'label' => 'CHALLENGER', 'note' => 'Seorang yang sangat berorientasi pada tugas dan sensitif pada permasalahan. Ia lebih mempedulikan tugas yang ada dibanding orang-orang di sekitarnya, termasuk perasaan mereka. Ia sangat kukuh/keras dan mempunyai pendekatan yang efektif dalam pemecahan masalah. Oleh karena sifat alamiah dan keinginannya akan hasil yang terukur, ia akan tampak dingin, tidak berperasaan dan menjaga jarak. Ia membuat keputusan berdasar pada fakta, bukan emosi. ia cenderung pendiam dan tidak mudah percaya. Karir: Directing, Managing or Supervising (Engineering, Research, Finance, Planning), Designer, Work Study, Sales (Technical/ Specialist), Logistic Support, Systems Analyst, Lecturer, Company Secretary, Negotiator and Purchasing.'],
                ['max' => 38, 'label' => 'CONTEMPLATOR', 'note' => 'Berorientasi pada hal detil dan mempunyai standard tinggi untuk dirinya. Ia logis dan analitis. Ia ingin berbuat yang terbaik, dan ia selalu berpikir ada ruang untuk peningkatan/kemajuan. Ia cenderung kompetitif dan ingin menghasilkan pekerjaan dengan mutu yang terbaik. Ia sebenarnya sensitif terhadap orang-orang, tetapi karena sifat logisnya, orientasinya terhadap tugas dapat menutupinya dengan mudah. Ia suka dihargai untuk pekerjaannya yang berkualitas. Ia mampu mengerjakan tugas-tugas; dan mencapai sasarannya. Ia sangat memusatkan perhatian pada tugas yang ada, mantap dan dapat diandalkan. Karir: Engineering, Research, Production and Finance (Director, Manager atau Supervisor), Work Study, Accountant, Administrator, Quality Controller, Safety Officer, Market Analyst, Planner and Personnel (Director, Manager, Administrator), MIS Manager, Security Manager, Loss Control.'],
                ['max' => 39, 'label' => 'ASSESSOR', 'note' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik. Karir: Directing, Managing or Supervising (Engineering, Research, Finance, Planning), Designer, Work Study, Sales (Technical/Specialist), Lecturer, Company Secretary, Negotiator and Purchasing.'],
                ['max' => 40, 'label' => 'PRECISIONIST', 'note' => 'Berpikir sistematis dan cenderung mengikuti prosedur dalam kehidupan pribadi dan pekerjaannya.  Teratur dan memiliki perencanaan yang baik, ia teliti dan fokus pada detil.  Ia bertindak dengan penuh kebijaksanaan, diplomatis dan jarang menentang rekan kerjanya dengan sengaja.  Ia sangat berhati-hati, ia sungguh-sungguh mengharapkan akurasi dan standard tinggi dalam pekerjaannya.  Ia cenderung terjebak dalam hal detil, khususnya jika harus memutuskan.  ia menginginkan adanya petunjuk standard pelaksanaan kerja dan tanpa perubahan mendadak. Karir: Engineering, Research Director, Production and Finance (Director, Manager, Supervisor), Work Study, Accountant, Administrator, Quality Controller, Financial Services Manager, Safety Officer, Market Analyst, Planner and Personnel (Director, Manager, Administrator), MIS Manager, Electrician, Security Manager, Financial Researcher, Planner, Printer, Production Controller, Production Manager, Personnel Management, Loss Control.'],

            ],
            'dimensions' => [
                'D' => ['label' => 'Dominance', 'items' => []],
                'I' => ['label' => 'Influence', 'items' => []],
                'S' => ['label' => 'Steadiness', 'items' => []],
                'C' => ['label' => 'Compliance', 'items' => []],
            ],
            'interpretation' => 'DISC 24 item ipsatif (forced-choice MOST/LEAST) — profil gaya perilaku, bukan penilaian kemampuan.',
            'license_note' => 'Model DISC (Marston 1928) — istilah tidak diasapi; item & tabel konversi milik klien.',
            'escalate_items' => null,
            'is_active' => true,
            'is_addon' => true,
        ]);

        // options = 4 statement; jawaban disimpan sebagai {DSnp, DSnk} (index statement).
        $items = [
            ['DS1', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 1 dari 24).', 'forced-choice', [
  0 => 'Gampang gaul, Mudah setuju',
  1 => 'Percaya, Mudah percaya pada orang',
  2 => 'Petualang, Mengambil resiko',
  3 => 'Toleran, Menghormati',
], false, 1, 3],
            ['DS2', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 2 dari 24).', 'forced-choice', [
  0 => 'Lembut suara, Pendiam',
  1 => 'Optimistik, Visioner',
  2 => 'Pusat Perhatian, Suka gaul',
  3 => 'Pendamai, Membawa Harmoni',
], false, 2, 3],
            ['DS3', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 3 dari 24).', 'forced-choice', [
  0 => 'Menyemangati orang',
  1 => 'Berusaha sempurna',
  2 => 'Bagian dari kelompok',
  3 => 'Ingin membuat tujuan',
], false, 3, 3],
            ['DS4', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 4 dari 24).', 'forced-choice', [
  0 => 'Menjadi frustrasi',
  1 => 'Menyimpan perasaan saya',
  2 => 'Menceritakan sisi saya',
  3 => 'Siap beroposisi',
], false, 4, 3],
            ['DS5', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 5 dari 24).', 'forced-choice', [
  0 => 'Hidup, Suka bicara',
  1 => 'Gerak cepat, Tekun',
  2 => 'Usaha menjaga keseimbangan',
  3 => 'Usaha mengikuti aturan',
], false, 5, 3],
            ['DS6', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 6 dari 24).', 'forced-choice', [
  0 => 'Kelola waktu secara efisien',
  1 => 'Sering terburu-buru, Merasa tertekan',
  2 => 'Masalah sosial itu penting',
  3 => 'Suka selesaikan apa yang saya mulai',
], false, 6, 3],
            ['DS7', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 7 dari 24).', 'forced-choice', [
  0 => 'Tolak perubahan mendadak',
  1 => 'Cenderung janji berlebihan',
  2 => 'Tarik diri di tengah tekanan',
  3 => 'Tidak takut bertempur',
], false, 7, 3],
            ['DS8', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 8 dari 24).', 'forced-choice', [
  0 => 'Penyemangat yang baik',
  1 => 'Pendengar yang baik',
  2 => 'Penganalisa yang baik',
  3 => 'Delegator yang baik',
], false, 8, 3],
            ['DS9', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 9 dari 24).', 'forced-choice', [
  0 => 'Hasil adalah penting',
  1 => 'Lakukan dengan benar, Akurasi penting',
  2 => 'Dibuat menyenangkan',
  3 => 'Mari kerjakan bersama',
], false, 9, 3],
            ['DS10', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 10 dari 24).', 'forced-choice', [
  0 => 'Akan berjalan terus tanpa kontrol diri',
  1 => 'Akan membeli sesuai dorongan hati',
  2 => 'Akan menunggu, Tanpa tekanan',
  3 => 'Akan mengusahakan  yang kuinginkan',
], false, 10, 3],
            ['DS11', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 11 dari 24).', 'forced-choice', [
  0 => 'Ramah, Mudah bergabung',
  1 => 'Unik, Bosan rutinitas',
  2 => 'Aktif mengubah sesuatu',
  3 => 'Ingin hal-hal yang pasti',
], false, 11, 3],
            ['DS12', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 12 dari 24).', 'forced-choice', [
  0 => 'Non-konfrontasi, Menyerah',
  1 => 'Dipenuhi hal detail',
  2 => 'Perubahan pada menit terakhir',
  3 => 'Menuntut, Kasar',
], false, 12, 3],
            ['DS13', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 13 dari 24).', 'forced-choice', [
  0 => 'Ingin kemajuan',
  1 => 'Puas dengan segalanya',
  2 => 'Terbuka memperlihatkan perasaan',
  3 => 'Rendah hati, Sederhana',
], false, 13, 3],
            ['DS14', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 14 dari 24).', 'forced-choice', [
  0 => 'Tenang, Pendiam',
  1 => 'Bahagia, Tanpa beban',
  2 => 'Menyenangkan, Baik hati',
  3 => 'Tak gentar, Berani',
], false, 14, 3],
            ['DS15', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 15 dari 24).', 'forced-choice', [
  0 => 'Menggunakan waktu berkualitas dgn teman',
  1 => 'Rencanakan masa depan, Bersiap',
  2 => 'Bepergian demi petualangan baru',
  3 => 'Menerima ganjaran atas tujuan yg dicapai',
], false, 15, 3],
            ['DS16', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 16 dari 24).', 'forced-choice', [
  0 => 'Aturan perlu dipertanyakan',
  1 => 'Aturan membuat adil',
  2 => 'Aturan membuat bosan',
  3 => 'Aturan membuat aman',
], false, 16, 3],
            ['DS17', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 17 dari 24).', 'forced-choice', [
  0 => 'Pendidikan, Kebudayaan',
  1 => 'Prestasi, Ganjaran',
  2 => 'Keselamatan, keamanan',
  3 => 'Sosial, Perkumpulan kelompok',
], false, 17, 3],
            ['DS18', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 18 dari 24).', 'forced-choice', [
  0 => 'Memimpin, Pendekatan langsung',
  1 => 'Suka bergaul, Antusias',
  2 => 'Dapat diramal, Konsisten',
  3 => 'Waspada, Hati-hati',
], false, 18, 3],
            ['DS19', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 19 dari 24).', 'forced-choice', [
  0 => 'Tidak mudah dikalahkan',
  1 => 'Kerjakan sesuai perintah, Ikut pimpinan',
  2 => 'Mudah terangsang, Riang',
  3 => 'Ingin segalanya teratur, Rapi',
], false, 19, 3],
            ['DS20', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 20 dari 24).', 'forced-choice', [
  0 => 'Saya akan pimpin mereka',
  1 => 'Saya akan melaksanakan',
  2 => 'Saya akan meyakinkan mereka',
  3 => 'Saya dapatkan fakta',
], false, 20, 3],
            ['DS21', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 21 dari 24).', 'forced-choice', [
  0 => 'Memikirkan orang dahulu',
  1 => 'Kompetitif, Suka tantangan',
  2 => 'Optimis, Positif',
  3 => 'Pemikir logis, Sistematik',
], false, 21, 3],
            ['DS22', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 22 dari 24).', 'forced-choice', [
  0 => 'Menyenangkan orang, Mudah setuju',
  1 => 'Tertawa lepas, Hidup',
  2 => 'Berani, Tak gentar',
  3 => 'Tenang, Pendiam',
], false, 22, 3],
            ['DS23', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 23 dari 24).', 'forced-choice', [
  0 => 'Ingin otoritas lebih',
  1 => 'Ingin kesempatan baru',
  2 => 'Menghindari konflik',
  3 => 'Ingin petunjuk yang jelas',
], false, 23, 3],
            ['DS24', 'Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda (item 24 dari 24).', 'forced-choice', [
  0 => 'Dapat diandalkan, Dapata dipercaya',
  1 => 'Kreatif, Unik',
  2 => 'Garis dasar, Orientasi hasil',
  3 => 'Jalankan standar yang tinggi, Akurat',
], false, 24, 3],

        ];
        $this->upsertItems('disc24', $items);
    }

    // --- ac1: MCMI-III 175 item (xlsx klien) — clinical stub, grant-only ------

    private function mcmi3Items(): void
    {
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
            'license_note' => 'Skor T MCMI-III memerlukan tabel konversi berlisensi Millon/Pearson (referensi klien rusak); jawaban mentah Ya/Tidak disimpan untuk interpretasi psikolog klinis via grant.',
            'escalate_items' => null,
            'is_active' => false,
        ]);

        // 175 butir benar/salah; skor = jumlah mentah 'Ya' (bukan T-score).
        $items = [
            ['MC1', 'Akhir-akhir ini sepertinya aku kehabisan tenaga, bahkan di pagi hari', 'bool', ['Tidak', 'Ya'], false, 1, 1],
            ['MC2', 'Saya pikir sangat bagus jika ada peraturan karena itu adalah panduan yang baik untuk diikuti,', 'bool', ['Tidak', 'Ya'], false, 2, 1],
            ['MC3', 'Saya menikmati melakukan begitu banyak hal berbeda sehingga saya tidak dapat memutuskan mana yang harus saya mulai.', 'bool', ['Tidak', 'Ya'], false, 3, 1],
            ['MC4', 'Seringkali saya merasa lemah dan lelah.', 'bool', ['Tidak', 'Ya'], false, 4, 1],
            ['MC5', 'Saya tahu saya lebih unggul dari orang lain, itu sebabnya saya tidak peduli apa yang orang pikirkan.', 'bool', ['Tidak', 'Ya'], false, 5, 1],
            ['MC6', 'Orang-orang tidak pernah cukup menyadari hal-hal yang telah saya lakukan.', 'bool', ['Tidak', 'Ya'], false, 6, 1],
            ['MC7', 'Jika keluarga menekan saya, kemungkinan besar saya akan marah dan menolak melakukan apa yang mereka inginkan.', 'bool', ['Tidak', 'Ya'], false, 7, 1],
            ['MC8', 'Orang-orang mengolok-olok saya di belakang, membicarakan tentang apa yang saya lakukan dan seperti apa penampilan saya.', 'bool', ['Tidak', 'Ya'], false, 8, 1],
            ['MC9', 'Saya sering kali sangat kritis terhadap orang yang membuat saya kesal.', 'bool', ['Tidak', 'Ya'], false, 9, 1],
            ['MC10', 'Saya jarang mengungkapkan beberapa emosi yang biasanya saya miliki.', 'bool', ['Tidak', 'Ya'], false, 10, 1],
            ['MC11', 'Saya merasa sulit untuk menjaga keseimbangan saat berjalan.', 'bool', ['Tidak', 'Ya'], false, 11, 1],
            ['MC12', 'Saya menunjukkan emosi saya dengan mudah dan cepat.', 'bool', ['Tidak', 'Ya'], false, 12, 1],
            ['MC13', 'Dulu, kebiasaan saya menggunakan narkoba seringkali menimbulkan masalah bagi saya.', 'bool', ['Tidak', 'Ya'], false, 13, 1],
            ['MC14', 'Terkadang saya bisa bersikap sangat kasar dan tidak menyenangkan dengan keluarga saya.', 'bool', ['Tidak', 'Ya'], false, 14, 1],
            ['MC15', 'Hal-hal yang berjalan baik hari ini tidak akan bertahan lama.', 'bool', ['Tidak', 'Ya'], false, 15, 1],
            ['MC16', 'Saya orang yang sangat baik dan penurut.', 'bool', ['Tidak', 'Ya'], false, 16, 1],
            ['MC17', 'Ketika saya masih remaja, saya mendapat banyak masalah karena perilaku buruk saya di sekolah.', 'bool', ['Tidak', 'Ya'], false, 17, 1],
            ['MC18', 'Saya takut terlalu dekat dengan orang lain karena saya mungkin akan diejek atau dipermalukan.', 'bool', ['Tidak', 'Ya'], false, 18, 1],
            ['MC19', 'Sepertinya saya memilih teman yang akhirnya memperlakukan saya dengan buruk.', 'bool', ['Tidak', 'Ya'], false, 19, 1],
            ['MC20', 'Saya mempunyai banyak pikiran sedih sepanjang hidup saya sejak saya masih kecil.', 'bool', ['Tidak', 'Ya'], false, 20, 1],
            ['MC21', 'Saya suka menggoda lawan jenis.', 'bool', ['Tidak', 'Ya'], false, 21, 1],
            ['MC22', 'Saya adalah orang yang sangat berubah-ubah dan saya selalu mengubah opini dan perasaan saya.', 'bool', ['Tidak', 'Ya'], false, 22, 1],
            ['MC23', 'Minum alkohol tidak pernah menimbulkan masalah nyata bagi saya di tempat kerja.', 'bool', ['Tidak', 'Ya'], false, 23, 1],
            ['MC24', 'Beberapa tahun yang lalu saya mulai merasa gagal.', 'bool', ['Tidak', 'Ya'], false, 24, 1],
            ['MC25', 'Saya sering merasa bersalah tanpa alasan.', 'bool', ['Tidak', 'Ya'], false, 25, 1],
            ['MC26', 'Orang lain iri dengan kemampuan saya.', 'bool', ['Tidak', 'Ya'], false, 26, 1],
            ['MC27', 'Ketika saya mempunyai pilihan, saya lebih suka melakukan sesuatu sendirian.', 'bool', ['Tidak', 'Ya'], false, 27, 1],
            ['MC28', 'Saya pikir perilaku keluarga saya harus dikontrol dengan ketat.', 'bool', ['Tidak', 'Ya'], false, 28, 1],
            ['MC29', 'Orang biasanya menganggap saya orang yang pendiam dan serius.', 'bool', ['Tidak', 'Ya'], false, 29, 1],
            ['MC30', 'Akhir-akhir ini aku mulai merasa ingin menghancurkan sesuatu.', 'bool', ['Tidak', 'Ya'], false, 30, 1],
            ['MC31', 'Saya percaya bahwa saya adalah orang yang spesial dan saya pantas mendapatkan perhatian khusus dari orang lain kepada saya.', 'bool', ['Tidak', 'Ya'], false, 31, 1],
            ['MC32', 'Saya selalu mencari teman baru dan bertemu orang baru.', 'bool', ['Tidak', 'Ya'], false, 32, 1],
            ['MC33', 'Jika seseorang mengkritik saya karena melakukan kesalahan, saya akan segera menunjukkan kesalahannya.', 'bool', ['Tidak', 'Ya'], false, 33, 1],
            ['MC34', 'Akhir-akhir ini aku kehilangan kesabaran.', 'bool', ['Tidak', 'Ya'], false, 34, 1],
            ['MC35', 'Saya sering menyerah melakukan sesuatu karena saya takut saya tidak akan melakukannya dengan baik.', 'bool', ['Tidak', 'Ya'], false, 35, 1],
            ['MC36', 'Sering kali saya membiarkan diri saya terbawa emosi marah dan kemudian merasa sangat bersalah karenanya.', 'bool', ['Tidak', 'Ya'], false, 36, 1],
            ['MC37', 'Seringkali saya kehilangan kemampuan untuk merasakan sensasi di bagian tubuh saya.', 'bool', ['Tidak', 'Ya'], false, 37, 1],
            ['MC38', 'Saya melakukan apa yang saya inginkan tanpa mengkhawatirkan dampaknya terhadap orang lain.', 'bool', ['Tidak', 'Ya'], false, 38, 1],
            ['MC39', 'Mengonsumsi apa yang disebut &quot;obat-obatan terlarang&quot; mungkin merupakan hal yang sembrono, namun saya menyadari bahwa saya pernah membutuhkannya di masa lalu.', 'bool', ['Tidak', 'Ya'], false, 39, 1],
            ['MC40', 'Saya pikir saya adalah orang yang penakut dan terhambat.', 'bool', ['Tidak', 'Ya'], false, 40, 1],
            ['MC41', 'Saya secara impulsif telah melakukan banyak hal bodoh yang menyebabkan masalah besar bagi saya.', 'bool', ['Tidak', 'Ya'], false, 41, 1],
            ['MC42', 'Saya tidak pernah memaafkan penghinaan atau melupakan situasi memalukan yang disebabkan oleh seseorang kepada saya.', 'bool', ['Tidak', 'Ya'], false, 42, 1],
            ['MC43', 'Saya sering merasa sedih atau tegang segera setelah sesuatu yang baik terjadi pada saya.', 'bool', ['Tidak', 'Ya'], false, 43, 1],
            ['MC44', 'Sekarang saya sering merasa sangat tertekan dan sedih.', 'bool', ['Tidak', 'Ya'], false, 44, 1],
            ['MC45', 'Saya selalu melakukan yang terbaik untuk menyenangkan orang lain, bahkan mereka yang tidak saya sukai.', 'bool', ['Tidak', 'Ya'], false, 45, 1],
            ['MC46', 'Saya selalu kurang tertarik pada seks dibandingkan kebanyakan orang.', 'bool', ['Tidak', 'Ya'], false, 46, 1],
            ['MC47', 'Saya selalu cenderung menyalahkan diri sendiri ketika terjadi kesalahan.', 'bool', ['Tidak', 'Ya'], false, 47, 1],
            ['MC48', 'Saya sudah lama memutuskan bahwa yang terbaik adalah tidak berhubungan dengan orang lain.', 'bool', ['Tidak', 'Ya'], false, 48, 1],
            ['MC49', 'Sejak saya masih kecil, saya selalu harus berhati-hati terhadap orang yang mencoba menipu saya.', 'bool', ['Tidak', 'Ya'], false, 49, 1],
            ['MC50', 'Saya tidak tahan dengan orang-orang berpengaruh yang selalu berpikir mereka bisa melakukan sesuatu lebih baik dari saya.', 'bool', ['Tidak', 'Ya'], false, 50, 1],
            ['MC51', 'Ketika keadaan sedang membosankan, saya suka memprovokasi sesuatu yang menarik atau menyenangkan.', 'bool', ['Tidak', 'Ya'], false, 51, 1],
            ['MC52', 'Saya mempunyai masalah alkohol yang menimbulkan kesulitan bagi keluarga saya dan saya.', 'bool', ['Tidak', 'Ya'], false, 52, 1],
            ['MC53', 'Hukuman tidak pernah menghentikan saya melakukan apa yang saya inginkan.', 'bool', ['Tidak', 'Ya'], false, 53, 1],
            ['MC54', 'Seringkali saya merasa sangat bahagia dan bersemangat tanpa alasan.', 'bool', ['Tidak', 'Ya'], false, 54, 1],
            ['MC55', 'Beberapa minggu terakhir ini saya merasa kelelahan tanpa sebab tertentu.', 'bool', ['Tidak', 'Ya'], false, 55, 1],
            ['MC56', 'Akhir-akhir ini saya merasa sangat bersalah karena saya tidak lagi mampu melakukan sesuatu dengan benar.', 'bool', ['Tidak', 'Ya'], false, 56, 1],
            ['MC57', 'Menurutku, aku adalah orang yang mudah bergaul dan ekstrover.', 'bool', ['Tidak', 'Ya'], false, 57, 1],
            ['MC58', 'Saya menjadi sangat gugup dalam beberapa minggu terakhir.', 'bool', ['Tidak', 'Ya'], false, 58, 1],
            ['MC59', 'Saya mengontrol uang saya dengan sangat baik untuk bersiap jika diperlukan.', 'bool', ['Tidak', 'Ya'], false, 59, 1],
            ['MC60', 'Saya hanya belum seberuntung orang lain dalam hidup.', 'bool', ['Tidak', 'Ya'], false, 60, 1],
            ['MC61', 'Beberapa ide terlintas di kepala saya berulang kali dan tidak hilang.', 'bool', ['Tidak', 'Ya'], false, 61, 1],
            ['MC62', 'Selama satu atau dua tahun, memikirkan kehidupan, saya merasa sangat sedih dan putus asa.', 'bool', ['Tidak', 'Ya'], false, 62, 1],
            ['MC63', 'Banyak orang telah memata-matai kehidupan pribadi saya selama bertahun-tahun.', 'bool', ['Tidak', 'Ya'], false, 63, 1],
            ['MC64', 'Aku tidak tahu kenapa tapi terkadang aku mengatakan hal-hal yang kejam hanya untuk membuat orang lain menderita.', 'bool', ['Tidak', 'Ya'], false, 64, 1],
            ['MC65', 'Dalam setahun terakhir saya telah melintasi Atlantik dengan pesawat sebanyak 30 kali.', 'bool', ['Tidak', 'Ya'], false, 65, 1],
            ['MC66', 'Dulu, kebiasaan menyalahgunakan narkoba menyebabkan saya tidak masuk kerja.', 'bool', ['Tidak', 'Ya'], false, 66, 1],
            ['MC67', 'Saya mempunyai banyak ide yang maju untuk masa sekarang.', 'bool', ['Tidak', 'Ya'], false, 67, 1],
            ['MC68', 'Akhir-akhir ini aku harus memikirkan banyak hal berulang kali tanpa alasan.', 'bool', ['Tidak', 'Ya'], false, 68, 1],
            ['MC69', 'Saya menghindari sebagian besar situasi sosial karena saya pikir orang-orang akan mengkritik atau menolak saya.', 'bool', ['Tidak', 'Ya'], false, 69, 1],
            ['MC70', 'Seringkali saya berpikir bahwa saya tidak pantas menerima hal-hal baik yang terjadi pada saya.', 'bool', ['Tidak', 'Ya'], false, 70, 1],
            ['MC71', 'Ketika saya sendirian, saya sering merasakan di dekat saya kehadiran kuat seseorang yang tidak dapat dilihat.', 'bool', ['Tidak', 'Ya'], false, 71, 1],
            ['MC72', 'Saya merasa bingung, tanpa tujuan, dan saya tidak tahu ke mana tujuan hidup saya.', 'bool', ['Tidak', 'Ya'], false, 72, 1],
            ['MC73', 'Saya sering membiarkan orang lain membuat keputusan penting bagi saya.', 'bool', ['Tidak', 'Ya'], false, 73, 1],
            ['MC74', 'Saya tidak bisa tidur, dan saya bangun dalam keadaan lelah seperti saat saya hendak tidur.', 'bool', ['Tidak', 'Ya'], false, 74, 1],
            ['MC75', 'Akhir-akhir ini aku banyak berkeringat dan merasa sangat tegang.', 'bool', ['Tidak', 'Ya'], false, 75, 1],
            ['MC76', 'Saya memiliki pikiran aneh berulang kali yang saya harap dapat saya singkirkan.', 'bool', ['Tidak', 'Ya'], false, 76, 1],
            ['MC77', 'Saya kesulitan mengendalikan keinginan untuk minum berlebihan.', 'bool', ['Tidak', 'Ya'], false, 77, 1],
            ['MC78', 'Meskipun aku bangun, sepertinya aku tidak memperhatikan orang-orang di sekitarku.', 'bool', ['Tidak', 'Ya'], false, 78, 1],
            ['MC79', 'Saya sering merasa kesal dan suasana hati saya buruk.', 'bool', ['Tidak', 'Ya'], false, 79, 1],
            ['MC80', 'Sangat mudah bagi saya untuk mendapatkan banyak teman.', 'bool', ['Tidak', 'Ya'], false, 80, 1],
            ['MC81', 'Saya malu atas beberapa pelecehan yang saya derita ketika saya masih muda.', 'bool', ['Tidak', 'Ya'], false, 81, 1],
            ['MC82', 'Saya selalu memastikan pekerjaan saya terencana dan terorganisir dengan baik.', 'bool', ['Tidak', 'Ya'], false, 82, 1],
            ['MC83', 'Suasana hati saya banyak berubah dari hari ke hari.', 'bool', ['Tidak', 'Ya'], false, 83, 1],
            ['MC84', 'Saya kurang percaya diri untuk mengambil risiko mencoba sesuatu yang baru.', 'bool', ['Tidak', 'Ya'], false, 84, 1],
            ['MC85', 'Saya tidak menyalahkan seseorang yang mengambil keuntungan dari seseorang yang mengizinkannya.', 'bool', ['Tidak', 'Ya'], false, 85, 1],
            ['MC86', 'Untuk beberapa waktu sekarang saya merasa sedih dan tertekan dan saya tidak dapat menghibur diri.', 'bool', ['Tidak', 'Ya'], false, 86, 1],
            ['MC87', 'Saya sering marah pada orang yang melakukan sesuatu dengan lambat.', 'bool', ['Tidak', 'Ya'], false, 87, 1],
            ['MC88', 'Ketika saya berada di sebuah pesta, saya tidak pernah mengisolasi diri dari orang lain.', 'bool', ['Tidak', 'Ya'], false, 88, 1],
            ['MC89', 'Saya memperhatikan keluarga saya dengan cermat untuk mengetahui siapa yang bisa dipercaya dan siapa yang tidak.', 'bool', ['Tidak', 'Ya'], false, 89, 1],
            ['MC90', 'Terkadang saya merasa bingung dan kesal ketika orang-orang bersikap baik kepada saya.', 'bool', ['Tidak', 'Ya'], false, 90, 1],
            ['MC91', 'Penggunaan &quot;obat-obatan terlarang&quot; telah menyebabkan saya bertengkar dengan keluarga.', 'bool', ['Tidak', 'Ya'], false, 91, 1],
            ['MC92', 'Saya sering sendirian dan saya lebih suka seperti itu.', 'bool', ['Tidak', 'Ya'], false, 92, 1],
            ['MC93', 'Beberapa anggota keluargaku mengatakan bahwa aku egois dan hanya memikirkan diriku sendiri.', 'bool', ['Tidak', 'Ya'], false, 93, 1],
            ['MC94', 'Orang dapat dengan mudah mengubah pikiran saya, bahkan ketika saya pikir saya sudah mengambil keputusan.', 'bool', ['Tidak', 'Ya'], false, 94, 1],
            ['MC95', 'Saya sering membuat orang kesal ketika saya memberi mereka perintah,', 'bool', ['Tidak', 'Ya'], false, 95, 1],
            ['MC96', 'Dulu orang bilang aku terlalu tertarik dan bergairah pada banyak hal.', 'bool', ['Tidak', 'Ya'], false, 96, 1],
            ['MC97', 'Saya setuju dengan pepatah: &quot;Tuhan membantu burung yang bangun pagi.&quot;', 'bool', ['Tidak', 'Ya'], false, 97, 1],
            ['MC98', 'Perasaan saya terhadap orang-orang penting dalam hidup saya seringkali berkisar antara cinta dan benci.', 'bool', ['Tidak', 'Ya'], false, 98, 1],
            ['MC99', 'Kalau saya sedang arisan, berkelompok, saya hampir selalu merasa tegang dan minder.', 'bool', ['Tidak', 'Ya'], false, 99, 1],
            ['MC100', 'Saya kira saya tidak berbeda dengan orang tua saya karena, sampai batas tertentu, saya telah menjadi seorang pecandu alkohol.', 'bool', ['Tidak', 'Ya'], false, 100, 1],
            ['MC101', 'Saya rasa saya tidak mengambil banyak tanggung jawab keluarga saya dengan serius sebagaimana seharusnya.', 'bool', ['Tidak', 'Ya'], false, 101, 1],
            ['MC102', 'Sejak saya masih kecil saya telah kehilangan kontak dengan kenyataan.', 'bool', ['Tidak', 'Ya'], false, 102, 1],
            ['MC103', 'Orang-orang picik sering mencoba mengambil keuntungan dari apa yang telah saya lakukan atau rencanakan.', 'bool', ['Tidak', 'Ya'], false, 103, 1],
            ['MC104', 'Saya tidak dapat merasakan banyak kesenangan karena saya rasa saya tidak pantas mendapatkannya.', 'bool', ['Tidak', 'Ya'], false, 104, 1],
            ['MC105', 'Saya memiliki sedikit keinginan untuk berteman dekat.', 'bool', ['Tidak', 'Ya'], false, 105, 1],
            ['MC106', 'Saya telah mengalami banyak periode dalam hidup saya ketika saya begitu ceria dan menghabiskan begitu banyak energi sehingga saya merasa sangat lemah setelahnya.', 'bool', ['Tidak', 'Ya'], false, 106, 1],
            ['MC107', 'Saya benar-benar kehilangan nafsu makan dan hampir setiap malam saya sulit tidur.', 'bool', ['Tidak', 'Ya'], false, 107, 1],
            ['MC108', 'Saya sangat khawatir ditinggal sendirian dan harus mengurus diri sendiri.', 'bool', ['Tidak', 'Ya'], false, 108, 1],
            ['MC109', 'Kenangan akan pengalaman yang sangat mengganggu dari masa laluku terus muncul di pikiranku.', 'bool', ['Tidak', 'Ya'], false, 109, 1],
            ['MC110', 'Tahun lalu saya muncul di sampul beberapa majalah.', 'bool', ['Tidak', 'Ya'], false, 110, 1],
            ['MC111', 'Sepertinya saya sudah kehilangan minat pada banyak hal yang dulunya saya anggap menyenangkan, seperti seks.', 'bool', ['Tidak', 'Ya'], false, 111, 1],
            ['MC112', 'Saya telah mengalami banyak kesedihan dan kesedihan dalam hidup saya sejak saya masih sangat muda.', 'bool', ['Tidak', 'Ya'], false, 112, 1],
            ['MC113', 'Saya mendapat masalah dengan hukum beberapa kali.', 'bool', ['Tidak', 'Ya'], false, 113, 1],
            ['MC114', 'Cara yang baik untuk menghindari kesalahan adalah dengan memiliki rutinitas dalam melakukan sesuatu.', 'bool', ['Tidak', 'Ya'], false, 114, 1],
            ['MC115', 'Orang lain sering menyalahkan saya atas hal-hal yang tidak saya lakukan.', 'bool', ['Tidak', 'Ya'], false, 115, 1],
            ['MC116', 'Saya harus bekerja sangat keras pada beberapa orang agar mereka tetap sejalan.', 'bool', ['Tidak', 'Ya'], false, 116, 1],
            ['MC117', 'Orang-orang mengira terkadang saya membicarakan hal-hal yang aneh atau berbeda dari mereka.', 'bool', ['Tidak', 'Ya'], false, 117, 1],
            ['MC118', 'Ada kalanya saya tidak bisa menjalani hari tanpa mengonsumsi narkoba.', 'bool', ['Tidak', 'Ya'], false, 118, 1],
            ['MC119', 'Orang-orang mencoba membuatku percaya bahwa aku gila.', 'bool', ['Tidak', 'Ya'], false, 119, 1],
            ['MC120', 'Saya akan melakukan sesuatu dengan putus asa untuk mencegah seseorang yang saya cintai meninggalkan saya.', 'bool', ['Tidak', 'Ya'], false, 120, 1],
            ['MC121', 'Saya masih makan berlebihan beberapa kali seminggu.', 'bool', ['Tidak', 'Ya'], false, 121, 1],
            ['MC122', 'Sepertinya saya menyia-nyiakan peluang bagus yang datang kepada saya.', 'bool', ['Tidak', 'Ya'], false, 122, 1],
            ['MC123', 'Saya selalu merasa sulit untuk berhenti merasa tertekan dan sedih.', 'bool', ['Tidak', 'Ya'], false, 123, 1],
            ['MC124', 'Saat saya sendirian dan jauh dari rumah, saya sering mulai merasa tegang dan panik.', 'bool', ['Tidak', 'Ya'], false, 124, 1],
            ['MC125', 'Kadang-kadang orang merasa kesal kepada saya karena mereka mengatakan saya berbicara terlalu banyak atau terlalu cepat bagi mereka.', 'bool', ['Tidak', 'Ya'], false, 125, 1],
            ['MC126', 'Saat ini, sebagian besar orang sukses adalah orang yang beruntung atau tidak jujur.', 'bool', ['Tidak', 'Ya'], false, 126, 1],
            ['MC127', 'Saya tidak mau terlibat dengan orang lain kecuali saya yakin mereka akan menyukai saya.', 'bool', ['Tidak', 'Ya'], false, 127, 1],
            ['MC128', 'Saya merasa sangat tertekan tanpa alasan yang dapat saya pikirkan.', 'bool', ['Tidak', 'Ya'], false, 128, 1],
            ['MC129', 'Bertahun-tahun kemudian, saya masih mengalami mimpi buruk tentang suatu peristiwa yang merupakan ancaman nyata bagi hidup saya.', 'bool', ['Tidak', 'Ya'], false, 129, 1],
            ['MC130', 'Saya tidak lagi memiliki energi untuk fokus pada tanggung jawab sehari-hari.', 'bool', ['Tidak', 'Ya'], false, 130, 1],
            ['MC131', 'Minum alkohol membantu saya ketika saya merasa depresi.', 'bool', ['Tidak', 'Ya'], false, 131, 1],
            ['MC132', 'Saya benci memikirkan beberapa pelecehan yang saya alami saat masih kecil.', 'bool', ['Tidak', 'Ya'], false, 132, 1],
            ['MC133', 'Bahkan di saat-saat yang baik, saya selalu takut bahwa segala sesuatunya akan menjadi buruk.', 'bool', ['Tidak', 'Ya'], false, 133, 1],
            ['MC134', 'Kadang-kadang ketika ada yang tidak beres dalam hidup saya, saya merasa seperti gila atau kehilangan kontak.', 'bool', ['Tidak', 'Ya'], false, 134, 1],
            ['MC135', 'Sendirian, tanpa bantuan orang terdekat, sungguh membuatku takut.', 'bool', ['Tidak', 'Ya'], false, 135, 1],
            ['MC136', 'Saya tahu bahwa saya telah menghabiskan lebih banyak uang daripada yang seharusnya untuk membeli &quot;obat-obatan terlarang&quot;.', 'bool', ['Tidak', 'Ya'], false, 136, 1],
            ['MC137', 'Saya selalu memeriksa apakah saya telah menyelesaikan pekerjaan saya sebelum beristirahat untuk kegiatan senggang.', 'bool', ['Tidak', 'Ya'], false, 137, 1],
            ['MC138', 'Saya memperhatikan bahwa orang-orang membicarakan saya ketika saya berjalan melewati mereka.', 'bool', ['Tidak', 'Ya'], false, 138, 1],
            ['MC139', 'Saya sangat pandai membuat alasan ketika saya mendapat masalah.', 'bool', ['Tidak', 'Ya'], false, 139, 1],
            ['MC140', 'Saya pikir ada konspirasi melawan saya.', 'bool', ['Tidak', 'Ya'], false, 140, 1],
            ['MC141', 'Saya merasa kebanyakan orang memandang rendah saya.', 'bool', ['Tidak', 'Ya'], false, 141, 1],
            ['MC142', 'Aku sering merasa tidak ada apa-apa di dalam diriku, seolah-olah aku kosong dan hampa.', 'bool', ['Tidak', 'Ya'], false, 142, 1],
            ['MC143', 'Terkadang saya memaksakan diri untuk muntah setelah makan.', 'bool', ['Tidak', 'Ya'], false, 143, 1],
            ['MC144', 'Saya pikir saya berusaha sangat keras untuk membuat orang lain mengagumi hal-hal yang saya lakukan atau katakan.', 'bool', ['Tidak', 'Ya'], false, 144, 1],
            ['MC145', 'Saya menghabiskan hidup saya dengan mengkhawatirkan satu atau lain hal.', 'bool', ['Tidak', 'Ya'], false, 145, 1],
            ['MC146', 'Saya selalu bertanya-tanya apa alasan sebenarnya seseorang bersikap baik kepada saya.', 'bool', ['Tidak', 'Ya'], false, 146, 1],
            ['MC147', 'Pikiran tertentu muncul kembali di benak saya berulang kali.', 'bool', ['Tidak', 'Ya'], false, 147, 1],
            ['MC148', 'Hanya sedikit hal dalam hidup yang memberi saya kesenangan.', 'bool', ['Tidak', 'Ya'], false, 148, 1],
            ['MC149', 'Saya merasa gemetar dan sulit tidur karena kenangan menyakitkan dari peristiwa masa lalu terus menerus terlintas di kepala saya.', 'bool', ['Tidak', 'Ya'], false, 149, 1],
            ['MC150', 'Memikirkan masa depan di awal setiap hari membuatku merasa sangat tertekan.', 'bool', ['Tidak', 'Ya'], false, 150, 1],
            ['MC151', 'Saya tidak pernah bisa menghilangkan perasaan tidak berharga di mata orang lain.', 'bool', ['Tidak', 'Ya'], false, 151, 1],
            ['MC152', 'Saya mempunyai masalah minum yang sudah saya coba atasi namun tidak berhasil.', 'bool', ['Tidak', 'Ya'], false, 152, 1],
            ['MC153', 'Seseorang telah mencoba mengendalikan pikiranku.', 'bool', ['Tidak', 'Ya'], false, 153, 1],
            ['MC154', 'Saya telah mencoba bunuh diri.', 'bool', ['Tidak', 'Ya'], false, 154, 1],
            ['MC155', 'Saya rela membuat diri saya kelaparan agar menjadi lebih kurus dari saya.', 'bool', ['Tidak', 'Ya'], false, 155, 1],
            ['MC156', 'Saya tidak mengerti mengapa beberapa orang tersenyum kepada saya.', 'bool', ['Tidak', 'Ya'], false, 156, 1],
            ['MC157', 'Saya belum pernah melihat mobil dalam sepuluh tahun terakhir.', 'bool', ['Tidak', 'Ya'], false, 157, 1],
            ['MC158', 'Saya menjadi sangat tegang dengan orang-orang yang tidak saya kenal dengan baik, karena mereka mungkin ingin menyakiti saya.', 'bool', ['Tidak', 'Ya'], false, 158, 1],
            ['MC159', 'Seseorang harus sangat luar biasa untuk memahami kemampuan khusus saya.', 'bool', ['Tidak', 'Ya'], false, 159, 1],
            ['MC160', 'Kehidupan saya saat ini masih dipengaruhi oleh \'gambaran mental\' dari sesuatu yang buruk yang terjadi pada saya.', 'bool', ['Tidak', 'Ya'], false, 160, 1],
            ['MC161', 'Sepertinya saya menciptakan situasi dengan orang lain di mana saya akhirnya terluka atau merasa ditolak.', 'bool', ['Tidak', 'Ya'], false, 161, 1],
            ['MC162', 'Saya sering tersesat dalam pikiran saya dan melupakan apa yang terjadi di sekitar saya.', 'bool', ['Tidak', 'Ya'], false, 162, 1],
            ['MC163', 'Orang bilang aku kurus, tapi menurutku paha dan pantatku terlalu besar.', 'bool', ['Tidak', 'Ya'], false, 163, 1],
            ['MC164', 'Ada peristiwa mengerikan dari masa laluku yang berulang kali menghantuiku dalam pikiran dan mimpiku.', 'bool', ['Tidak', 'Ya'], false, 164, 1],
            ['MC165', 'Aku tidak punya teman dekat selain keluargaku.', 'bool', ['Tidak', 'Ya'], false, 165, 1],
            ['MC166', 'Saya hampir selalu bertindak cepat dan tidak memikirkan segala sesuatunya sebagaimana mestinya.', 'bool', ['Tidak', 'Ya'], false, 166, 1],
            ['MC167', 'Saya sangat berhati-hati untuk menjaga kehidupan pribadi saya sehingga tidak ada yang dapat mengambil keuntungan dari saya.', 'bool', ['Tidak', 'Ya'], false, 167, 1],
            ['MC168', 'Seringkali saya mendengar sesuatu dengan sangat jelas sehingga mengganggu saya.', 'bool', ['Tidak', 'Ya'], false, 168, 1],
            ['MC169', 'Saya selalu bersedia menyerah pada pertengkaran atau perselisihan karena saya takut akan kemarahan atau penolakan orang lain.', 'bool', ['Tidak', 'Ya'], false, 169, 1],
            ['MC170', 'Saya mengulangi perilaku tertentu berulang kali, terkadang untuk mengurangi kecemasan saya dan di lain waktu untuk mencegah terjadinya hal buruk.', 'bool', ['Tidak', 'Ya'], false, 170, 1],
            ['MC171', 'Baru-baru ini saya berpikir dengan sangat serius untuk menyingkir.', 'bool', ['Tidak', 'Ya'], false, 171, 1],
            ['MC172', 'Orang-orang mengatakan kepada saya bahwa saya adalah orang yang sangat formal dan bermoral.', 'bool', ['Tidak', 'Ya'], false, 172, 1],
            ['MC173', 'Saya masih merasa takut ketika memikirkan pengalaman traumatis yang saya alami bertahun-tahun yang lalu.', 'bool', ['Tidak', 'Ya'], false, 173, 1],
            ['MC174', 'Meski aku takut berteman, aku ingin mempunyai lebih dari apa yang aku punya.', 'bool', ['Tidak', 'Ya'], false, 174, 1],
            ['MC175', 'Beberapa orang yang seharusnya menjadi temanku malah ingin menyakitiku.', 'bool', ['Tidak', 'Ya'], false, 175, 1],

        ];
        $this->upsertItems('mcmi3', $items);
    }
}
