<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.4 — konfirmasi nama + pilih divisi/kelas saat login pertama.
 * Baris roster hasil import dibuat dengan profile_confirmed_at NULL, sehingga
 * karyawan/siswa mendarat di halaman profil sebelum portal terbuka.
 */
class FirstLoginProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function perusahaan(string $name = 'PT Profil'): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function sekolah(string $name = 'SMA Profil'): Sekolah
    {
        return Sekolah::create([
            'name' => $name, 'brand' => $name, 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function unconfirmedEmployee(?Perusahaan $perusahaan = null): PerusahaanEmployee
    {
        $perusahaan ??= $this->perusahaan();
        $user = $this->user('karyawan.baru@uji.local', 'employee');

        $user->update(['must_confirm_profile' => true]);

        return PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id,
            'name' => 'Nama Sementara', 'email' => $user->email,
        ]);
    }

    public function test_unconfirmed_employee_is_redirected_to_profile(): void
    {
        $employee = $this->unconfirmedEmployee();

        $this->actingAs($employee->user)
            ->get(route('perusahaan.employee'))
            ->assertRedirect(route('perusahaan.profile'));
    }

    public function test_profile_page_renders_and_does_not_loop(): void
    {
        $employee = $this->unconfirmedEmployee();

        $this->actingAs($employee->user)
            ->get(route('perusahaan.profile'))
            ->assertOk()
            ->assertSee('Nama Sementara');
    }

    public function test_employee_picks_division_and_name_is_saved(): void
    {
        $perusahaan = $this->perusahaan();
        $employee = $this->unconfirmedEmployee($perusahaan);
        $dept = Departement::create(['name' => 'Engineering', 'perusahaan_id' => $perusahaan->id]);

        $this->actingAs($employee->user)
            ->post(route('perusahaan.profile.store'), [
                'name' => 'Nama Sebenarnya',
                'departement_id' => $dept->id,
            ])
            ->assertRedirect(route('perusahaan.employee'));

        $fresh = $employee->fresh();
        $this->assertSame('Nama Sebenarnya', $fresh->name);
        $this->assertSame($dept->id, $fresh->departement_id);
        $this->assertFalse($fresh->user->fresh()->must_confirm_profile);

        // Nama akun ikut terkoreksi, bukan hanya row roster.
        $user = $fresh->user->fresh();
        $this->assertSame('Nama', $user->firstname);
        $this->assertSame('Sebenarnya', $user->lastname);
        $this->assertSame('Nama Sebenarnya', $user->fullname);
    }

    public function test_onboarding_route_rejects_roles_without_a_roster_row(): void
    {
        $this->actingAs($this->user('bk.profil@uji.local', 'bk'))
            ->get(route('perusahaan.profile'))
            ->assertForbidden();
    }

    public function test_division_of_other_tenant_is_rejected(): void
    {
        $perusahaan = $this->perusahaan();
        $other = $this->perusahaan('PT Lain');
        $employee = $this->unconfirmedEmployee($perusahaan);
        $foreign = Departement::create(['name' => 'Legal Lain', 'perusahaan_id' => $other->id]);

        $this->actingAs($employee->user)
            ->post(route('perusahaan.profile.store'), [
                'name' => 'Nama Sebenarnya',
                'departement_id' => $foreign->id,
            ])
            ->assertStatus(422);

        $this->assertTrue($employee->fresh()->user->must_confirm_profile);
    }

    public function test_empty_name_is_rejected(): void
    {
        $employee = $this->unconfirmedEmployee();

        $this->actingAs($employee->user)
            ->post(route('perusahaan.profile.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_consent_is_untouched_by_profile_confirmation(): void
    {
        $perusahaan = $this->perusahaan();
        $employee = $this->unconfirmedEmployee($perusahaan);
        $dept = Departement::create(['name' => 'HR', 'perusahaan_id' => $perusahaan->id]);

        $this->actingAs($employee->user)
            ->post(route('perusahaan.profile.store'), ['name' => 'Nama Baru', 'departement_id' => $dept->id]);

        $this->assertNull($employee->fresh()->consent_at);
    }

    public function test_confirmed_employee_is_not_redirected_again(): void
    {
        $perusahaan = $this->perusahaan();
        $employee = $this->unconfirmedEmployee($perusahaan);
        $employee->update(['consent_at' => now()]);
        $employee->user->update(['must_confirm_profile' => false]);

        $this->actingAs($employee->user)
            ->get(route('perusahaan.employee'))
            ->assertOk();
    }

    public function test_hr_admin_is_not_gated(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->user('hr.profil@uji.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $perusahaan->id, 'user_id' => $hr->id]);

        $this->actingAs($hr)->get(route('perusahaan.dashboard'))->assertOk();
    }

    public function test_password_gate_wins_over_profile_gate(): void
    {
        $employee = $this->unconfirmedEmployee();
        $employee->user->update(['must_change_password' => true]);

        $this->actingAs($employee->user->fresh())
            ->get(route('perusahaan.employee'))
            ->assertRedirect(route('password.change'));
    }

    public function test_unconfirmed_student_is_redirected_to_profile(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa.baru@uji.local', 'student');
        $user->update(['must_confirm_profile' => true]);
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Nama Sementara', 'email' => $user->email, 'kelas' => 'X IPA 1',
        ]);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertRedirect(route('sekolah.profile'));
    }

    public function test_student_saves_kelas(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa.baru@uji.local', 'student');
        $user->update(['must_confirm_profile' => true]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Nama Sementara', 'email' => $user->email, 'kelas' => 'X IPA 1',
        ]);

        $this->actingAs($user)
            ->post(route('sekolah.profile.store'), ['name' => 'Nama Siswa', 'kelas' => 'XI IPS 2'])
            ->assertRedirect(route('sekolah.student'));

        $fresh = $student->fresh();
        $this->assertSame('Nama Siswa', $fresh->name);
        $this->assertSame('XI IPS 2', $fresh->kelas);
        $this->assertFalse($fresh->user->fresh()->must_confirm_profile);
        $this->assertNull($fresh->parent_consent_at);
    }

    public function test_student_kelas_is_required(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa.baru@uji.local', 'student');
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Nama Sementara', 'email' => $user->email, 'kelas' => 'X IPA 1',
        ]);

        $this->actingAs($user)
            ->post(route('sekolah.profile.store'), ['name' => 'Nama Siswa'])
            ->assertSessionHasErrors('kelas');
    }

    public function test_user_without_roster_row_is_not_gated(): void
    {
        // Akun yang belum punya row roster (mis. superadmin) tidak boleh diblokir.
        $admin = $this->user('admin.profil@uji.local', 'admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    /**
     * Regresi: flag menyala tapi row roster hilang (HR hapus/pindah tenant
     * sebelum login pertama). Tanpa row, halaman profil sendiri 403 — gate
     * harus diam, bukan mengunci user di redirect yang tidak pernah selesai.
     */
    public function test_flag_alone_without_roster_row_does_not_lock_the_user_out(): void
    {
        $user = $this->user('tanpa.row@uji.local', 'employee');
        $user->update(['must_confirm_profile' => true]);

        // Controller boleh 403 (memang tanpa row), yang penting gate TIDAK
        // melempar ke halaman profil — di sana user akan 403 selamanya.
        $response = $this->actingAs($user)->get(route('perusahaan.employee'));

        $this->assertFalse($response->isRedirect(route('perusahaan.profile')));
    }

    public function test_student_flag_without_roster_row_is_not_gated(): void
    {
        $user = $this->user('siswa.tanpa.row@uji.local', 'student');
        $user->update(['must_confirm_profile' => true]);

        $response = $this->actingAs($user)->get(route('sekolah.student'));

        $this->assertFalse($response->isRedirect(route('sekolah.profile')));
    }

    /**
     * Regresi penting: row roster lama (input manual HR / self-register) tidak
     * boleh tiba-tiba kena gerbang profil — hanya akun hasil import CSV.
     */
    public function test_legacy_roster_row_is_not_gated(): void
    {
        $perusahaan = $this->perusahaan();
        $user = $this->user('karyawan.lama@uji.local', 'employee');
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id,
            'name' => 'Karyawan Lama', 'email' => $user->email, 'consent_at' => now(),
        ]);

        $this->actingAs($user)->get(route('perusahaan.employee'))->assertOk();
    }
}