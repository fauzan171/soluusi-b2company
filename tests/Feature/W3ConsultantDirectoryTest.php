<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Direktori konsultan employee-facing (profil, kategorisasi, tipe, jadwal 7 hari,
// rating agregat) + tenant isolation + kalender konsultan portal + admin role fix.
class W3ConsultantDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaan;
    private Perusahaan $perusahaanB;
    private User $employeeA;
    private User $consultantUser;
    private Consultant $consultant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT A', 'brand' => 'CoA', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $this->perusahaanB = Perusahaan::create([
            'name' => 'PT B', 'brand' => 'CoB', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);

        $empRoleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $this->employeeA = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A',
            'username' => 'emp_a_w3', 'email' => 'emp_a_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $empRoleId,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->employeeA->id,
            'name' => 'Emp A', 'email' => 'emp_a_w3@test.local', 'consent_at' => now(),
        ]);

        $consultRoleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $this->consultantUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Sinta', 'lastname' => 'Konsultan',
            'username' => 'doc_dir_w3', 'email' => 'doc_dir_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $consultRoleId,
        ]);
        $this->consultant = Consultant::create([
            'user_id' => $this->consultantUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Psikolog klinis berpengalaman 10 tahun.',
            'profit_sharing' => 0, 'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $catId = DB::table('categories')->insertGetId([
            'name' => 'Psikolog', 'code_category' => Str::random(10),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consultant_categories')->insert([
            'consultant_id' => $this->consultant->id, 'category_id' => $catId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $pool = ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id,
            'consultant_id' => $this->consultant->id,
        ]);
        ConsultantWorkHour::create([
            'consultant_pool_id' => $pool->id, 'weekday' => 1,
            'start_time' => '09:00:00', 'end_time' => '11:00:00', 'slot_minutes' => 60,
        ]);
    }

    public function test_directory_lists_pool_consultants_for_own_tenant()
    {
        $this->actingAs($this->employeeA)
            ->get(route('perusahaan.consultants'))
            ->assertOk()
            ->assertSeeInOrder(['Sinta', 'Psikolog']);
    }

    public function test_directory_detail_shows_profile_slots_and_rating()
    {
        // Rating aggregate: 2 sessions rated 4 and 5 → avg 4.5.
        $empRow = PerusahaanEmployee::first();
        foreach ([4, 5] as $i => $rating) {
            PerusahaanSession::create([
                'perusahaan_id' => $this->perusahaan->id, 'employee_id' => $empRow->id,
                'consultant_id' => $this->consultant->id, 'konsultan_type' => 'psikolog',
                'status' => 'done', 'rating' => $rating,
                'scheduled_at' => now()->subDays($i + 1), 'completed_at' => now()->subDay(),
            ]);
        }

        $this->actingAs($this->employeeA)
            ->get(route('perusahaan.consultants.show', $this->consultant))
            ->assertOk()
            ->assertSee('Sinta')
            ->assertSee('Psikolog')
            ->assertSee('4.5');
    }

    public function test_employee_of_company_a_cannot_see_company_b_pool_consultant()
    {
        // Consultant only in PT A pool; PT B employee must not see his schedule/detail.
        $empBRoleId = DB::table('roles')->where('name', 'employee')->value('id');
        $empB = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B',
            'username' => 'emp_b_w3', 'email' => 'emp_b_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $empBRoleId,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaanB->id, 'user_id' => $empB->id,
            'name' => 'Emp B', 'email' => 'emp_b_w3@test.local', 'consent_at' => now(),
        ]);

        // Directory for PT B employee must not include Sinta.
        $this->actingAs($empB)
            ->get(route('perusahaan.consultants'))
            ->assertOk()
            ->assertDontSee('Sinta');

        // Detail access cross-tenant = 403.
        $this->actingAs($empB)
            ->get(route('perusahaan.consultants.show', $this->consultant))
            ->assertStatus(403);
    }

    public function test_consultant_calendar_shows_own_sessions_across_companies()
    {
        $session = PerusahaanSession::create([
            'perusahaan_id' => $this->perusahaan->id, 'employee_id' => PerusahaanEmployee::first()->id,
            'consultant_id' => $this->consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(2),
        ]);

        $this->actingAs($this->consultantUser)
            ->get(route('consultant.calendar'))
            ->assertOk()
            ->assertSee('CoA'); // lintas perusahaan: label perusahaan terlihat
    }

    public function test_superadmin_can_access_consultant_calendar()
    {
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        $admin = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Super', 'lastname' => 'Kal',
            'username' => 'admin_kal', 'email' => 'admin_kal@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $adminRoleId,
        ]);

        // recon-R2 fixed via ViewAs (prod B3): superadmin masuk portal konsultan
        // lewat view-as ke user konsultan (audited), bukan akses langsung.
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $this->consultantUser->id])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('consultant.sessions'))
            ->assertOk();

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id, 'action' => 'view_as.start',
        ]);
    }

    public function test_employee_cannot_access_consultant_calendar()
    {
        $this->actingAs($this->employeeA)
            ->get(route('consultant.calendar'))
            ->assertStatus(403);
    }
}
