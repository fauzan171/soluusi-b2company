<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
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
            'Asesmen', 'Expert & Konsultasi', 'Ops Queue', 'Billing & Kontrak'] as $label) {
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
            'Laporan', 'Program & Subscription', 'Antrian Review', 'Karyawan', 'Konsultan'] as $label) {
            $res->assertSee($label);
        }
        // Audit: Profil Perusahaan route 403s for hr_admin — not in HR nav.
        $res->assertDontSee('Profil Perusahaan');

        foreach (['perusahaan.review', 'perusahaan.employees', 'perusahaan.consultants.pool'] as $name) {
            $res->assertSee('href="'.route($name).'"', false);
            $this->actingAs($hr)->get(route($name))->assertOk();
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
        foreach (['Kesejahteraanku', 'Asesmenku', 'Hasilku',
            'Expert Chat', 'Konsultasiku', 'Aktivitasku', 'Privasi & Consent'] as $label) {
            $res->assertSee($label);
        }
        // Audit: "Rekomendasi" duplicated Hasilku's target.
        $res->assertDontSee('Rekomendasi');
    }

    public function test_bk_nav_links_review_pool_and_import(): void
    {
        $bk = $this->makeUser('bk', 'bk');
        $s = Sekolah::create([
            'name' => 'SMA Nav', 'brand' => 'SMA Nav', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        SekolahAdmin::create(['sekolah_id' => $s->id, 'user_id' => $bk->id, 'role' => 'bk']);

        $res = $this->actingAs($bk)->get(route('sekolah.dashboard'));

        $res->assertOk();
        foreach (['Review Permintaan', 'Pool Konsultan', 'Import Siswa'] as $label) {
            $res->assertSee($label);
        }
        foreach (['sekolah.review', 'sekolah.consultants.pool', 'sekolah.students.import'] as $name) {
            $res->assertSee('href="'.route($name).'"', false);
            $this->actingAs($bk)->get(route($name))->assertOk();
        }
    }

    public function test_nav_has_change_password_link_and_mobile_keeps_hash(): void
    {
        $assoc = $this->makeUser('associate', 'assoc');

        $res = $this->actingAs($assoc)->get(route('associate.dashboard'));

        $res->assertOk();
        $res->assertSee('Ubah password');
        $res->assertSee('href="'.route('password.change').'"', false);
        // Desktop + mobile nav both deep-link to the payout anchor.
        $this->assertSame(2, substr_count($res->getContent(), 'href="'.route('associate.dashboard').'#komisi"'));
    }
}
