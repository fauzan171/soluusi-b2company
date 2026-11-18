<?php

namespace Tests\Feature\Sekolah;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// F7: angka kuota di portal siswa harus memakai kalkulasi yang sama dengan
// penegakan saat booking (BookingService::quotaUsed) — kalau beda, siswa
// melihat "sisa 0" padahal masih bisa booking (atau sebaliknya).
class SekolahQuotaDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    public function test_done_session_scheduled_last_month_does_not_eat_this_month_quota(): void
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Kuota', 'brand' => 'SMA Kuota',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $user = $this->user('siswa@kuota.local', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Kuota', 'email' => 'siswa@kuota.local',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);
        // Dijadwalkan & diselesaikan bulan lalu.
        SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'konsultan_type' => 'psikolog',
            'status' => 'done', 'scheduled_at' => now()->subMonthNoOverflow(),
            'completed_at' => now()->subMonthNoOverflow(),
        ]);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            // Sisa = 2 dari 2: sesi bulan lalu tidak boleh memotong kuota bulan ini.
            ->assertSeeText('2 / 2');
    }

    /**
     * F7: sesi dijadwalkan bulan lalu, baru diselesaikan bulan ini.
     * Penegakan booking menghitung by scheduled_at (bulan lalu → tidak makan
     * kuota); tampilan lama menghitung by completed_at → siswa melihat sisa
     * lebih sedikit daripada yang benar-benar bisa dia booking.
     */
    public function test_done_session_scheduled_last_month_completed_now_still_free(): void
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Kuota 2', 'brand' => 'SMA Kuota 2',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $user = $this->user('siswa2@kuota.local', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Dua', 'email' => 'siswa2@kuota.local',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);
        SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'konsultan_type' => 'psikolog',
            'status' => 'done', 'scheduled_at' => now()->subMonthNoOverflow(),
            'completed_at' => now()->startOfMonth()->addHour(),
        ]);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            ->assertSeeText('2 / 2');
    }
}