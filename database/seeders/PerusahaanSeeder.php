<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data vertikal Perusahaan (CAP). Idempotent.
 * Password semua akun: "password".
 */
class PerusahaanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (['hr_admin', 'employee'] as $name) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['created_at' => $now, 'updated_at' => $now]);
        }
        $hrRoleId = DB::table('roles')->where('name', 'hr_admin')->value('id');
        $employeeRoleId = DB::table('roles')->where('name', 'employee')->value('id');

        // Departemen (tabel departements sudah ada, kosong).
        $depts = [];
        foreach (['Engineering', 'Marketing', 'Finance', 'HR', 'Operations'] as $name) {
            $depts[$name] = Departement::firstOrCreate(['name' => $name])->id;
        }

        $companies = [
            ['name' => 'PT Solusi Digital Indonesia', 'brand' => 'Solusi Digital', 'employees' => 24, 'nps' => 4.2],
            ['name' => 'PT Maju Bersama Sejahtera', 'brand' => 'Maju Bersama', 'employees' => 12, 'nps' => 3.8],
        ];

        foreach ($companies as $ci => $c) {
            $p = Perusahaan::firstOrCreate(
                ['name' => $c['name']],
                [
                    'brand' => $c['brand'],
                    'pepm_rate' => 15000,
                    'sessions_per_employee_month' => 2,
                    'employee_count' => $c['employees'],
                    'nps_score' => $c['nps'],
                    'mou_at' => now()->subDays(60 - $ci * 20),
                    'is_active' => true,
                ]
            );

            // Karyawan dummy spread departemen.
            $firstNames = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fira', 'Galih', 'Hana', 'Irfan', 'Joko', 'Kiki', 'Lina', 'Maya', 'Nadia', 'Oka', 'Putri', 'Rizky', 'Sari', 'Tono', 'Umar', 'Vina', 'Wawan', 'Yuni', 'Zaki'];
            $lastNames = ['Pratama', 'Santoso', 'Wijaya', 'Kusuma', 'Halim', 'Saputra', 'Gunawan', 'Hartono'];
            $deptNames = array_keys($depts);
            for ($i = 0; $i < $c['employees']; $i++) {
                $name = $firstNames[$i % 24] . ' ' . $lastNames[$i % 8];
                $email = strtolower(Str::slug($name, '.')) . $p->id . '@demo-karyawan.test';
                $emp = PerusahaanEmployee::firstOrCreate(
                    ['perusahaan_id' => $p->id, 'email' => $email],
                    [
                        'departement_id' => $depts[$deptNames[$i % 5]],
                        'name' => $name,
                        'consent_at' => now()->subDays(rand(10, 60)),
                    ]
                );
                $this->seedSessions($p, $emp);
                $this->seedAssessments($p, $emp);
            }

            // HR admin user + perusahaan_admins row.
            $hrEmail = 'hr@' . Str::slug($c['brand'], '') . '.test';
            $hr = User::firstOrCreate(
                ['email' => $hrEmail],
                [
                    'uuid' => Str::uuid(),
                    'firstname' => 'HR', 'lastname' => $c['brand'],
                    'username' => 'hr_' . Str::slug($c['brand'], '_'),
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'role_id' => $hrRoleId,
                ]
            );
            PerusahaanAdmin::firstOrCreate(['perusahaan_id' => $p->id, 'user_id' => $hr->id], ['role' => 'hr_admin']);

            // Satu employee user untuk demo dashboard karyawan (karyawan pertama).
            $empUser = PerusahaanEmployee::where('perusahaan_id', $p->id)->whereNull('user_id')->first();
            if ($empUser) {
                // Email akun = email roster, supaya yang tampil di tabel HR bisa langsung dipakai login.
                $email = $empUser->email;
                $u = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'uuid' => Str::uuid(),
                        'firstname' => strtok($empUser->name, ' '), 'lastname' => trim(strstr($empUser->name, ' ') ?: ''),
                        'username' => Str::slug($empUser->name, '_') . '_emp',
                        'password' => bcrypt('password'),
                        'is_active' => true,
                        'role_id' => $employeeRoleId,
                    ]
                );
                $empUser->user_id = $u->id;
                $empUser->save();
                PerusahaanAdmin::firstOrCreate(['perusahaan_id' => $p->id, 'user_id' => $u->id], ['role' => 'employee']);
            }
        }
    }

    /** Sesi dummy deterministik: spread 2 bulan, mix status/topik. */
    protected function seedSessions(Perusahaan $p, PerusahaanEmployee $emp): void
    {
        if ($emp->sessions()->exists()) {
            return;
        }

        $topics = ['Stress', 'Kecemasan', 'Karir', 'Keuangan', 'Work-life balance', 'Relasi'];
        $types = ['psikolog', 'psikolog', 'psikolog', 'nutrisionis', 'karir'];
        // Sekitar 40% karyawan punya riwayat sesi.
        $count = ($emp->id % 10 < 4) ? rand(1, 3) : 0;

        for ($i = 0; $i < $count; $i++) {
            $daysAgo = rand(0, 55);
            $scheduled = now()->subDays($daysAgo)->setTime(rand(9, 17), [0, 30][rand(0, 1)]);
            $roll = rand(1, 10);
            $status = $daysAgo < 1 ? 'scheduled' : ($roll <= 8 ? 'done' : 'no_show');

            PerusahaanSession::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'konsultan_type' => $types[$emp->id % 5],
                'topic_category' => $topics[$emp->id % 6],
                'status' => $status,
                'rating' => $status === 'done' ? rand(3, 5) : null,
                'completed_at' => $status === 'done' ? $scheduled->addHour() : null,
                'scheduled_at' => $scheduled,
            ]);
        }

        // Karyawan tanpa riwayat: beri 1 sesi terjadwal agar agenda HR terisi.
        if ($count === 0 && $emp->id % 3 === 0) {
            PerusahaanSession::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'konsultan_type' => 'psikolog',
                'topic_category' => null,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(rand(1, 10))->setTime(10, 0),
            ]);
        }
    }

    /** Asesmen WHO-5 dummy: ~70% karyawan, 1-2 hasil, spread 90 hari. */
    protected function seedAssessments(Perusahaan $p, PerusahaanEmployee $emp): void
    {
        if ($emp->assessments()->exists() || $emp->id % 10 >= 7) {
            return;
        }

        $rounds = ($emp->id % 3 === 0) ? 2 : 1;
        foreach (range(1, $rounds) as $round) {
            $answers = collect(range(1, 5))->map(fn () => rand(0, 5))->values()->all();
            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'answers' => $answers,
                'score' => array_sum($answers) * 4,
                'taken_at' => now()->subDays($round === 2 ? rand(1, 20) : rand(25, 90))->setTime(rand(8, 17), 0),
            ]);
        }
    }
}
