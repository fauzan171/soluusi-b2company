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

// T6.2 / ADR 001 (opsi A): maksimal 3 jadwal total per sesi perusahaan.
class MaxSchedulesPerSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo',
            'sessions_per_employee_month' => 10,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'Max',
            'username' => 'emp_max', 'email' => 'emp-max@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp Max', 'email' => 'emp-max@test.local', 'consent_at' => now(),
        ]);

        return [$p, $emp, $user];
    }

    public function test_reschedule_keeps_schedule_version_across_cancel_book(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id, 'konsultan_type' => 'psikolog',
        ])->assertRedirect();
        $this->assertSame(1, $emp->sessions()->first()->schedule_version);

        $target = $slot->replicate();
        $target->started_at = now()->addDays(4);
        $target->ended_at = $target->started_at->copy()->addHour();
        $target->save();

        $this->post(route('perusahaan.booking.reschedule', $emp->sessions()->first()), [
            'schedule_id' => $target->id,
        ])->assertRedirect(route('perusahaan.employee'));

        $new = $emp->sessions()->latest('id')->first();
        $this->assertSame('scheduled', $new->status);
        $this->assertSame(2, $new->schedule_version);
    }

    public function test_fourth_schedule_attempt_rejected_with_422(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        // Sesi sudah di jadwal ke-3 (version == max).
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $slot->consultant_id, 'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $slot->started_at, 'ended_at' => $slot->ended_at,
            'schedule_version' => 3,
        ]);

        $target = $slot->replicate();
        $target->started_at = now()->addDays(5);
        $target->ended_at = $target->started_at->copy()->addHour();
        $target->save();

        $this->actingAs($user)->post(route('perusahaan.booking.reschedule', $session), [
            'schedule_id' => $target->id,
        ])->assertStatus(422);

        $this->assertSame('scheduled', $session->fresh()->status);
        $this->assertSame(3, $session->fresh()->schedule_version);
    }

    public function test_third_schedule_attempt_allowed(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $slot->consultant_id, 'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $slot->started_at, 'ended_at' => $slot->ended_at,
            'schedule_version' => 2,
        ]);

        $target = $slot->replicate();
        $target->started_at = now()->addDays(6);
        $target->ended_at = $target->started_at->copy()->addHour();
        $target->save();

        $this->actingAs($user)->post(route('perusahaan.booking.reschedule', $session), [
            'schedule_id' => $target->id,
        ])->assertRedirect(route('perusahaan.employee'));

        $new = $emp->sessions()->where('status', 'scheduled')->latest('id')->first();
        $this->assertSame(3, $new->schedule_version);
    }
}
