<?php

namespace Database\Seeders;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Database\Seeder;

/**
 * Demo padat untuk dashboard HR — chart penuh & bagus buat presentasi.
 * Jalan setelah PerusahaanSeeder. Idempotent per perusahaan (skip kalau sudah padat).
 * Hanya untuk environment lokal/demo.
 */
class PerusahaanDashboardDemoSeeder extends Seeder
{
    private array $topics = ['Stress', 'Kecemasan', 'Karir', 'Keuangan', 'Work-life balance', 'Relasi', 'Keluarga', 'Burnout'];
    private array $types = ['psikolog', 'psikolog', 'psikolog', 'nutrisionis', 'karir', 'fisioterapis'];

    public function run(): void
    {
        foreach (Perusahaan::where('is_active', true)->get() as $p) {
            $employees = $p->employees()->whereNotNull('consent_at')->get();
            if (!$employees->count()) {
                continue;
            }

            // Skip kalau sudah padat (rerun aman).
            if (PerusahaanSession::where('perusahaan_id', $p->id)->count() > 200) {
                continue;
            }

            // Sesi done 6 bulan ke belakang — tren naik biar chartMonthly bagus.
            // Volume per bulan: makin baru makin banyak.
            $monthlyVolume = [14, 18, 22, 26, 32, 38];
            foreach ($monthlyVolume as $monthsAgo => $target) {
                $this->seedMonth($p, $employees, 5 - $monthsAgo, $target);
            }

            // Sesi terjadwal 14 hari ke depan — isi agenda.
            foreach (range(1, 14) as $d) {
                $day = now()->addDays($d);
                if ($day->isWeekend()) {
                    continue;
                }
                $perDay = rand(1, 4);
                for ($i = 0; $i < $perDay; $i++) {
                    $emp = $employees->random();
                    PerusahaanSession::create([
                        'perusahaan_id' => $p->id,
                        'employee_id' => $emp->id,
                        'departement_id' => $emp->departement_id,
                        'konsultan_type' => $this->types[array_rand($this->types)],
                        'topic_category' => null,
                        'status' => 'scheduled',
                        'scheduled_at' => $day->copy()->setTime(rand(9, 16), [0, 30][rand(0, 1)]),
                    ]);
                }
            }

            // Asesmen WHO-5 — pastikan ≥80% karyawan punya, spread band realistis.
            foreach ($employees as $emp) {
                $has = $emp->assessments()->count();
                $want = $emp->id % 5 === 0 ? 3 : 2; // sebagian 3x biar trend chart employee bagus
                for ($r = $has; $r < $want; $r++) {
                    // Distribusi: 55% sehat, 30% perlu perhatian, 15% berisiko.
                    $roll = rand(1, 100);
                    $base = $roll <= 55 ? rand(19, 25) : ($roll <= 85 ? rand(13, 18) : rand(4, 12));
                    $answers = $this->spreadAnswers($base);
                    PerusahaanAssessment::create([
                        'perusahaan_id' => $p->id,
                        'employee_id' => $emp->id,
                        'departement_id' => $emp->departement_id,
                        'answers' => $answers,
                        'score' => array_sum($answers) * 4,
                        'taken_at' => now()->subDays(rand(3, 85))->setTime(rand(8, 17), 0),
                    ]);
                }
            }

            $p->nps_score = round(rand(38, 46) / 10, 1);
            $p->save();
        }
    }

    /** Sesi done + no_show untuk satu bulan (offset dari sekarang). */
    private function seedMonth(Perusahaan $p, $employees, int $monthsAgo, int $target): void
    {
        $start = now()->subMonths($monthsAgo)->startOfMonth();
        $end = $monthsAgo === 0 ? now() : $start->copy()->endOfMonth();

        for ($i = 0; $i < $target; $i++) {
            $emp = $employees->random();
            $scheduled = $start->copy()->addDays(rand(0, max(0, $start->diffInDays($end) - 1)))->setTime(rand(9, 16), [0, 30][rand(0, 1)]);
            if ($scheduled->gt($end)) {
                $scheduled = $end->copy()->setTime(15, 0);
            }
            $roll = rand(1, 100);
            $status = $roll <= 88 ? 'done' : 'no_show';

            PerusahaanSession::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'konsultan_type' => $this->types[array_rand($this->types)],
                'topic_category' => $status === 'done' ? $this->topics[array_rand($this->topics)] : null,
                'status' => $status,
                // Rating: mayoritas 4-5, sedikit 3, jarang 1-2.
                'rating' => $status === 'done' ? collect([5, 5, 5, 4, 4, 4, 4, 3, 2])->random() : null,
                'completed_at' => $status === 'done' ? $scheduled->copy()->addHour() : null,
                'scheduled_at' => $scheduled,
            ]);
        }
    }

    /** Pecah total mentah (0-25) jadi 5 jawaban 0-5. */
    private function spreadAnswers(int $total): array
    {
        $answers = [0, 0, 0, 0, 0];
        for ($i = 0; $i < $total; $i++) {
            $answers[array_rand($answers)]++;
        }
        return array_map(fn ($a) => min(5, $a), $answers);
    }
}
