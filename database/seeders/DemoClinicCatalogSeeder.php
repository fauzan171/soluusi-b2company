<?php

namespace Database\Seeders;

use App\Services\Klinik\MarkupService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoClinicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $jakartaSelatan = 3171;

        $klinik = DB::table('kliniks')
            ->where('name', 'Klinik Demo Customer')
            ->orWhere('brand', 'Klinik Demo Customer')
            ->first();

        if (!$klinik) {
            $klinik = DB::table('kliniks')->where('is_active', 1)->orderBy('id')->first();
        }

        if (!$klinik) {
            $this->command?->warn('Tidak ada klinik aktif untuk diisi katalog demo.');
            return;
        }

        if (empty($klinik->regency_id)) {
            DB::table('kliniks')->where('id', $klinik->id)->update([
                'regency_id' => $jakartaSelatan,
                'updated_at' => $now,
            ]);
        }

        foreach (['admin', 'consultant', 'customer', 'klinik_admin'] as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');

        $categories = [
            'psikolog-klinis' => 'Psikolog Klinis',
            'psikolog-anak' => 'Psikolog Anak & Remaja',
            'konselor-keluarga' => 'Konselor Keluarga',
            'ahli-gizi' => 'Ahli Gizi',
            'fisioterapis' => 'Fisioterapis',
        ];

        foreach ($categories as $code => $name) {
            DB::table('categories')->updateOrInsert(
                ['code_category' => $code],
                [
                    'name' => $name,
                    'vertical' => 'klinik',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $categoryIds = DB::table('categories')
            ->whereIn('code_category', array_keys($categories))
            ->pluck('id', 'code_category');

        $consultants = [
            [
                'email' => 'demo.catalog.maya@klinik-demo.local',
                'first' => 'Maya',
                'last' => 'Kusuma, M.Psi.',
                'category' => 'psikolog-klinis',
                'about' => 'Psikolog klinis untuk kecemasan, depresi ringan, burnout, dan penyesuaian hidup. Cocok untuk pasien dewasa yang butuh sesi terstruktur dan praktis.',
                'skills' => ['Anxiety', 'Burnout', 'CBT', 'Stress Management'],
                'prices' => [275000, 375000],
                'regency' => 3171,
            ],
            [
                'email' => 'demo.catalog.arief@klinik-demo.local',
                'first' => 'Arief',
                'last' => 'Rahman, M.Psi.',
                'category' => 'psikolog-anak',
                'about' => 'Fokus pada anak dan remaja: regulasi emosi, bullying, tekanan akademik, dan komunikasi orang tua-anak.',
                'skills' => ['Child Psychology', 'Parenting', 'Assessment'],
                'prices' => [300000, 425000],
                'regency' => 3171,
            ],
            [
                'email' => 'demo.catalog.bimo@klinik-demo.local',
                'first' => 'Bimo',
                'last' => 'Aditya, M.Psi.',
                'category' => 'konselor-keluarga',
                'about' => 'Konselor keluarga dan relasi pasangan untuk isu komunikasi, konflik rumah tangga, dan perencanaan sesi lanjutan.',
                'skills' => ['Family Counseling', 'Relationship', 'Emotional Regulation'],
                'prices' => [260000, 360000],
                'regency' => 3171,
            ],
            [
                'email' => 'demo.catalog.nadia@klinik-demo.local',
                'first' => 'Nadia',
                'last' => 'Putri, S.Gz, RD',
                'category' => 'ahli-gizi',
                'about' => 'Ahli gizi klinis untuk diabetes, obesitas, nutrisi ibu, dan meal plan yang realistis mengikuti kebiasaan pasien.',
                'skills' => ['Diabetes Care', 'Meal Planning', 'Weight Management'],
                'prices' => [220000, 320000],
                'regency' => 3172,
            ],
            [
                'email' => 'demo.catalog.fajar@klinik-demo.local',
                'first' => 'Fajar',
                'last' => 'Nugroho, S.Ft.',
                'category' => 'fisioterapis',
                'about' => 'Fisioterapis untuk nyeri punggung, cedera olahraga, pemulihan pasca operasi, dan latihan fungsional bertahap.',
                'skills' => ['Back Pain', 'Sports Injury', 'Rehab Program'],
                'prices' => [250000, 350000],
                'regency' => 3173,
            ],
            [
                'email' => 'demo.catalog.laras@klinik-demo.local',
                'first' => 'Laras',
                'last' => 'Wulandari, M.Psi.',
                'category' => 'psikolog-anak',
                'about' => 'Psikolog pendidikan untuk kesiapan sekolah, kesulitan belajar, konseling karier pelajar, dan asesmen kebutuhan belajar.',
                'skills' => ['Learning Assessment', 'Career Counseling', 'School Readiness'],
                'prices' => [280000, 390000],
                'regency' => 3174,
            ],
        ];

        foreach ($consultants as $index => $data) {
            $userId = DB::table('users')->where('email', $data['email'])->value('id');

            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'username' => 'demo-catalog-' . ($index + 1),
                    'email' => $data['email'],
                    'password' => bcrypt('password'),
                    'firstname' => $data['first'],
                    'lastname' => $data['last'],
                    'role_id' => $consultantRoleId,
                    'regency_id' => $data['regency'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'firstname' => $data['first'],
                    'lastname' => $data['last'],
                    'role_id' => $consultantRoleId,
                    'regency_id' => $data['regency'],
                    'is_active' => 1,
                    'updated_at' => $now,
                ]);
            }

            $consultantId = DB::table('consultants')->where('user_id', $userId)->value('id');

            if (!$consultantId) {
                $consultantId = DB::table('consultants')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'about' => $data['about'],
                    'profit_sharing' => 80,
                    'status' => 'accept',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('consultants')->where('id', $consultantId)->update([
                    'about' => $data['about'],
                    'profit_sharing' => 80,
                    'status' => 'accept',
                    'is_active' => 1,
                    'updated_at' => $now,
                ]);
            }

            DB::table('consultant_categories')->updateOrInsert(
                ['consultant_id' => $consultantId, 'category_id' => $categoryIds[$data['category']]],
                ['created_at' => $now, 'updated_at' => $now]
            );

            foreach ($data['skills'] as $skillName) {
                $skillId = DB::table('skills')->where('name', $skillName)->value('id');

                if (!$skillId) {
                    $skillId = DB::table('skills')->insertGetId([
                        'name' => $skillName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('consultant_skills')->updateOrInsert(
                    ['consultant_id' => $consultantId, 'skill_id' => $skillId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }

            $packages = [
                ['Konsultasi Online 60 Menit', 'online', 60, $data['prices'][0]],
                ['Konsultasi Online 90 Menit', 'online', 90, $data['prices'][1]],
                ['Konsultasi Tatap Muka 60 Menit', 'offline', 60, $data['prices'][0] + 50000],
                ['Konsultasi Tatap Muka 90 Menit', 'offline', 90, $data['prices'][1] + 50000],
            ];

            foreach ($packages as [$name, $mode, $duration, $price]) {
                DB::table('consultant_packages')->updateOrInsert(
                    [
                        'consultant_id' => $consultantId,
                        'name' => $name,
                        'mode' => $mode,
                        'duration' => $duration,
                    ],
                    [
                        'price' => $price,
                        'quota' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $packageId = DB::table('consultant_packages')
                    ->where('consultant_id', $consultantId)
                    ->where('name', $name)
                    ->where('mode', $mode)
                    ->where('duration', $duration)
                    ->value('id');

                DB::table('markup_rules')->updateOrInsert(
                    ['klinik_id' => $klinik->id, 'consultant_package_id' => $packageId],
                    [
                        'markup_amount' => min(35000, max(0, MarkupService::maxFinalFor((int) $price) - (int) $price)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $this->seedSchedules((int) $consultantId, (int) $klinik->id, $now->copy(), $index);
        }

        $this->command?->line('Demo clinic catalog seeded for klinik #' . $klinik->id . '.');
    }

    private function seedSchedules(int $consultantId, int $klinikId, Carbon $baseDate, int $offset): void
    {
        $slotPlan = [
            ['online', 9 + ($offset % 3)],
            ['online', 13 + ($offset % 3)],
            ['offline', 10 + ($offset % 4)],
            ['online', 15],
            ['offline', 16],
            ['online', 17],
        ];

        $created = 0;
        for ($day = 1; $day <= 12 && $created < count($slotPlan); $day++) {
            $date = $baseDate->copy()->addDays($day);

            if ($date->isWeekend()) {
                continue;
            }

            [$mode, $hour] = $slotPlan[$created];
            $start = $date->copy()->setTime($hour, 0);

            DB::table('schedules')->updateOrInsert(
                [
                    'consultant_id' => $consultantId,
                    'klinik_id' => $klinikId,
                    'started_at' => $start,
                    'mode' => $mode,
                ],
                [
                    'ended_at' => $start->copy()->addMinutes(60),
                    'status' => 'confirmed',
                    'created_by' => null,
                    'latest_updated_by' => 'demo-catalog',
                    'meeting_url' => null,
                    'location' => $mode === 'offline' ? 'Ruang Konsultasi Klinik Demo Customer' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $created++;
        }
    }
}
