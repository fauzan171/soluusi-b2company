<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use App\Services\Perusahaan\EmployeeProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task D.1: parity provisioner karyawan (mirror Sekolah\StudentProvisioner).
class EmployeeProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): void
    {
        if (!DB::table('roles')->where('name', $name)->exists()) {
            DB::table('roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email).'-'.Str::random(4), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function perusahaan(string $brand = 'PT Test'): Perusahaan
    {
        return Perusahaan::create([
            'name' => $brand, 'brand' => $brand,
            'sessions_per_employee_month' => 2, 'employee_count' => 3, 'is_active' => true,
        ]);
    }

    private function hr(Perusahaan $perusahaan, string $email): User
    {
        $user = $this->user($email, 'hr_admin');
        PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id, 'name' => 'HR', 'role' => 'hr_admin',
        ]);

        return $user;
    }

    private function employee(Perusahaan $perusahaan, ?string $email, ?int $userId = null): PerusahaanEmployee
    {
        return PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $userId,
            'name' => 'Emp', 'email' => $email,
        ]);
    }

    public function test_hr_roster_creation_provisions_employee_user(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ])->assertRedirect();

        $row = PerusahaanEmployee::withoutGlobalScopes()->where('email', 'emp1@test.local')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->user_id);

        $user = User::findOrFail($row->user_id);
        $this->assertSame('emp1@test.local', $user->email);
        $this->assertSame('employee', $user->role->name);
        $this->assertSame(1, User::where('email', 'emp1@test.local')->count());

        // Audit trail undangan, parity dengan alur siswa.
        $this->assertDatabaseHas('roster_invitations', [
            'type' => 'karyawan', 'roster_id' => $row->id, 'email' => 'emp1@test.local',
        ]);
    }

    public function test_duplicate_email_in_same_tenant_is_rejected(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ])->assertRedirect();

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu Lagi', 'email' => 'emp1@test.local',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, PerusahaanEmployee::withoutGlobalScopes()->count());
        $this->assertSame(1, User::where('email', 'emp1@test.local')->count());
    }

    public function test_provisioning_is_idempotent(): void
    {
        $perusahaan = $this->perusahaan();
        $row = $this->employee($perusahaan, 'emp1@test.local');

        $first = EmployeeProvisioner::ensure($row);
        $second = EmployeeProvisioner::ensure($row->fresh());

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->id, $row->fresh()->user_id);
        $this->assertSame(1, User::where('email', 'emp1@test.local')->count());
        $this->assertSame(1, PerusahaanEmployee::withoutGlobalScopes()->count());
    }

    public function test_provisioner_without_email_returns_null(): void
    {
        $perusahaan = $this->perusahaan();
        $row = $this->employee($perusahaan, null);

        $this->assertNull(EmployeeProvisioner::ensure($row));
        $this->assertNull($row->fresh()->user_id);
        $this->assertSame(0, User::count());
    }

    public function test_invite_without_email_is_rejected_without_server_error(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $row = $this->employee($perusahaan, null);

        $this->actingAs($hr)->post(route('perusahaan.employees.invite'), [
            'employee_id' => $row->id,
        ])->assertStatus(422);

        $this->assertSame(0, DB::table('roster_invitations')->count());
    }

    public function test_roster_row_never_links_non_employee_account(): void
    {
        $p = $this->perusahaan('PT Test');
        $hrUser = $this->user('boss@test.local', 'hr_admin');
        $row = $this->employee($p, 'boss@test.local');

        // Email milik akun HR: jangan ditarik jadi karyawan (role-nya bukan employee).
        $this->assertNull(EmployeeProvisioner::ensure($row));
        $this->assertNull($row->fresh()->user_id);
        $this->assertSame($hrUser->role_id, $hrUser->fresh()->role_id);
    }

    public function test_roster_row_never_links_user_from_another_tenant(): void
    {
        $tenantA = $this->perusahaan('PT Test A');
        $tenantB = $this->perusahaan('PT Test B');

        $userA = $this->user('emp1@test.local', 'employee');
        $this->employee($tenantA, 'emp1@test.local', $userA->id);

        $hrB = $this->hr($tenantB, 'hr-b@test.local');

        $this->actingAs($hrB)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ])->assertSessionHasErrors('email');

        // Rollback: tidak ada roster tenant B, user tenant A tetap utuh.
        $this->assertSame(0, PerusahaanEmployee::withoutGlobalScopes()
            ->where('perusahaan_id', $tenantB->id)->count());
        $this->assertSame($tenantA->id, PerusahaanEmployee::withoutGlobalScopes()
            ->where('user_id', $userA->id)->value('perusahaan_id'));

        // Service juga menolak menautkan user milik tenant lain (bypass controller).
        $rowB = $this->employee($tenantB, 'emp1@test.local');
        $this->assertNull(EmployeeProvisioner::ensure($rowB));
        $this->assertNull($rowB->fresh()->user_id);
        $this->assertSame($tenantA->id, PerusahaanEmployee::withoutGlobalScopes()
            ->where('user_id', $userA->id)->value('perusahaan_id'));
    }
}