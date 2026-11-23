<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Support\RevenueShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// wv5: revenue-share caps S&K Klinik §5 — unit boundaries + checkpoint view.
class KlinikRevenueShareTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function klinikDenganSesi(int $jumlah, float $packagePrice): Klinik
    {
        $klinik = Klinik::create([
            'name' => 'Klinik RevenueShare Test',
            'brand' => 'KlinikRS',
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);
        foreach (range(1, $jumlah) as $i) {
            $consultant = Consultant::factory()->create();
            $package = ConsultantPackage::factory()->create(['consultant_id' => $consultant->id]);
            $schedule = Schedule::factory()->create(['consultant_id' => $consultant->id]);

            $session = ConsultationSession::create([
                'schedule_id' => $schedule->id,
                'consultant_id' => $consultant->id,
                'consultant_package_id' => $package->id,
                'klinik_id' => $klinik->id,
                'order_number' => 'RS-' . uniqid(),
                'payment' => (string) $packagePrice,
                'amount' => $packagePrice,
                'package_price' => $packagePrice,
                'order_at' => now(),
                'payment_status' => 'verified',
                'status' => 'scheduled',
            ]);
            $session->completion_verified_at = now();
            $session->status = 'done';
            $session->save();
        }

        return $klinik;
    }

    // ------------------------------------------------------------------
    // (a) Unit: 8 boundary values RevenueShare::rate
    // ------------------------------------------------------------------

    public function test_rate_boundaries(): void
    {
        $this->assertSame(null, RevenueShare::rate(249_999)); // below → HPA
        $this->assertSame(0.10, RevenueShare::rate(250_000));
        $this->assertSame(0.10, RevenueShare::rate(299_999));
        $this->assertSame(0.15, RevenueShare::rate(300_000));
        $this->assertSame(0.15, RevenueShare::rate(349_999));
        $this->assertSame(0.20, RevenueShare::rate(350_000));
        $this->assertSame(0.20, RevenueShare::rate(399_999));
        $this->assertSame(0.25, RevenueShare::rate(400_000));
    }

    // ------------------------------------------------------------------
    // (b) Feature: 3 sesi verified package_price 350000 → 20% + estimate
    // ------------------------------------------------------------------

    public function test_checkpoint_menampilkan_rate_20_persen_dan_estimasi(): void
    {
        $admin = $this->admin();
        $klinik = $this->klinikDenganSesi(3, 350_000.0);

        $response = $this->actingAs($admin)->get(route('admin.klinik-checkpoints'));
        $response->assertOk()
            ->assertSee('20%')
            // 0.20 × (3 × 350.000) = 210.000
            ->assertSee('Rp 210.000')
            ->assertSee('Bagi hasil maks.')
            ->assertSee('batas MAKSIMUM S&K Klinik §5');
    }

    // ------------------------------------------------------------------
    // (c) Klinik tanpa sesi → "—"
    // ------------------------------------------------------------------

    public function test_checkpoint_klinik_tanpa_sesi_menampilkan_dash(): void
    {
        $admin = $this->admin();
        Klinik::create([
            'name' => 'Klinik Kosong',
            'brand' => 'KlinikKosong',
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.klinik-checkpoints'))
            ->assertOk()
            ->assertSee('Klinik Kosong')
            ->assertSee('—');
    }

    // ------------------------------------------------------------------
    // (d) Non-admin → 403 (admin role middleware sudah ada; tetap diassert)
    // ------------------------------------------------------------------

    public function test_checkpoint_tidak_bisa_diakses_non_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'klinik_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.klinik-checkpoints'))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // (e) QA3-LOW: median even-count — 4 sesi harga beda → rerata 2 tengah.
    // [349_999, 350_000, 350_000, 400_000] → median 350_000 → tier 20%.
    // ------------------------------------------------------------------

    public function test_checkpoint_median_even_count_menggunakan_rerata_dua_tengah(): void
    {
        $admin = $this->admin();
        $klinik = Klinik::create([
            'name' => 'Klinik Even Median',
            'brand' => 'KlinikEM',
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);
        foreach ([349_999.0, 350_000.0, 350_000.0, 400_000.0] as $price) {
            $consultant = Consultant::factory()->create();
            $package = ConsultantPackage::factory()->create(['consultant_id' => $consultant->id]);
            $schedule = Schedule::factory()->create(['consultant_id' => $consultant->id]);
            $session = ConsultationSession::create([
                'schedule_id' => $schedule->id,
                'consultant_id' => $consultant->id,
                'consultant_package_id' => $package->id,
                'klinik_id' => $klinik->id,
                'order_number' => 'EM-' . uniqid(),
                'payment' => (string) $price,
                'amount' => $price,
                'package_price' => $price,
                'order_at' => now(),
                'payment_status' => 'verified',
                'status' => 'scheduled',
            ]);
            $session->completion_verified_at = now();
            $session->status = 'done';
            $session->save();
        }

        // median = (350_000 + 350_000) / 2 = 350_000 → 20%; sum = 1_449_999.
        // 0.20 × 1_449_999 = 289_999,8 → number_format 0 desimal → "Rp 290.000".
        $this->actingAs($admin)->get(route('admin.klinik-checkpoints'))
            ->assertOk()
            ->assertSee('20%')
            ->assertSee('Rp 290.000');
    }
}
