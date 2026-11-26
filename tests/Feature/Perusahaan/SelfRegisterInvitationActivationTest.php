<?php

namespace Tests\Feature\Perusahaan;

use App\Mail\Perusahaan\RosterInvitationMail;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

// First-login karyawan via undangan HR: EmployeeProvisioner membuat user
// seketika (password acak, must_change_password=true) sehingga jalur
// "daftar akun baru" tidak pernah tersedia — token undangan wajib
// dianggap bukti kepemilikan email untuk user provisioned.
class SelfRegisterInvitationActivationTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): void
    {
        if (!DB::table('roles')->where('name', $name)->exists()) {
            DB::table('roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function perusahaan(string $brand = 'PT Test'): Perusahaan
    {
        return Perusahaan::create([
            'name' => $brand, 'brand' => $brand,
            'sessions_per_employee_month' => 2, 'employee_count' => 3, 'is_active' => true,
        ]);
    }

    private function hr(Perusahaan $perusahaan, string $email): User
    {
        $this->role('hr_admin');
        $roleId = DB::table('roles')->where('name', 'hr_admin')->value('id');
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Test',
            'username' => 'hr-'.Str::random(8), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id, 'name' => 'HR', 'role' => 'hr_admin',
        ]);

        return $user;
    }

    /** Alur penuh: HR store → mail → aktivasi via token → portal 200. */
    public function test_provisioned_employee_activates_account_via_invitation_token(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');
        Mail::fake();

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ])->assertRedirect();
        Mail::assertSent(RosterInvitationMail::class, 1);

        $emp = PerusahaanEmployee::withoutGlobalScopes()->where('email', 'emp1@test.local')->firstOrFail();
        $this->assertNotNull($emp->user_id);
        $this->assertTrue((bool) User::find($emp->user_id)->must_change_password);

        $token = DB::table('roster_invitations')->where('roster_id', $emp->id)
            ->whereNull('consumed_at')->whereNull('revoked_at')->value('token_hash');
        // Token hash tidak bisa dibalik — ambil URL dari Mailable yang dikirim.
        $url = null;
        Mail::sent(RosterInvitationMail::class, function ($mail) use (&$url) {
            $url = $mail->registerUrl;
        });
        $this->assertNotNull($url);
        $this->assertNotNull($token);

        $this->get($url)->assertOk();

        // Karyawan belum login: token undangan = bukti kepemilikan email.
        $newPassword = 'SI_SECRET_PASSWORD_0Y41RB8F';
        $this->post($url, ['name' => 'Emp Satu', 'password' => $newPassword])
            ->assertRedirect(route('perusahaan.employee'));

        $user = User::find($emp->user_id);
        $this->assertEquals($user->id, auth()->id());
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse((bool) $user->must_change_password);
        // Dua undangan terbit (provisioner + kirim email, yang lama di-revoke);
        // yang aktif harus terkonsumsi.
        $this->assertNotNull(DB::table('roster_invitations')->where('roster_id', $emp->id)
            ->whereNull('revoked_at')->value('consumed_at'));

        // Portal karyawan terjangkau tanpa ganti password lagi.
        $this->get(route('perusahaan.employee'))->assertOk();
    }

    public function test_activation_rejects_user_linked_to_another_tenant_roster(): void
    {
        $tenantA = $this->perusahaan('PT A');
        $tenantB = $this->perusahaan('PT B');
        $hrB = $this->hr($tenantB, 'hr-b@test.local');
        $this->role('employee');
        Mail::fake();

        // User employee ter-link ke roster tenant A.
        $roleId = DB::table('roles')->where('name', 'employee')->value('id');
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A',
            'username' => 'emp-'.Str::random(8), 'email' => 'emp1@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $tenantA->id, 'user_id' => $user->id,
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ]);

        // Row tenant B belum ter-link; email duplikat lintas tenant sudah ditolak
        // controller, jadi buat row + undangan langsung via service (parity jalur
        // "kirim ulang undangan" HR).
        $rowB = PerusahaanEmployee::create([
            'perusahaan_id' => $tenantB->id, 'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ]);
        $url = \App\Services\RosterInvitation::issue('karyawan', $rowB);

        // Membership ambigu → 403, bukan takeover akun tenant A.
        $this->post($url, ['name' => 'Emp Satu', 'password' => 'SecretPass123'])->assertForbidden();
        $this->assertNull(DB::table('roster_invitations')->where('roster_id', $rowB->id)->value('consumed_at'));
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_activation_rejects_non_employee_role_account(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');
        Mail::fake();

        // Akun hr_admin memakai email yang sama dengan roster row tenant ini
        // (provisioner menolak menautkan — row tetap dapat undangan).
        $bossRoleId = DB::table('roles')->where('name', 'hr_admin')->value('id');
        $boss = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Boss', 'lastname' => 'Test',
            'username' => 'boss-'.Str::random(8), 'email' => 'boss@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $bossRoleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'name' => 'Boss Test', 'email' => 'boss@test.local',
        ]);

        $url = \App\Services\RosterInvitation::issue('karyawan', $emp);

        $this->post($url, ['name' => 'Boss Test', 'password' => 'SecretPass123'])->assertForbidden();
        $this->assertNull($emp->fresh()->user_id);
        $this->assertTrue(Hash::check('password', $boss->fresh()->password));
    }

    public function test_password_reset_clears_must_change_password_flag(): void
    {
        $perusahaan = $this->perusahaan();
        $this->role('employee');
        $roleId = DB::table('roles')->where('name', 'employee')->value('id');
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'Test',
            'username' => 'emp-'.Str::random(8), 'email' => 'emp1@test.local',
            'password' => bcrypt('old-password'), 'is_active' => true,
            'role_id' => $roleId, 'must_change_password' => true,
        ]);

        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email' => 'emp1@test.local', 'token' => bcrypt($token), 'created_at' => now(),
        ]);

        $newPassword = 'SI_SECRET_PASSWORD_K6ZD2X4R';
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'emp1@test.local',
            'password' => $newPassword, 'password_confirmation' => $newPassword,
        ])->assertRedirect(route('loginIndex'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }
}
