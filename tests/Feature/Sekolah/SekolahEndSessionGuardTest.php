<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G.7 (sekolah): sesi SAP yang sudah di-assign konsultan tapi BELUM dijadwalkan
 * (scheduled_at NULL) tidak boleh diakhiri. End prematur = komisi SAP + summary
 * + kuota terbakar untuk konsultasi yang tidak pernah terjadi.
 */
class SekolahEndSessionGuardTest extends TestCase
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

    /** @return array{0: Sekolah, 1: User(siswa), 2: User(konsultan), 3: SekolahSession} */
    private function fixture(bool $scheduled = true): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Guard', 'brand' => 'SMA Guard', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $siswa = $this->user('siswa@test.local', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $siswa->id,
            'name' => 'Siswa', 'email' => 'siswa@test.local', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $scheduled ? now()->addDay() : null,
        ]);

        return [$sekolah, $siswa, $docUser, $session];
    }

    public function test_consultant_cannot_end_assigned_but_unscheduled_session(): void
    {
        [, , $docUser, $session] = $this->fixture(scheduled: false);

        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.end', $session))
            ->assertStatus(422);

        $session->refresh();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->completed_at);
        $this->assertSame(0, SekolahPervorma::count());
    }

    public function test_student_cannot_end_assigned_but_unscheduled_session(): void
    {
        [, $siswa, , $session] = $this->fixture(scheduled: false);

        $this->actingAs($siswa)->post(route('sekolah.chat.end', $session))
            ->assertStatus(422);

        $session->refresh();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->completed_at);
        $this->assertSame(0, SekolahPervorma::count());
    }

    public function test_staff_cannot_end_assigned_but_unscheduled_session(): void
    {
        [$sekolah, , $docUser, ] = $this->fixture(scheduled: false);
        $staff = $this->user('staff@test.local', 'sekolah_staff');
        \App\Models\Sekolah\SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $staff->id, 'role' => 'sekolah_staff',
        ]);
        $person = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $staff->id,
            'name' => 'Staf', 'email' => 'staff@test.local', 'type' => 'staff',
            'kelas' => 'Guru', 'consent_at' => now(),
        ]);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $person->id,
            'kelas' => 'Guru', 'consultant_id' => null,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
        ]);

        $this->actingAs($staff)->post(route('sekolah.staff.chat.end', $session))
            ->assertStatus(422);

        $this->assertSame('scheduled', $session->refresh()->status);
        $this->assertSame(0, SekolahPervorma::count());
    }

    public function test_consultant_can_end_a_scheduled_session(): void
    {
        [, , $docUser, $session] = $this->fixture(scheduled: true);

        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.end', $session))
            ->assertRedirect();

        $this->assertSame('done', $session->refresh()->status);
        $this->assertNotNull($session->completed_at);
    }
}