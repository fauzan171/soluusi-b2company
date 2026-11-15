<?php

namespace Tests\Unit;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Support\SessionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SessionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): PerusahaanEmployee
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'E', 'lastname' => 'T',
            'username' => 'emp-sm', 'email' => 'emp-sm@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $p = Perusahaan::create([
            'name' => 'PT SM', 'brand' => 'SMCo', 'sessions_per_employee_month' => 5,
            'employee_count' => 3, 'is_active' => true,
        ]);

        return PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp-sm@test.local', 'consent_at' => now(),
        ]);
    }

    private function consultant(): Consultant
    {
        $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'C', 'lastname' => 'T',
            'username' => 'doc-sm', 'email' => 'doc-sm@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);

        return Consultant::create([
            'user_id' => $user->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
    }

    private function makeSession(PerusahaanEmployee $emp, array $attrs = []): PerusahaanSession
    {
        return PerusahaanSession::create(array_merge([
            'perusahaan_id' => $emp->perusahaan_id,
            'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => null,
            'consultant_id' => null,
        ], $attrs));
    }

    private function expect422(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected 422 HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_assign_pending_review_session(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp);

        SessionStateMachine::assign($s, $consultant->id);

        $this->assertSame($consultant->id, $s->fresh()->consultant_id);
        $this->assertSame('scheduled', $s->fresh()->status);
    }

    public function test_assign_rejects_already_scheduled_session(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp, ['scheduled_at' => now()->addDay()]);

        $this->expect422(fn () => SessionStateMachine::assign($s, 1));
    }

    public function test_assign_rejects_cancelled_session(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp, ['status' => 'cancelled']);

        $this->expect422(fn () => SessionStateMachine::assign($s, 1));
    }

    public function test_schedule_requires_assignment(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp); // consultant NULL

        $this->expect422(fn () => SessionStateMachine::schedule($s, now()->addDay(), now()->addDay()->addHour()));
    }

    public function test_schedule_assigned_session(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp, ['consultant_id' => $consultant->id]);

        $start = now()->addDay()->setTime(9, 0);
        $end = $start->copy()->addHour();
        SessionStateMachine::schedule($s, $start, $end);

        $fresh = $s->fresh();
        $this->assertEquals($start->toDateTimeString(), $fresh->scheduled_at->toDateTimeString());
        $this->assertEquals($end->toDateTimeString(), $fresh->ended_at->toDateTimeString());
    }

    public function test_schedule_rejects_already_booked_session(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp, [
            'consultant_id' => $consultant->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->expect422(fn () => SessionStateMachine::schedule($s, now()->addDays(2), now()->addDays(2)->addHour()));
    }

    public function test_cancel_scheduled_session_restores_quota(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp, ['scheduled_at' => now()->addDay()]);

        SessionStateMachine::cancel($s, 'Test cancel');

        $fresh = $s->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertFalse((bool) $fresh->quota_consumed);
        $this->assertSame('Test cancel', $fresh->cancel_reason);
        $this->assertNotNull($fresh->cancelled_at);
    }

    public function test_cancel_without_restore_keeps_quota_consumed(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp, ['scheduled_at' => now()->addHour()]);

        SessionStateMachine::cancel($s, 'Late cancel', restoreQuota: false);

        $this->assertTrue((bool) $s->fresh()->quota_consumed);
    }

    public function test_schedule_sets_version_one(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp, ['consultant_id' => $consultant->id]);

        SessionStateMachine::schedule($s, now()->addDay(), now()->addDay()->addHour());

        $this->assertSame(1, $s->fresh()->schedule_version);
    }

    public function test_reschedule_increments_version_up_to_max(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp, [
            'consultant_id' => $consultant->id,
            'scheduled_at' => now()->addDay(),
            'schedule_version' => 1,
        ]);

        SessionStateMachine::reschedule($s, now()->addDays(2), now()->addDays(2)->addHour());
        $this->assertSame(2, $s->fresh()->schedule_version);

        SessionStateMachine::reschedule($s->fresh(), now()->addDays(3), now()->addDays(3)->addHour());
        $this->assertSame(3, $s->fresh()->schedule_version);
    }

    public function test_reschedule_beyond_max_throws_422(): void
    {
        $emp = $this->employee();
        $consultant = $this->consultant();
        $s = $this->makeSession($emp, [
            'consultant_id' => $consultant->id,
            'scheduled_at' => now()->addDay(),
            'schedule_version' => 3, // max (ADR 001: total 3 jadwal)
        ]);

        $this->expect422(fn () => SessionStateMachine::reschedule($s, now()->addDays(2), now()->addDays(2)->addHour()));
        $this->assertSame(3, $s->fresh()->schedule_version);
    }

    public function test_reschedule_requires_scheduled_session(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp); // scheduled_at NULL

        $this->expect422(fn () => SessionStateMachine::reschedule($s, now()->addDay(), now()->addDay()->addHour()));
    }

    public function test_cancel_forbidden_from_done(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp, ['status' => 'done', 'completed_at' => now()]);

        $this->expect422(fn () => SessionStateMachine::cancel($s, 'Nope'));
        $this->assertSame('done', $s->fresh()->status);
    }

    public function test_cancel_forbidden_twice(): void
    {
        $emp = $this->employee();
        $s = $this->makeSession($emp);

        SessionStateMachine::cancel($s, 'First');

        $this->expect422(fn () => SessionStateMachine::cancel($s->fresh(), 'Second'));
    }
}
