<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * W2 — QuotaService edge cases (brief: hangus terhitung, refund menambah
 * saldo, pergantian bulan reset, double-reserve idempotent via unique).
 */
class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'employee'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function fixture(int $quota = 2): array
    {
        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => 'PT Quota'.$n, 'brand' => 'QuotaCo'.$n, 'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $pkg = Package::create(['name' => 'Wellness Basic'.$n, 'monthly_quota' => $quota, 'is_active' => true]);
        $user = $this->user("emp{$n}@quota.local");
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp'.$n, 'email' => "emp{$n}@quota.local", 'consent_at' => now(),
        ]);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        return [$p, $pkg, $ep, $emp, $user, $session];
    }

    public function test_reserve_decrements_balance()
    {
        [, , $ep, $emp, , $session] = $this->fixture(2);

        QuotaService::reserve($emp, $session);

        $this->assertSame(1, QuotaService::balanceFor($emp));
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
        ]);
    }

    public function test_reserve_rejects_when_quota_exhausted_with_package_name()
    {
        [$p, , $ep, $emp, , $session] = $this->fixture(1);

        QuotaService::reserve($emp, $session);
        $session2 = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now(),
        ]);

        try {
            QuotaService::reserve($emp, $session2);
            $this->fail('Expected 403 quota exhausted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $pkg = $ep->package;
            $this->assertSame("Kuota bulan ini habis ({$pkg->name} {$pkg->monthly_quota}x).", $e->getMessage());
        }
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $session2->id,
        ]);
    }

    public function test_forfeit_counts_as_consumed()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);

        QuotaService::reserve($emp, $session);
        QuotaService::forfeit($session, 'no_show sweeper');

        // forfeited = terminal, saldo tetap 0 (hangus TERHITUNG terpakai)
        $this->assertSame(0, QuotaService::balanceFor($emp));
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'forfeited',
        ]);
    }

    public function test_refund_restores_balance_and_writes_audit_log()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);
        $hr = $this->user('hr@quota.local', 'hr_admin');

        QuotaService::reserve($emp, $session);
        $ledger = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', 'reserved')->first();

        QuotaService::refund($ledger, $hr, 'kompensasi reschedule salah');

        $this->assertSame(1, QuotaService::balanceFor($emp));
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'refunded',
            'actor_id' => $hr->id, 'reason' => 'kompensasi reschedule salah',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $hr->id, 'action' => 'quota.refund',
            'subject_type' => 'consultation_quota_ledger', 'subject_id' => $ledger->id,
        ]);
    }

    public function test_month_rollover_resets_balance()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);
        // FIX semantik unledgered: sesi fixture tanpa reserve ikut terhitung —
        // batalkan agar tidak polusi saldo bulan berjalan.
        $session->update(['status' => 'cancelled']);

        $this->travelTo(now()->startOfMonth()->subMonth());
        $oldSession = PerusahaanSession::create([
            'perusahaan_id' => $ep->perusahaan_id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);
        QuotaService::reserve($emp, $oldSession);
        $this->assertSame(0, QuotaService::balanceFor($emp));

        // bulan baru: saldo penuh lagi (periode = bulan sesi, calendar month)
        $this->travelBack();
        $this->assertSame(1, QuotaService::balanceFor($emp));
    }

    public function test_double_reserve_is_idempotent_via_unique_constraint()
    {
        [, , $ep, $emp, , $session] = $this->fixture(2);

        QuotaService::reserve($emp, $session);
        // retried request: same session, same event → UNIQUE(session, event) menolak
        QuotaService::reserve($emp, $session);

        $this->assertSame(1, ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', 'reserved')->count());
        $this->assertSame(1, QuotaService::balanceFor($emp));
    }

    public function test_balance_scoped_to_own_employee_package_not_cross_tenant()
    {
        // karyawan PT A tidak bisa konsumsi kuota PT B: ledger scoped via
        // employee_package_id → assignment milik employee sendiri.
        [$pA, , , $empA, , $sessionAfx] = $this->fixture(2);
        [$pB, , , $empB, , $sessionB] = $this->fixture(5);
        // FIX semantik unledgered: sesi fixture tanpa reserve ikut terhitung —
        // batalkan agar saldo keduanya bersih.
        $sessionAfx->update(['status' => 'cancelled']);
        $sessionB->update(['status' => 'cancelled']);

        $sessionA = PerusahaanSession::create([
            'perusahaan_id' => $pA->id, 'employee_id' => $empA->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now(),
        ]);
        QuotaService::reserve($empA, $sessionA);

        $this->assertSame(1, QuotaService::balanceFor($empA));
        $this->assertSame(5, QuotaService::balanceFor($empB));

        $ledgerA = ConsultationQuotaLedger::where('event', 'reserved')->first();
        $this->assertSame($empA->id, $ledgerA->employeePackage->perusahaan_employee_id);
    }

    public function test_legacy_employee_without_package_uses_tenant_default_quota()
    {
        // baseline path: employee tanpa employee_packages row (pre-mission data)
        $p = Perusahaan::create([
            'name' => 'PT Legacy', 'brand' => 'LegacyCo', 'sessions_per_employee_month' => 1,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = $this->user('legacy@quota.local');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Legacy', 'email' => 'legacy@quota.local', 'consent_at' => now(),
        ]);

        $this->assertSame(1, QuotaService::balanceFor($emp));
    }
}
