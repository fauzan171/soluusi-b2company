<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Super admin memprovision tenant B2B: perusahaan + akun HR sekaligus.
 * Karyawan tetap masuk via undangan roster HR — bukan dibuat super admin.
 */
class AdminPerusahaanProvisionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_super_admin_bisa_buat_perusahaan_dan_akun_hr()
    {
        $hrRole = Role::firstOrCreate(['name' => 'hr_admin']);

        $response = $this->actingAs($this->admin())->post('/admin/perusahaans', [
            'name' => 'PT Uji Coba',
            'brand' => 'UjiCoba',
            'hr_name' => 'Budi Santoso',
            'hr_email' => 'budi@ujicoba.co.id',
            'hr_password' => 'rahasia123',
        ]);

        $response->assertRedirect();
        $perusahaan = Perusahaan::where('name', 'PT Uji Coba')->first();
        $this->assertNotNull($perusahaan);
        $this->assertTrue($perusahaan->is_active);

        $hr = User::where('email', 'budi@ujicoba.co.id')->first();
        $this->assertNotNull($hr);
        $this->assertEquals($hrRole->id, $hr->role_id);
        $this->assertSame(1, (int) $hr->is_active);

        $this->assertDatabaseHas('perusahaan_admins', [
            'perusahaan_id' => $perusahaan->id,
            'user_id' => $hr->id,
            'role' => 'hr_admin',
        ]);
    }

    public function test_email_hr_yang_sudah_dipakai_ditolak()
    {
        $taken = User::factory()->create(['email' => 'ada@ujicoba.co.id']);

        $response = $this->actingAs($this->admin())->post('/admin/perusahaans', [
            'name' => 'PT Duplikat',
            'brand' => 'Duplikat',
            'hr_name' => 'Ada Saja',
            'hr_email' => 'ada@ujicoba.co.id',
            'hr_password' => 'rahasia123',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('hr_email');
        $this->assertSame(0, Perusahaan::where('name', 'PT Duplikat')->count());
    }

    public function test_non_admin_tidak_bisa_akses()
    {
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $user = User::factory()->create(['role_id' => $employeeRole->id]);

        $this->actingAs($user)->get('/admin/perusahaans')->assertStatus(403);
        $this->actingAs($user)->post('/admin/perusahaans', [])->assertStatus(403);
    }
}
