<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * E.2 blocker: request SAP yang sudah di-ACC BK (consultant_id terisi,
 * scheduled_at NULL) tidak pernah bisa dijadwalkan — chat terkunci permanen
 * dan sweep stale tidak menyentuhnya (butuh consultant_id NULL).
 * Konsultan sekarang bisa set jadwal dari portal sesinya.
 */
class SekolahScheduleSekolahTest extends TestCase
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

    /** @return array{0: SekolahSession, 1: User, 2: Schedule} */
    private function fixture(): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Jadwal', 'brand' => 'SMA Jadwal', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa J',
            'email' => 'siswa@jadwal.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@jadwal.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id,
        ]);

        // Request yang sudah di-ACC BK: consultant assigned, jadwal belum di-set.
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
        ]);

        $slot = Schedule::create([
            'sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id,
            'started_at' => now()->addDays(2), 'ended_at' => now()->addDays(2)->addHour(),
            'status' => Schedule::STATUS_CONFIRMED, 'mode' => 'online',
        ]);

        return [$session, $docUser, $slot];
    }

    public function test_consultant_can_schedule_approved_sap_request(): void
    {
        [$session, $docUser, $slot] = $this->fixture();

        $this->actingAs($docUser)
            ->post(route('consultant.sekolah.schedule', $session), ['schedule_id' => $slot->id])
            ->assertRedirect();

        $fresh = $session->fresh();
        $this->assertNotNull($fresh->scheduled_at, 'Konsultan set jadwal harus mengisi scheduled_at.');
        $this->assertSame($slot->id, $fresh->schedule_id);

        // Inti blocker: chat sekarang bisa dibuka (policy butuh scheduled_at).
        $this->actingAs($docUser)->get(route('consultant.sekolah.chat', $session))->assertOk();
    }

    public function test_other_consultant_cannot_schedule(): void
    {
        [$session, , $slot] = $this->fixture();
        $otherUser = $this->user('other@jadwal.test', 'consultant');
        $other = Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $otherSlot = Schedule::create([
            'sekolah_id' => $session->sekolah_id, 'consultant_id' => $other->id,
            'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(),
            'status' => Schedule::STATUS_CONFIRMED, 'mode' => 'online',
        ]);

        $this->actingAs($otherUser)
            ->post(route('consultant.sekolah.schedule', $session), ['schedule_id' => $otherSlot->id])
            ->assertStatus(403);

        $this->assertNull($session->fresh()->scheduled_at);
    }

    public function test_already_scheduled_session_is_rejected(): void
    {
        [$session, $docUser, $slot] = $this->fixture();
        $session->update(['scheduled_at' => now()->addDay()]);

        $this->actingAs($docUser)
            ->post(route('consultant.sekolah.schedule', $session), ['schedule_id' => $slot->id])
            ->assertStatus(422);
    }

    public function test_consultant_index_lists_unregistered_school_sessions(): void
    {
        [$session, $docUser] = $this->fixture();

        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee('Sesi sekolah (SAP)', false);

        $this->assertNull($session->scheduled_at);
    }

    public function test_set_schedule_select_only_lists_own_slots(): void
    {
        [$session, $docUser, $slot] = $this->fixture();
        $otherUser = $this->user('other@jadwal.test', 'consultant');
        $other = Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'sekolah_id' => $session->sekolah_id,
            'consultant_id' => $other->id,
        ]);
        $foreign = Schedule::create([
            'sekolah_id' => $session->sekolah_id, 'consultant_id' => $other->id,
            'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(),
            'status' => Schedule::STATUS_CONFIRMED, 'mode' => 'online',
        ]);

        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee('value="'.$slot->id.'"', false)
            ->assertDontSee('value="'.$foreign->id.'"', false);

        $slot->delete();
        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee('Belum ada slot kosong kamu');
    }
}
