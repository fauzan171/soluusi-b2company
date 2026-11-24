<?php

namespace Tests\Feature;

use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A1 — Tenant management pages superadmin: list/search/detail/edit untuk
 * klinik+perusahaan, list/create/detail/edit untuk sekolah. Semua edit
 * ter-audit di admin_actions.
 */
class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function nonAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'klinik_admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function klinik(string $name, string $brand): Klinik
    {
        return Klinik::create(['name' => $name, 'brand' => $brand, 'total_rate' => 1_000_000]);
    }

    private function perusahaan(string $name, string $brand): Perusahaan
    {
        return Perusahaan::create(['name' => $name, 'brand' => $brand, 'is_active' => true]);
    }

    private function sekolah(string $name, string $brand): Sekolah
    {
        return Sekolah::create(['name' => $name, 'brand' => $brand, 'is_active' => true]);
    }

    public function test_admin_bisa_lihat_list_3_vertikal()
    {
        $this->klinik('Klinik Sehat Sentosa', 'Klinik Sehat');
        $this->perusahaan('PT Maju Jaya', 'MajuJaya');
        $this->sekolah('SMA Cendekia', 'SMA Cendekia');

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/tenants/kliniks')->assertOk()->assertSee('Klinik Sehat');
        $this->actingAs($admin)->get('/admin/tenants/perusahaans')->assertOk()->assertSee('MajuJaya');
        $this->actingAs($admin)->get('/admin/tenants/sekolahs')->assertOk()->assertSee('SMA Cendekia');
    }

    public function test_search_nama_dan_brand_memfilter_hasil()
    {
        $this->klinik('Klinik Alpha Utama', 'AlphaCare');
        $this->klinik('Klinik Beta Sejahtera', 'BetaCare');

        $response = $this->actingAs($this->admin())->get('/admin/tenants/kliniks?q=Alpha');

        // Legal name hanya muncul di tabel hasil (dropdown header memakai brand semua klinik).
        $response->assertOk()->assertSee('Klinik Alpha Utama')->assertDontSee('Klinik Beta Sejahtera');

        // Search juga cocok dengan legal name, bukan cuma brand.
        $this->get('/admin/tenants/kliniks?q=Beta Sejahtera')->assertOk()->assertSee('BetaCare');
    }

    public function test_detail_tenant_render()
    {
        $klinik = $this->klinik('Klinik Delta', 'DeltaCare');
        $perusahaan = $this->perusahaan('PT Delta', 'DeltaCorp');
        $sekolah = $this->sekolah('SMA Delta', 'SMA Delta');

        $this->actingAs($this->admin())
            ->get("/admin/tenants/kliniks/{$klinik->id}")->assertOk()->assertSee('DeltaCare');
        $this->actingAs($this->admin())
            ->get("/admin/tenants/perusahaans/{$perusahaan->id}")->assertOk()->assertSee('DeltaCorp');
        $this->actingAs($this->admin())
            ->get("/admin/tenants/sekolahs/{$sekolah->id}")->assertOk()->assertSee('SMA Delta');
    }

    public function test_edit_klinik_terupdate_dan_teraudit()
    {
        $klinik = $this->klinik('Klinik Lama', 'LamaCare');

        $response = $this->actingAs($this->admin())->put("/admin/tenants/kliniks/{$klinik->id}", [
            'name' => 'Klinik Baru',
            'brand' => 'BaruCare',
            'total_rate' => 2_000_000,
            'ai_enabled' => true,
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $klinik->refresh();
        $this->assertSame('Klinik Baru', $klinik->name);
        $this->assertSame('BaruCare', $klinik->brand);
        $this->assertSame(2_000_000, (int) $klinik->total_rate);
        $this->assertTrue((bool) $klinik->ai_enabled);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => User::where('role_id', Role::where('name', 'admin')->first()->id)->latest('id')->first()->id,
            'action' => 'klinik.update',
            'target_id' => $klinik->id,
        ]);
    }

    public function test_edit_perusahaan_dan_sekolah_terupdate_dan_teraudit()
    {
        $perusahaan = $this->perusahaan('PT Lama', 'LamaCorp');
        $sekolah = $this->sekolah('SMA Lama', 'SMA Lama');

        $this->actingAs($this->admin())->put("/admin/tenants/perusahaans/{$perusahaan->id}", [
            'name' => 'PT Baru',
            'brand' => 'BaruCorp',
            'employee_count' => 120,
            'pepm_rate' => 20000,
            'sessions_per_employee_month' => 3,
            'ai_enabled' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $perusahaan->refresh();
        $this->assertSame('PT Baru', $perusahaan->name);
        $this->assertSame(120, (int) $perusahaan->employee_count);
        $this->assertSame(20000, (int) $perusahaan->pepm_rate);
        $this->assertSame(3, (int) $perusahaan->sessions_per_employee_month);
        $this->assertTrue((bool) $perusahaan->ai_enabled);
        $this->assertDatabaseHas('admin_actions', ['action' => 'perusahaan.update', 'target_id' => $perusahaan->id]);

        $this->actingAs($this->admin())->put("/admin/tenants/sekolahs/{$sekolah->id}", [
            'name' => 'SMA Baru',
            'brand' => 'SMA Baru',
            'tingkat' => 'SMA',
            'sessions_per_student_month' => 4,
            'ai_enabled' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $sekolah->refresh();
        $this->assertSame('SMA Baru', $sekolah->name);
        $this->assertSame(4, (int) $sekolah->sessions_per_student_month);
        $this->assertTrue((bool) $sekolah->ai_enabled);
        $this->assertDatabaseHas('admin_actions', ['action' => 'sekolah.update', 'target_id' => $sekolah->id]);
    }

    public function test_edit_mengabaikan_field_tidak_aman()
    {
        // is_active (program activation) punya route dedicated — tidak boleh
        // bisa diubah lewat form edit tenant.
        $klinik = $this->klinik('Klinik Guard', 'GuardCare');

        $this->actingAs($this->admin())->put("/admin/tenants/kliniks/{$klinik->id}", [
            'name' => 'Klinik Guard',
            'brand' => 'GuardCare',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertTrue((bool) $klinik->fresh()->is_active);
    }

    public function test_sekolah_create_dari_admin_page()
    {
        $response = $this->actingAs($this->admin())->post('/admin/tenants/sekolahs', [
            'name' => 'SMA Nusantara',
            'brand' => 'Nusantara',
            'tingkat' => 'SMA',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $sekolah = Sekolah::where('name', 'SMA Nusantara')->first();
        $this->assertNotNull($sekolah);
        $this->assertSame('SMA', $sekolah->tingkat);
        $this->assertDatabaseHas('admin_actions', ['action' => 'sekolah.create', 'target_id' => $sekolah->id]);
    }

    public function test_sekolah_create_validasi_nama_brand_wajib()
    {
        $this->actingAs($this->admin())->post('/admin/tenants/sekolahs', ['tingkat' => 'SMA'])
            ->assertRedirect()->assertSessionHasErrors(['name', 'brand']);
    }

    public function test_non_admin_tidak_bisa_akses()
    {
        $klinik = $this->klinik('Klinik X', 'XCare');
        $user = $this->nonAdmin();

        $this->actingAs($user)->get('/admin/tenants/kliniks')->assertForbidden();
        $this->actingAs($user)->get("/admin/tenants/kliniks/{$klinik->id}")->assertForbidden();
        $this->actingAs($user)->put("/admin/tenants/kliniks/{$klinik->id}", [])->assertForbidden();
        $this->actingAs($user)->post('/admin/tenants/sekolahs', [])->assertForbidden();
    }
}
