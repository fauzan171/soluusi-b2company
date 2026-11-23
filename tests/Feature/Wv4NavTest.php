<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv4: nav restructure — 3 portal menus (admin 8 / HR 10 / employee 8).
class Wv4NavTest extends TestCase
{
    use RefreshDatabase;

    private function makeRole(string $name): int
    {
        return DB::table('roles')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function makeUser(string $role, string $suffix): User
    {
        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Nav', 'lastname' => 'Wv4',
            'username' => "nav_{$suffix}", 'email' => "nav_{$suffix}@test.local",
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => $this->makeRole($role),
        ]);
    }

    private function makeCompany(): Perusahaan
    {
        return Perusahaan::create([
            'name' => 'PT Nav', 'brand' => 'NavCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 50, 'is_active' => true,
        ]);
    }

    public function test_admin_nav_has_eight_internal_menus(): void
    {
        $admin = $this->makeUser('admin', 'admin');

        $res = $this->actingAs($admin)->get(route('admin.dashboard'));

        $res->assertOk();
        foreach (['Overview', 'Perusahaan', 'Program Wellbeing', 'Karyawan',
            'Asesmen', 'Expert & Konsultasi', 'Laporan & Insight', 'Billing & Kontrak'] as $label) {
            $res->assertSee($label);
        }
    }

    public function test_hr_nav_has_ten_menus(): void
    {
        $hr = $this->makeUser('hr_admin', 'hr');
        $p = $this->makeCompany();
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->actingAs($hr)->get(route('perusahaan.dashboard'));

        $res->assertOk();
        foreach (['Ringkasan Perusahaan', 'Keterlibatan Karyawan', 'Asesmen Wellbeing',
            'Insight Wellbeing', 'Utilisasi Expert', 'Action Plan', 'Progres Program',
            'Laporan', 'Program & Subscription', 'Profil Perusahaan'] as $label) {
            $res->assertSee($label);
        }
    }

    public function test_employee_nav_has_eight_menus(): void
    {
        $emp = $this->makeUser('employee', 'emp');
        $p = $this->makeCompany();
        DB::table('perusahaan_employees')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $emp->id, 'name' => 'Nav Employee',
            'email' => 'nav_emp@test.local', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->actingAs($emp)->get(route('perusahaan.employee'));

        $res->assertOk();
        foreach (['Kesejahteraanku', 'Asesmenku', 'Hasilku', 'Rekomendasi',
            'Expert Chat', 'Konsultasiku', 'Aktivitasku', 'Privasi & Consent'] as $label) {
            $res->assertSee($label);
        }
    }
}
