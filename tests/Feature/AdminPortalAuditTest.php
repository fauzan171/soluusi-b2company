<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingProgramReview;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Admin portal audit: every admin flow completable from the UI.
class AdminPortalAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function perusahaan(int $employeeCount = 500, bool $active = true): Perusahaan
    {
        return Perusahaan::create([
            'name' => 'PT Audit Fake', 'brand' => 'AuditCo',
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount, 'is_active' => $active,
        ]);
    }

    private function poolOf(Perusahaan $p, int $n = 2): void
    {
        foreach (range(1, $n) as $i) {
            $c = Consultant::factory()->create(['status' => Consultant::ACCEPT]);
            ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c->id, 'is_active' => true]);
        }
    }

    private function program(Perusahaan $p, array $over = []): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::where('code', 'growth')->firstOrFail();

        return CompanyWellbeingProgram::create(array_merge([
            'perusahaan_id' => $p->id, 'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 100, 'deep_support_mode' => 'mhcu',
            'starts_at' => today()->toDateString(), 'ends_at' => today()->addDays(90)->toDateString(),
            'expert_chat_quota' => 0, 'deep_support_quota' => 15, 'webinar_quota' => 3,
            'assessment_count' => 2, 'monthly_price' => 1000000, 'status' => 'active',
        ], $over));
    }

    // --- 1: Assign Program wizard real path -------------------------------

    public function test_wizard_walks_get_step1_then_step2_then_post_store()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $p = $this->perusahaan(500);
        $this->poolOf($p);

        $step1 = $this->actingAs($admin)->get(route('admin.wellbeing-programs.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<form method="GET" action="' . preg_quote(route('admin.wellbeing-programs.create'), '/') . '"/', $step1);
        $this->assertStringContainsString('name="step" value="2"', $step1);

        $step2 = $this->actingAs($admin)
            ->get(route('admin.wellbeing-programs.create', ['step' => 2, 'perusahaan_id' => $p->id, 'package_code' => 'growth']))
            ->assertOk()
            ->assertSee('Growth')
            ->assertSee('Disarankan')
            ->assertSee('401–600 karyawan')
            ->assertSee('180 hari');
        $html = $step2->getContent();
        $this->assertStringContainsString('name="perusahaan_id" value="' . $p->id . '"', $html);
        $this->assertStringContainsString('name="package_code" value="growth"', $html);
        // back link to step 1 preselects choices
        $this->assertStringContainsString(e(route('admin.wellbeing-programs.create', ['perusahaan_id' => $p->id, 'package_code' => 'growth'])), $html);

        $this->actingAs($admin)->post(route('admin.wellbeing-programs.store'), [
            'perusahaan_id' => $p->id, 'package_code' => 'growth', 'band_min' => 401,
            'deep_support_mode' => 'one_on_one', 'starts_at' => now()->addDay()->toDateString(), 'confirm' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('one_on_one', $p->programs()->first()->deep_support_mode);
    }

    public function test_wizard_step2_discovery_lists_employee_checkboxes_without_email()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $p = $this->perusahaan(30);
        PerusahaanEmployee::create(['perusahaan_id' => $p->id, 'name' => 'Karyawan Fiktif', 'email' => 'fiktif@example.test']);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.create', ['step' => 2, 'perusahaan_id' => $p->id, 'package_code' => 'discovery']))
            ->assertOk()
            ->assertSee('name="selected_employee_ids[]"', false)
            ->assertSee('Karyawan Fiktif')
            ->assertSee('maks 8')
            ->assertDontSee('fiktif@example.test');
    }

    // --- 2: billing renewal flag ------------------------------------------

    public function test_billing_renewal_only_flags_contracts_ending_within_30_days()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $this->program($this->perusahaan(), ['ends_at' => today()->addDays(90)->toDateString()]);

        $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()->assertDontSee('renewal &lt;30d', false);

        CompanyWellbeingProgram::query()->update(['ends_at' => today()->addDays(10)->toDateString()]);
        $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()->assertSee('renewal &lt;30d', false);
    }

    // --- 3: dashboard badge not escaped -----------------------------------

    public function test_dashboard_zero_badge_is_markup_not_text()
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<span class="ops-queue-badge-zero"></span>', false)
            ->assertDontSee('&lt;span class=&quot;ops-queue-badge-zero', false);
    }

    // --- 4: removing a pool member ends it instead of deleting ------------

    public function test_consultant_pool_remove_keeps_row_as_ended_and_reassign_reactivates()
    {
        $admin = $this->admin();
        $p = $this->perusahaan();
        $c = Consultant::factory()->create(['status' => Consultant::ACCEPT]);
        $pool = ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c->id]);
        $payload = ['tenant' => 'perusahaan', 'tenant_id' => $p->id, 'consultant_id' => $c->id];

        $this->actingAs($admin)->post(route('admin.consultant-assignment.remove'), $payload)->assertRedirect();
        $this->assertSame('ended', $pool->fresh()->status);
        $this->assertFalse(ConsultantPool::poolFor('perusahaan', $p->id)->contains('id', $c->id));

        $this->actingAs($admin)->post(route('admin.consultant-assignment.assign'), $payload)->assertRedirect();
        $this->assertSame('active', $pool->fresh()->status);
        $this->assertSame(1, ConsultantPool::where('perusahaan_id', $p->id)->count());
    }

    // --- 7: employee_count editable ---------------------------------------

    public function test_admin_updates_perusahaan_employee_count()
    {
        $p = $this->perusahaan(10);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.tenants.perusahaans.edit', $p))->assertOk()->assertSee('name="employee_count"', false);

        $this->actingAs($admin)->put(route('admin.tenants.perusahaans.update', $p), [
            'name' => $p->name, 'brand' => $p->brand, 'employee_count' => 250,
        ])->assertRedirect(route('admin.tenants.perusahaans.show', $p));
        $this->assertSame(250, (int) $p->fresh()->employee_count);

        $this->actingAs($admin)->get(route('admin.tenants.perusahaans.show', $p))->assertSee('250');

        $this->actingAs($admin)->put(route('admin.tenants.perusahaans.update', $p), [
            'name' => $p->name, 'brand' => $p->brand, 'employee_count' => -1,
        ])->assertSessionHasErrors('employee_count');
    }

    // --- 8: quarterly review completion UI --------------------------------

    public function test_webinar_page_renders_review_complete_form()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $program = $this->program($this->perusahaan());
        $review = WellbeingProgramReview::create([
            'program_id' => $program->id, 'period_no' => 1,
            'due_at' => today()->addMonths(3)->toDateString(), 'status' => WellbeingProgramReview::STATUS_UPCOMING,
        ]);

        $this->actingAs($this->admin())->get(route('admin.wellbeing-webinars.index', $program))
            ->assertOk()
            ->assertSee(route('admin.wellbeing-reviews.complete', $review), false)
            ->assertSee('name="summary"', false);
    }

    // --- 11: cancel termination -------------------------------------------

    public function test_admin_cancels_pending_termination()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $program = $this->program($this->perusahaan());

        $this->actingAs($admin)->post(route('admin.wellbeing-programs.request-termination', $program), [
            'effective_date' => now()->addDays(30)->toDateString(),
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('admin.wellbeing-programs.index'))
            ->assertSee('Terminasi efektif')
            ->assertSee(route('admin.wellbeing-programs.cancel-termination', $program), false);

        $this->actingAs($admin)->post(route('admin.wellbeing-programs.cancel-termination', $program))
            ->assertRedirect()->assertSessionHas('success');

        $fresh = $program->fresh();
        $this->assertNull($fresh->terminated_at);
        $this->assertNull($fresh->notice_requested_at);
        $this->assertSame('active', $fresh->status);
        $this->assertDatabaseHas('admin_actions', ['action' => 'wellbeing_program.termination_cancelled', 'target_id' => $program->id]);

        // nothing pending any more → 422
        $this->actingAs($admin)->post(route('admin.wellbeing-programs.cancel-termination', $program))->assertStatus(422);
    }

    public function test_cancel_termination_forbidden_for_hr_admin()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $program = $this->program($this->perusahaan(), ['terminated_at' => now()->addDays(30)->toDateString()]);
        $hr = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]);

        $this->actingAs($hr)->post(route('admin.wellbeing-programs.cancel-termination', $program))->assertStatus(403);
        $this->assertNotNull($program->fresh()->terminated_at);
    }

    // --- 12: approve/activate from tenant show ----------------------------

    public function test_inactive_perusahaan_show_has_activate_button_and_related_links()
    {
        $p = $this->perusahaan(10, false);

        $this->actingAs($this->admin())->get(route('admin.tenants.perusahaans.show', $p))
            ->assertOk()
            ->assertSee(route('admin.perusahaans.activate', $p), false)
            ->assertSee(route('admin.wellbeing-programs.index'), false);
    }

    // --- 14: sekolah assignment inline edit -------------------------------

    public function test_sekolah_assignment_edit_form_renders_and_updates()
    {
        $admin = $this->admin();
        $sekolah = Sekolah::create([
            'name' => 'SMA Fiktif', 'brand' => 'SMA Fiktif', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $a = AssessmentAssignment::create([
            'assignable_type' => $sekolah->getMorphClass(), 'assignable_id' => $sekolah->id,
            'instrument_code' => 'pss10', 'audience' => 'student', 'source' => 'core', 'round' => 1,
            'opens_at' => today()->toDateString(), 'closes_at' => null, 'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.tenants.sekolahs.show', $sekolah))
            ->assertOk()
            ->assertSee(route('admin.tenants.sekolahs.assignments.update', [$sekolah, $a]), false)
            ->assertSee('name="round"', false);

        $this->actingAs($admin)->put(route('admin.tenants.sekolahs.assignments.update', [$sekolah, $a]), [
            'round' => 2, 'opens_at' => today()->addDay()->toDateString(), 'closes_at' => today()->addDays(10)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $a->fresh()->round);
    }

    // --- 15/16: audit + provisioning redirect -----------------------------

    public function test_admin_assignment_actions_are_audited()
    {
        $admin = $this->admin();
        $p = $this->perusahaan();
        $c = Consultant::factory()->create(['status' => Consultant::ACCEPT]);

        $this->actingAs($admin)->post(route('admin.assignments.store'), ['perusahaan_id' => $p->id, 'consultant_id' => $c->id])->assertRedirect();
        $pool = ConsultantPool::where('perusahaan_id', $p->id)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.assignments.end', $pool))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.assignments.activate', $pool))->assertRedirect();

        foreach (['consultant_pool.assign', 'consultant_pool.end', 'consultant_pool.activate'] as $action) {
            $this->assertDatabaseHas('admin_actions', ['admin_id' => $admin->id, 'action' => $action]);
        }
    }

    public function test_provisioning_redirects_to_new_tenant_show()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        $res = $this->actingAs($this->admin())->post(route('admin.provision.store'), [
            'vertical' => 'perusahaan', 'name' => 'PT Prov Fiktif', 'brand' => 'ProvFiktif',
            'admin_name' => 'Admin Fiktif', 'admin_email' => 'prov-' . Str::random(6) . '@example.test',
            'admin_password' => 'password123',
        ]);

        $p = Perusahaan::where('brand', 'ProvFiktif')->firstOrFail();
        $res->assertRedirect(route('admin.tenants.perusahaans.show', $p))->assertSessionHas('success');
    }
}
