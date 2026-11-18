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

    public function test_employee_can_request_consultation_and_enters_hr_review()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Stress',
            'body' => 'Saya butuh konsultasi.', 'mode' => 'online',
        ]);

        $session = PerusahaanSession::first();
        $response->assertRedirect(route('perusahaan.employee'));
        // F1.1: konsultan belum di-assign — menunggu review HR.
        $this->assertNull($session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertSame(0, $session->chatMessages()->count());
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
            'body' => 'Saya butuh konsultasi lagi.', 'mode' => 'online',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_employee_cannot_request_while_awaiting_acc()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Request kedua saat masih menunggu ACC.', 'mode' => 'online',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_employee_can_request_even_if_pool_empty_reviewed_by_hr()
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

        // Pool kosong bukan masalah di sisi employee — request masuk antrean HR.
        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Tidak ada konsultan.', 'mode' => 'online',
        ]);

        $response->assertRedirect(route('perusahaan.employee'));
        $this->assertNull(PerusahaanSession::first()->consultant_id);
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

        // Setelah ACC (scheduled_at terisi), karyawan bisa kirim pesan.
        $this->actingAs($user)->postJson(route('perusahaan.chat.store', $session), ['body' => 'Terima kasih dok'])
            ->assertOk();
        $this->assertSame(1, $session->chatMessages()->count());
    }

    public function test_employee_sees_pending_request_card_on_dashboard()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->actingAs($user)->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi konsultan')
            ->assertSee('Batalkan permintaan');
    }

    public function test_employee_can_cancel_pending_request_and_quota_restored()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->actingAs($user)->post(route('perusahaan.request.cancel', $session))
            ->assertRedirect(route('perusahaan.employee'));

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertFalse((bool) $session->quota_consumed);

        // Kuota kembali: request baru bisa dibuat lagi.
        $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Request baru setelah cancel.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));
    }

    public function test_employee_cannot_cancel_pending_request_of_other_employee()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $otherUser = $this->user('other@test.local', 'employee');
        $otherEmp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $otherUser->id,
            'name' => 'Other', 'email' => 'other@test.local', 'consent_at' => now(),
        ]);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $otherEmp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->actingAs($user)->post(route('perusahaan.request.cancel', $session))
            ->assertStatus(403);

        $this->assertSame('scheduled', $session->fresh()->status);
    }

    public function test_employee_cannot_cancel_already_scheduled_session_via_pending_route()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('perusahaan.request.cancel', $session))
            ->assertStatus(422);
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
