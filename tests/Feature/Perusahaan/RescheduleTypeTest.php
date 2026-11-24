<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Perusahaan\Room;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit UX perusahaan item 3: BookingService::reschedule tidak meneruskan
// consultation_type ke book() → one_on_one/online_meeting senyap jatuh ke
// default 'chat' dan room otomatis hilang.
class RescheduleTypeTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Resched', 'brand' => 'RS',
            'sessions_per_employee_month' => 4, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'R',
            'username' => 'emp_rs', 'email' => 'emp@rs.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp R', 'email' => 'emp@rs.local', 'consent_at' => now(),
        ]);

        return [$p, $emp, $user];
    }

    public function test_reschedule_preserves_consultation_type_and_room(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot1 = $this->programSlot('perusahaan', $p->id);
        $slot2 = $this->programSlot('perusahaan', $p->id);
        $room = Room::create(['perusahaan_id' => $p->id, 'name' => 'Ruang RS', 'status' => 'active']);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'consultation_type' => 'one_on_one',
            'status' => 'scheduled', 'schedule_version' => 0,
            'scheduled_at' => $slot1->started_at, 'ended_at' => $slot1->ended_at,
        ]);
        unset($session);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot1->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'one_on_one',
        ])->assertRedirect(route('perusahaan.employee'));

        $booked = PerusahaanSession::where('employee_id', $emp->id)->where('schedule_id', $slot1->id)->firstOrFail();
        $this->assertSame('one_on_one', $booked->consultation_type);
        $this->assertSame($room->id, $booked->room_id);

        // Reschedule ke slot2 (konsultan lain, sama tipe) — consultation_type
        // harus dipertahankan, room di-assign ulang otomatis.
        $this->actingAs($user)->post(route('perusahaan.booking.reschedule', $booked), [
            'schedule_id' => $slot2->id,
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertDatabaseHas('perusahaan_sessions', [
            'schedule_id' => $slot2->id,
            'consultation_type' => 'one_on_one',
        ]);
        $rescheduled = PerusahaanSession::where('employee_id', $emp->id)->where('schedule_id', $slot2->id)->firstOrFail();
        $this->assertSame('one_on_one', $rescheduled->consultation_type);
        $this->assertNotNull($rescheduled->room_id, 'Room harus ter-assign ulang untuk one_on_one setelah reschedule.');
        $this->assertSame('cancelled', $booked->fresh()->status);
    }
}
