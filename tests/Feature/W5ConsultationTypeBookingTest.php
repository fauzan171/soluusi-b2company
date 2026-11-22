<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// W5: consultation_type split di booking (K9). Fallback: W3 belum merge → semua tipe.
class W5ConsultationTypeBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(int $quota = 2): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT W5', 'brand' => 'W5Co',
            'sessions_per_employee_month' => $quota,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'W5',
            'username' => 'emp_w5', 'email' => 'emp@w5.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp W5', 'email' => 'emp@w5.local', 'consent_at' => now(),
        ]);
        return [$p, $emp, $user];
    }

    public function test_booking_form_lists_three_consultation_types()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->get(route('perusahaan.booking'))
            ->assertOk()
            ->assertSee('name="consultation_type"', false)
            ->assertSee('value="online_meeting"', false)
            ->assertSee('value="chat"', false)
            ->assertSee('value="one_on_one"', false);
    }

    public function test_booking_persists_consultation_type()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'online_meeting',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertDatabaseHas('perusahaan_sessions', [
            'schedule_id' => $slot->id,
            'consultation_type' => 'online_meeting',
        ]);
    }

    public function test_booking_rejects_invalid_consultation_type()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'telepathy',
        ])->assertInvalid('consultation_type');

        $this->assertEquals(0, $emp->sessions()->count());
    }

    public function test_chat_type_booking_defaults_when_omitted()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertDatabaseHas('perusahaan_sessions', [
            'schedule_id' => $slot->id,
            'consultation_type' => 'chat',
        ]);
    }

    public function test_one_on_one_booking_assigns_free_room()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);
        $room = \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $p->id, 'name' => 'Ruang A', 'status' => 'active',
        ]);
        \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $p->id, 'name' => 'Ruang B', 'status' => 'inactive',
        ]);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'one_on_one',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertDatabaseHas('perusahaan_sessions', [
            'schedule_id' => $slot->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_one_on_one_conflict_on_same_room_rejected()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);
        $room = \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $p->id, 'name' => 'Ruang A', 'status' => 'active',
        ]);

        // Sesi existing menempati ruang di jam slot yang sama — konsultan BEDA
        // supaya yang teruji adalah konflik room, bukan konflik jadwal konsultan.
        $otherUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Other', 'lastname' => 'Emp',
            'username' => 'other_w5', 'email' => 'other@w5.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $user->role_id,
        ]);
        $otherEmp = \App\Models\Perusahaan\PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $otherUser->id,
            'name' => 'Other', 'email' => 'other@w5.local', 'consent_at' => now(),
        ]);
        $otherDoc = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Other', 'lastname' => 'Doc',
            'username' => 'otherdoc_w5', 'email' => 'otherdoc@w5.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => DB::table('roles')->where('name', 'consultant')->value('id')
                ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]),
        ]);
        $otherConsultant = \App\Models\Consultant::create([
            'user_id' => $otherDoc->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => \App\Models\Consultant::ACCEPT, 'is_active' => true,
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $otherEmp->id,
            'consultant_id' => $otherConsultant->id,
            'konsultan_type' => 'psikolog', 'consultation_type' => 'one_on_one',
            'room_id' => $room->id, 'status' => 'scheduled',
            'scheduled_at' => $slot->started_at, 'ended_at' => $slot->ended_at,
        ]);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'one_on_one',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('perusahaan_sessions', [
            'schedule_id' => $slot->id,
            'room_id' => $room->id,
        ]);
    }
}
