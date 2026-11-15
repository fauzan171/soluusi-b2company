<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /progress menampilkan metrik internal DB — wajib gated auth + role:admin (T1.3).
 */
class ProgressGateTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    public function test_anonymous_diarahkan_ke_login()
    {
        $this->get('/progress')->assertRedirect('/login');
    }

    public function test_employee_mendapat_403()
    {
        $this->actingAs($this->userWithRole('employee'))
            ->get('/progress')
            ->assertForbidden();
    }

    public function test_admin_mendapat_200()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/progress')
            ->assertOk();
    }
}
