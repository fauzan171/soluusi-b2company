<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// wb-T3: superadmin katalog paket Wellbeing — read/edit (no create/delete,
// seeder = sumber paket), harga band inline, AdminAction audit, snapshot
// kontrak tak tersentuh saat master katalog berubah (K4).
class WellbeingCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function seedCatalog(): void
    {
        $this->artisan('db:seed', ['--class' => 'WellbeingPackageSeeder'])->assertExitCode(0);
    }

    private function package(string $code): WellbeingPackage
    {
        return WellbeingPackage::where('code', $code)->firstOrFail();
    }

    public function test_admin_index_menampilkan_4_paket()
    {
        $this->seedCatalog();

        $res = $this->actingAs($this->user('admin'))->get(route('admin.wellbeing-packages.index'));

        $res->assertOk();
        foreach (['Discovery', 'Foundation', 'Growth', 'Impact'] as $name) {
            $res->assertSeeText($name);
        }
    }

    public function test_admin_edit_page_render_dengan_band()
    {
        $this->seedCatalog();
        $pkg = $this->package('growth');

        $res = $this->actingAs($this->user('admin'))->get(route('admin.wellbeing-packages.edit', $pkg));

        $res->assertOk();
        $res->assertSeeText('Harga per Band Karyawan');
        $res->assertSeeText('exclude pajak');
        $res->assertSee('50–150'); // pola markup-rules: input inline per band
    }

    public function test_admin_update_package_fields_dan_admin_action()
    {
        $this->seedCatalog();
        $admin = $this->user('admin');
        $pkg = $this->package('growth');

        $this->actingAs($admin)->put(route('admin.wellbeing-packages.update', $pkg), [
            'description' => 'Intervensi terarah 6 bulan di dua domain.',
            'expert_chat_experts' => 2,
            'deep_support_quota' => 18,
            'webinar_count' => 4,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('wellbeing_packages', [
            'id' => $pkg->id,
            'description' => 'Intervensi terarah 6 bulan di dua domain.',
            'deep_support_quota' => 18,
            'webinar_count' => 4,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'wellbeing_package.update',
            'target_id' => $pkg->id,
        ]);
    }

    public function test_admin_update_band_harga_tersimpan_dan_tercatat()
    {
        $this->seedCatalog();
        $admin = $this->user('admin');
        $pkg = $this->package('growth');
        $band = $pkg->pricingBands()->where('band_min', 151)->firstOrFail();

        $this->actingAs($admin)->put(route('admin.wellbeing-packages.bands.update', [$pkg, $band]), [
            'band_min' => 151,
            'band_max' => 400,
            'monthly_price' => 15500000,
            'mhcu_quota' => 12,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('wellbeing_pricing_bands', [
            'id' => $band->id,
            'band_min' => 151,
            'band_max' => 400,
            'monthly_price' => 15500000,
            'mhcu_quota' => 12,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'wellbeing_band.update',
            'target_id' => $band->id,
        ]);
    }

    public function test_band_overlap_dan_rentang_balik_ditolak_db_bersih()
    {
        $this->seedCatalog();
        $admin = $this->user('admin');
        $pkg = $this->package('growth');
        $band = $pkg->pricingBands()->where('band_min', 401)->firstOrFail();

        // overlap dengan band 151–400 dan 50–150 (form web → redirect + error)
        $this->actingAs($admin)->put(route('admin.wellbeing-packages.bands.update', [$pkg, $band]), [
            'band_min' => 100,
            'band_max' => 600,
            'monthly_price' => 9999999,
        ])->assertRedirect()->assertSessionHasErrors('band_min');
        $this->assertDatabaseHas('wellbeing_pricing_bands', [
            'id' => $band->id, 'band_min' => 401, 'monthly_price' => 20499999,
        ]);

        // band_min >= band_max
        $this->actingAs($admin)->putJson(route('admin.wellbeing-packages.bands.update', [$pkg, $band]), [
            'band_min' => 600,
            'band_max' => 401,
            'monthly_price' => 123,
        ])->assertStatus(422);
        $this->assertDatabaseHas('wellbeing_pricing_bands', [
            'id' => $band->id, 'band_min' => 401,
        ]);
    }

    public function test_hr_admin_dan_employee_403_semua_verb()
    {
        $this->seedCatalog();
        $pkg = $this->package('foundation');
        $band = $pkg->pricingBands()->first();

        foreach (['hr_admin', 'employee'] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('admin.wellbeing-packages.index'))->assertForbidden();
            $this->actingAs($u)->get(route('admin.wellbeing-packages.edit', $pkg))->assertForbidden();
            $this->actingAs($u)->put(route('admin.wellbeing-packages.update', $pkg), [])->assertForbidden();
            $this->actingAs($u)->put(route('admin.wellbeing-packages.bands.update', [$pkg, $band]), [])->assertForbidden();
        }
    }

    public function test_band_paket_gratis_discovery_ditolak()
    {
        $this->seedCatalog();
        $discovery = $this->package('discovery');
        $band = WellbeingPricingBand::create([
            'wellbeing_package_id' => $discovery->id,
            'band_min' => 1, 'band_max' => 49, 'monthly_price' => 0,
        ]);

        $this->actingAs($this->user('admin'))
            ->putJson(route('admin.wellbeing-packages.bands.update', [$discovery, $band]), [
                'band_min' => 1, 'band_max' => 49, 'monthly_price' => 500000,
            ])->assertStatus(422);

        $this->assertDatabaseHas('wellbeing_pricing_bands', ['id' => $band->id, 'monthly_price' => 0]);
    }

    public function test_edit_harga_band_tidak_mengubah_snapshot_kontrak()
    {
        $this->seedCatalog();
        $growth = $this->package('growth');
        $band = $growth->pricingBands()->where('band_min', 50)->firstOrFail();

        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => "PT WB Catalog {$n}", 'brand' => "WBCat{$n}",
            'sessions_per_employee_month' => 2, 'employee_count' => 100, 'is_active' => true,
        ]);
        $program = CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $growth->id,
            'pricing_band_id' => $band->id,
            'employee_band_size' => 100,
            'deep_support_mode' => 'mhcu',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 5,
            'deep_support_quota' => 15,
            'status' => 'active',
            'monthly_price' => 7499999,
        ]);

        $this->actingAs($this->user('admin'))
            ->put(route('admin.wellbeing-packages.bands.update', [$growth, $band]), [
                'band_min' => 50,
                'band_max' => 150,
                'monthly_price' => 9999999,
                'mhcu_quota' => 5,
            ])->assertRedirect();

        // master berubah, snapshot kontrak tetap (K4)
        $this->assertDatabaseHas('wellbeing_pricing_bands', [
            'id' => $band->id, 'monthly_price' => 9999999,
        ]);
        $this->assertDatabaseHas('company_wellbeing_programs', [
            'id' => $program->id, 'monthly_price' => 7499999,
        ]);
    }
}
