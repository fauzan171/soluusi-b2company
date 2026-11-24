<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Package;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit UX perusahaan item 10: halaman booking menghitung quotaLeft dengan
// monthly_quota − monthQuotaUsed (legacy) sedangkan dashboard memakai
// QuotaService::balanceFor (ledger-aware) → angka bisa berbeda saat ada
// event released/refunded. bookingForm wajib pakai balanceFor.
class BookingQuotaBalanceTest extends TestCase
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

    private function fixture(int $quota = 2): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Bal', 'brand' => 'BalCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $pkg = Package::create(['name' => 'Bal Basic', 'monthly_quota' => $quota, 'is_active' => true]);
        $user = $this->user('emp@bal.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@bal.local', 'consent_at' => now(),
        ]);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
        $docUser = $this->user('doc@bal.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $user, $emp, $ep];
    }

    public function test_booking_form_banner_reflects_ledger_balance(): void
    {
        [$p, $user, $emp, $ep] = $this->fixture(2);

        // Ledger: 1 reserved (dengan sesi nyata) + 1 released → balanceFor =
        // 2 - 1 + 1 = 2. Legacy (monthly_quota - monthQuotaUsed) = 2 - 0 = 2 juga
        // di sini — mari buat legacy BEDA: gunakan released TANPA reserved dulu.
        $session = \App\Models\Perusahaan\PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now(),
        ]);
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => null,
            'period_ym' => now()->format('Y-m'), 'event' => 'released',
        ]);

        // balanceFor = 2 - 1 + 1 = 2; legacy = 2 - 1 (monthQuotaUsed melihat 1 sesi
        // scheduled) = 1. Banner booking harus menampilkan 2 (ledger-aware).
        $this->assertSame(2, \App\Services\QuotaService::balanceFor($emp));
        $this->assertSame(1, \App\Services\BookingService::monthQuotaUsed($emp));

        $this->actingAs($user)->get(route('perusahaan.booking'))
            ->assertOk()
            ->assertSee('Sisa kuota bulan ini:')
            ->assertSeeInOrder(['Sisa kuota bulan ini:', '<span class="font-semibold">2 sesi</span>'], false);
    }
}
