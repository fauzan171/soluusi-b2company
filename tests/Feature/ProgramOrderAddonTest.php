<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use Database\Seeders\AssessmentInstrumentSeeder;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// B2: Program Order menampilkan baris add-on ("Add-on: {name} — Rp {price}",
// sekali bayar) + total = total kontrak + jumlah addon_price.
class ProgramOrderAddonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function program(string $code = 'growth', int $employeeCount = 120, ?int $bandMin = 50): CompanyWellbeingProgram
    {
        $this->seed(WellbeingPackageSeeder::class);
        $this->seed(AssessmentInstrumentSeeder::class);
        $perusahaan = Perusahaan::create([
            'name' => 'PT Addon Snap', 'brand' => 'AddonSnap',
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

    private function addon(CompanyWellbeingProgram $program, string $code, float $price): AssessmentAssignment
    {
        return AssessmentAssignment::create([
            'assignable_type' => $program->getMorphClass(),
            'assignable_id' => $program->id,
            'instrument_code' => $code,
            'audience' => 'employee',
            'source' => AssessmentAssignment::SOURCE_ADDON,
            'addon_price' => $price,
            'round' => 1,
            'opens_at' => $program->starts_at,
            'closes_at' => $program->ends_at,
            'status' => 'active',
        ]);
    }

    public function test_order_shows_addon_rows_and_total()
    {
        $program = $this->program('growth', 120, 50);
        $this->addon($program, 'bigfive', 500000);
        $this->addon($program, 'ergonomic', 250000);

        // growth 180 hari = 6 bulan × 7.499.999 = 44.999.994 + 750.000 = 45.749.994
        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Add-on: Big Five (Kepribadian) — Rp 500.000')
            ->assertSee('Add-on: Ergonomi & Nyeri Kerja — Rp 250.000')
            ->assertSee('sekali bayar, belum termasuk pajak')
            ->assertSee('Rp 45.749.994 + pajak')
            // baris total lama (kontrak saja) tidak lagi tampil angka tanpa addon
            ->assertDontSee('Rp 44.999.994 + pajak');
    }

    public function test_order_discovery_gratis_with_addon_shows_addon_total()
    {
        $program = $this->program('discovery', 120);
        $this->addon($program, 'bigfive', 300000);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Add-on: Big Five (Kepribadian) — Rp 300.000')
            // discovery gratis: total = addon saja
            ->assertSee('Rp 300.000 + pajak');
    }

    public function test_order_without_addon_untouched()
    {
        $program = $this->program('growth', 120, 50);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Rp 44.999.994 + pajak')
            ->assertSee('— (layanan tambahan berbayar terpisah)');
    }

    public function test_core_instruments_listed_on_order()
    {
        $program = $this->program('growth', 120, 50);
        AssessmentAssignment::create([
            'assignable_type' => $program->getMorphClass(),
            'assignable_id' => $program->id,
            'instrument_code' => 'dass21',
            'audience' => 'employee',
            'source' => AssessmentAssignment::SOURCE_CORE,
            'round' => 1,
            'opens_at' => $program->starts_at,
            'closes_at' => $program->ends_at,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('DASS-21 (Depresi, Kecemasan, Stres)');
    }
}
