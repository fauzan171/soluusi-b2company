<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data dummy untuk development B2B klinik: 1 klinik demo (Jakarta Selatan),
 * 2 admin, 8 konsultan klinik (profil, pendidikan, skill, sertifikat),
 * paket online+offline, jadwal confirmed 14 hari ke depan, markup rules.
 * Jalankan: php artisan db:seed --class=KlinikDemoSeeder
 */
class KlinikDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->where('email', 'admin.prima@soluusi.local')->exists()) {
            $this->command->line('KlinikDemoSeeder already ran, skipping...');
            return;
        }

        $now = now();
        $regencyJakartaSelatan = 3171;

        // 1. Klinik demo
        // 1a. Associate (HAP) owner klinik — dashboard komisi di /associate/dashboard
        $hapUserId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'username' => 'happrima' . rand(10, 99),
            'email' => 'hap.prima@soluusi.local',
            'password' => bcrypt('password'),
            'firstname' => 'Hana',
            'lastname' => 'Primadona',
            'role_id' => DB::table('roles')->where('name', 'associate')->value('id'),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 1b. Klinik demo (dimiliki associate di atas)
        $klinikId = DB::table('kliniks')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'PT Klinik Prima Sehat',
            'brand' => 'Klinik Prima Sehat',
            'associate_id' => $hapUserId,
            'regency_id' => $regencyJakartaSelatan,
            'total_rate' => 1000000,
            'is_active' => 1,
            'approved_at' => $now,
            'mou_at' => $now->copy()->subMonth(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 1c. HAP pervorma stages dummy (biar dashboard HAP langsung ada isi).
        // Amount mengikuti config: stage1 flat, stage2/stage3 = sisa rate dibagi 1/3 + 2/3.
        $cfg = config('klinik.hap');
        $rate = 1000000;
        $stage2 = (int) round(($rate - $cfg['stage1_flat']) / 3);
        $stage3 = $rate - $cfg['stage1_flat'] - $stage2;
        foreach ([
            [1, $cfg['stage1_flat'], 'due'],
            [2, $stage2, 'due'],
            [3, $stage3, 'paid'],
        ] as [$stage, $amount, $status]) {
            DB::table('hap_pervormas')->insert([
                'klinik_id' => $klinikId,
                'stage' => $stage,
                'associate_id' => $hapUserId,
                'amount' => $amount,
                'status' => $status,
                'due_at' => $now->copy()->addWeekdays(7),
                'paid_at' => $status === 'paid' ? $now->copy()->subDays(3) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Admin klinik (Partner Admin & Staff) — login password: password
        foreach ([
            ['admin.prima@soluusi.local', 'Ratna', 'Prima', 'admin'],
            ['staff.prima@soluusi.local', 'Dodi', 'Setiawan', 'staff'],
        ] as [$email, $first, $last, $role]) {
            $userId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'username' => Str::slug($first . $last) . rand(10, 99),
                'email' => $email,
                'password' => bcrypt('password'),
                'firstname' => $first,
                'lastname' => $last,
                'role_id' => DB::table('roles')->where('name', 'klinik_admin')->value('id'),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('klinik_admins')->insert([
                'klinik_id' => $klinikId,
                'user_id' => $userId,
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Konsultan pool (regency Jakarta & sekitar biar muncul di filter default)
        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');
        $catIds = DB::table('categories')->whereIn('vertical', ['klinik', 'both'])->pluck('id', 'code_category');

        $consultants = [
            ['Dr. Maya Kusuma, M.Psi., Psikolog', 'psikolog-klinis', 'Psikolog Klinis', 3171, 8,
                'Psikolog klinis dengan 8 tahun pengalaman menangani kecemasan, depresi, dan masalah keluarga. Pendekatan kognitif-perilaku dengan fokus pada solusi praktis yang bisa langsung diterapkan pasien.',
                ['Anxiety', 'Psychotherapy', 'Counseling'], 'S2 Profesi Psikolog Klinis', 2016,
                'Sertifikat Praktik Psikologi Klinis', 2020],
            ['Arief Rahman, S.Psi., M.Psi.', 'psikolog-klinis', 'Psikolog Klinis Anak & Remaja', 3171, 6,
                'Fokus pada tumbuh kembang anak dan remaja: bullying, tekanan akademik, dan regulasi emosi. Berpengalaman kolaborasi dengan sekolah dan klinik tumbuh kembang.',
                ['Counseling', 'Mental Health', 'Psychological Assessment'], 'S2 Psikologi Klinis Anak', 2018,
                'Certified Child Psychologist', 2021],
            ['Nadia Putri, S.Gz, RD', 'ahli-gizi', 'Nutrisionis Klinis', 3172, 5,
                'Nutrisionis registered dietitian untuk diabetes, obesitas, dan gizi olahraga. Menyusun meal plan realistis sesuai kebiasaan makan pasien, bukan diet ekstrem.',
                ['Mental Health', 'Counseling'], 'S1 Ilmu Gizi + RD', 2019,
                'Registered Dietitian Indonesia', 2022],
            ['Fajar Nugroho, S.Ft., Ftr.', 'fisioterapis', 'Fisioterapis Muskuloskeletal', 3173, 7,
                'Fisioterapis nyeri punggung, cedera olahraga, dan pasca-operasi. Kombinasi manual therapy dan exercise therapy dengan target fungsional yang terukur.',
                ['Assessment & Training'], 'Profesi Fisioterapi', 2017,
                'Certified Orthopedic Manual Therapy', 2021],
            ['Sari Indah, S.Tr.OT', 'terapis-okupasi', 'Terapis Okupasi', 3173, 4,
                'Terapis okupasi untuk anak dengan kebutuhan khusus dan pasien pasca-stroke. Fokus mengembalikan kemandirian aktivitas sehari-hari.',
                ['Counseling'], 'D4 Terapi Okupasi', 2020,
                'Pediatric Sensory Integration', 2023],
            ['Bimo Aditya, M.Psi., Psikolog', 'konselor', 'Konselor Keluarga & Pernikahan', 3171, 9,
                'Konselor dengan spesialisasi relasi pasangan dan komunikasi keluarga. 500+ jam sesi konseling, pendekatan sistemik dan emosional.',
                ['Counseling', 'Emotional'], 'S2 Psikologi Profesi', 2015,
                'Certified Marriage & Family Therapist', 2019],
            ['Laras Wulandari, S.Psi', 'psikolog-pendidikan', 'Psikolog Pendidikan', 3174, 6,
                'Psikolog pendidikan untuk kesulitan belajar, asesmen kesiapan sekolah, dan konseling karier pelajar. Berpengalaman di sekolah dan klinik tumbuh kembang.',
                ['Psychological Assessment', 'Counseling'], 'S2 Psikologi Pendidikan', 2018,
                'Asesor Psikologi Pendidikan', 2022],
            ['Doni Prasetyo, S.Gz', 'ahli-gizi', 'Nutrisionis Komunitas', 3175, 5,
                'Nutrisionis untuk edukasi gizi keluarga dan program wellness klinik. Berpengalaman menyusun program gizi komunitas dan corporate wellness.',
                ['Mental Health'], 'S1 Ilmu Gizi', 2020,
                'Nutrition for Wellness Program', 2023],
        ];

        $packageTemplates = [
            ['Konsultasi Online %d Menit', 'online', fn ($price) => $price],
            ['Konsultasi Tatap Muka %d Menit', 'offline', fn ($price) => $price + 50000],
        ];

        foreach ($consultants as $idx => [$name, $catCode, $specialty, $regency, $years, $about, $skills, $degree, $gradYear, $cert, $certYear]) {
            $parts = explode(' ', $name);
            $username = Str::slug($name) . rand(10, 99);
            $email = $username . '@soluusi-demo.local';

            $userId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'username' => $username,
                'email' => $email,
                'password' => bcrypt('password'),
                'firstname' => $parts[0],
                'lastname' => implode(' ', array_slice($parts, 1)),
                'role_id' => $consultantRoleId,
                'regency_id' => $regency,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $consultantId = DB::table('consultants')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'about' => $about,
                'status' => 'accept',
                'is_active' => 1,
                'profit_sharing' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($catId = $catIds->get($catCode)) {
                DB::table('consultant_categories')->insert([
                    'consultant_id' => $consultantId,
                    'category_id' => $catId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // skills (pakai yang sudah ada; bikin baru kalau belum)
            foreach ($skills as $skillName) {
                $skillId = DB::table('skills')->where('name', $skillName)->value('id')
                    ?: DB::table('skills')->insertGetId(['name' => $skillName, 'created_at' => $now, 'updated_at' => $now]);
                DB::table('consultant_skills')->insert([
                    'consultant_id' => $consultantId,
                    'skill_id' => $skillId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // pendidikan
            $educationId = DB::table('education')->where('name', 'Universitas Indonesia')->value('id')
                ?: DB::table('education')->insertGetId(['name' => 'Universitas Indonesia', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('consultant_education')->insert([
                'consultant_id' => $consultantId,
                'education_id' => $educationId,
                'degree' => $degree,
                'start_date' => ($gradYear - 2) . '-08-01',
                'end_date' => $gradYear . '-07-01',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // sertifikat
            DB::table('certificates')->insert([
                'consultant_id' => $consultantId,
                'name' => $cert,
                'number' => 'SRT-' . Str::upper(Str::random(8)),
                'year' => $certYear,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // net fee konsultan (flat, effective sekarang)
            DB::table('consultant_fees')->insert([
                'consultant_id' => $consultantId,
                'consultant_package_id' => null,
                'net_fee' => 200000,
                'effective_at' => $now->copy()->subYear(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // paket: online & offline, dua durasi
            foreach ([[60, 250000], [90, 350000]] as [$duration, $price]) {
                foreach ($packageTemplates as [$tpl, $mode, $fn]) {
                    $finalPrice = (int) $fn($price);
                    $packageId = DB::table('consultant_packages')->insertGetId([
                        'consultant_id' => $consultantId,
                        'name' => sprintf($tpl, $duration),
                        'price' => $finalPrice,
                        'duration' => $duration,
                        'quota' => 1,
                        'mode' => $mode,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // markup Soluusi untuk klinik demo (read-only di UI klinik)
                    DB::table('markup_rules')->updateOrInsert(
                        ['klinik_id' => $klinikId, 'consultant_package_id' => $packageId],
                        ['markup_amount' => 50000, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            // jadwal confirmed 14 hari ke depan: 4 slot online, 2 offline (skip weekend)
            $slotsOnline = [10, 13, 15, 17];
            $slotsOffline = [11, 16];
            $slotIdx = 0;
            for ($day = 1; $day <= 14 && ($slotIdx < count($slotsOnline) + count($slotsOffline)); $day++) {
                $date = $now->copy()->addDays($day);
                if ($date->isWeekend()) {
                    continue;
                }
                foreach ($slotsOnline as $hour) {
                    if ($slotIdx >= count($slotsOnline) + count($slotsOffline)) break;
                    $this->makeSchedule($consultantId, $klinikId, $date->copy()->setTime($hour, 0), 60, 'online');
                    $slotIdx++;
                }
                foreach ($slotsOffline as $hour) {
                    if ($slotIdx >= count($slotsOnline) + count($slotsOffline)) break;
                    $this->makeSchedule($consultantId, $klinikId, $date->copy()->setTime($hour, 0), 60, 'offline');
                    $slotIdx++;
                }
            }
        }

        $this->command->line('  Klinik demo: Klinik Prima Sehat (login admin.prima@soluusi.local / password)');
        $this->command->line('  ' . count($consultants) . ' konsultan + paket online/offline + jadwal + markup seeded');
    }

    protected function makeSchedule(int $consultantId, int $klinikId, $start, int $minutes, string $mode): void
    {
        DB::table('schedules')->insert([
            'consultant_id' => $consultantId,
            'klinik_id' => $klinikId,
            'created_by' => null,
            'latest_updated_by' => 'seeder',
            'started_at' => $start,
            'ended_at' => $start->copy()->addMinutes($minutes),
            'mode' => $mode,
            'status' => 'confirmed',
            'meeting_url' => $mode === 'online' ? 'https://meet.google.com/demo-' . Str::random(6) : null,
            'location' => $mode === 'offline' ? 'Ruang Konsultasi Klinik Prima Sehat' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
