<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use App\Models\WellbeingPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv4: feature B — Action Plan CRUD + gate paket Growth/Impact.
class Wv4ActionPlanTest extends TestCase
{
    use RefreshDatabase;

    private function makeHrCompany(): array
    {
        $hrRoleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT AP', 'brand' => 'APCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 60, 'is_active' => true,
        ]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'AP',
            'username' => 'hr_ap', 'email' => 'hr_ap@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$p, $hr];
    }

    private function makeProgram(Perusahaan $p, string $packageCode, int $suffix): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::create([
            'code' => $packageCode, 'name' => ucfirst($packageCode), 'duration_days' => 180,
            'assessment_count' => 2, 'assessment_domains' => ['mh'],
            'expert_chat_experts' => 1, 'webinar_count' => 3, 'quarterly_review' => false,
        ]);

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 100,
            'deep_support_mode' => 'mhcu',
            'starts_at' => today()->toDateString(),
            'ends_at' => today()->addDays(179)->toDateString(),
            'expert_chat_quota' => 30,
            'deep_support_quota' => 15,
            'webinar_quota' => 3,
            'assessment_count' => 2,
            'assessment_max_participants' => 100,
            'monthly_price' => 9999999,
            'status' => 'active',
        ]);
    }

    public function test_growth_company_can_create_and_update_action_plan(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $program = $this->makeProgram($p, 'growth', 1);

        // Create
        $this->actingAs($hr)->post(route('perusahaan.action-plan.store'), [
            'area' => 'Keuangan', 'note' => 'Webinar stres kerja', 'status' => 'planned',
        ])->assertRedirect(route('perusahaan.action-plan.index'));

        $this->assertDatabaseHas('action_plans', [
            'company_wellbeing_program_id' => $program->id,
            'area' => 'Keuangan', 'status' => 'planned',
        ]);

        // Update status
        $plan = $program->actionPlans()->first();
        $this->actingAs($hr)->post(route('perusahaan.action-plan.status', $plan), [
            'status' => 'in_progress',
        ])->assertRedirect(route('perusahaan.action-plan.index'));

        $this->assertDatabaseHas('action_plans', ['id' => $plan->id, 'status' => 'in_progress']);

        // Delete
        $this->actingAs($hr)->delete(route('perusahaan.action-plan.destroy', $plan))
            ->assertRedirect(route('perusahaan.action-plan.index'));
        $this->assertDatabaseMissing('action_plans', ['id' => $plan->id]);
    }

    public function test_impact_company_sees_action_plan_page(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $this->makeProgram($p, 'impact', 2);

        $this->actingAs($hr)->get(route('perusahaan.action-plan.index'))
            ->assertOk()
            ->assertSee('Tambah action plan');
    }

    public function test_discovery_package_gets_gate_message(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $this->makeProgram($p, 'discovery', 3);

        $res = $this->actingAs($hr)->get(route('perusahaan.action-plan.index'));
        $res->assertOk()->assertSee('paket Growth');

        // POST gated too.
        $this->actingAs($hr)->post(route('perusahaan.action-plan.store'), [
            'area' => 'Keuangan',
        ])->assertStatus(403);
        $this->assertDatabaseCount('action_plans', 0);
    }

    public function test_no_program_shows_empty_state(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $this->actingAs($hr)->get(route('perusahaan.action-plan.index'))
            ->assertOk()
            ->assertSee('Belum ada program Wellbeing aktif');
    }

    public function test_employee_cannot_access_action_plan(): void
    {
        [$p,] = $this->makeHrCompany();
        $this->makeProgram($p, 'growth', 4);

        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $emp = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'AP',
            'username' => 'emp_ap', 'email' => 'emp_ap@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);

        $this->actingAs($emp)->get(route('perusahaan.action-plan.index'))->assertStatus(403);
    }
}
