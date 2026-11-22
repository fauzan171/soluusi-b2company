<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Package;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * W2 — SEMUA jalur pembuatan sesi wajib lewat QuotaService::reserve
 * (recon-R3 quota hole: startChat:232 / requestConsultation:277).
 */
class QuotaEntryPointsTest extends TestCase
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

    private function fixture(int $quota = 1): array
    {
        // K8: consultant_request_enabled defaults false (W3 lock); tests exercise
        // the unlocked request flow, so fixtures must explicitly enable it.
        $p = Perusahaan::create([
            'name' => 'PT Entry', 'brand' => 'EntryCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'consultant_request_enabled' => true,
        ]);
        $pkg = Package::create(['name' => 'Entry Basic', 'monthly_quota' => $quota, 'is_active' => true]);
        $user = $this->user('emp@entry.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@entry.local', 'consent_at' => now(),
        ]);
        EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
        $docUser = $this->user('doc@entry.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $user, $emp, $docUser, $consultant, $pkg];
    }

    private function exhaustQuota($p, $emp, $consultant): void
    {
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now(),
        ]);
        // represent existing consumption via ledger reserved row (same month)
        \App\Models\Perusahaan\ConsultationQuotaLedger::create([
            'employee_package_id' => \App\Models\Perusahaan\EmployeePackage::where('perusahaan_employee_id', $emp->id)->first()->id,
            'perusahaan_session_id' => PerusahaanSession::latest('id')->first()->id,
            'period_ym' => now()->format('Y-m'),
            'event' => 'reserved',
        ]);
    }

    public function test_request_consultation_rejected_when_quota_exhausted()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture(1);
        $this->exhaustQuota($p, $emp, $consultant);

        $response = $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Saya butuh konsultasi.',
        ]);

        // 422 + pesan package jelas (konsisten error handling requestConsultation)
        $response->assertStatus(422);
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_request_consultation_reserves_ledger_row()
    {
        [$p, $user, $emp, , $consultant] = $this->fixture(2);

        $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Saya butuh konsultasi.',
        ])->assertRedirect();

        $session = PerusahaanSession::first();
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
            'period_ym' => now()->format('Y-m'),
        ]);
    }

    public function test_start_chat_rejected_when_quota_exhausted()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture(1);
        // pakai konsultan LAIN supaya jalur "existing session" tidak keambil
        $otherUser = $this->user('other@entry.local', 'consultant');
        $other = Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $this->exhaustQuota($p, $emp, $other);

        $response = $this->actingAs($user)
            ->post(route('perusahaan.consultants.start-chat', $consultant));

        $response->assertRedirect(route('perusahaan.employee'))
            ->assertSessionHas('error');
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_booking_reserves_ledger_row_in_same_transaction()
    {
        [$p, $user, $emp, , $consultant] = $this->fixture(2);
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = PerusahaanSession::first();
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
        ]);
    }

    public function test_booking_rejected_when_ledger_exhausted()
    {
        [$p, $user, $emp, , $consultant] = $this->fixture(1);
        $this->exhaustQuota($p, $emp, $consultant);
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertStatus(403);

        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_session_end_writes_used_event()
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture(2);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        \App\Services\QuotaService::reserve($emp, $session);

        $this->actingAs($user)->postJson(route('perusahaan.chat.end', $session))
            ->assertRedirect();

        $session->refresh();
        $this->assertSame('done', $session->status);
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'used',
        ]);
    }
}
