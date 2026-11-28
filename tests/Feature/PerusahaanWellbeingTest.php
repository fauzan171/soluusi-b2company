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

// ponytail: satu check per logika non-trivial — formula WHO-5 + enforcement kuota booking.
class PerusahaanWellbeingTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(int $quota = 2): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo',
            'sessions_per_employee_month' => $quota,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'Test',
            'username' => 'emp_test', 'email' => 'emp@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp Test', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        return [$p, $emp, $user];
    }

    public function test_assessment_score_uses_who5_formula()
    {
        // B1: legacy WHO-5 path (NULL instrument_code) dihapus — formula
        // WHO-5 kini lewat engine assignment-driven (contoh D8: perusahaan
        // tanpa program dapat legacy who5 assignment).
        [, $emp, $user] = $this->makeEmployee();
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);
        $a = \App\Models\AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $emp->perusahaan_id,
            'instrument_code' => 'who5', 'audience' => 'employee', 'source' => 'legacy',
            'opens_at' => today()->subDay(),
        ]);

        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => ['W1' => 3, 'W2' => 4, 'W3' => 2, 'W4' => 5, 'W5' => 1], // sum 15 × 4 = 60
        ])->assertRedirect();

        $row = $emp->assessments()->first();
        $this->assertNotNull($row);
        $this->assertEquals(60, $row->score);
        $this->assertEquals('Perlu Perhatian', $row->band());
        // legacy (open-ended) row tanpa assignment_id — retake tiap 30 hari.
        $this->assertNull($row->assessment_assignment_id);
    }

    public function test_booking_rejected_when_quota_full()
    {
        // Quota is counted per calendar month; pin mid-month so now+2d and now+3d never straddle a boundary.
        $this->travelTo(now()->startOfMonth()->addDays(10)->setTime(9, 0));
        [$p, $emp, $user] = $this->makeEmployee(1);

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $slot = $this->programSlot('perusahaan', $p->id);
        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertStatus(403);

        $this->assertEquals(1, $emp->sessions()->count());
    }

    public function test_booking_creates_scheduled_session_within_quota()
    {
        [$company, $emp, $user] = $this->makeEmployee(2);
        $slot = $this->programSlot('perusahaan', $company->id, 'nutrisionis');

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'nutrisionis',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('perusahaan.employee'));

        $ses = $emp->sessions()->first();
        $this->assertEquals('scheduled', $ses->status);
        $this->assertEquals('nutrisionis', $ses->konsultan_type);
    }
    public function test_schedule_booking_reschedule_and_cutoff_regression(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00:00'));
        [$company, $employee, $user] = $this->makeEmployee(1);
        $slot = $this->programSlot('perusahaan', $company->id);
        $this->actingAs($user)->get(route('perusahaan.booking'))->assertOk()->assertSee('name="schedule_id"', false);
        $this->post(route('perusahaan.booking.store'), ['schedule_id' => $slot->id, 'konsultan_type' => 'psikolog'])->assertRedirect(route('perusahaan.employee'));
        $old = $employee->sessions()->first();
        $target = $slot->replicate();
        $target->started_at = now()->addMonth()->addDays(3);
        $target->ended_at = $target->started_at->copy()->addHour();
        $target->save();
        $this->post(route('perusahaan.booking.reschedule', $old), ['schedule_id' => $target->id])->assertRedirect(route('perusahaan.employee'));
        $this->assertSame('cancelled', $old->fresh()->status);
        $new = $employee->sessions()->latest('id')->first();
        $this->assertEquals($target->id, $new->schedule_id);
        $this->assertTrue($new->ended_at->eq($target->ended_at));
        $this->assertDatabaseMissing('booking_claims', ['schedule_id' => $slot->id]);
        $this->assertDatabaseHas('booking_claims', ['schedule_id' => $target->id, 'owner_id' => $new->id]);
        $this->travelTo($target->started_at->copy()->subHours(2));
        $this->post(route('perusahaan.booking.cancel', $new), ['reason' => 'Test'])->assertSessionHas('success', 'Sesi dibatalkan. Kuota tetap terpakai karena melewati batas pembatalan.');
        $this->assertTrue((bool) $new->fresh()->quota_consumed);
        $this->assertDatabaseMissing('booking_claims', ['schedule_id' => $target->id]);
        $this->travelBack();
    }

}
