<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use App\Models\CompanyWellbeingProgram;
use App\Models\WellbeingQuotaLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// wb-T1: skema katalog + kontrak Wellbeing (MASTER-PLAN §1, arch §3.1–3.6).
// Tabel, kolom kunci, relasi, seeder idempotent. 0 tabel existing berubah.
class WellbeingSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function perusahaan(): Perusahaan
    {
        static $n = 0;
        $n++;

        return Perusahaan::create([
            'name' => "PT WB Schema {$n}", 'brand' => "WBCo{$n}",
            'sessions_per_employee_month' => 2, 'employee_count' => 100, 'is_active' => true,
        ]);
    }

    public function test_wellbeing_catalog_tables_exist_with_key_columns()
    {
        $this->assertTrue(Schema::hasTable('wellbeing_packages'));
        $this->assertTrue(Schema::hasTable('wellbeing_pricing_bands'));

        foreach (['code', 'name', 'duration_days', 'is_free', 'assessment_count',
            'assessment_max_participants', 'assessment_domains', 'expert_chat_experts',
            'expert_chat_quota', 'deep_support_quota', 'deep_support_mode',
            'webinar_count', 'quarterly_review', 'is_active'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('wellbeing_packages', $col),
                "wellbeing_packages.{$col} missing"
            );
        }

        foreach (['wellbeing_package_id', 'band_min', 'band_max', 'monthly_price', 'mhcu_quota'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('wellbeing_pricing_bands', $col),
                "wellbeing_pricing_bands.{$col} missing"
            );
        }

        // code unique
        WellbeingPackage::create([
            'code' => 'testpkg', 'name' => 'Test Pkg', 'duration_days' => 30,
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        WellbeingPackage::create([
            'code' => 'testpkg', 'name' => 'Dup', 'duration_days' => 30,
        ]);
    }

    public function test_program_and_ledger_tables_exist_with_key_columns()
    {
        $this->assertTrue(Schema::hasTable('company_wellbeing_programs'));
        $this->assertTrue(Schema::hasTable('wellbeing_quota_ledger'));

        foreach (['perusahaan_id', 'wellbeing_package_id', 'pricing_band_id',
            'employee_band_size', 'deep_support_mode', 'starts_at', 'ends_at',
            'expert_chat_quota', 'deep_support_quota', 'webinar_quota',
            'assessment_count', 'assessment_max_participants', 'monthly_price',
            'assessment_domains', 'selected_employee_ids', 'notice_requested_at',
            'terminated_at', 'status', 'created_by'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('company_wellbeing_programs', $col),
                "company_wellbeing_programs.{$col} missing"
            );
        }

        foreach (['program_id', 'bucket', 'perusahaan_session_id',
            'perusahaan_employee_id', 'event', 'reason', 'actor_id'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('wellbeing_quota_ledger', $col),
                "wellbeing_quota_ledger.{$col} missing"
            );
        }
    }

    public function test_program_default_status_active_and_relations()
    {
        $p = $this->perusahaan();
        $pkg = WellbeingPackage::create([
            'code' => 'relpkg', 'name' => 'Rel Pkg', 'duration_days' => 90,
            'assessment_domains' => ['mh'],
        ]);
        $band = WellbeingPricingBand::create([
            'wellbeing_package_id' => $pkg->id, 'band_min' => 50, 'band_max' => 150,
            'monthly_price' => 7999999, 'mhcu_quota' => 5,
        ]);

        $program = CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'pricing_band_id' => $band->id,
            'employee_band_size' => 100,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(89)->toDateString(),
            'expert_chat_quota' => 0,
            'deep_support_quota' => 5,
            'monthly_price' => 7999999,
            'assessment_domains' => ['mh'],
        ]);

        $this->assertSame('active', $program->refresh()->status);
        $this->assertEquals($p->id, $program->perusahaan->id);
        $this->assertEquals($pkg->id, $program->wellbeingPackage->id);
        $this->assertEquals($band->id, $program->pricingBand->id);
        $this->assertEquals(['mh'], $program->assessment_domains);
        $this->assertEquals($program->id, $p->programs()->first()->id);
        $this->assertEquals($program->id, $pkg->programs()->first()->id);
        $this->assertEquals($program->id, $band->programs()->first()->id);

        $ledger = WellbeingQuotaLedger::create([
            'program_id' => $program->id, 'bucket' => 'deep_support', 'event' => 'used',
        ]);
        $this->assertEquals($program->id, $ledger->program->id);
        $this->assertEquals($program->id, $program->quotaLedgers()->first()->id);
    }

    public function test_wellbeing_package_seeder_is_idempotent()
    {
        $this->artisan('db:seed', ['--class' => 'WellbeingPackageSeeder'])->assertExitCode(0);

        $this->assertSame(4, WellbeingPackage::count());
        $this->assertSame(15, WellbeingPricingBand::count());

        $discovery = WellbeingPackage::where('code', 'discovery')->first();
        $this->assertTrue($discovery->is_free);
        $this->assertSame(14, $discovery->duration_days);
        $this->assertSame(40, $discovery->assessment_max_participants);
        $this->assertSame(8, $discovery->deep_support_quota);

        $impact = WellbeingPackage::where('code', 'impact')->first();
        $this->assertTrue($impact->quarterly_review);
        $this->assertSame(365, $impact->duration_days);
        $this->assertSame(6, $impact->webinar_count);

        // spot-check harga eksak (PRODUCT-DOC-SUMMARY §2)
        $byCode = WellbeingPackage::whereIn('code', ['foundation', 'growth', 'impact'])
            ->get()->keyBy('code');

        $spot = [
            ['foundation', 50, '7999999.00'],
            ['foundation', 801, '30999999.00'],
            ['growth', 151, '14499999.00'],
            ['growth', 401, '20499999.00'],
            ['impact', 601, '24999999.00'],
        ];

        foreach ($spot as [$code, $bandMin, $price]) {
            $row = WellbeingPricingBand::where('wellbeing_package_id', $byCode[$code]->id)
                ->where('band_min', $bandMin)->first();
            $this->assertNotNull($row, "{$code} band {$bandMin} missing");
            $this->assertSame($price, number_format($row->monthly_price, 2, '.', ''), "{$code} band {$bandMin} price");
        }

        // MHCU quota spot-check (band table menang atas default katalog)
        $growthLow = WellbeingPricingBand::where('wellbeing_package_id', $byCode['growth']->id)
            ->where('band_min', 50)->first();
        $impactTop = WellbeingPricingBand::where('wellbeing_package_id', $byCode['impact']->id)
            ->where('band_min', 801)->first();
        $this->assertSame(5, $growthLow->mhcu_quota);
        $this->assertSame(50, $impactTop->mhcu_quota);

        // re-run: idempotent
        $this->artisan('db:seed', ['--class' => 'WellbeingPackageSeeder'])->assertExitCode(0);
        $this->assertSame(4, WellbeingPackage::count());
        $this->assertSame(15, WellbeingPricingBand::count());
    }
}
