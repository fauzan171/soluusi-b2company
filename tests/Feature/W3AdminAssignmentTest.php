<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantAvailabilityException;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// K6/K7: superadmin assign/unassign konsultan (contract CRUD) + workhour
// template CRUD + exception CRUD. HR hanya lihat. Admin-only enforcement.
class W3AdminAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Perusahaan $perusahaan;
    private Consultant $consultant;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        $this->admin = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Super', 'lastname' => 'Admin',
            'username' => 'admin_w3', 'email' => 'admin_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $adminRoleId,
        ]);

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);

        $consultRoleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $docUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Doc', 'lastname' => 'W3',
            'username' => 'doc_w3a', 'email' => 'doc_w3a@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $consultRoleId,
        ]);
        $this->consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
    }

    public function test_superadmin_can_assign_consultant_with_contract()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.assignments.store'), [
                'perusahaan_id' => $this->perusahaan->id,
                'consultant_id' => $this->consultant->id,
                'contract_start' => '2026-11-01',
                'contract_end' => '2027-11-01',
            ])->assertRedirect();

        $pool = ConsultantPool::where('perusahaan_id', $this->perusahaan->id)
            ->where('consultant_id', $this->consultant->id)->first();
        $this->assertNotNull($pool);
        $this->assertEquals('active', $pool->status);
        $this->assertEquals('2026-11-01', $pool->contract_start->toDateString());
    }

    public function test_superadmin_can_end_assignment()
    {
        $pool = ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id,
            'consultant_id' => $this->consultant->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.assignments.end', $pool))
            ->assertRedirect();

        $this->assertEquals('ended', $pool->fresh()->status);
    }

    public function test_superadmin_can_set_workhours()
    {
        $pool = ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id,
            'consultant_id' => $this->consultant->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.workhours.store'), [
                'consultant_pool_id' => $pool->id,
                'weekday' => 1,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'slot_minutes' => 60,
            ])->assertRedirect();

        $this->assertDatabaseHas('consultant_work_hours', [
            'consultant_pool_id' => $pool->id, 'weekday' => 1, 'slot_minutes' => 60,
        ]);
    }

    public function test_superadmin_can_delete_workhour()
    {
        $pool = ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id,
            'consultant_id' => $this->consultant->id,
        ]);
        $wh = ConsultantWorkHour::create([
            'consultant_pool_id' => $pool->id, 'weekday' => 2,
            'start_time' => '09:00:00', 'end_time' => '12:00:00',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.workhours.destroy', $wh))
            ->assertRedirect();

        $this->assertDatabaseMissing('consultant_work_hours', ['id' => $wh->id]);
    }

    public function test_superadmin_can_add_exception()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.exceptions.store'), [
                'consultant_id' => $this->consultant->id,
                'date' => '2026-12-25',
                'start_time' => null,
                'end_time' => null,
            ])->assertRedirect();

        $this->assertDatabaseHas('consultant_availability_exceptions', [
            'consultant_id' => $this->consultant->id, 'date' => '2026-12-25 00:00:00',
        ]);
    }

    public function test_hr_admin_cannot_assign_consultants()
    {
        $hrRoleId = DB::table('roles')->where('name', 'hr_admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'W3',
            'username' => 'hr_w3', 'email' => 'hr_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);

        $this->actingAs($hr)
            ->post(route('admin.assignments.store'), [
                'perusahaan_id' => $this->perusahaan->id,
                'consultant_id' => $this->consultant->id,
            ])->assertStatus(403);

        $this->assertEquals(0, ConsultantPool::count());
    }

    public function test_employee_cannot_access_admin_assignment_routes()
    {
        $empRoleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $emp = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'W3',
            'username' => 'emp_w3', 'email' => 'emp_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $empRoleId,
        ]);

        $this->actingAs($emp)->get(route('admin.assignments.index'))->assertStatus(403);
    }
}
