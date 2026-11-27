<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// A2: halaman superadmin per perusahaan — alokasikan instrumen aktif ke
// perusahaan (morph 'perusahaan', audience employee) + grant MCMI per employee.
class AdminPerusahaanAssessmentAllocationTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaan;

    private User $admin;

    private PerusahaanEmployee $emp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT Alloc', 'brand' => 'AllocCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
        $empRole = Role::firstOrCreate(['name' => 'employee']);
        $empUser = User::factory()->create(['role_id' => $empRole->id]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp Alloc', 'email' => 'emp@alloc.local', 'consent_at' => now(),
        ]);
    }

    public function test_page_lists_active_instruments_with_allocation_state()
    {
        AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $this->perusahaan->id,
            'instrument_code' => 'who5', 'audience' => 'employee', 'source' => 'core',
            'round' => 1, 'opens_at' => today()->subDay(),
        ]);

        $res = $this->actingAs($this->admin)
            ->get(route('admin.tenants.perusahaans.assessments', $this->perusahaan))
            ->assertOk();

        // active instruments visible (incl. bfitrader, disc24); clinical NOT allocatable.
        $res->assertSee('bfitrader')->assertSee('disc24')->assertSee('Big Five Trader');
        $res->assertSee('Dialokasikan'); // who5 allocation rendered as allocated

        // employee list for MCMI grant form present.
        $res->assertViewHas('employees');
        $this->assertTrue($res->viewData('employees')->contains('name', 'Emp Alloc'));
    }

    public function test_page_role_gated()
    {
        $url = route('admin.tenants.perusahaans.assessments', $this->perusahaan);

        $this->actingAs(User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]))
            ->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]))
            ->get($url)->assertForbidden();
        // guest: auth middleware tolak (403 via role middleware setelah redirect di test).
    }

    public function test_store_creates_direct_allocation_with_audit()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'disc24',
            ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_assignments', [
            'assignable_type' => 'perusahaan',
            'assignable_id' => $this->perusahaan->id,
            'instrument_code' => 'disc24',
            'audience' => 'employee',
            'source' => 'addon',
            'round' => 1,
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'perusahaan.assessment_allocation.created',
            'admin_id' => $this->admin->id,
        ]);
    }

    public function test_store_core_instrument_uses_core_source()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'who5',
            ])->assertRedirect();

        $this->assertDatabaseHas('assessment_assignments', [
            'assignable_type' => 'perusahaan',
            'instrument_code' => 'who5',
            'source' => 'core',
        ]);
    }

    public function test_store_rejects_mcmi3()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'mcmi3',
            ])->assertSessionHasErrors('instrument_code');

        $this->assertDatabaseMissing('assessment_assignments', [
            'instrument_code' => 'mcmi3',
        ]);
    }

    public function test_store_duplicate_rejected_friendly()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'who5',
            ])->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'who5',
            ])->assertSessionHasErrors('instrument_code');

        $this->assertSame(1, AssessmentAssignment::where('instrument_code', 'who5')->count());
    }

    public function test_close_removes_allocation_with_audit()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.store', $this->perusahaan), [
                'instrument_code' => 'who5',
            ])->assertRedirect();

        $assignment = AssessmentAssignment::where('instrument_code', 'who5')->first();

        $this->actingAs($this->admin)
            ->post(route('admin.tenants.perusahaans.assessments.close', [$this->perusahaan, $assignment]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_assignments', [
            'id' => $assignment->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'perusahaan.assessment_allocation.closed',
            'admin_id' => $this->admin->id,
        ]);
    }

    public function test_grant_mcmi3_to_perusahaan_employee()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.instruments.grants.store'), [
                'employee_id' => $this->emp->id,
                'instrument_code' => 'mcmi3',
                'reason' => 'indikasi klinis (fake data)',
                'from_allocation' => '1',
            ])->assertRedirect(route('admin.tenants.perusahaans.assessments', $this->perusahaan))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('instrument_grants', [
            'employee_id' => $this->emp->id,
            'instrument_code' => 'mcmi3',
        ]);
    }

    public function test_grant_requires_reason()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.instruments.grants.store'), [
                'employee_id' => $this->emp->id,
                'instrument_code' => 'mcmi3',
                'reason' => '',
            ])->assertSessionHasErrors('reason');

        $this->assertSame(0, InstrumentGrant::count());
    }
}
