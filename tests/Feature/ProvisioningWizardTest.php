<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A3 — Provisioning wizard 1 pintu: superadmin create tenant 3 vertikal +
 * akun admin tenant dalam 1 transaksi DB. Pool konsultan opsional
 * (perusahaan — consultant_pools tak punya klinik_id, lihat Out of Scope).
 * Self-register /daftar-mitra tetap jalan terpisah (approval queue).
 */
class ProvisioningWizardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // Role tenant dibuat di sini: controller pakai firstOrFail (role vertikal
        // adalah data master — seeder production yang menjamin ada).
        foreach (['admin', 'klinik_admin', 'hr_admin', 'bk', 'employee'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
        return User::factory()->create(['role_id' => Role::where('name', 'admin')->first()->id]);
    }

    private function basePayload(array $override = []): array
    {
        return array_merge([
            'vertical' => 'klinik',
            'name' => 'Klinik Uji Aja',
            'brand' => 'UjiCare',
            'admin_name' => 'Ani Wijaya',
            'admin_email' => 'ani@uji.test',
            'admin_password' => 'SI_SECRET_PASSWORD_ZZ8QK4M2',
        ], $override);
    }

    public function test_form_wizard_render()
    {
        $this->actingAs($this->admin())->get('/admin/provision')
            ->assertOk()
            ->assertSee('Provisioning');
    }

    public function test_provision_klinik_dengan_akun_admin_dan_audit()
    {
        $response = $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
            'vertical' => 'klinik',
        ]));

        $response->assertRedirect();
        $klinik = Klinik::where('name', 'Klinik Uji Aja')->first();
        $this->assertNotNull($klinik);
        $this->assertTrue((bool) $klinik->is_active); // superadmin vetted → langsung aktif
        $this->assertNotNull($klinik->approved_at);

        $role = Role::where('name', 'klinik_admin')->first();
        $user = User::where('email', 'ani@uji.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals($role->id, $user->role_id);
        $this->assertSame(1, (int) $user->is_active);

        $this->assertDatabaseHas('klinik_admins', [
            'klinik_id' => $klinik->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('admin_actions', [
            'action' => 'klinik.provision',
            'target_type' => (new Klinik())->getMorphClass(),
            'target_id' => $klinik->id,
        ]);
    }

    public function test_provision_perusahaan_dengan_pool_konsultan_opsional()
    {
        $c1 = Consultant::factory()->create(['status' => 'accept']);
        $c2 = Consultant::factory()->create(['status' => 'accept']);

        $response = $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
            'vertical' => 'perusahaan',
            'name' => 'PT Uji Coba',
            'brand' => 'UjiCorp',
            'pepm_rate' => 20000,
            'consultant_ids' => [$c1->id, $c2->id],
        ]));

        $response->assertRedirect();
        $p = Perusahaan::where('name', 'PT Uji Coba')->first();
        $this->assertNotNull($p);
        $this->assertTrue((bool) $p->is_active);
        $this->assertNotNull($p->mou_at);
        $this->assertSame(20000, (int) $p->pepm_rate);

        $hr = User::where('email', 'ani@uji.test')->first();
        $this->assertEquals(Role::where('name', 'hr_admin')->first()->id, $hr->role_id);
        $this->assertDatabaseHas('perusahaan_admins', [
            'perusahaan_id' => $p->id,
            'user_id' => $hr->id,
            'role' => 'hr_admin',
        ]);

        $this->assertSame(2, ConsultantPool::where('perusahaan_id', $p->id)->count());
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'perusahaan.provision',
            'target_type' => (new Perusahaan())->getMorphClass(),
            'target_id' => $p->id,
        ]);
    }

    public function test_provision_sekolah_tanpa_pool()
    {
        $response = $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
            'vertical' => 'sekolah',
            'name' => 'SMA Uji Pintar',
            'brand' => 'SMA Uji',
            'tingkat' => 'SMA',
        ]));

        $response->assertRedirect();
        $s = Sekolah::where('name', 'SMA Uji Pintar')->first();
        $this->assertNotNull($s);
        $this->assertSame('SMA', $s->tingkat);
        $this->assertFalse((bool) $s->is_active); // pending aktivasi program (pattern A1 sekolah)

        $bk = User::where('email', 'ani@uji.test')->first();
        $this->assertEquals(Role::where('name', 'bk')->first()->id, $bk->role_id);
        $this->assertDatabaseHas('sekolah_admins', [
            'sekolah_id' => $s->id,
            'user_id' => $bk->id,
            'role' => 'bk',
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'sekolah.provision',
            'target_id' => $s->id,
        ]);
    }

    public function test_rollback_email_terpakai_tidak_meninggalkan_tenant_yatim()
    {
        User::factory()->create(['email' => 'taken@uji.test']);

        foreach (['klinik', 'perusahaan', 'sekolah'] as $vertical) {
            $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
                'vertical' => $vertical,
                'admin_email' => 'taken@uji.test',
            ]))->assertRedirect()->assertSessionHasErrors('admin_email');
        }

        $this->assertSame(0, Klinik::count());
        $this->assertSame(0, Perusahaan::count());
        $this->assertSame(0, Sekolah::count());
        $this->assertSame(0, KlinikAdmin::count());
        $this->assertSame(0, PerusahaanAdmin::count());
        $this->assertSame(0, SekolahAdmin::count());
    }

    public function test_rollback_konsultan_invalid_tidak_meninggalkan_tenant_yatim()
    {
        $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
            'vertical' => 'perusahaan',
            'consultant_ids' => [99999],
        ]))->assertRedirect()->assertSessionHasErrors('consultant_ids.0');

        $this->assertSame(0, Perusahaan::count());
        $this->assertSame(0, ConsultantPool::count());
    }

    public function test_password_pendek_ditolak()
    {
        $this->actingAs($this->admin())->post('/admin/provision', $this->basePayload([
            'admin_password' => 'pendek',
        ]))->assertRedirect()->assertSessionHasErrors('admin_password');
    }

    public function test_non_admin_tidak_bisa_akses()
    {
        $role = Role::firstOrCreate(['name' => 'employee']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/admin/provision')->assertStatus(403);
        $this->actingAs($user)->post('/admin/provision', [])->assertStatus(403);
    }

    public function test_self_register_daftar_mitra_tetap_jalan_masuk_approval_queue()
    {
        Role::firstOrCreate(['name' => 'klinik_admin']);
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $response = $this->post('/daftar-mitra', [
            'org_name' => 'Klinik Mitra Baru',
            'brand' => 'MitraBaru',
            'facility_type' => 'klinik_pratama',
            'regency_id' => '3171',
            'service_interest' => 'psikolog',
            'pic_name' => 'Citra Lestari',
            'email' => 'citra@mitra.test',
            'phone' => '081234567890',
            'password' => 'SI_SECRET_PASSWORD_T7VMBG8C',
            'password_confirmation' => 'SI_SECRET_PASSWORD_T7VMBG8C',
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $klinik = Klinik::where('name', 'Klinik Mitra Baru')->first();
        $this->assertNotNull($klinik);
        $this->assertFalse((bool) $klinik->is_active); // menunggu approval ops — bukan duplikat wizard
        $this->assertNull($klinik->approved_at);
        $this->assertDatabaseHas('klinik_admins', ['klinik_id' => $klinik->id]);
        $this->assertNotNull(User::where('email', 'citra@mitra.test')->first());
    }
}
