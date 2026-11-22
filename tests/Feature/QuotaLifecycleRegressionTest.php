<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Services\BookingService;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi review-fix (B1/B2/M3): lifecycle kuota penuh dengan headroom —
 * pernah double-count (1 sesi selesai = 2 kuota) dan cancel tidak release.
 * Fix: outflow per-SESI + release di early-cancel + pre-mission sessions
 * dihitung sebagai terpakai.
 */
class QuotaLifecycleRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(int $quota): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Life', 'brand' => 'LifeCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'E', 'lastname' => 'L',
            'username' => Str::slug('e@life.local'), 'email' => 'e@life.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'E L', 'email' => 'e@life.local', 'consent_at' => now(),
        ]);
        $pkg = Package::create(['name' => 'Life '.$quota, 'monthly_quota' => $quota, 'is_active' => true]);
        $ep = EmployeePackage::create([
            'perusahaan_id' => $p->id, 'perusahaan_employee_id' => $emp->id,
            'package_id' => $pkg->id, 'starts_at' => now()->startOfMonth()->toDateString(), 'status' => 'active',
        ]);

        return [$p, $emp, $user, $ep];
    }

    private function makeSession(Perusahaan $p, PerusahaanEmployee $emp): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);
    }

    public function test_consume_does_not_double_count_one_session()
    {
        // B1: quota 3, 1 sesi di-reserve lalu selesai → saldo harus 2 (bukan 1).
        [$p, $emp, $user, $ep] = $this->fixture(3);
        $s = $this->makeSession($p, $emp);

        QuotaService::reserve($emp, $s);
        $this->assertSame(2, QuotaService::balanceFor($emp));

        QuotaService::consume($s);
        $this->assertSame(2, QuotaService::balanceFor($emp), 'used event harus terminal, bukan outflow tambahan');
    }

    public function test_forfeit_does_not_double_count_one_session()
    {
        // B1: no-show sweeper → 1 sesi hangus = 1 kuota (bukan 2).
        [$p, $emp, $user, $ep] = $this->fixture(3);
        $s = $this->makeSession($p, $emp);

        QuotaService::reserve($emp, $s);
        QuotaService::forfeit($s, 'no_show');

        $this->assertSame(2, QuotaService::balanceFor($emp));
    }

    public function test_early_cancel_releases_quota()
    {
        // B2: cancel sebelum cutoff → saldo kembali (UI bilang begitu, ledger harus sama).
        [$p, $emp, $user, $ep] = $this->fixture(2);
        $s = $this->makeSession($p, $emp);
        $s->consultant_id = null;
        $s->save();

        QuotaService::reserve($emp, $s);
        $this->assertSame(1, QuotaService::balanceFor($emp));

        $restored = BookingService::cancel('perusahaan', $s, 'tes');
        $this->assertTrue($restored);
        $this->assertSame(2, QuotaService::balanceFor($emp), 'early cancel wajib release');
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $s->id, 'event' => 'released',
        ]);
    }

    public function test_pre_mission_session_counts_as_consumed()
    {
        // Backfill gap: sesi manual tanpa ledger → reserve sesi BARU harus
        // memperhitungkannya (saldo turun), bukan bohong penuh.
        [$p, $emp, $user, $ep] = $this->fixture(2);
        $this->makeSession($p, $emp); // pre-mission, tanpa ledger row

        $s2 = $this->makeSession($p, $emp);
        QuotaService::reserve($emp, $s2);
        $this->assertSame(0, QuotaService::balanceFor($emp), 'sesi tanpa ledger tetap dihitung terpakai');
    }

    public function test_reschedule_does_not_burn_extra_quota()
    {
        // B2 composite: reschedule = cancel + book → total kuota terpakai 1, bukan 2.
        [$p, $emp, $user, $ep] = $this->fixture(3);
        $s = $this->makeSession($p, $emp);

        QuotaService::reserve($emp, $s);
        QuotaService::release($s, 'cancel: reschedule');
        $s3 = $this->makeSession($p, $emp);
        QuotaService::reserve($emp, $s3);

        // quota 3, 1 sesi aktif → saldo 2 (double-burn akan menghasilkan 1)
        $this->assertSame(2, QuotaService::balanceFor($emp), 'reschedule tidak boleh membakar kuota ekstra');
    }
}
