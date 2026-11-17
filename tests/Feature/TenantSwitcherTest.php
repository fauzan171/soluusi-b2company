<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A4: tenant switcher navbar admin — dropdown 3 vertikal di layout admin,
 * navigasi dengan ?{tenant}_id= context override (PerusahaanContext/
 * KlinikContext/SekolahContext).
 */
class TenantSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function klinik(string $name, string $brand): Klinik
    {
        return Klinik::create(['name' => $name, 'brand' => $brand, 'total_rate' => 1_000_000, 'is_active' => true]);
    }

    private function perusahaan(string $name, string $brand): Perusahaan
    {
        return Perusahaan::create(['name' => $name, 'brand' => $brand, 'is_active' => true]);
    }

    private function sekolah(string $name, string $brand): Sekolah
    {
        return Sekolah::create(['name' => $name, 'brand' => $brand, 'is_active' => true]);
    }

    /** Sesi verified di klinik tsb — muncul sebagai gross di dashboard klinik. */
    private function sessionAt(Klinik $klinik, string $orderNumber, int $amount): ConsultationSession
    {
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => 250_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);

        return ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'order_number' => $orderNumber,
            'payment' => '0',
            'amount' => $amount,
            'order_at' => now(),
            'payment_status' => 'verified',
            'status' => 'scheduled',
        ]);
    }

    public function test_navbar_admin_menampilkan_dropdown_3_vertikal()
    {
        $this->klinik('Klinik A', 'Klinik A');
        $this->perusahaan('PT B', 'PT B');
        $this->sekolah('Sekolah C', 'Sekolah C');

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('— pilih klinik —')
            ->assertSee('— pilih perusahaan —')
            ->assertSee('— pilih sekolah —')
            ->assertSee('Klinik A')
            ->assertSee('PT B')
            ->assertSee('Sekolah C');
    }

    public function test_pilih_klinik_mengganti_context_dan_tidak_bocor_ke_tenant_lain()
    {
        $a = $this->klinik('Klinik Alpha', 'Alpha');
        $b = $this->klinik('Klinik Beta', 'Beta');
        $this->sessionAt($a, 'TSW-A1', 300_000);
        $this->sessionAt($b, 'TSW-B1', 500_000);

        // Default: klinik pertama (Alpha) — gross Alpha terlihat, Beta tidak.
        $this->actingAs($this->admin())
            ->get('/klinik/dashboard')
            ->assertOk()
            ->assertSee('Rp300.000')
            ->assertDontSee('Rp500.000');

        // Switch ke Beta via ?klinik_id= — context berubah, tidak ada bocoran.
        $this->actingAs($this->admin())
            ->get('/klinik/dashboard?klinik_id=' . $b->id)
            ->assertOk()
            ->assertSee('Rp500.000')
            ->assertDontSee('Rp300.000');

        // Context persist di session (request berikutnya tanpa query param).
        $this->actingAs($this->admin())
            ->get('/klinik/dashboard')
            ->assertOk()
            ->assertSee('Rp500.000')
            ->assertDontSee('Rp300.000');
    }
}
