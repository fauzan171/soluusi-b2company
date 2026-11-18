<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.3 — gerbang wajib ganti password saat login pertama.
 * Akun hasil import dibuat dengan must_change_password = true; semua route
 * portal membalikkan mereka ke /ubah-password sampai password diganti.
 */
class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role, bool $mustChange = true): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
            'must_change_password' => $mustChange,
        ]);
    }

    private function perusahaan(): Perusahaan
    {
        return Perusahaan::create([
            'name' => 'PT Uji', 'brand' => 'PT Uji', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function sekolah(): Sekolah
    {
        return Sekolah::create([
            'name' => 'SMA Uji', 'brand' => 'SMA Uji', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function employeeWithFlag(): User
    {
        $perusahaan = $this->perusahaan();
        $user = $this->user('karyawan@uji.local', 'employee');
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id,
            'name' => 'Karyawan Uji', 'email' => $user->email, 'consent_at' => now(),
        ]);

        return $user;
    }

    public function test_employee_with_flag_is_redirected_to_change_password(): void
    {
        $this->actingAs($this->employeeWithFlag())
            ->get(route('perusahaan.employee'))
            ->assertRedirect(route('password.change'));
    }

    public function test_change_password_page_does_not_loop(): void
    {
        $this->actingAs($this->employeeWithFlag())
            ->get(route('password.change'))
            ->assertOk();
    }

    public function test_employee_without_flag_is_not_redirected(): void
    {
        $perusahaan = $this->perusahaan();
        $user = $this->user('biasa@uji.local', 'employee', mustChange: false);
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id,
            'name' => 'Karyawan Biasa', 'email' => $user->email, 'consent_at' => now(),
        ]);

        $this->actingAs($user)->get(route('perusahaan.employee'))->assertOk();
    }

    /**
     * Semua grup portal wajib kena gerbang; admin TIDAK (superadmin bypass).
     */
    public function test_admin_with_flag_is_not_gated(): void
    {
        $admin = $this->user('admin@uji.local', 'admin');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_every_portal_group_redirects_user_with_flag(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->user('hr@uji.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $perusahaan->id, 'user_id' => $hr->id]);

        $sekolah = $this->sekolah();
        $bk = $this->user('bk@uji.local', 'bk');
        \App\Models\Sekolah\SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $bk->id]);
        $student = $this->user('siswa@uji.local', 'student');
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $student->id,
            'name' => 'Siswa Uji', 'email' => $student->email,
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);

        $consultantUser = $this->user('konsultan@uji.local', 'consultant');
        Consultant::create([
            'user_id' => $consultantUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
        ]);

        $associate = $this->user('associate@uji.local', 'associate');

        $klinik = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'Klinik Uji', 'is_active' => true]);
        $klinikAdmin = $this->user('klinik@uji.local', 'klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $klinikAdmin->id]);

        $cases = [
            [$hr, 'perusahaan.dashboard'],
            [$bk, 'sekolah.dashboard'],
            [$student, 'sekolah.student'],
            [$consultantUser, 'consultant.sessions'],
            [$associate, 'associate.dashboard'],
            [$klinikAdmin, 'klinik.dashboard'],
        ];

        foreach ($cases as [$user, $route]) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertRedirect(route('password.change'));
        }
    }

    public function test_short_password_is_rejected(): void
    {
        $user = $this->employeeWithFlag();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_same_password_is_rejected(): void
    {
        $user = $this->employeeWithFlag();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->employeeWithFlag();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'bukan-password-saya',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_successful_change_clears_flag_and_lands_on_role_dashboard(): void
    {
        $user = $this->employeeWithFlag();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertRedirect(route('perusahaan.employee'));

        $fresh = $user->fresh();
        $this->assertFalse((bool) $fresh->must_change_password);
        $this->assertNotNull($fresh->password_changed_at);
        $this->assertTrue(Hash::check('password-baru-123', $fresh->password));
    }

    public function test_flag_is_cleared_permanently_across_requests(): void
    {
        $user = $this->employeeWithFlag();

        $this->actingAs($user)->post(route('password.change.store'), [
            'current_password' => 'password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $this->actingAs($user->fresh())->get(route('perusahaan.employee'))->assertOk();
    }

    public function test_guest_is_not_redirected_by_the_gate(): void
    {
        // Gate tidak boleh mencuri redirect dari middleware auth.
        $this->get(route('perusahaan.employee'))->assertRedirect(route('loginIndex'));
    }
}