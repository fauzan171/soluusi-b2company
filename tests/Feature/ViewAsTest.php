<?php

namespace Tests\Feature;

use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F2.2 — Superadmin view-as + audit.
 * View-as aktif disimpan di session; middleware set context + banner;
 * start/stop dicatat ke admin_actions.
 */
class ViewAsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    private function hr(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]);
    }

    private function employee(): array
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT V', 'brand' => 'VCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp V', 'email' => 'emp-v@test.local', 'consent_at' => now(),
        ]);

        return [$perusahaan, $empUser, $employee];
    }

    public function test_superadmin_can_start_view_as()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id])
            ->assertRedirect();

        $this->assertTrue(\App\Support\ViewAs::isActive());
        $this->assertSame($employee->id, \App\Support\ViewAs::current()['employee_id']);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'view_as.start',
            'target_id' => $employee->id,
        ]);
    }

    public function test_non_superadmin_cannot_start_view_as()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();

        $this->actingAs($this->hr())
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id])
            ->assertForbidden();

        $this->assertFalse(\App\Support\ViewAs::isActive());
        $this->assertDatabaseMissing('admin_actions', ['action' => 'view_as.start']);
    }

    public function test_view_as_stop_clears_context_and_audits()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);
        $this->assertTrue(\App\Support\ViewAs::isActive());

        $this->actingAs($admin)->post(route('admin.view-as.stop'))->assertRedirect();
        $this->assertFalse(\App\Support\ViewAs::isActive());
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'view_as.stop',
        ]);
    }

    public function test_view_as_banner_rendered_when_active()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang melihat sebagai')
            ->assertSee('Emp V');
    }

    public function test_view_as_context_is_scoped_to_target_employee_tenant()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);

        // View-as aktif → PerusahaanContext ikut tenant target, bukan admin_perusahaan_id.
        $this->actingAs($admin)->get(route('perusahaan.dashboard'));
        $this->assertSame($perusahaan->id, \App\Support\PerusahaanContext::current()?->id);
    }

    public function test_view_as_session_carries_full_struct()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);

        $current = \App\Support\ViewAs::current();
        foreach (['target_user_id', 'target_role', 'vertical', 'tenant_id', 'started_at', 'expires_at'] as $key) {
            $this->assertArrayHasKey($key, $current);
        }
        $this->assertSame($empUser->id, $current['target_user_id']);
        $this->assertSame('employee', $current['target_role']);
        $this->assertSame('perusahaan', $current['vertical']);
        $this->assertSame($perusahaan->id, $current['tenant_id']);
        $this->assertNotEmpty($current['started_at']);
        $this->assertNotEmpty($current['expires_at']);
    }

    public function test_superadmin_can_view_as_non_employee_roles()
    {
        $admin = $this->admin();

        // consultant — no tenant
        $consultant = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'consultant'])->id]);
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $consultant->id])
            ->assertRedirect();
        $current = \App\Support\ViewAs::current();
        $this->assertSame($consultant->id, $current['target_user_id']);
        $this->assertSame('consultant', $current['target_role']);
        $this->assertNull($current['vertical']);
        $this->assertNull($current['tenant_id']);

        \App\Support\ViewAs::stop();

        // hr_admin — vertical perusahaan via perusahaan_admins
        [$perusahaan] = $this->employee();
        $hrUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]);
        \App\Models\Perusahaan\PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id,
            'user_id' => $hrUser->id,
        ]);
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $hrUser->id])
            ->assertRedirect();
        $current = \App\Support\ViewAs::current();
        $this->assertSame('hr_admin', $current['target_role']);
        $this->assertSame('perusahaan', $current['vertical']);
        $this->assertSame($perusahaan->id, $current['tenant_id']);
    }

    public function test_cannot_view_as_another_admin()
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $other->id])
            ->assertStatus(422);

        $this->assertFalse(\App\Support\ViewAs::isActive());
    }

    public function test_anonymous_cannot_start_view_as()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();

        $this->post(route('admin.view-as.start'), ['employee_id' => $employee->id])
            ->assertRedirect(route('login'));
    }

    /** B2 — view-as klinik_admin → KlinikContext scope ke tenant target, bukan tenant admin. */
    public function test_view_as_klinik_admin_scopes_klinik_context_to_target_tenant()
    {
        $admin = $this->admin();

        $klinikA = Klinik::create(['name' => 'Klinik A', 'brand' => 'KA', 'is_active' => true]);
        $klinikB = Klinik::create(['name' => 'Klinik B', 'brand' => 'KB', 'is_active' => true]);

        $kaUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'klinik_admin'])->id]);
        KlinikAdmin::create(['klinik_id' => $klinikB->id, 'user_id' => $kaUser->id]);

        // Admin punya preferensi klinik A di session; view-as klinik_admin B harus override.
        $this->actingAs($admin)->withSession(['admin_klinik_id' => $klinikA->id])
            ->post(route('admin.view-as.start'), ['user_id' => $kaUser->id]);

        $this->assertSame($klinikB->id, \App\Support\KlinikContext::current()?->id);
        $current = \App\Support\ViewAs::current();
        $this->assertSame('klinik', $current['vertical']);
        $this->assertSame($klinikB->id, $current['tenant_id']);
    }

    /** B2 — view-as bk → SekolahContext scope ke tenant target. */
    public function test_view_as_bk_scopes_sekolah_context_to_target_tenant()
    {
        $admin = $this->admin();

        $sekolahA = Sekolah::create(['name' => 'SMA A', 'brand' => 'SA', 'is_active' => true]);
        $sekolahB = Sekolah::create(['name' => 'SMA B', 'brand' => 'SB', 'is_active' => true]);

        $bkUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'bk'])->id]);
        SekolahAdmin::create(['sekolah_id' => $sekolahB->id, 'user_id' => $bkUser->id]);

        $this->actingAs($admin)->withSession(['admin_sekolah_id' => $sekolahA->id])
            ->post(route('admin.view-as.start'), ['user_id' => $bkUser->id]);

        $this->assertSame($sekolahB->id, \App\Support\SekolahContext::current()?->id);
        $current = \App\Support\ViewAs::current();
        $this->assertSame('sekolah', $current['vertical']);
        $this->assertSame($sekolahB->id, $current['tenant_id']);
    }

    /** E.1 — view-as staf sekolah harus scope ke tenant & mendarat di portal staf. */
    public function test_view_as_sekolah_staff_scopes_context_and_lands_on_staff_portal()
    {
        $admin = $this->admin();

        $sekolahA = Sekolah::create(['name' => 'SMA A', 'brand' => 'SA', 'is_active' => true]);
        $sekolahB = Sekolah::create(['name' => 'SMA B', 'brand' => 'SB', 'is_active' => true]);

        $staffUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'sekolah_staff'])->id]);
        SekolahAdmin::create(['sekolah_id' => $sekolahB->id, 'user_id' => $staffUser->id, 'role' => 'sekolah_staff']);
        SekolahStudent::create([
            'sekolah_id' => $sekolahB->id, 'user_id' => $staffUser->id,
            'name' => 'Staf B', 'email' => 'staf-b@test.local', 'type' => 'staff', 'consent_at' => now(),
        ]);

        $this->actingAs($admin)->withSession(['admin_sekolah_id' => $sekolahA->id])
            ->post(route('admin.view-as.start'), ['user_id' => $staffUser->id])
            ->assertRedirect(route('sekolah.staff'));

        $this->assertSame($sekolahB->id, \App\Support\SekolahContext::current()?->id);
        $current = \App\Support\ViewAs::current();
        $this->assertSame('sekolah', $current['vertical']);
        $this->assertSame($sekolahB->id, $current['tenant_id']);
    }

    /** B2 — PerusahaanSessionPolicy pakai REAL user identity, BUKAN view-as target. */
    public function test_view_as_does_not_bypass_perusahaan_session_policy()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        // HR admin perusahaan lain (bukan pemilik sesi).
        $otherPerusahaan = Perusahaan::create([
            'name' => 'PT X', 'brand' => 'XCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $hrUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'hr_admin'])->id]);
        PerusahaanAdmin::create(['perusahaan_id' => $otherPerusahaan->id, 'user_id' => $hrUser->id]);

        // Sesi milik employee perusahaan pertama.
        $session = PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id,
            'employee_id' => $employee->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        // View-as HR admin → policy tetap pakai real user (admin) yang tidak punya akses.
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $hrUser->id]);

        $this->assertFalse(
            (new \App\Policies\PerusahaanSessionPolicy())->view($admin, $session),
            'Policy harus pakai real user, bukan view-as target'
        );
    }

    /** B3 — admin boleh masuk grup consultant saat view-as consultant aktif. */
    public function test_admin_can_view_consultant_portal_with_view_as()
    {
        $admin = $this->admin();
        $consultantUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'consultant'])->id]);
        \App\Models\Consultant::factory()->create(['user_id' => $consultantUser->id, 'status' => 'accept']);

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $consultantUser->id]);

        $this->actingAs($admin)
            ->get(route('consultant.sessions'))
            ->assertOk();
    }

    /** B3 — admin boleh masuk grup associate saat view-as associate aktif. */
    public function test_admin_can_view_associate_portal_with_view_as()
    {
        $admin = $this->admin();
        $associate = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'associate'])->id]);

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $associate->id]);

        $this->actingAs($admin)
            ->get(route('associate.dashboard'))
            ->assertOk();
    }

    /** B3 — tanpa view-as, admin ditolak dari grup consultant + associate. */
    public function test_admin_without_view_as_forbidden_from_consultant_and_associate()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('consultant.sessions'))->assertForbidden();
        $this->actingAs($admin)->get(route('associate.dashboard'))->assertForbidden();
    }

    /** B5 — TTL konstanta 30 menit ter-expose + ponytail. */
    public function test_view_as_ttl_constant_is_30_minutes()
    {
        $this->assertSame(30, \App\Support\ViewAs::TTL_MINUTES);
    }

    /** B5 — expires_at di masa lalu → request berikutnya auto-stop + flash + audit. */
    public function test_view_as_expired_auto_stops_with_flash_and_audit()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        // Start view-as.
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);
        $this->assertTrue(\App\Support\ViewAs::isActive());

        // Paksa expires_at ke masa lalu di session.
        $data = session(\App\Support\ViewAs::SESSION_KEY);
        $data['expires_at'] = now()->subMinute()->toIso8601String();
        session([\App\Support\ViewAs::SESSION_KEY => $data]);

        // Request berikutnya → middleware auto-stop + flash + audit.
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSessionHas('info', function ($msg) {
                return str_contains($msg, 'view-as expired');
            });

        $this->assertFalse(\App\Support\ViewAs::isActive());
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'view_as.stop',
            'target_id' => $empUser->id,
        ]);
        $stop = \App\Models\AdminAction::where('action', 'view_as.stop')->latest('id')->first();
        $this->assertSame('expired', $stop->payload_json['reason'] ?? null);
        $this->assertSame('employee', $stop->payload_json['target_role'] ?? null);
        $this->assertSame($perusahaan->id, $stop->payload_json['tenant_id'] ?? null);
    }

    /** B5 — audit start payload lengkap (target_role, tenant_id, reason). */
    public function test_view_as_start_audit_has_full_payload()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), [
                'employee_id' => $employee->id,
                'reason' => 'Investigasi tiket #123',
            ]);

        $start = \App\Models\AdminAction::where('action', 'view_as.start')->latest('id')->first();
        $this->assertNotNull($start);
        $this->assertSame('employee', $start->payload_json['target_role'] ?? null);
        $this->assertSame($perusahaan->id, $start->payload_json['tenant_id'] ?? null);
        $this->assertSame('Investigasi tiket #123', $start->payload_json['reason'] ?? null);
    }

    /** B5 — audit stop payload lengkap (target_role, tenant_id). */
    public function test_view_as_stop_audit_has_full_payload()
    {
        [$perusahaan, $empUser, $employee] = $this->employee();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['employee_id' => $employee->id]);
        $this->actingAs($admin)
            ->post(route('admin.view-as.stop'));

        $stop = \App\Models\AdminAction::where('action', 'view_as.stop')->latest('id')->first();
        $this->assertNotNull($stop);
        $this->assertSame('employee', $stop->payload_json['target_role'] ?? null);
        $this->assertSame($perusahaan->id, $stop->payload_json['tenant_id'] ?? null);
    }
}
