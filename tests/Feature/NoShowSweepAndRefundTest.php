<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * W2 — sweeper no-show + refund route HR (audit log) + UI label.
 */
class NoShowSweepAndRefundTest extends TestCase
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
        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => 'PT Sweep'.$n, 'brand' => 'SweepCo'.$n, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $pkg = Package::create(['name' => 'Wellness Basic', 'monthly_quota' => 2, 'is_active' => true]);
        $user = $this->user("emp{$n}@sweep.local", 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => "emp{$n}@sweep.local", 'consent_at' => now(),
        ]);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        return [$p, $pkg, $ep, $emp, $user];
    }

    private function makeSession($p, $emp, $scheduledAt): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function test_sweeper_marks_stale_scheduled_session_no_show_and_forfeits()
    {
        [$p, , $ep, $emp, ] = $this->fixture();
        $stale = $this->makeSession($p, $emp, now()->subHours(25));
        $fresh = $this->makeSession($p, $emp, now()->addDay());

        \App\Models\Perusahaan\ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $stale->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);

        $this->assertSame('no_show', $stale->fresh()->status);
        $this->assertSame('scheduled', $fresh->fresh()->status);
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $stale->id, 'event' => 'forfeited',
        ]);
    }

    public function test_sweeper_ignores_awaiting_acc_sessions_without_schedule()
    {
        // requestConsultation menunggu ACC: scheduled_at NULL, belum ada jadwal
        // untuk dilewatkan — bukan no-show.
        [$p, , , $emp, ] = $this->fixture();
        $awaiting = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);

        $this->assertSame('scheduled', $awaiting->fresh()->status);
    }

    public function test_sweeper_is_idempotent()
    {
        [$p, , $ep, $emp, ] = $this->fixture();
        $stale = $this->makeSession($p, $emp, now()->subDays(3));
        \App\Models\Perusahaan\ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $stale->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);
        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);

        $this->assertDatabaseCount('consultation_quota_ledger', 2); // reserved + forfeited (sekali)
        $this->assertSame(1, ConsultationQuotaLedger::where('perusahaan_session_id', $stale->id)
            ->where('event', 'forfeited')->count());
    }

    public function test_hr_can_refund_ledger_with_reason_and_audit_log()
    {
        [$p, , $ep, $emp, $user] = $this->fixture();
        $hr = $this->user('hr@sweep.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id,
        ]);
        $session = $this->makeSession($p, $emp, now()->subDays(2));
        $ledger = ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        // reason wajib
        $this->actingAs($hr)->postJson(route('perusahaan.quota.refund', $ledger))
            ->assertStatus(422);

        $this->actingAs($hr)->postJson(route('perusahaan.quota.refund', $ledger), [
            'reason' => 'kompensasi gangguan sistem',
        ])->assertRedirect();

        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'refunded',
            'actor_id' => $hr->id, 'reason' => 'kompensasi gangguan sistem',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $hr->id, 'action' => 'quota.refund',
            'subject_type' => 'consultation_quota_ledger', 'subject_id' => $ledger->id,
        ]);
    }

    public function test_employee_cannot_access_refund_route()
    {
        [$p, , $ep, $emp, $user] = $this->fixture();
        $session = $this->makeSession($p, $emp, now()->subDay());
        $ledger = ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->actingAs($user)->postJson(route('perusahaan.quota.refund', $ledger), [
            'reason' => 'coba-coba',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('consultation_quota_ledger', ['event' => 'refunded']);
    }

    public function test_refund_cannot_cross_tenant()
    {
        // HR PT A tidak bisa refund ledger employee PT B
        [$pA, , $epA, $empA, ] = $this->fixture();
        [$pB, , , $empB, ] = $this->fixture();
        $hrA = $this->user('hrA@sweep.local', 'hr_admin');
        // hubungkan HR ke PT A
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $pA->id, 'user_id' => $hrA->id,
        ]);

        $sessionB = $this->makeSession($pB, $empB, now()->subDay());
        $ledgerB = ConsultationQuotaLedger::create([
            'employee_package_id' => \App\Models\Perusahaan\EmployeePackage::where('perusahaan_employee_id', $empB->id)->first()->id,
            'perusahaan_session_id' => $sessionB->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->actingAs($hrA)->postJson(route('perusahaan.quota.refund', $ledgerB), [
            'reason' => 'cross-tenant attempt',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('consultation_quota_ledger', ['event' => 'refunded']);
    }

    public function test_refund_cannot_double_refund_same_session()
    {
        [$p, , $ep, $emp, ] = $this->fixture();
        $hr = $this->user('hr2@sweep.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id,
        ]);
        $session = $this->makeSession($p, $emp, now()->subDay());
        $ledger = ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->actingAs($hr)->postJson(route('perusahaan.quota.refund', $ledger), ['reason' => 'refund 1'])->assertRedirect();
        $this->actingAs($hr)->postJson(route('perusahaan.quota.refund', $ledger), ['reason' => 'refund 2'])
            ->assertStatus(422);
    }

    public function test_hr_roster_shows_package_and_remaining_quota()
    {
        [$p, , $ep, $emp, ] = $this->fixture();
        $hr = $this->user('hrroster@sweep.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id,
        ]);
        $session = $this->makeSession($p, $emp, now()->subDay());
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $res = $this->actingAs($hr)->get(route('perusahaan.employees'));

        $res->assertOk()
            ->assertSee('Wellness Basic')
            ->assertSee('1 / 2'); // sisa 1 dari 2 — count only, no session content
    }

    public function test_employee_dashboard_shows_package_badge_and_remaining()
    {
        [$p, , $ep, $emp, $user] = $this->fixture();
        $session = $this->makeSession($p, $emp, now()->addDay());
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $res = $this->actingAs($user)->get(route('perusahaan.employee'));

        $res->assertOk()
            ->assertSee('Wellness Basic')
            ->assertSee('1');
    }
}
