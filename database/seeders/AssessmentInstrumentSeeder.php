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

    public function run(): void
    {
        $this->who5();
        $this->phq9();
        $this->gad7();
        $this->cbi();
    }

    private function upsertInstrument(array $def): AssessmentInstrument
    {
        return AssessmentInstrument::updateOrCreate(['code' => $def['code']], $def);
    }

    /** [code, text, type, options, reverse, ord, weight] */
    private function upsertItems(string $instrumentCode, array $items): void
    {
        foreach ($items as [$code, $text, $type, $options, $reverse, $ord, $weight]) {
            ScreeningQuestion::updateOrCreate(
                ['vertical' => 'perusahaan', 'code' => $code],
                [
                    'section' => 'mental_health',
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
}
