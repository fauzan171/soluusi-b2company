<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T2.4 — Tenant global scope enforcement.
 *
 * Scope aktif hanya bila tenant context resolve (request dengan auth user
 * yang terikat perusahaan/sekolah). Tanpa context (console, guest, superadmin
 * tanpa seleksi tenant) query mengembalikan semua baris — perilaku lama
 * tidak berubah. Superadmin/explicit bypass via withoutGlobalScope.
 */
class TenantScopeTest extends TestCase
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

    private function perusahaan(string $name): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
    }

    private function sekolah(string $name): Sekolah
    {
        return Sekolah::create([
            'name' => $name, 'brand' => $name, 'sessions_per_student_month' => 2,
            'student_count' => 3, 'is_active' => true,
        ]);
    }

    private function employee(Perusahaan $p, string $name): PerusahaanEmployee
    {
        return PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'name' => $name,
            'email' => Str::slug($name) . '@t.local', 'consent_at' => now(),
        ]);
    }

    private function student(Sekolah $s, string $name): SekolahStudent
    {
        return SekolahStudent::create([
            'sekolah_id' => $s->id, 'name' => $name,
            'email' => Str::slug($name) . '@t.local', 'consent_at' => now(),
            'parent_consent_at' => now(), 'kelas' => 'X-1',
        ]);
    }

    private function hrAdmin(Perusahaan $p): User
    {
        $hr = $this->user('hr-' . $p->id . '@t.local', 'hr_admin');
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hr;
    }

    private function sekolahAdmin(Sekolah $s): User
    {
        $admin = $this->user('bk-' . $s->id . '@t.local', 'bk');
        DB::table('sekolah_admins')->insert([
            'sekolah_id' => $s->id, 'user_id' => $admin->id, 'role' => 'sekolah_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $admin;
    }

    /** Spec verify #1: scope aktif saat context resolve → count lebih kecil dari total. */
    public function test_scope_active_when_perusahaan_context_resolves()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $empX = $this->employee($px, 'Emp X');
        $empY = $this->employee($py, 'Emp Y');
        PerusahaanSession::create([
            'perusahaan_id' => $px->id, 'employee_id' => $empX->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $py->id, 'employee_id' => $empY->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $hrX = $this->hrAdmin($px);

        // Tanpa context: scope tidak aktif (semua baris).
        $this->assertSame(2, PerusahaanSession::count());

        // Dengan context (HTTP request HR X): hanya sesi X.
        $this->actingAs($hrX)->get(route('perusahaan.dashboard'))->assertOk();
        $this->assertSame(1, PerusahaanSession::count());
        $this->assertSame(2, PerusahaanSession::withoutGlobalScopes()->count());
    }

    /** Spec verify #2: query dari tenant A tidak return data tenant B. */
    public function test_tenant_a_query_never_returns_tenant_b_rows()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $empX = $this->employee($px, 'Emp X');
        $empY = $this->employee($py, 'Emp Y');
        PerusahaanSession::create([
            'perusahaan_id' => $px->id, 'employee_id' => $empX->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        $sessionY = PerusahaanSession::create([
            'perusahaan_id' => $py->id, 'employee_id' => $empY->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $hrX = $this->hrAdmin($px);
        $this->actingAs($hrX)->get(route('perusahaan.dashboard'))->assertOk();

        $this->assertSame(1, PerusahaanSession::count());
        $this->assertSame(1, PerusahaanEmployee::count());
        $this->assertNull(PerusahaanSession::find($sessionY->id));
        $this->assertNull(PerusahaanEmployee::find($empY->id));
        // Bypass tetap menjangkau.
        $this->assertNotNull(PerusahaanSession::withoutGlobalScopes()->find($sessionY->id));
    }

    /** Spec verify #3: superadmin bisa bypass scope dengan withoutGlobalScope. */
    public function test_superadmin_bypass_via_without_global_scope()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $this->employee($px, 'Emp X');
        $this->employee($py, 'Emp Y');

        $admin = $this->user('sa@t.local', 'admin');
        $this->actingAs($admin)->get(route('perusahaan.dashboard') . '?perusahaan_id=' . $px->id)
            ->assertOk();

        // Scoped: hanya X. Bypass: semua.
        $this->assertSame(1, PerusahaanEmployee::count());
        $this->assertSame(2, PerusahaanEmployee::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count());
    }

    /** Scope tidak pernah aktif di console / guest (no auth user). */
    public function test_scope_inactive_without_auth_context()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $this->employee($px, 'Emp X');
        $this->employee($py, 'Emp Y');
        PerusahaanAssessment::create([
            'perusahaan_id' => $px->id, 'answers' => [1, 1, 1, 1, 1],
            'score' => 80, 'taken_at' => now(), 'status' => 'done',
        ]);
        PerusahaanAssessment::create([
            'perusahaan_id' => $py->id, 'answers' => [1, 1, 1, 1, 1],
            'score' => 40, 'taken_at' => now(), 'status' => 'done',
        ]);

        // Guest: tidak ada auth user → scope inactive.
        $this->assertSame(2, PerusahaanEmployee::count());
        $this->assertSame(2, PerusahaanAssessment::count());
    }

    /** Sekolah mirror: scope aktif untuk sekolah vertical. */
    public function test_scope_active_when_sekolah_context_resolves()
    {
        $sx = $this->sekolah('Sekolah X');
        $sy = $this->sekolah('Sekolah Y');
        $stX = $this->student($sx, 'Murid X');
        $stY = $this->student($sy, 'Murid Y');
        SekolahSession::create([
            'sekolah_id' => $sx->id, 'student_id' => $stX->id, 'kelas' => 'X-1',
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        SekolahSession::create([
            'sekolah_id' => $sy->id, 'student_id' => $stY->id, 'kelas' => 'X-1',
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        SekolahAssessment::create([
            'sekolah_id' => $sx->id, 'student_id' => $stX->id, 'kelas' => 'X-1',
            'answers' => [1, 1, 1, 1, 1], 'score' => 80, 'taken_at' => now(), 'status' => 'done',
        ]);

        $adminX = $this->sekolahAdmin($sx);
        $this->actingAs($adminX)->get(route('sekolah.dashboard'))->assertOk();

        $this->assertSame(1, SekolahSession::count());
        $this->assertSame(1, SekolahStudent::count());
        $this->assertSame(1, SekolahAssessment::count());
        $this->assertSame(2, SekolahSession::withoutGlobalScopes()->count());
    }
}
