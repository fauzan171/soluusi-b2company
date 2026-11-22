<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// W5: meeting link override (K10, research-E gap) + room CRUD admin perusahaan.
class W5MeetingLinkRoomTest extends TestCase
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

    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT W5 Meet', 'brand' => 'W5Meet', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $hr = $this->user('hr@w5meet.local', 'hr_admin');
        PerusahaanAdmin::create([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'name' => 'HR', 'email' => 'hr@w5meet.local',
        ]);
        $empUser = $this->user('emp@w5meet.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@w5meet.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@w5meet.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        return [$p, $hr, $empUser, $emp, $docUser, $consultant];
    }

    private function makeSession($p, $emp, $consultant, array $attrs = []): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
            ...$attrs,
        ]);
    }

    // --- Meeting link override ---

    public function test_consultant_sets_meeting_link()
    {
        [$p, , , $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->makeSession($p, $emp, $consultant, ['consultation_type' => 'online_meeting']);

        $this->actingAs($docUser)->postJson(route('consultant.sessions.meeting-link', $session), [
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ])->assertRedirect();

        $this->assertDatabaseHas('perusahaan_sessions', [
            'id' => $session->id, 'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ]);
    }

    public function test_hr_sets_meeting_link()
    {
        [$p, $hr, , $emp, , $consultant] = $this->fixture();
        $session = $this->makeSession($p, $emp, $consultant, ['consultation_type' => 'online_meeting']);

        $this->actingAs($hr)->postJson(route('perusahaan.sessions.meeting-link', $session), [
            'meeting_url' => 'https://zoom.us/j/1234567890',
        ])->assertRedirect();

        $this->assertDatabaseHas('perusahaan_sessions', [
            'id' => $session->id, 'meeting_url' => 'https://zoom.us/j/1234567890',
        ]);
    }

    public function test_meeting_link_rejects_non_meeting_url()
    {
        [$p, , , $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->makeSession($p, $emp, $consultant, ['consultation_type' => 'online_meeting']);

        $this->actingAs($docUser)->postJson(route('consultant.sessions.meeting-link', $session), [
            'meeting_url' => 'http://meet.google.com/abc', // bukan https
        ])->assertInvalid('meeting_url');

        $this->actingAs($docUser)->postJson(route('consultant.sessions.meeting-link', $session), [
            'meeting_url' => 'https://example.com/whatever',
        ])->assertInvalid('meeting_url');
    }

    public function test_employee_cannot_set_meeting_link()
    {
        [$p, , $empUser, $emp, , $consultant] = $this->fixture();
        $session = $this->makeSession($p, $emp, $consultant, ['consultation_type' => 'online_meeting']);

        $this->actingAs($empUser)->postJson(route('perusahaan.sessions.meeting-link', $session), [
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ])->assertStatus(403);
    }

    public function test_hr_cannot_set_link_on_other_tenant_session()
    {
        [$p, $hr, , $emp, , $consultant] = $this->fixture();
        $other = Perusahaan::create([
            'name' => 'PT Other', 'brand' => 'OtherCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 1, 'is_active' => true,
        ]);
        $session = $this->makeSession($other, $emp, $consultant, ['consultation_type' => 'online_meeting']);

        $this->actingAs($hr)->postJson(route('perusahaan.sessions.meeting-link', $session), [
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ])->assertStatus(403);
    }

    // --- Room CRUD (HR admin perusahaan) ---

    public function test_hr_can_create_and_list_rooms()
    {
        [$p, $hr] = $this->fixture();

        $this->actingAs($hr)->post(route('perusahaan.rooms.store'), [
            'name' => 'Ruang Konseling 1',
        ])->assertRedirect(route('perusahaan.rooms.index'));

        $this->assertDatabaseHas('rooms', ['perusahaan_id' => $p->id, 'name' => 'Ruang Konseling 1', 'status' => 'active']);
    }

    public function test_room_validation_requires_name()
    {
        [, $hr] = $this->fixture();

        $this->actingAs($hr)->post(route('perusahaan.rooms.store'), ['name' => ''])->assertInvalid('name');
    }

    public function test_hr_can_toggle_room_status()
    {
        [$p, $hr] = $this->fixture();
        $room = \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $p->id, 'name' => 'Ruang A', 'status' => 'active',
        ]);

        $this->actingAs($hr)->post(route('perusahaan.rooms.toggle', $room))->assertRedirect(route('perusahaan.rooms.index'));
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'inactive']);
    }

    public function test_employee_cannot_manage_rooms()
    {
        [$p, , $empUser] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.rooms.store'), ['name' => 'X'])
            ->assertStatus(403);
    }

    public function test_hr_cannot_toggle_other_tenant_room()
    {
        [$p, $hr] = $this->fixture();
        $other = Perusahaan::create([
            'name' => 'PT Other', 'brand' => 'OtherCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 1, 'is_active' => true,
        ]);
        $room = \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $other->id, 'name' => 'Ruang Lain', 'status' => 'active',
        ]);

        $this->actingAs($hr)->post(route('perusahaan.rooms.toggle', $room))->assertStatus(403);
    }
}
