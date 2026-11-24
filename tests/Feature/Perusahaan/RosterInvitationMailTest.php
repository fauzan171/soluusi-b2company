<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit UX perusahaan:
// 1. RosterController::store harus langsung mengirim email undangan
//    (provisioning saja membuat karyawan menunggu aksi HR kedua tanpa sinyal).
// 5. Roster punya aksi "kirim ulang undangan" sampai karyawan mengganti password
//    (must_change_password false = undangan sudah dikonsumsi).
// 14. Stat "Belum Diundang" menghitung undangan yang belum dikonsumsi.
class RosterInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): void
    {
        if (!DB::table('roles')->where('name', $name)->exists()) {
            DB::table('roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email).'-'.Str::random(4), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
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
        $user = $this->user($email, 'hr_admin');
        PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $user->id, 'name' => 'HR', 'role' => 'hr_admin',
        ]);

        return $user;
    }

    public function test_store_with_email_sends_invitation_mail(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');
        Mail::fake();

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Satu', 'email' => 'emp1@test.local',
        ])->assertRedirect();

        // Undangan langsung terkirim saat tambah karyawan (tidak menunggu aksi HR kedua).
        Mail::assertSent(\App\Mail\Perusahaan\RosterInvitationMail::class, 1);
        Mail::assertSent(\App\Mail\Perusahaan\RosterInvitationMail::class,
            fn ($m) => $m->hasTo('emp1@test.local'));
        $row = PerusahaanEmployee::withoutGlobalScopes()->where('email', 'emp1@test.local')->first();
        $this->assertNotNull($row->user_id);

        // Akun baru harus mengganti password dulu (sinyal undangan belum dikonsumsi).
        $this->assertTrue((bool) User::find($row->user_id)->must_change_password);
    }

    public function test_resend_invitation_sends_mail_until_password_changed(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');
        Mail::fake();

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Dua', 'email' => 'emp2@test.local',
        ])->assertRedirect();

        $emp = PerusahaanEmployee::withoutGlobalScopes()->where('email', 'emp2@test.local')->first();

        // Kirim-ulang diperbolehkan selama password belum diganti.
        Mail::fake();
        $this->actingAs($hr)->post(route('perusahaan.employees.invite'), [
            'employee_id' => $emp->id,
        ])->assertRedirect()->assertSessionHas('success');
        Mail::assertSent(\App\Mail\Perusahaan\RosterInvitationMail::class, 1);

        // Setelah password diganti, kirim-ulang ditolak (akun sudah aktif).
        User::find($emp->user_id)->forceFill(['must_change_password' => false])->save();
        Mail::fake();
        $this->actingAs($hr)->post(route('perusahaan.employees.invite'), [
            'employee_id' => $emp->id,
        ])->assertRedirect()->assertSessionHas('error', function ($v) {
            return str_contains($v, 'sudah aktif');
        });
        Mail::assertNothingSent();
    }

    public function test_belum_diundang_stat_counts_unconsumed_invitations(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan, 'hr@test.local');
        $this->role('employee');
        Mail::fake();

        $this->actingAs($hr)->post(route('perusahaan.employees.store'), [
            'name' => 'Emp Tiga', 'email' => 'emp3@test.local',
        ])->assertRedirect();

        $response = $this->actingAs($hr)->get(route('perusahaan.employees'));
        $response->assertOk();

        // Satu karyawan: akun aktif (provisioned) tapi undangan belum dikonsumsi →
        // stat "Belum Diundang" tetap 1 (bukan 0 seperti semula).
        $emp = PerusahaanEmployee::withoutGlobalScopes()->where('email', 'emp3@test.local')->first();
        $this->assertNotNull($emp->user_id);
        $this->assertSame(1, DB::table('roster_invitations')->where('roster_id', $emp->id)->whereNull('consumed_at')->whereNull('revoked_at')->count());

        // Stat "Belum Diundang" merender 1 (sebelumnya selalu 0 karena menghitung
        // baris tanpa user_id — padahal akun langsung diprovision).
        $emp->setRelation('user', User::find($emp->user_id));
        $this->assertFalse($emp->isInvitationConsumed());
    }

    public function test_invite_is_tenant_scoped(): void
    {
        $tenantA = $this->perusahaan('PT A');
        $tenantB = $this->perusahaan('PT B');
        $hrB = $this->hr($tenantB, 'hr-b@test.local');
        $this->role('employee');
        Mail::fake();

        $empA = PerusahaanEmployee::create([
            'perusahaan_id' => $tenantA->id, 'name' => 'Emp A', 'email' => 'emp-a@test.local',
        ]);

        // TenantScope menyembunyikan karyawan tenant lain → 404 (tidak membocorkan
        // keberadaan); yang penting invite ditolak dan tidak ada undangan terbit.
        $this->actingAs($hrB)->post(route('perusahaan.employees.invite'), [
            'employee_id' => $empA->id,
        ])->assertNotFound();

        $this->assertSame(0, DB::table('roster_invitations')->count());
    }
}
