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

// Task B.1: student self-request konseling — mirror perusahaan F1.1 pattern.
// Siswa kirim request tanpa pilih slot → status 'scheduled' + scheduled_at NULL
// + consultant_id NULL, menunggu review/assign. Paritas dengan employee flow.
class SekolahStudentRequestTest extends TestCase
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

    /** Siswa fixture dengan parent_consent_at terisi (aktif penuh). */
    private function fixture(array $studentOverride = []): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Test', 'brand' => 'SMA Test',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $user = $this->user('siswa@test.local', 'student');
        $student = SekolahStudent::create(array_merge([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Test', 'email' => 'siswa@test.local',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ], $studentOverride));

        return [$sekolah, $user, $student];
    }

    public function test_student_can_request_consultation_pending_review(): void
    {
        [$sekolah, $user, $student] = $this->fixture();

        $response = $this->actingAs($user)->post(route('sekolah.siswa.request'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Cemas ujian',
            'request_notes' => 'Saya sering susah tidur menjelang ujian.',
        ]);

        $response->assertRedirect(route('sekolah.student'));

        $session = SekolahSession::first();
        $this->assertNotNull($session);
        $this->assertSame($student->id, $session->student_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertNull($session->consultant_id);
        $this->assertSame('Saya sering susah tidur menjelang ujian.', $session->request_notes);
        $this->assertSame($user->id, $session->requested_by_user_id);
        $this->assertSame('self', $session->requested_for_role);
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->post(route('sekolah.siswa.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Test',
        ])->assertRedirect('/login');
    }

    public function test_employee_role_forbidden(): void
    {
        $empUser = $this->user('emp@test.local', 'employee');

        $this->actingAs($empUser)->post(route('sekolah.siswa.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Bukan siswa.',
        ])->assertStatus(403);

        $this->assertSame(0, SekolahSession::count());
    }

    public function test_invalid_payload_returns_422(): void
    {
        [, $user] = $this->fixture();

        $response = $this->actingAs($user)->postJson(route('sekolah.siswa.request'), [
            'konsultan_type' => 'dokter-umum', // bukan psikolog|karir
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, SekolahSession::count());
    }

    public function test_student_without_parent_consent_blocked(): void
    {
        [, $user, $student] = $this->fixture(['parent_consent_at' => null]);

        $response = $this->actingAs($user)->post(route('sekolah.siswa.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Izin wali belum ada.',
        ]);

        $this->assertContains($response->status(), [403, 422]);
        $this->assertSame(0, SekolahSession::count());
    }

    public function test_student_can_cancel_own_pending_request(): void
    {
        [$sekolah, $user, $student] = $this->fixture();

        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => null,
            'request_notes' => 'Request sendiri.',
            'requested_by_user_id' => $user->id,
            'requested_for_role' => 'self',
        ]);

        $this->actingAs($user)->post(route('sekolah.siswa.request.cancel', $session))
            ->assertRedirect(route('sekolah.student'));

        $this->assertSame('cancelled', $session->fresh()->status);
    }

    public function test_student_cannot_cancel_other_students_request(): void
    {
        [$sekolah, $user, $student] = $this->fixture();

        $otherUser = $this->user('other@test.local', 'student');
        $otherStudent = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $otherUser->id,
            'name' => 'Siswa Lain', 'email' => 'other@test.local',
            'kelas' => 'X IPA 2', 'parent_consent_at' => now(),
        ]);

        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $otherStudent->id,
            'kelas' => $otherStudent->kelas,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => null,
            'request_notes' => 'Milik siswa lain.',
            'requested_by_user_id' => $otherUser->id,
            'requested_for_role' => 'self',
        ]);

        $this->actingAs($user)->post(route('sekolah.siswa.request.cancel', $session))
            ->assertStatus(403);

        $this->assertSame('scheduled', $session->fresh()->status);
    }

    public function test_student_sees_pending_request_card_on_dashboard(): void
    {
        [$sekolah, $user, $student] = $this->fixture();

        SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => null,
            'request_notes' => 'Tolong dibantu.',
            'requested_by_user_id' => $user->id,
            'requested_for_role' => 'self',
        ]);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            ->assertSee('Permintaan konseling kamu sedang diproses')
            ->assertSee('Batalkan permintaan');
    }
}
