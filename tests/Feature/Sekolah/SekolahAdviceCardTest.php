<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task C.3: advice card AI di dashboard siswa — siswa hanya lihat client_advice
// milik sesinya sendiri; siswa lain tidak melihat.
class SekolahAdviceCardTest extends TestCase
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

    /** @return array{0: User, 1: SekolahSession, 2: User} */
    private function fixture(): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Advice', 'brand' => 'SMA Advice',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $siswa = $this->user('siswa@adv.test', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $siswa->id,
            'name' => 'Siswa Adv', 'email' => 'siswa@adv.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@adv.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'done',
            'scheduled_at' => now()->subDay(), 'completed_at' => now()->subDay(),
        ]);

        return [$siswa, $session, $sekolah];
    }

    public function test_student_sees_own_advice_card(): void
    {
        [$siswa, $session] = $this->fixture();

        SessionSummary::create([
            'sekolah_session_id' => $session->id,
            'summary' => 'ringkasan konsultan',
            'client_advice' => 'Coba jadwal belajar rutin dan tidur cukup.',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->actingAs($siswa)->get(route('sekolah.student'))
            ->assertOk()
            ->assertSee('Saran dari sesi konseling kamu', false)
            ->assertSee('Coba jadwal belajar rutin dan tidur cukup.', false)
            ->assertDontSee('ringkasan konsultan', false); // summary hanya untuk konsultan
    }

    public function test_other_student_does_not_see_advice(): void
    {
        [$siswa, $session, $sekolah] = $this->fixture();

        SessionSummary::create([
            'sekolah_session_id' => $session->id,
            'summary' => 'x',
            'client_advice' => 'RAHASIA-ADVICE-XYZ',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $other = $this->user('other@adv.test', 'student');
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $other->id,
            'name' => 'Siswa Other', 'email' => 'other@adv.test', 'kelas' => 'X IPA 2',
        ]);

        $this->actingAs($other)->get(route('sekolah.student'))
            ->assertOk()
            ->assertDontSee('RAHASIA-ADVICE-XYZ', false);
    }

    public function test_failed_summary_renders_no_card(): void
    {
        [$siswa, $session] = $this->fixture();

        SessionSummary::create([
            'sekolah_session_id' => $session->id,
            'input_hash' => hash('sha256', 'x'),
            'status' => 'failed',
            'error_code' => 'ai_disabled',
        ]);

        $this->actingAs($siswa)->get(route('sekolah.student'))
            ->assertOk()
            ->assertDontSee('Saran dari sesi konseling kamu', false);
    }
}
