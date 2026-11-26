<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\WellbeingPackage;
use App\Services\AssessmentAccess;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// A2: perusahaan-level direct allocation (non-legacy) UNION dengan program
// aktif; legacy tetap suppressed saat program aktif (D8 dipertahankan).
class PerusahaanAssessmentUnionTest extends TestCase
{
    use RefreshDatabase;

    private PerusahaanEmployee $emp;

    private Perusahaan $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $this->company = Perusahaan::create([
            'name' => 'PT A2', 'brand' => 'A2',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = \App\Models\User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A2',
            'username' => 'emp_a2', 'email' => 'emp@a2.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $user->id,
            'name' => 'Emp A2', 'email' => 'emp@a2.local', 'consent_at' => now(),
        ]);
    }

    private function program(Perusahaan $p): CompanyWellbeingProgram
    {
        $this->seed(\Database\Seeders\WellbeingPackageSeeder::class);

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => WellbeingPackage::where('code', 'growth')->firstOrFail()->id,
            'employee_band_size' => $p->employee_count,
            'deep_support_mode' => 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'status' => 'active',
            'assessment_domains' => ['mh'],
        ]);
    }

    private function assign($assignable, string $code, array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => $assignable->getMorphClass(),
            'assignable_id' => $assignable->id,
            'instrument_code' => $code,
            'audience' => 'employee',
            'source' => 'core',
            'opens_at' => today()->subDay(),
            'closes_at' => null,
        ], $overrides));
    }

    public function test_program_plus_perusahaan_direct_union()
    {
        $program = $this->program($this->company);
        $this->assign($program, 'dass21');
        // direct allocation level perusahaan (bukan legacy):
        $this->assign($this->company, 'bfitrader', ['source' => 'addon']);
        // legacy tetap suppressed saat program aktif:
        $this->assign($this->company, 'who5', ['source' => 'legacy']);

        $codes = AssessmentAccess::forEmployee($this->emp)->pluck('instrument_code')->sort()->values()->all();

        $this->assertEquals(['bfitrader', 'dass21'], $codes);
    }

    public function test_program_wins_on_instrument_code_conflict()
    {
        $program = $this->program($this->company);
        $progA = $this->assign($program, 'dass21');
        $directA = $this->assign($this->company, 'dass21', ['source' => 'core']);

        $resolved = AssessmentAccess::forEmployee($this->emp)->values();

        $this->assertCount(1, $resolved);
        $this->assertEquals($progA->id, $resolved->first()->id);
        $this->assertNotEquals($directA->id, $resolved->first()->id);
    }

    public function test_resolve_for_employee_finds_direct_allocation()
    {
        $program = $this->program($this->company);
        $this->assign($program, 'dass21');
        $direct = $this->assign($this->company, 'disc24', ['source' => 'addon']);

        $this->assertEquals($direct->id, AssessmentAccess::resolveForEmployee($this->emp, 'disc24')?->id);
    }

    public function test_hr_report_includes_direct_allocation_while_program_active()
    {
        // HR report harus melihat assignment program + direct perusahaan.
        $program = $this->program($this->company);
        $this->assign($program, 'dass21');
        $this->assign($this->company, 'bfitrader', ['source' => 'addon']);

        $hrUser = \App\Models\User::factory()->create([
            'role_id' => \App\Models\Role::firstOrCreate(['name' => 'hr_admin'])->id,
        ]);
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $hrUser->id,
        ]);

        $res = $this->actingAs($hrUser)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $codes = collect($res->viewData('sections'))->pluck('code')->all();
        $this->assertContains('dass21', $codes);
        $this->assertContains('bfitrader', $codes);
    }
}
