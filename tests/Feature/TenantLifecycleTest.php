<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A2: tenant lifecycle — suspend/activate superadmin dengan alasan wajib,
 * admin tenant suspended tidak bisa login, audit tercatat.
 */
class TenantLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function hrOf(Perusahaan $p): User
    {
        $role = Role::firstOrCreate(['name' => 'hr_admin']);
        return User::factory()->create(['role_id' => $role->id, 'password' => Hash::make('SI_SECRET_PASSWORD_Q72NFRH5')]);
    }

    private function makePerusahaan(): Perusahaan
    {
        $p = Perusahaan::create([
            'name' => 'PT Uji', 'brand' => 'Uji', 'pepm_rate' => 15000,
            'sessions_per_employee_month' => 2, 'employee_count' => 10,
            'is_active' => true,
        ]);
        $u = $this->hrOf($p);
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $u->id, 'role' => 'hr_admin']);
        return $p->refresh();
    }

    public function test_suspend_tanpa_alasan_ditolak()
    {
        $p = $this->makePerusahaan();
        $this->actingAs($this->admin())
            ->post("/admin/tenants/perusahaans/{$p->id}/suspend", [])
            ->assertRedirect(); // validation error → redirect back (web session)
        $this->assertNotSame('suspended', $p->refresh()->status);
    }

    public function test_suspend_lalu_aktifkan_dengan_audit()
    {
        $p = $this->makePerusahaan();
        $this->actingAs($this->admin())
            ->post("/admin/tenants/perusahaans/{$p->id}/suspend", ['reason' => 'tunggu pembayaran'])
            ->assertRedirect();
        $this->assertSame('suspended', $p->refresh()->status);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'perusahaan.suspend',
            'target_id' => $p->id,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/tenants/perusahaans/{$p->id}/activate")
            ->assertRedirect();
        $this->assertSame('active', $p->refresh()->status);
        $this->assertDatabaseHas('admin_actions', ['action' => 'perusahaan.activate']);
    }

    public function test_hr_dari_tenant_suspended_tidak_bisa_login()
    {
        $p = $this->makePerusahaan();
        $hr = PerusahaanAdmin::where('perusahaan_id', $p->id)->first()->user;

        $this->post('/login', ['email' => $hr->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($hr);
        auth()->logout();

        $p->status = 'suspended';
        $p->save();

        $this->post('/login', ['email' => $hr->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5'])
            ->assertRedirect();
        $this->assertGuest();
    }

    public function test_context_portal_hr_ditolak_saat_suspended()
    {
        $p = $this->makePerusahaan();
        $p->status = 'suspended';
        $p->save();
        $hr = PerusahaanAdmin::where('perusahaan_id', $p->id)->first()->user;

        // dashboard route dikecualikan dari is_active check, tapi lifecycle guard tetap menolak
        $this->actingAs($hr)->get('/perusahaan/dashboard')->assertStatus(403);
    }

    public function test_suspend_klinik_dan_sekolah_sama_aturan()
    {
        $k = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU', 'is_active' => true]);
        $this->actingAs($this->admin())
            ->post("/admin/tenants/kliniks/{$k->id}/suspend", ['reason' => 'uji'])
            ->assertRedirect();
        $this->assertSame('suspended', $k->refresh()->status);
    }

    public function test_employee_role_tidak_bisa_suspend()
    {
        $p = $this->makePerusahaan();
        $role = Role::firstOrCreate(['name' => 'employee']);
        $u = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($u)
            ->post("/admin/tenants/perusahaans/{$p->id}/suspend", ['reason' => 'x'])
            ->assertStatus(403);
        $this->assertSame('active', $p->refresh()->status);
    }

    public function test_employee_dari_tenant_suspended_tidak_bisa_login()
    {
        $p = $this->makePerusahaan();
        $roleEmp = Role::firstOrCreate(['name' => 'employee']);
        $emp = User::factory()->create(['role_id' => $roleEmp->id, 'password' => Hash::make('SI_SECRET_PASSWORD_Q72NFRH5')]);
        \App\Models\Perusahaan\PerusahaanEmployee::create([
            'perusahaan_id' => $p->id,
            'user_id' => $emp->id,
            'name' => 'Karyawan Uji',
            'email' => $emp->email,
            'consent_at' => now(),
        ]);

        // Login OK saat aktif
        $this->post('/login', ['email' => $emp->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertAuthenticatedAs($emp);
        auth()->logout();

        // Suspend → login ditolak
        $p->status = 'suspended';
        $p->save();

        $this->post('/login', ['email' => $emp->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertGuest();
    }

    public function test_employee_portal_ditolak_saat_tenant_suspended()
    {
        $p = $this->makePerusahaan();
        $roleEmp = Role::firstOrCreate(['name' => 'employee']);
        $emp = User::factory()->create(['role_id' => $roleEmp->id]);
        \App\Models\Perusahaan\PerusahaanEmployee::create([
            'perusahaan_id' => $p->id,
            'user_id' => $emp->id,
            'name' => 'Karyawan Uji',
            'email' => $emp->email,
            'consent_at' => now(),
        ]);

        // Aktif → bisa akses portal (200/302, bukan 403 suspended)
        $this->actingAs($emp)->get('/perusahaan/employee')->assertOk();

        // Suspended → 403
        $p->status = 'suspended';
        $p->save();
        $this->actingAs($emp)->get('/perusahaan/employee')->assertStatus(403);
    }

    public function test_student_dari_sekolah_suspended_tidak_bisa_login()
    {
        $s = \App\Models\Sekolah\Sekolah::create([
            'name' => 'SMA Uji', 'brand' => 'SMAU',
            'sessions_per_student_month' => 2, 'student_count' => 100,
            'is_active' => true,
        ]);
        $roleStu = Role::firstOrCreate(['name' => 'student']);
        $stu = User::factory()->create(['role_id' => $roleStu->id, 'password' => Hash::make('SI_SECRET_PASSWORD_Q72NFRH5')]);
        \App\Models\Sekolah\SekolahStudent::create([
            'sekolah_id' => $s->id,
            'user_id' => $stu->id,
            'name' => 'Siswa Uji',
            'email' => $stu->email,
            'parent_consent_at' => now(),
        ]);

        $this->post('/login', ['email' => $stu->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertAuthenticatedAs($stu);
        auth()->logout();

        $s->status = 'suspended';
        $s->save();

        $this->post('/login', ['email' => $stu->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertGuest();
    }

    public function test_konsultan_dari_klinik_suspended_tidak_bisa_login()
    {
        $k = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU', 'is_active' => true]);
        $roleKon = Role::firstOrCreate(['name' => 'consultant']);
        $konUser = User::factory()->create(['role_id' => $roleKon->id, 'password' => Hash::make('SI_SECRET_PASSWORD_Q72NFRH5')]);
        $consultant = \App\Models\Consultant::create([
            'user_id' => $konUser->id,
            'status' => 'accept',
            'about' => 'Konsultan uji',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'profit_sharing' => 0,
        ]);
        // Konsultan melayani klinik via schedules (klinik_id)
        \App\Models\Schedule::create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $k->id,
            'started_at' => now()->addDay(),
            'ended_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $this->post('/login', ['email' => $konUser->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertAuthenticatedAs($konUser);
        auth()->logout();

        $k->status = 'suspended';
        $k->save();

        $this->post('/login', ['email' => $konUser->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertGuest();
    }

    public function test_konsultan_tanpa_klinik_tetap_bisa_login()
    {
        // Konsultan independen (tidak melayani klinik manapun) tidak kena block
        $roleKon = Role::firstOrCreate(['name' => 'consultant']);
        $konUser = User::factory()->create(['role_id' => $roleKon->id, 'password' => Hash::make('SI_SECRET_PASSWORD_Q72NFRH5')]);
        \App\Models\Consultant::create([
            'user_id' => $konUser->id,
            'status' => 'accept',
            'about' => 'Konsultan uji',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'profit_sharing' => 0,
        ]);

        $this->post('/login', ['email' => $konUser->email, 'password' => 'SI_SECRET_PASSWORD_Q72NFRH5']);
        $this->assertAuthenticatedAs($konUser);
    }
}
