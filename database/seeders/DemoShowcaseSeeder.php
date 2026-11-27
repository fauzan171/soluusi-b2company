<?php

namespace Database\Seeders;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Category;
use App\Models\CompanyWellbeingProgram;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Departement;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\WellbeingPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Showcase data presentasi portal B2B (presentasi demo, BUKAN di-chain di
 * DatabaseSeeder). Jalankan eksplisit:
 *   php artisan db:seed --class=DemoShowcaseSeeder
 * Semua email @demo.com, nama fiktif, password DemoPass#2026. Idempotent
 * (firstOrCreate / updateOrCreate — rerun tidak menduplikasi). Cleanup via
 * DemoShowcaseSeeder::purge() (dipakai test).
 */
class DemoShowcaseSeeder extends Seeder
{
    public const PASSWORD = 'DemoPass#2026';
    public const PERUSAHAAN_NAME = 'PT Demo Wellbeing Nusantara';
    public const SEKOLAH_NAME = 'SMA Demo Harapan';
    public const KLINIK_NAME = 'Klinik Demo Sehat';

    private const TOPICS = ['Stress', 'Kecemasan', 'Karir', 'Keuangan', 'Work-life balance', 'Relasi', 'Burnout'];
    private const TYPES = ['psikolog', 'psikolog', 'psikolog', 'nutrisionis', 'karir'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoShowcaseSeeder refused: APP_ENV=production.');
            return;
        }
        $this->seedPerusahaan();
        $this->seedSekolah();
        $this->seedKlinik();
        $this->seedConsultants();
    }

    // ------------------------------------------------------------------
    // 1. Perusahaan + HR + 15 karyawan + program wellbeing + asesmen + sesi
    // ------------------------------------------------------------------

    private function seedPerusahaan(): void
    {
        $hrRoleId = $this->roleId('hr_admin');
        $employeeRoleId = $this->roleId('employee');

        $p = Perusahaan::firstOrCreate(
            ['name' => self::PERUSAHAAN_NAME],
            [
                'brand' => 'Demo Wellbeing',
                'pepm_rate' => 15000,
                'sessions_per_employee_month' => 2,
                'employee_count' => 15,
                'nps_score' => 4.3,
                'mou_at' => now()->subDays(90),
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $depts = [];
        foreach (['Engineering', 'Marketing', 'Finance', 'HR', 'Operations'] as $name) {
            $depts[$name] = Departement::firstOrCreate([
                'perusahaan_id' => $p->id,
                'name' => $name,
            ])->id;
        }

        $hr = $this->firstOrCreateUser('hr@demo.com', 'Hana', 'Demo HR', 'hr_demo', $hrRoleId);
        PerusahaanAdmin::firstOrCreate(
            ['perusahaan_id' => $p->id, 'user_id' => $hr->id],
            ['role' => 'hr_admin']
        );

        $firstNames = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fira', 'Galih', 'Hana', 'Irfan', 'Joko', 'Kiki', 'Lina', 'Maya', 'Nadia', 'Oka'];
        $lastNames = ['Pratama', 'Santoso', 'Wijaya', 'Kusuma', 'Halim'];
        $deptNames = array_keys($depts);

        $employees = collect();
        foreach (range(1, 15) as $i) {
            $name = $firstNames[$i - 1] . ' ' . $lastNames[($i - 1) % 5];
            $email = sprintf('karyawan%02d@demo.com', $i);
            $user = $this->firstOrCreateUser($email, $firstNames[$i - 1], $lastNames[($i - 1) % 5], sprintf('karyawan%02d_demo', $i), $employeeRoleId);
            $emp = PerusahaanEmployee::firstOrCreate(
                ['perusahaan_id' => $p->id, 'email' => $email],
                [
                    'departement_id' => $depts[$deptNames[($i - 1) % 5]],
                    'user_id' => $user->id,
                    'name' => $name,
                    'consent_at' => now()->subDays(rand(10, 60)),
                ]
            );
            $employees->push($emp);
        }

        $program = $this->seedProgram($p);
        $this->seedAssessments($p, $employees, $program);
        $this->seedSessions($p, $employees);
    }

    /** Program Growth aktif — snapshot manual, mirror PerusahaanAssessmentUnionTest::program(). */
    private function seedProgram(Perusahaan $p): CompanyWellbeingProgram
    {
        // Pastikan katalog paket ada (WellbeingPackageSeeder idempotent — aman dipanggil ulang).
        if (!WellbeingPackage::where('code', 'growth')->exists()) {
            $this->ensureCalled(WellbeingPackageSeeder::class);
        }
        if (!AssessmentInstrument::where('code', 'who5')->exists()) {
            $this->ensureCalled(AssessmentInstrumentSeeder::class);
        }

        $package = WellbeingPackage::where('code', 'growth')->firstOrFail();

        $program = CompanyWellbeingProgram::firstOrCreate(
            ['perusahaan_id' => $p->id, 'wellbeing_package_id' => $package->id, 'status' => 'active'],
            [
                'employee_band_size' => $p->employee_count,
                'deep_support_mode' => 'one_on_one',
                'starts_at' => today()->subMonth()->toDateString(),
                'ends_at' => today()->addMonths(5)->toDateString(),
                'expert_chat_quota' => 10,
                'deep_support_quota' => 15,
                'webinar_quota' => 3,
                'assessment_count' => 2,
                'assessment_max_participants' => null,
                'monthly_price' => 7499999,
                'assessment_domains' => ['mh', 'nutrition'],
                'status' => 'active',
            ]
        );

        // Assignment instrumen LEVEL PROGRAM (mirror Admin\WellbeingProgramController
        // ::createAssignments growth): core dass21+nutrition, extra MH who5, add-on
        // bfitrader/disc24. Level perusahaan tidak dipakai: saat program aktif,
        // assignment legacy perusahaan tersupresi (AssessmentAccess::forEmployee D8)
        // dan row asesmen tanpa assessment_assignment_id tidak terhitung di report.
        // Rerun aman: unique (assignable, instrument, audience, round).
        $starts = $program->starts_at->toDateString();
        $ends = $program->ends_at->toDateString();
        foreach ([
            // [code, source, round, opens, closes]
            ['dass21', AssessmentAssignment::SOURCE_CORE, 1, $starts, $program->starts_at->copy()->addDays(29)->toDateString()],
            ['dass21', AssessmentAssignment::SOURCE_CORE, 2, $program->ends_at->copy()->subDays(29)->toDateString(), $ends],
            ['nutrition', AssessmentAssignment::SOURCE_CORE, 1, $starts, $program->starts_at->copy()->addDays(29)->toDateString()],
            ['nutrition', AssessmentAssignment::SOURCE_CORE, 2, $program->ends_at->copy()->subDays(29)->toDateString(), $ends],
            ['who5', AssessmentAssignment::SOURCE_CORE, 1, $starts, $program->starts_at->copy()->addDays(29)->toDateString()],
            ['who5', AssessmentAssignment::SOURCE_CORE, 2, $program->ends_at->copy()->subDays(29)->toDateString(), $ends],
            ['bfitrader', AssessmentAssignment::SOURCE_ADDON, 1, $starts, $ends],
            ['disc24', AssessmentAssignment::SOURCE_ADDON, 1, $starts, $ends],
        ] as [$code, $source, $round, $opens, $closes]) {
            AssessmentAssignment::firstOrCreate([
                'assignable_type' => $program->getMorphClass(),
                'assignable_id' => $program->id,
                'instrument_code' => $code,
                'audience' => AssessmentAssignment::AUDIENCE_EMPLOYEE,
                'round' => $round,
            ], [
                'source' => $source,
                'addon_price' => $source === AssessmentAssignment::SOURCE_ADDON ? 250000 : null,
                'opens_at' => $opens,
                'closes_at' => $closes,
                'status' => 'active',
                'assigned_by' => null,
            ]);
        }

        return $program;
    }

    /** Asesmen selesai 13/15 karyawan, band varied biar dashboard hidup. */
    private function seedAssessments(Perusahaan $p, $employees, CompanyWellbeingProgram $program): void
    {
        $who5 = AssessmentInstrument::where('code', 'who5')->firstOrFail();
        $questions = $who5->questions()->get();

        // Link ke assignment program round 1 supaya terhitung di report completers.
        $assignment = AssessmentAssignment::where('assignable_type', $program->getMorphClass())
            ->where('assignable_id', $program->id)
            ->where('instrument_code', 'who5')
            ->where('round', 1)
            ->first();

        foreach ($employees as $idx => $emp) {
            if ($idx >= 13) {
                continue; // 2 karyawan belum isi
            }
            if (PerusahaanAssessment::where('employee_id', $emp->id)->where('instrument_code', 'who5')->exists()) {
                continue;
            }

            // Distribusi band over idx 0-12 (idx%3): 5× Sehat(80), 4× Perlu Perhatian(60), 4× Berisiko(40).
            $roll = $idx % 3;
            // sum item (0-25) * 4: 5×4=20→80 Sehat; 5×3=15→60 Perlu Perhatian; 5×2=10→40 Berisiko.
            $perItem = $roll === 0 ? 4 : ($roll === 1 ? 3 : 2);

            $answers = $questions->mapWithKeys(fn ($q) => [$q->code => $perItem])->all();

            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'instrument_code' => 'who5',
                'assessment_assignment_id' => $assignment?->id,
                'answers' => $answers,
                'score' => $perItem * 5 * 4,
                'escalated' => false,
                'taken_at' => now()->subDays(rand(3, 25))->setTime(rand(8, 17), 0),
                'section' => 'mental_health',
                'status' => 'completed',
            ]);
        }
    }

    /** Sesi done spread 2 bulan + scheduled 10 hari ke depan. */
    private function seedSessions(Perusahaan $p, $employees): void
    {
        if (PerusahaanSession::where('perusahaan_id', $p->id)->exists()) {
            return;
        }

        // 30 sesi done dalam 60 hari
        foreach (range(1, 30) as $i) {
            $emp = $employees[$i % $employees->count()];
            $scheduled = now()->subDays(rand(1, 60))->setTime(rand(9, 16), [0, 30][$i % 2]);
            PerusahaanSession::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'konsultan_type' => self::TYPES[$i % 5],
                'consultation_type' => $i % 3 === 0 ? 'chat' : 'one_on_one',
                'mode' => $i % 2 === 0 ? 'online' : 'offline',
                'topic_category' => self::TOPICS[$i % 7],
                'status' => 'done',
                'rating' => collect([5, 5, 4, 4, 3])->random(),
                'completed_at' => $scheduled->copy()->addHour(),
                'scheduled_at' => $scheduled,
            ]);
        }

        // 8 scheduled 14 hari ke depan (weekday only)
        $d = 0;
        foreach (range(1, 8) as $i) {
            $day = now()->addDays(++$d);
            while ($day->isWeekend()) {
                $day = now()->addDays(++$d);
            }
            $emp = $employees[$i % $employees->count()];
            PerusahaanSession::create([
                'perusahaan_id' => $p->id,
                'employee_id' => $emp->id,
                'departement_id' => $emp->departement_id,
                'konsultan_type' => self::TYPES[$i % 5],
                'consultation_type' => 'one_on_one',
                'mode' => 'online',
                'topic_category' => null,
                'status' => 'scheduled',
                'scheduled_at' => $day->copy()->setTime(10 + ($i % 6), 0),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // 2. Sekolah
    // ------------------------------------------------------------------

    private function seedSekolah(): void
    {
        $bkRoleId = $this->roleId('bk');
        $studentRoleId = $this->roleId('student');
        $staffRoleId = $this->roleId('sekolah_staff');

        $sekolah = Sekolah::firstOrCreate(
            ['name' => self::SEKOLAH_NAME],
            [
                'brand' => 'SMA Demo Harapan',
                'tingkat' => 'SMA',
                'student_count' => 12,
                'sessions_per_student_month' => 2,
                'mou_at' => now()->subDays(45),
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $bk = $this->firstOrCreateUser('sekolah@demo.com', 'Guru', 'BK Demo', 'bk_demo', $bkRoleId);
        SekolahAdmin::firstOrCreate(
            ['sekolah_id' => $sekolah->id, 'user_id' => $bk->id],
            ['role' => 'bk']
        );

        // Staf sekolah (end-user program wellbeing sendiri).
        $staffEmail = 'staff.sekolah@demo.com';
        $staffUser = $this->firstOrCreateUser($staffEmail, 'Guru', 'Staf Demo', 'staff_sekolah_demo', $staffRoleId);
        SekolahAdmin::firstOrCreate(
            ['sekolah_id' => $sekolah->id, 'user_id' => $staffUser->id],
            ['role' => 'sekolah_staff']
        );
        SekolahStudent::firstOrCreate(
            ['sekolah_id' => $sekolah->id, 'email' => $staffEmail],
            [
                'type' => SekolahStudent::TYPE_STAFF,
                'user_id' => $staffUser->id,
                'name' => 'Guru Staf Demo',
                'kelas' => null,
                'consent_at' => now()->subDays(30),
            ]
        );

        $firstNames = ['Aisyah', 'Bagas', 'Cindy', 'Dimas', 'Elsa', 'Farhan', 'Gita', 'Hafiz', 'Indah', 'Kirana', 'Lutfi', 'Mayang'];
        $kelasList = ['X IPA 1', 'X IPA 2', 'XI IPS 1', 'XII IPA 1', 'XII IPS 1'];

        foreach (range(1, 12) as $i) {
            $name = $firstNames[$i - 1] . ' ' . ['Pratama', 'Wijaya', 'Saputra'][$i % 3];
            $email = sprintf('siswa%02d@demo.com', $i);
            $user = $this->firstOrCreateUser($email, $firstNames[$i - 1], ['Pratama', 'Wijaya', 'Saputra'][$i % 3], sprintf('siswa%02d_demo', $i), $studentRoleId);
            SekolahStudent::firstOrCreate(
                ['sekolah_id' => $sekolah->id, 'email' => $email],
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'kelas' => $kelasList[$i % 5],
                    'parent_name' => 'Ortu ' . $firstNames[$i - 1],
                    'parent_consent_at' => $i === 12 ? null : now()->subDays(rand(5, 40)),
                ]
            );
        }
    }

    // ------------------------------------------------------------------
    // 3. Klinik
    // ------------------------------------------------------------------

    private function seedKlinik(): void
    {
        $klinikAdminRoleId = $this->roleId('klinik_admin');
        $regencyId = 3171; // Jakarta Selatan (nilai regency seeded IndoRegion; null-safe kalau kosong)

        $klinik = Klinik::firstOrCreate(
            ['name' => self::KLINIK_NAME],
            [
                'uuid' => Str::uuid(),
                'brand' => 'Demo Sehat',
                'from_email' => 'no-reply@demosehat.local',
                'is_active' => true,
                'status' => 'active',
                'approved_at' => now(),
                'mou_at' => now()->subMonth(),
                'regency_id' => $regencyId,
                'facility_type' => 'klinik',
                'address' => 'Jl. Demo No. 1, Jakarta Selatan',
                'terms_agreed_at' => now(),
                'total_rate' => 1000000,
            ]
        );

        $admin = $this->firstOrCreateUser('klinik@demo.com', 'Ratna', 'Demo Klinik', 'klinik_demo', $klinikAdminRoleId);
        KlinikAdmin::firstOrCreate(
            ['klinik_id' => $klinik->id, 'user_id' => $admin->id],
            ['role' => 'admin']
        );

        $staff = $this->firstOrCreateUser('staff.klinik@demo.com', 'Dodi', 'Demo Staf', 'staff_klinik_demo', $klinikAdminRoleId);
        KlinikAdmin::firstOrCreate(
            ['klinik_id' => $klinik->id, 'user_id' => $staff->id],
            ['role' => 'staff']
        );

        // Pasien demo: akun user role customer (login portal), tanpa row
        // consultation session — nama fiktif, email @demo.com.

        $patientRoleId = $this->roleId('customer');
        foreach (range(1, 3) as $i) {
            $this->firstOrCreateUser(sprintf('pasien%02d@demo.com', $i), 'Pasien', 'Demo ' . $i, sprintf('pasien%02d_demo', $i), $patientRoleId);
        }
    }

    // ------------------------------------------------------------------
    // 4. Konsultan pool B2B
    // ------------------------------------------------------------------

    private function seedConsultants(): void
    {
        $p = Perusahaan::where('name', self::PERUSAHAAN_NAME)->firstOrFail();

        $consultants = [
            [
                'email' => 'chat-b2b@demo.com', 'username' => 'konsultan_chat_b2b',
                'first' => 'Konsultan', 'last' => 'Chat B2B',
                'headline' => 'Psikolog Klinis B2B',
                'about' => 'Konsultan chat B2B untuk program wellbeing perusahaan. Psikolog klinis berpengalaman menangani stress, kecemasan, dan burnout karyawan.',
                'profile' => [
                    'categories' => [['code' => 'mentalhealth', 'name' => 'Kesehatan Mental']],
                    'education' => [[
                        'institution' => 'Universitas Indonesia', 'degree' => 'S2 Profesi Psikolog Klinis',
                        'department' => null, 'start' => '2014-08-01', 'end' => '2016-07-01', 'description' => null,
                    ]],
                    'certificates' => [[
                        'name' => 'Sertifikat Praktik Psikologi Klinis', 'number' => 'SRT-DEMO0001',
                        'year' => 2020, 'expired_at' => null, 'url' => null,
                    ]],
                    'skills' => ['Counseling', 'Mental Health', 'Psychotherapy'],
                    'experience' => [],
                    'packages' => [['name' => 'Konsultasi Chat 60 Menit', 'price' => 200000, 'duration' => 60, 'quota' => 1]],
                ],
            ],
            [
                'email' => 'tester@demo.com', 'username' => 'konsultan_tester',
                'first' => 'Konsultan', 'last' => 'Tester',
                'headline' => 'Konselor Tester B2B',
                'about' => 'Konsultan tester untuk verifikasi flow B2B: chat, kuota, jadwal, dan sesi perusahaan.',
                'profile' => [
                    'categories' => [['code' => 'mentalhealth', 'name' => 'Kesehatan Mental']],
                    'education' => [[
                        'institution' => 'Universitas Gadjah Mada', 'degree' => 'S1 Psikologi',
                        'department' => null, 'start' => '2016-08-01', 'end' => '2020-07-01', 'description' => null,
                    ]],
                    'certificates' => [[
                        'name' => 'Certified Counselor', 'number' => 'SRT-DEMO0002',
                        'year' => 2022, 'expired_at' => null, 'url' => null,
                    ]],
                    'skills' => ['Counseling', 'Emotional'],
                    'experience' => [],
                    'packages' => [['name' => 'Konsultasi Chat 30 Menit', 'price' => 100000, 'duration' => 30, 'quota' => 1]],
                ],
            ],
        ];

        $consultantRoleId = $this->roleId('consultant');
        foreach ($consultants as $c) {
            $user = $this->firstOrCreateUser($c['email'], $c['first'], $c['last'], $c['username'], $consultantRoleId);

            $consultant = Consultant::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => Consultant::ACCEPT,
                    'is_active' => true,
                    'about' => $c['about'],
                    'profit_sharing' => 80,
                    'decision_at' => now(),
                    'headline' => $c['headline'],
                    'profile' => $c['profile'],
                    'photo_url' => null,
                ]
            );

            $pool = ConsultantPool::firstOrCreate(
                ['consultant_id' => $consultant->id, 'perusahaan_id' => $p->id],
                ['is_active' => true, 'status' => 'active']
            );

            // Work hours Mon-Fri 09:00-17:00 (weekday 1=Senin..5=Jumat).
            foreach (range(1, 5) as $weekday) {
                ConsultantWorkHour::firstOrCreate([
                    'consultant_pool_id' => $pool->id,
                    'weekday' => $weekday,
                    'start_time' => '09:00:00',
                ], [
                    'end_time' => '17:00:00',
                    'consultation_types' => null,
                    'slot_minutes' => 60,
                ]);
            }

            // Kategori pool (poolFor tampil kalau punya kategori).
            $catId = Category::where('code_category', 'mentalhealth')->value('id')
                ?? Category::where('name', 'Kesehatan Mental')->value('id');
            if ($catId && !DB::table('consultant_categories')->where('consultant_id', $consultant->id)->where('category_id', $catId)->exists()) {
                DB::table('consultant_categories')->insert([
                    'consultant_id' => $consultant->id,
                    'category_id' => $catId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function roleId(string $name): int
    {
        DB::table('roles')->updateOrInsert(['name' => $name], ['created_at' => now(), 'updated_at' => now()]);

        return (int) DB::table('roles')->where('name', $name)->value('id');
    }

    private function firstOrCreateUser(string $email, string $first, string $last, string $username, int $roleId): \App\Models\User
    {
        return \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'uuid' => Str::uuid(),
                'firstname' => $first,
                'lastname' => $last,
                'username' => $username,
                'password' => bcrypt(self::PASSWORD),
                'is_active' => true,
                'role_id' => $roleId,
            ]
        );
    }

    /** call() seeder lain hanya sekali per run — marker via property cache. */
    private array $calledOnce = [];

    private function ensureCalled(string $class): void
    {
        if (!isset($this->calledOnce[$class])) {
            $this->calledOnce[$class] = true;
            $this->call([$class]);
        }
    }

    /**
     * Cleanup: hapus SEMUA row demo (test). Hanya menyentuh row demo:
     * users @demo.com + tenant sesuai nama + relasi yang menggantung.
     * Urutan: relasi dependent dulu, lalu users, lalu tenants.
     */
    public static function purge(): void
    {
        DB::transaction(function (): void {
            self::doPurge();
        });
    }

    private static function doPurge(): void
    {
        $demoUserIds = \App\Models\User::where('email', 'like', '%@demo.com')->pluck('id')->all();

        // Tenants demo (exact name).
        $p = Perusahaan::where('name', self::PERUSAHAAN_NAME)->first();
        $s = Sekolah::where('name', self::SEKOLAH_NAME)->first();
        $k = Klinik::where('name', self::KLINIK_NAME)->first();

        if ($p) {
            // Jawaban item asesmen demo (polymorphic, tanpa FK — harus eksplisit).
            $assessmentIds = PerusahaanAssessment::where('perusahaan_id', $p->id)->pluck('id');
            DB::table('screening_answers')
                ->where('assessable_type', \App\Models\Perusahaan\PerusahaanAssessment::class)
                ->whereIn('assessable_id', $assessmentIds)
                ->delete();
            PerusahaanSession::where('perusahaan_id', $p->id)->delete();
            PerusahaanAssessment::where('perusahaan_id', $p->id)->delete();
            PerusahaanEmployee::where('perusahaan_id', $p->id)->delete();
            PerusahaanAdmin::where('perusahaan_id', $p->id)->delete();
            // Assignment level perusahaan + level program (morph 'program').
            AssessmentAssignment::where(function ($q) use ($p) {
                $q->where(function ($pq) use ($p) {
                    $pq->where('assignable_type', $p->getMorphClass())->where('assignable_id', $p->id);
                })->orWhereIn('assignable_id', CompanyWellbeingProgram::where('perusahaan_id', $p->id)->pluck('id'))
                    ->where('assignable_type', 'program');
            })->delete();
            CompanyWellbeingProgram::where('perusahaan_id', $p->id)->delete();
            foreach (\App\Models\ConsultantPool::where('perusahaan_id', $p->id)->get() as $pool) {
                ConsultantWorkHour::where('consultant_pool_id', $pool->id)->delete();
                $pool->delete();
            }
            Departement::where('perusahaan_id', $p->id)->delete();
            $p->delete();
        }

        if ($s) {
            \App\Models\Sekolah\SekolahSession::where('sekolah_id', $s->id)->delete();
            \App\Models\Sekolah\SekolahAssessment::where('sekolah_id', $s->id)->delete();
            SekolahStudent::where('sekolah_id', $s->id)->delete();
            SekolahAdmin::where('sekolah_id', $s->id)->delete();
            foreach (\App\Models\ConsultantPool::where('sekolah_id', $s->id)->get() as $pool) {
                ConsultantWorkHour::where('consultant_pool_id', $pool->id)->delete();
                $pool->delete();
            }
            $s->delete();
        }

        if ($k) {
            KlinikAdmin::where('klinik_id', $k->id)->delete();
            DB::table('consultation_sessions')->where('klinik_id', $k->id)->delete();
            DB::table('schedules')->where('klinik_id', $k->id)->delete();
            DB::table('markup_rules')->where('klinik_id', $k->id)->delete();
            DB::table('checkout_links')->where('klinik_id', $k->id)->delete();
            $k->delete();
        }

        // Konsultan demo (row consultants milik user @demo.com) — FK restrict
        // ke consultant_categories / work hours via pool sudah dihapus di atas.
        $demoConsultantIds = Consultant::whereIn('user_id', $demoUserIds)->pluck('id')->all();
        if ($demoConsultantIds) {
            DB::table('consultant_categories')->whereIn('consultant_id', $demoConsultantIds)->delete();
            DB::table('consultant_skills')->whereIn('consultant_id', $demoConsultantIds)->delete();
            DB::table('certificates')->whereIn('consultant_id', $demoConsultantIds)->delete();
            DB::table('consultant_education')->whereIn('consultant_id', $demoConsultantIds)->delete();
            DB::table('consultant_packages')->whereIn('consultant_id', $demoConsultantIds)->delete();
            // Hard delete: Consultant pakai SoftDeletes, soft delete meninggalkan
            // row consultants.user_id (FK RESTRICT) yang blokir penghapusan user.
            Consultant::whereIn('id', $demoConsultantIds)->forceDelete();
        }

        if ($demoUserIds) {
            \App\Models\User::whereIn('id', $demoUserIds)->delete();
        }
    }
}
