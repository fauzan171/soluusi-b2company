<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit: internal roles (wv4) used to hit "Role tidak dikenal" after login.
class LoginRoleRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $roleId = DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Role', 'lastname' => $role,
            'username' => "role_{$role}", 'email' => "role_{$role}@test.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    public function test_operations_lands_on_wellbeing_programs_and_can_open_it(): void
    {
        $ops = $this->makeUser('operations');

        $this->post('/login', ['email' => $ops->email, 'password' => 'password'])
            ->assertRedirect(route('admin.wellbeing-programs.index'));
        $this->assertAuthenticatedAs($ops);

        $this->get(route('admin.wellbeing-programs.index'))->assertOk();
    }

    public function test_internal_roles_without_portal_are_logged_out_with_message(): void
    {
        foreach (['finance', 'account_manager', 'expert_coordinator'] as $role) {
            $user = $this->makeUser($role);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('loginIndex'))
                ->assertSessionHas('error', 'Akun internal ini belum punya portal — hubungi admin.');
            $this->assertGuest();
        }
    }
}
