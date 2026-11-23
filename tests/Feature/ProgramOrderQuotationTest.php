<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Quotation rows di Program Order: "Total biaya kontrak"
// (monthly_price × bulan, bulan = round(duration_days/30)) + row "Add-on" (S&K §8).
class ProgramOrderQuotationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function program(string $code, int $employeeCount, ?int $bandMin = null): CompanyWellbeingProgram
    {
        $this->seed(WellbeingPackageSeeder::class);
        $perusahaan = Perusahaan::create([
            'name' => 'PT Quotation Snap', 'brand' => 'QuotationSnap',
            'employee_count' => $employeeCount, 'is_active' => true, 'status' => 'active',
        ]);
        $package = WellbeingPackage::where('code', $code)->first();
        $band = $bandMin
            ? \App\Models\WellbeingPricingBand::where('wellbeing_package_id', $package->id)
                ->where('band_min', $bandMin)->first()
            : null;

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $perusahaan->id,
            'wellbeing_package_id' => $package->id,
            'pricing_band_id' => $band?->id,
            'employee_band_size' => $employeeCount,
            'deep_support_mode' => $code === 'discovery' ? 'one_on_one' : 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'ends_at' => now()->addDay()->copy()->addDays($package->duration_days)->subDay()->toDateString(),
            'expert_chat_quota' => 0,
            'deep_support_quota' => $code === 'discovery' ? 8 : 15,
            'webinar_quota' => 3,
            'assessment_count' => 2,
            'monthly_price' => $band?->monthly_price,
            'assessment_domains' => ['mh', 'nutrition'],
            'selected_employee_ids' => null,
            'status' => 'active',
        ]);
    }

    public function test_order_growth_menampilkan_total_biaya_kontrak_dan_addon()
    {
        // growth 180 hari (6 bulan), band 50–150 → 7.499.999 × 6 = 44.999.994
        $program = $this->program('growth', 120, 50);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Total biaya kontrak')
            ->assertSee('Rp 44.999.994 + pajak')
            ->assertSee('6 bulan')
            ->assertSee('Add-on')
            ->assertSee('— (layanan tambahan berbayar terpisah)');
    }

    public function test_order_discovery_gratis_tanpa_row_total()
    {
        $program = $this->program('discovery', 120);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Gratis')
            ->assertDontSee('Total biaya kontrak')
            ->assertSee('Add-on');
    }
}
