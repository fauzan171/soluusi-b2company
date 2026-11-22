<?php

namespace Tests\Feature;

use App\Models\Perusahaan\ConsultationQuotaLedger;
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
 * W8 — celah edge kuota yang belum ter-cover QuotaServiceTest/NoShowSweepAndRefundTest
 * (tanpa duplikasi): release() mengembalikan saldo, refund atas entry
 * non-reserved (hangus) ditolak, periode ledger = bulan TANGGAL SESI saat
 * booking lewat BookingService (slot bulan depan).
 */
class W8QuotaEdgeFinalTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(int $quota = 1): array
    {
        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => 'PT W8Q'.$n, 'brand' => 'W8Q'.$n, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $pkg = \App\Models\Package::create(['name' => 'W8 Paket'.$n, 'monthly_quota' => $quota, 'is_active' => true]);
        $roleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'W8Q',
            'username' => "emp_w8q_{$n}", 'email' => "emp{$n}@w8q.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => "Emp W8Q{$n}", 'email' => "emp{$n}@w8q.local", 'consent_at' => now(),
        ]);
        $ep = \App\Models\Perusahaan\EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        return [$p, $pkg, $ep, $emp, $user, $session];
    }

    public function test_release_restores_balance()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);

        QuotaService::reserve($emp, $session);
        $this->assertSame(0, QuotaService::balanceFor($emp));

        QuotaService::release($session, 'batal sebelum cutoff');

        // pembatalan dini → kuota kembali (event released = inflow).
        $this->assertSame(1, QuotaService::balanceFor($emp));
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'released',
            'reason' => 'batal sebelum cutoff',
        ]);
    }

    public function test_refund_rejected_on_forfeited_entry()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'W8Q',
            'username' => 'hr_w8q', 'email' => 'hr@w8q.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]),
        ]);

        QuotaService::reserve($emp, $session);
        $reserved = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', 'reserved')->firstOrFail();
        QuotaService::forfeit($session, 'no_show');

        // hangus = terminal: entry reserved sudah ada pasangan forfeited,
        // refund entry non-reserved ditolak (hanya reserved yang bisa direfund).
        $forfeited = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', 'forfeited')->firstOrFail();
        try {
            QuotaService::refund($forfeited, $hr, 'salah hangus');
            $this->fail('Expected 422 refund over forfeited entry.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'refunded',
        ]);
        $this->assertSame(0, QuotaService::balanceFor($emp));
    }

    public function test_reserve_period_is_session_month_not_booking_month()
    {
        [, , $ep, $emp, , $session] = $this->fixture(1);
        // FIX semantik unledgered: sesi fixture (bulan ini, tanpa reserve) ikut
        // terhitung — batalkan agar saldo bulan ini benar-benar penuh.
        $session->update(['status' => 'cancelled']);

        // slot bulan depan, dipesan bulan ini → ledger tercatat di period bulan SLOT.
        $nextMonth = now()->addMonth();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $ep->perusahaan_id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $nextMonth,
        ]);
        QuotaService::reserve($emp, $session, $nextMonth);

        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
            'period_ym' => $nextMonth->format('Y-m'),
        ]);
        // saldo bulan sesi (bulan depan) terpakai; saldo bulan ini masih penuh.
        $this->assertSame(0, QuotaService::balanceFor($emp, $nextMonth));
        $this->assertSame(1, QuotaService::balanceFor($emp, now()));
    }
}
