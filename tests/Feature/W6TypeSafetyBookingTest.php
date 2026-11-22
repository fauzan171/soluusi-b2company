<?php

namespace Tests\Feature;

use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * W5×W3 integration (seam ponytail closed): consultation_type booking wajib
 * didukung consultant_work_hours konsultan di pool perusahaan (null = semua).
 */
class W6TypeSafetyBookingTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(?array $workTypes): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT TypeSafe', 'brand' => 'TypeSafeCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'TS',
            'username' => 'emp_ts', 'email' => 'emp@ts.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp TS', 'email' => 'emp@ts.local', 'consent_at' => now(),
        ]);

        $consultant = \App\Models\Consultant::factory()->create(['status' => 'accept', 'is_active' => true]);
        $catId = DB::table('categories')->insertGetId(['name' => 'Psikolog', 'code_category' => Str::random(12), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('consultant_categories')->insert(['consultant_id' => $consultant->id, 'category_id' => $catId, 'created_at' => now(), 'updated_at' => now()]);
        $pool = ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);
        if ($workTypes !== null) {
            ConsultantWorkHour::create([
                'consultant_pool_id' => $pool->id, 'weekday' => 1,
                'start_time' => '09:00', 'end_time' => '17:00',
                'consultation_types' => $workTypes,
            ]);
        }
        $slot = Schedule::factory()->create([
            'consultant_id' => $consultant->id, 'perusahaan_id' => $p->id, 'klinik_id' => null,
            'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(), 'status' => 'confirmed',
        ]);

        return [$p, $emp, $user, $slot];
    }

    public function test_booking_rejected_when_type_not_supported_by_work_hours()
    {
        [$p, $emp, $user, $slot] = $this->fixture(['online_meeting']);

        // Web request: ValidationException → redirect back + session errors.
        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'one_on_one',
        ])->assertInvalid('consultation_type');

        $this->assertEquals(0, $emp->sessions()->count());
    }

    public function test_booking_accepted_when_type_supported()
    {
        [$p, $emp, $user, $slot] = $this->fixture(['online_meeting', 'one_on_one', 'chat']);
        \App\Models\Perusahaan\Room::create(['perusahaan_id' => $p->id, 'name' => 'Ruang A', 'status' => 'active']);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'one_on_one',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertDatabaseHas('perusahaan_sessions', [
            'schedule_id' => $slot->id, 'consultation_type' => 'one_on_one',
        ]);
    }

    public function test_booking_accepted_when_work_hours_null_means_all_types()
    {
        [$p, $emp, $user, $slot] = $this->fixture(null);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'consultation_type' => 'online_meeting',
        ])->assertRedirect(route('perusahaan.employee'));
    }
}
