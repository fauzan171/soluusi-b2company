<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// K8: flag lock request konsultan per perusahaan `consultant_request_enabled`
// default 0 — POST request 403 server-side + tombol hidden. Toggle superadmin.
class W3RequestLockTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaan;
    private User $employee;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
            'consultant_request_enabled' => false,
        ]);

        $empRoleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $this->employee = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'Lock',
            'username' => 'emp_lock', 'email' => 'emp_lock@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $empRoleId,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->employee->id,
            'name' => 'Emp Lock', 'email' => 'emp_lock@test.local', 'consent_at' => now(),
        ]);

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        $this->admin = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Super', 'lastname' => 'Lock',
            'username' => 'admin_lock', 'email' => 'admin_lock@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $adminRoleId,
        ]);
    }

    public function test_request_blocked_403_when_flag_off()
    {
        $this->actingAs($this->employee)
            ->post(route('perusahaan.request-consultation'), [
                'konsultan_type' => 'psikolog',
                'body' => 'Butuh konsultasi.',
            ])->assertStatus(403);
    }

    public function test_request_allowed_when_flag_on()
    {
        $this->perusahaan->update(['consultant_request_enabled' => true]);

        $consultRoleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $docUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Doc', 'lastname' => 'Lock',
            'username' => 'doc_lock', 'email' => 'doc_lock@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $consultRoleId,
        ]);
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id, 'consultant_id' => $consultant->id,
        ]);

        $this->actingAs($this->employee)
            ->post(route('perusahaan.request-consultation'), [
                'konsultan_type' => 'psikolog',
                'body' => 'Butuh konsultasi.',
            ])->assertRedirect();
    }

    public function test_button_hidden_when_flag_off()
    {
        $this->actingAs($this->employee)
            ->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertDontSee('Minta Konsultasi →');
    }

    public function test_button_tooltip_dikunci_admin_when_flag_off()
    {
        // Tombol locked + tooltip "Dikunci admin" di employee dashboard.
        $this->actingAs($this->employee)
            ->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertSeeText('Dikunci admin');
    }

    public function test_superadmin_can_toggle_flag()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.perusahaans.request-toggle', $this->perusahaan))
            ->assertRedirect();

        $this->assertTrue((bool) $this->perusahaan->fresh()->consultant_request_enabled);

        $this->actingAs($this->admin)
            ->post(route('admin.perusahaans.request-toggle', $this->perusahaan))
            ->assertRedirect();

        $this->assertFalse((bool) $this->perusahaan->fresh()->consultant_request_enabled);
    }

    public function test_hr_cannot_toggle_flag()
    {
        $hrRoleId = DB::table('roles')->where('name', 'hr_admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Lock',
            'username' => 'hr_lock', 'email' => 'hr_lock@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);

        $this->actingAs($hr)
            ->post(route('admin.perusahaans.request-toggle', $this->perusahaan))
            ->assertStatus(403);
    }
}
