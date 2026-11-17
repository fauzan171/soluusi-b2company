<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A5 — Audit log viewer superadmin: list admin_actions + filter
 * (admin, action, target type, tanggal) + pagination 25. Payload TIDAK
 * dirender mentah: email di-mask e***@domain, nama jadi inisial.
 */
class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function klinik(): Klinik
    {
        return Klinik::create(['name' => 'Klinik Sehat Sentosa', 'brand' => 'Klinik Sehat', 'total_rate' => 1_000_000]);
    }

    private function action(User $admin, string $name, $target, ?array $payload = null): AdminAction
    {
        return AdminAction::record($admin->id, $name, $target, $payload);
    }

    public function test_admin_bisa_lihat_audit_log()
    {
        $admin = $this->admin();
        $this->action($admin, 'klinik.update', $this->klinik(), ['name' => 'Klinik Sehat']);

        $this->actingAs($admin)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('klinik.update');
    }

    public function test_non_admin_ditolak()
    {
        $role = Role::firstOrCreate(['name' => 'klinik_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_filter_admin_action_target_type_dan_tanggal()
    {
        $admin = $this->admin();
        $other = $this->admin();
        $target = $this->klinik();

        $this->action($admin, 'klinik.update', $target);
        $this->action($other, 'klinik.suspend', $target, ['reason' => 'tidak aktif']);
        $this->action($admin, 'user.impersonate', $admin);

        $old = $this->action($admin, 'klinik.update', $target, ['note' => 'lama']);
        $old->created_at = now()->subDays(3);
        $old->save();

        $res = fn (array $q) => $this->actingAs($admin)
            ->get('/admin/audit-logs?' . http_build_query($q))
            ->assertOk();

        // Filter admin: hanya action milik $admin.
        $res(['admin_id' => $other->id])->assertSee('klinik.suspend')->assertDontSee('user.impersonate');

        // Filter action.
        $res(['action' => 'klinik.suspend'])->assertSee('klinik.suspend')->assertDontSee('user.impersonate');

        // Filter target type (morph class tanpa map — FQCN).
        $res(['target_type' => User::class])->assertSee('user.impersonate')->assertDontSee('klinik.suspend');

        // Filter tanggal: record 3 hari lalu tidak muncul saat from = hari ini.
        $res(['date_from' => now()->toDateString()])->assertDontSee('lama');
        $res(['date_to' => now()->subDays(2)->toDateString()])->assertSee('lama')->assertDontSee('klinik.suspend');
    }

    public function test_payload_email_dan_nama_ter_mask()
    {
        $admin = $this->admin();
        $this->action($admin, 'klinik.update', $this->klinik(), [
            'email' => 'budi.santoso@example.com',
            'name' => 'Budi Santoso',
            'reason' => 'perubahan data',
        ]);

        $response = $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();

        $this->assertStringNotContainsString('budi.santoso@example.com', $response->getContent());
        $this->assertStringNotContainsString('Budi Santoso', $response->getContent());
        $this->assertStringContainsString('b***@example.com', $response->getContent());
        $this->assertStringContainsString('B*** S***', $response->getContent());
    }

    public function test_pagination_25_per_halaman()
    {
        $admin = $this->admin();
        $klinik = $this->klinik();
        for ($i = 0; $i < 30; $i++) {
            $this->action($admin, 'klinik.update', $klinik);
        }

        $page1 = $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
        $page2 = $this->actingAs($admin)->get('/admin/audit-logs?page=2')->assertOk();

        // Header + 25 baris data di halaman 1; sisa 5 di halaman 2.
        $this->assertSame(26, substr_count($page1->getContent(), '<tr'));
        $this->assertSame(6, substr_count($page2->getContent(), '<tr'));
    }
}
