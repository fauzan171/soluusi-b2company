<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F2.2 — Superadmin view-as + audit.
 * View-as aktif disimpan di session; middleware set context + banner;
 * start/stop dicatat ke admin_actions.
 */
class ViewAsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    private function hr(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]);
    }

    private function employee(): array
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT V', 'brand' => 'VCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp V', 'email' => 'emp-v@test.local', 'consent_at' => now(),
        ]);

        return [$perusahaan, $empUser, $employee];
    }

    public function test_superadmin_can_start_view_as()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id])
            ->assertRedirect();

        $this->assertTrue(\App\Support\ViewAs::isActive());
        $this->assertSame($employee->id, \App\Support\ViewAs::current()['employee_id']);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'view_as.start',
            'target_id' => $employee->id,
        ]);
    }

    public function test_non_superadmin_cannot_start_view_as()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();

        $this->actingAs($this->hr())
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id])
            ->assertForbidden();

        $this->assertFalse(\App\Support\ViewAs::isActive());
        $this->assertDatabaseMissing('admin_actions', ['action' => 'view_as.start']);
    }

    public function test_view_as_stop_clears_context_and_audits()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);
        $this->assertTrue(\App\Support\ViewAs::isActive());

        $this->actingAs($admin)->post(route('admin.view-as.stop'))->assertRedirect();
        $this->assertFalse(\App\Support\ViewAs::isActive());
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'view_as.stop',
        ]);
    }

    public function test_view_as_banner_rendered_when_active()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang melihat sebagai')
            ->assertSee('Emp V');
    }

    public function test_view_as_context_is_scoped_to_target_employee_tenant()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);

        // View-as aktif → PerusahaanContext ikut tenant target, bukan admin_perusahaan_id.
        $this->actingAs($admin)->get(route('perusahaan.dashboard'));
        $this->assertSame($perusahaan->id, \App\Support\PerusahaanContext::current()?->id);
    }
}
