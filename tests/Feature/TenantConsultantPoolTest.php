<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.5 — HR/BK menyalakan/mematikan konsultan yang SUDAH ada di pool tenant.
 * Tidak ada jalur menambah konsultan baru: itu tetap wewenang superadmin.
 */
class TenantConsultantPoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['hr_admin', 'bk', 'employee', 'student'] as $name) {
            DB::table('roles')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function user(string $email, string $role, string $firstname = 'U'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => $firstname, 'lastname' => 'Test',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function perusahaan(string $name = 'PT Pool'): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function sekolah(string $name = 'SMA Pool'): Sekolah
    {
        return Sekolah::create([
            'name' => $name, 'brand' => $name, 'sessions_per_student_month' => 2,
            'student_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function hr(Perusahaan $p): User
    {
        $user = $this->user('hr-' . $p->id . '.' . uniqid() . '@pool.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $user->id]);

        return $user;
    }

    private function bk(Sekolah $s): User
    {
        $user = $this->user('bk-' . $s->id . '.' . uniqid() . '@pool.local', 'bk');
        DB::table('sekolah_admins')->insert([
            'sekolah_id' => $s->id, 'user_id' => $user->id, 'role' => 'bk',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user;
    }

    private function consultant(string $name = 'Konsultan', string $status = Consultant::ACCEPT): Consultant
    {
        $consultant = Consultant::factory()->create(['status' => $status]);
        $consultant->user->update(['firstname' => $name, 'lastname' => '', 'is_active' => true]);

        return $consultant;
    }

    private function pooled(Consultant $c, Perusahaan|Sekolah $tenant): ConsultantPool
    {
        return ConsultantPool::create($tenant instanceof Perusahaan
            ? ['consultant_id' => $c->id, 'perusahaan_id' => $tenant->id]
            : ['consultant_id' => $c->id, 'sekolah_id' => $tenant->id]);
    }

    public function test_hr_sees_only_own_company_pool(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        $this->pooled($this->consultant('Konsultan A'), $a);
        $this->pooled($this->consultant('Konsultan B'), $b);

        $this->actingAs($this->hr($a))
            ->get(route('perusahaan.consultants.pool'))
            ->assertOk()
            ->assertSee('Konsultan A')
            ->assertDontSee('Konsultan B');
    }

    public function test_toggle_off_hides_consultant_from_pool(): void
    {
        $p = $this->perusahaan();
        $c = $this->consultant('Konsultan Off');
        $this->pooled($c, $p);

        $this->assertTrue(ConsultantPool::poolFor('perusahaan', $p->id)->contains(fn ($i) => $i['id'] === $c->id));

        $this->actingAs($this->hr($p))
            ->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertRedirect();

        $this->assertFalse(ConsultantPool::where('consultant_id', $c->id)->value('is_active'));
        $this->assertFalse(ConsultantPool::poolFor('perusahaan', $p->id)->contains(fn ($i) => $i['id'] === $c->id));
    }

    public function test_toggle_back_on_restores_consultant(): void
    {
        $p = $this->perusahaan();
        $c = $this->consultant('Konsultan On');
        $pool = $this->pooled($c, $p);
        $pool->update(['is_active' => false]);

        $hr = $this->hr($p);
        $this->actingAs($hr)->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id]);

        $this->assertTrue($pool->fresh()->is_active);
        $this->assertTrue(ConsultantPool::poolFor('perusahaan', $p->id)->contains(fn ($i) => $i['id'] === $c->id));
    }

    public function test_hr_cannot_toggle_another_companys_row(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        $c = $this->consultant('Konsultan B');
        $row = $this->pooled($c, $b);

        $this->actingAs($this->hr($a))
            ->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertNotFound();

        $this->assertTrue($row->fresh()->is_active);
    }

    /**
     * Skema membolehkan satu row dengan kedua kolom tenant terisi (unique
     * consultant_id+sekolah_id+perusahaan_id). Satu bit is_active berarti HR A
     * akan mematikan konsultan yang sama di sekolah S — cross-tenant write.
     * Row seperti itu hanya boleh diubah superadmin.
     */
    public function test_hr_cannot_toggle_a_row_shared_with_another_vertical(): void
    {
        $p = $this->perusahaan('PT A');
        $s = $this->sekolah('SMA S');
        $c = $this->consultant('Konsultan Bersama');
        $row = ConsultantPool::create([
            'consultant_id' => $c->id,
            'perusahaan_id' => $p->id,
            'sekolah_id' => $s->id,
        ]);

        $this->actingAs($this->hr($p))
            ->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertNotFound();

        $this->assertTrue($row->fresh()->is_active);

        // Tidak muncul di daftar HR — yang tampil hanya konsultan miliknya sendiri.
        $this->actingAs($this->hr($p))
            ->get(route('perusahaan.consultants.pool'))
            ->assertSee('Pool Konsultan')
            ->assertDontSee('Konsultan Bersama');
    }

    public function test_bk_can_toggle_own_school_pool(): void
    {
        $s = $this->sekolah();
        $c = $this->consultant('Konsultan Sekolah');
        $this->pooled($c, $s);

        $bk = $this->bk($s);

        $this->actingAs($bk)->get(route('sekolah.consultants.pool'))->assertOk()->assertSee('Konsultan Sekolah');

        $this->actingAs($bk)
            ->post(route('sekolah.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertRedirect();

        $this->assertFalse(ConsultantPool::where('consultant_id', $c->id)->value('is_active'));
    }

    public function test_bk_cannot_toggle_another_school_row(): void
    {
        $s1 = $this->sekolah('SMA Satu');
        $s2 = $this->sekolah('SMA Dua');
        $c = $this->consultant('Konsultan Dua');
        $row = $this->pooled($c, $s2);

        $this->actingAs($this->bk($s1))
            ->post(route('sekolah.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertNotFound();

        $this->assertTrue($row->fresh()->is_active);
    }

    public function test_employee_and_student_are_forbidden(): void
    {
        $p = $this->perusahaan();
        $s = $this->sekolah();
        $c = $this->consultant('Konsultan X');
        $this->pooled($c, $p);

        $this->actingAs($this->user('karyawan@pool.local', 'employee'))
            ->get(route('perusahaan.consultants.pool'))->assertForbidden();
        $this->actingAs($this->user('siswa@pool.local', 'student'))
            ->get(route('sekolah.consultants.pool'))->assertForbidden();
    }

    public function test_no_route_to_add_consultant_to_pool(): void
    {
        // Satu-satunya route HR/BK adalah index + toggle. Tidak ada jalur tambah.
        $names = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter(fn ($n) => $n && str_contains($n, 'consultants.pool'));

        $this->assertEqualsCanonicalizing([
            'perusahaan.consultants.pool',
            'perusahaan.consultants.pool.toggle',
            'sekolah.consultants.pool',
            'sekolah.consultants.pool.toggle',
        ], $names->values()->all());
    }

    public function test_toggle_is_audited_without_pii(): void
    {
        $p = $this->perusahaan();
        $c = $this->consultant('Konsultan Audit');
        $this->pooled($c, $p);

        $this->actingAs($this->hr($p))
            ->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id]);

        $audit = AdminAction::where('action', 'consultant_pool.toggle')->firstOrFail();
        $payload = json_encode($audit->payload_json);

        $this->assertSame($c->id, $audit->payload_json['consultant_id']);
        $this->assertFalse($audit->payload_json['is_active']);
        $this->assertStringNotContainsString('Konsultan Audit', $payload);
        $this->assertStringNotContainsString('@pool.local', $payload);
    }

    public function test_non_accepted_consultant_is_not_listed(): void
    {
        $p = $this->perusahaan();
        $this->pooled($this->consultant('Konsultan Pending', Consultant::PENDING), $p);
        $this->pooled($this->consultant('Konsultan Aktif'), $p);

        $this->actingAs($this->hr($p))
            ->get(route('perusahaan.consultants.pool'))
            ->assertOk()
            ->assertSee('Konsultan Aktif')
            ->assertDontSee('Konsultan Pending');
    }

    public function test_toggle_requires_a_row_in_own_pool(): void
    {
        $p = $this->perusahaan();
        $c = $this->consultant('Tidak Di-pool');

        $this->actingAs($this->hr($p))
            ->post(route('perusahaan.consultants.pool.toggle'), ['consultant_id' => $c->id])
            ->assertNotFound();
    }
}