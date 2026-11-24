<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// F2.1: superadmin assign/unassign konsultan ke pool tenant.
class ConsultantAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function perusahaan(): Perusahaan
    {
        return Perusahaan::create([
            'name' => 'PT Assign', 'brand' => 'AssignCo',
            'sessions_per_employee_month' => 5, 'employee_count' => 3,
            'is_active' => true,
        ]);
    }

    private function acceptedConsultant(): Consultant
    {
        return Consultant::factory()->create(['status' => 'accept']);
    }

    public function test_admin_assign_consultant_to_perusahaan_pool(): void
    {
        $p = $this->perusahaan();
        $c = $this->acceptedConsultant();

        $this->actingAs($this->admin())->post(route('admin.consultant-assignment.assign'), [
            'tenant' => 'perusahaan',
            'tenant_id' => $p->id,
            'consultant_id' => $c->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('consultant_pools', [
            'consultant_id' => $c->id,
            'perusahaan_id' => $p->id,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'consultant_pool.assign',
        ]);
    }

    public function test_assigned_consultant_shows_in_perusahaan_pool(): void
    {
        $p = $this->perusahaan();
        $c = $this->acceptedConsultant();
        $c->user->update(['is_active' => true]);

        ConsultantPool::create(['consultant_id' => $c->id, 'perusahaan_id' => $p->id]);

        $pool = ConsultantPool::poolFor('perusahaan', $p->id);
        $this->assertTrue($pool->contains(fn ($item) => $item['id'] === $c->id));
    }

    public function test_admin_remove_consultant_from_pool(): void
    {
        $p = $this->perusahaan();
        $c = $this->acceptedConsultant();
        ConsultantPool::create(['consultant_id' => $c->id, 'perusahaan_id' => $p->id]);

        $this->actingAs($this->admin())->post(route('admin.consultant-assignment.remove'), [
            'tenant' => 'perusahaan',
            'tenant_id' => $p->id,
            'consultant_id' => $c->id,
        ])->assertRedirect();

        // Audit F5: row TIDAK dihapus (cascade schedules/workhours) — status=ended.
        $this->assertDatabaseHas('consultant_pools', [
            'consultant_id' => $c->id,
            'perusahaan_id' => $p->id,
            'status' => 'ended',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'consultant_pool.remove',
        ]);
    }

    public function test_non_admin_cannot_assign(): void
    {
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $user = User::factory()->create(['role_id' => $employeeRole->id]);
        $p = $this->perusahaan();
        $c = $this->acceptedConsultant();

        $this->actingAs($user)->post(route('admin.consultant-assignment.assign'), [
            'tenant' => 'perusahaan',
            'tenant_id' => $p->id,
            'consultant_id' => $c->id,
        ])->assertStatus(403);

        $this->assertDatabaseMissing('consultant_pools', [
            'consultant_id' => $c->id,
            'perusahaan_id' => $p->id,
        ]);
    }
}
