<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequestConsultationTest extends TestCase
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
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $user = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'perusahaan_id' => $p->id,
            'consultant_id' => $consultant->id,
        ]);

        return [$p, $user, $emp, $docUser, $consultant];
    }

    public function test_employee_can_request_consultation_and_get_assigned_consultant()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Stress',
            'body' => 'Saya butuh konsultasi.',
        ]);

        $session = PerusahaanSession::first();
        $response->assertRedirect();
        $this->assertSame($consultant->id, $session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertSame(1, $session->chatMessages()->count());
        $this->assertSame('Saya butuh konsultasi.', $session->chatMessages()->first()->body);
    }

    public function test_employee_cannot_request_if_quota_exceeded()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();
        $p->update(['sessions_per_employee_month' => 1]);

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Saya butuh konsultasi lagi.',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_employee_cannot_request_if_no_consultant_in_pool()
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $user = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Tidak ada konsultan.',
        ]);

        $response->assertStatus(422);
    }

    public function test_consultant_can_schedule_unscheduled_session()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $slot = \App\Models\Schedule::create([
            'consultant_id' => $consultant->id, 'started_at' => now()->addDay(),
            'ended_at' => now()->addDay()->addHour(), 'is_open' => true,
        ]);

        $this->actingAs($docUser)->postJson(route('consultant.sessions.schedule', $session), [
            'schedule_id' => $slot->id,
        ])->assertRedirect();

        $session->refresh();
        $this->assertSame($slot->id, $session->schedule_id);
        $this->assertSame($slot->started_at->toDateTimeString(), $session->scheduled_at->toDateTimeString());
    }

    public function test_consultant_cannot_schedule_already_scheduled_session()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $slot = \App\Models\Schedule::create([
            'consultant_id' => $consultant->id, 'started_at' => now()->addDays(2),
            'ended_at' => now()->addDays(2)->addHour(), 'is_open' => true,
        ]);

        $this->actingAs($docUser)->postJson(route('consultant.sessions.schedule', $session), [
            'schedule_id' => $slot->id,
        ])->assertStatus(422);
    }
}
