<?php

namespace Tests\Feature;

use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B6 — UI view-as di detail tenant: form render, start dari form → session
 * view-as aktif + banner menampilkan role target + sisa waktu.
 */
class ViewAsUiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    public function test_perusahaan_show_renders_view_as_form_with_users()
    {
        $admin = $this->admin();
        $perusahaan = Perusahaan::create([
            'name' => 'PT UI', 'brand' => 'UICo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp UI', 'email' => 'emp-ui@test.local', 'consent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tenants.perusahaans.show', $perusahaan))
            ->assertOk()
            ->assertSee('Masuk sebagai')
            ->assertSee('name="user_id"', false)
            ->assertSee('name="reason"', false)
            ->assertSee('Emp UI')
            ->assertSee('employee');
    }

    public function test_klinik_show_renders_view_as_form()
    {
        $admin = $this->admin();
        $klinik = Klinik::create(['name' => 'Klinik UI', 'brand' => 'KU', 'is_active' => true]);
        $kaUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'klinik_admin'])->id]);
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $kaUser->id]);

        $this->actingAs($admin)
            ->get(route('admin.tenants.kliniks.show', $klinik))
            ->assertOk()
            ->assertSee('Masuk sebagai')
            ->assertSee('klinik_admin');
    }

    public function test_sekolah_show_renders_view_as_form_with_bk_and_students()
    {
        $admin = $this->admin();
        $sekolah = Sekolah::create(['name' => 'SMA UI', 'brand' => 'SU', 'is_active' => true]);
        $bkUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'bk'])->id]);
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $bkUser->id]);
        $stUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'student'])->id]);
        SekolahStudent::withoutGlobalScopes()->create([
            'sekolah_id' => $sekolah->id, 'user_id' => $stUser->id,
            'name' => 'Siswa UI', 'email' => 'siswa-ui@test.local',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tenants.sekolahs.show', $sekolah))
            ->assertOk()
            ->assertSee('Masuk sebagai')
            ->assertSee('bk')
            ->assertSee('student');
    }

    public function test_start_from_form_activates_view_as_and_banner_shows_role_and_remaining_time()
    {
        $admin = $this->admin();
        $perusahaan = Perusahaan::create([
            'name' => 'PT UI', 'brand' => 'UICo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp UI', 'email' => 'emp-ui@test.local', 'consent_at' => now(),
        ]);

        // Start dari form (user_id + reason).
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), [
                'user_id' => $empUser->id,
                'reason' => 'UI test',
            ])
            ->assertRedirect();

        $this->assertTrue(\App\Support\ViewAs::isActive());

        // Banner menampilkan role target + sisa waktu.
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang melihat sebagai')
            ->assertSee('Emp UI')
            ->assertSee('(employee)')
            ->assertSee('Sisa waktu')
            ->assertSee('menit');
    }
}
