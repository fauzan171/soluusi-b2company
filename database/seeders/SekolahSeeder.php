<?php

namespace Database\Seeders;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data vertikal Sekolah. Idempotent. Password semua akun: "password".
 * Mirror PerusahaanSeeder.
 */
class SekolahSeeder extends Seeder
{
    private array $topics = ['Stress', 'Kecemasan', 'Bullying', 'Karir', 'Keluarga', 'Percaya diri', 'Relasi'];
    private array $types = ['psikolog', 'psikolog', 'psikolog', 'karir'];

    public function run(): void
    {
        foreach (['bk', 'student'] as $name) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['created_at' => now(), 'updated_at' => now()]);
        }
        $bkRoleId = DB::table('roles')->where('name', 'bk')->value('id');
        $studentRoleId = DB::table('roles')->where('name', 'student')->value('id');

        $schools = [
            ['name' => 'SMA Cendekia Nusantara', 'brand' => 'SMA Cendekia', 'tingkat' => 'SMA', 'students' => 40],
            ['name' => 'SMP Harapan Bangsa', 'brand' => 'SMP Harapan', 'tingkat' => 'SMP', 'students' => 20],
        ];

        foreach ($schools as $si => $s) {
            $sekolah = Sekolah::firstOrCreate(
                ['name' => $s['name']],
                [
                    'brand' => $s['brand'],
                    'tingkat' => $s['tingkat'],
                    'student_count' => $s['students'],
                    'sessions_per_student_month' => 2,
                    'mou_at' => now()->subDays(45),
                    'is_active' => true,
                ]
            );

            $firstNames = ['Aisyah', 'Bagas', 'Cindy', 'Dimas', 'Elsa', 'Farhan', 'Gita', 'Hafiz', 'Indah', 'Jasper', 'Kirana', 'Lutfi', 'Mayang', 'Naufal', 'Olivia', 'Putra', 'Queen', 'Rafi', 'Salma', 'Teguh'];
            $kelasList = $s['tingkat'] === 'SMA'
                ? ['X IPA 1', 'X IPA 2', 'XI IPS 1', 'XII IPA 1', 'XII IPS 1']
                : ['VII A', 'VII B', 'VIII A', 'IX A'];

            for ($i = 0; $i < $s['students']; $i++) {
                $name = $firstNames[$i % 20] . ' ' . ['Pratama', 'Wijaya', 'Saputra', 'Halim'][$i % 4];
                $email = strtolower(Str::slug($name, '.')) . $sekolah->id . '@demo-siswa.test';
                $student = SekolahStudent::firstOrCreate(
                    ['sekolah_id' => $sekolah->id, 'email' => $email],
                    [
                        'name' => $name,
                        'kelas' => $kelasList[$i % count($kelasList)],
                        'parent_name' => 'Ortu ' . $firstNames[$i % 20],
                        // 90% sudah parent consent (realistis).
                        'parent_consent_at' => $i % 10 === 9 ? null : now()->subDays(rand(5, 40)),
                    ]
                );
                $this->seedSessions($sekolah, $student);
                $this->seedAssessments($sekolah, $student);
            }

            // BK user + sekolah_admins row.
            $bkEmail = 'bk@' . Str::slug($s['brand'], '') . '.test';
            $bk = User::firstOrCreate(
                ['email' => $bkEmail],
                [
                    'uuid' => Str::uuid(),
                    'firstname' => 'Guru', 'lastname' => 'BK ' . $s['brand'],
                    'username' => 'bk_' . Str::slug($s['brand'], '_'),
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'role_id' => $bkRoleId,
                ]
            );
            SekolahAdmin::firstOrCreate(['sekolah_id' => $sekolah->id, 'user_id' => $bk->id], ['role' => 'bk']);

            // Satu siswa user untuk demo portal siswa.
            $stuUser = SekolahStudent::where('sekolah_id', $sekolah->id)->whereNull('user_id')->first();
            if ($stuUser) {
                $email = Str::slug($stuUser->name, '.') . $sekolah->id . '-login@demo-siswa.test';
                $u = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'uuid' => Str::uuid(),
                        'firstname' => strtok($stuUser->name, ' '), 'lastname' => trim(strstr($stuUser->name, ' ') ?: ''),
                        'username' => Str::slug($stuUser->name, '_') . '_stu',
                        'password' => bcrypt('password'),
                        'is_active' => true,
                        'role_id' => $studentRoleId,
                    ]
                );
                $stuUser->user_id = $u->id;
                $stuUser->save();
            }
        }
    }

    private function seedSessions(Sekolah $sekolah, SekolahStudent $student): void
    {
        if ($student->sessions()->exists()) {
            return;
        }

        // ~50% siswa punya riwayat (sekolah = rujukan BK, bukan semua).
        $count = ($student->id % 10 < 5) ? rand(1, 3) : 0;

        for ($i = 0; $i < $count; $i++) {
            $daysAgo = rand(0, 55);
            $scheduled = now()->subDays($daysAgo)->setTime(rand(8, 15), [0, 30][rand(0, 1)]);
            $roll = rand(1, 10);
            $status = $daysAgo < 1 ? 'scheduled' : ($roll <= 8 ? 'done' : 'no_show');

            SekolahSession::create([
                'sekolah_id' => $sekolah->id,
                'student_id' => $student->id,
                'kelas' => $student->kelas,
                'konsultan_type' => $this->types[$student->id % 4],
                'topic_category' => $status === 'done' ? $this->topics[$student->id % count($this->topics)] : null,
                'mode' => $student->id % 2 === 0 ? 'online' : 'offline',
                'origin' => $student->id % 3 === 0 ? 'self_booking' : 'bk_referral',
                'status' => $status,
                'rating' => $status === 'done' ? rand(3, 5) : null,
                'completed_at' => $status === 'done' ? $scheduled->addHour() : null,
                'scheduled_at' => $scheduled,
            ]);
        }

        // Siswa tanpa riwayat: beri 1 sesi terjadwal agar agenda terisi.
        if ($count === 0 && $student->id % 3 === 0) {
            SekolahSession::create([
                'sekolah_id' => $sekolah->id,
                'student_id' => $student->id,
                'kelas' => $student->kelas,
                'konsultan_type' => 'psikolog',
                'mode' => 'offline',
                'origin' => 'bk_referral',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(rand(1, 10))->setTime(9, 0),
            ]);
        }
    }

    private function seedAssessments(Sekolah $sekolah, SekolahStudent $student): void
    {
        if ($student->assessments()->exists() || $student->id % 10 >= 8) {
            return;
        }

        $answers = collect(range(1, 5))->map(fn () => rand(0, 5))->values()->all();
        SekolahAssessment::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'answers' => $answers,
            'score' => array_sum($answers) * 4,
            'taken_at' => now()->subDays(rand(3, 85))->setTime(rand(8, 14), 0),
        ]);
    }
}
