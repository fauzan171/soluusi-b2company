<?php

namespace Tests\Feature;

use App\Http\Middleware\ViewAsReadOnly;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * B4 — View-as read-only middleware (deny-by-default).
 * Saat view-as aktif: non-GET di luar prefix admin → 403.
 * Whitelist: view-as.stop. GET tetap jalan. Admin prefix tetap jalan.
 *
 * Strategi: daftarkan route uji langsung ke middleware ini (bukan lewat grup web),
 * supaya terisolasi dari role middleware yang juga bisa 403.
 */
class ViewAsReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    private function employeeTarget(): array
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT R', 'brand' => 'RCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'employee'])->id]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp R', 'email' => 'emp-r@test.local', 'consent_at' => now(),
        ]);

        return [$perusahaan, $empUser, $employee];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Route uji: middleware web penuh (session) + ViewAsReadOnly saja.
        Route::middleware(['web', ViewAsReadOnly::class])->group(function () {
            Route::get('/__t/portal', fn () => 'ok-get')->name('__t.portal.get');
            Route::post('/__t/portal', fn () => 'ok-post')->name('__t.portal.post');
            Route::post('/__t/admin-ish', fn () => 'ok-not-admin-prefix')->name('__t.notadmin');
            Route::post('/admin/__t/action', fn () => 'ok-admin')->name('__t.admin');
        });
    }

    private function startViewAs(User $admin, int $targetUserId): void
    {
        $this->actingAs($admin)
            ->post(route('admin.view-as.start'), ['user_id' => $targetUserId])
            ->assertRedirect();
        $this->assertTrue(\App\Support\ViewAs::isActive());
    }

    public function test_post_forbidden_when_view_as_active()
    {
        [, $empUser] = $this->employeeTarget();
        $admin = $this->admin();
        $this->startViewAs($admin, $empUser->id);

        $this->actingAs($admin)
            ->post('/__t/portal')
            ->assertForbidden();
    }

    public function test_post_allowed_when_view_as_not_active()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/__t/portal')
            ->assertOk()
            ->assertSee('ok-post');
    }

    public function test_get_allowed_when_view_as_active()
    {
        [, $empUser] = $this->employeeTarget();
        $admin = $this->admin();
        $this->startViewAs($admin, $empUser->id);

        $this->actingAs($admin)
            ->get('/__t/portal')
            ->assertOk()
            ->assertSee('ok-get');
    }

    public function test_view_as_stop_whitelisted()
    {
        [, $empUser] = $this->employeeTarget();
        $admin = $this->admin();
        $this->startViewAs($admin, $empUser->id);

        $this->actingAs($admin)
            ->post(route('admin.view-as.stop'))
            ->assertRedirect();

        $this->assertFalse(\App\Support\ViewAs::isActive());
    }

    public function test_admin_prefix_allowed_when_view_as_active()
    {
        [, $empUser] = $this->employeeTarget();
        $admin = $this->admin();
        $this->startViewAs($admin, $empUser->id);

        // POST ke path /admin/* (bukan route admin.view-as.stop) tetap lolos.
        $this->actingAs($admin)
            ->post('/admin/__t/action')
            ->assertOk()
            ->assertSee('ok-admin');
    }

    public function test_non_admin_prefix_post_forbidden_even_if_name_looks_admin()
    {
        [, $empUser] = $this->employeeTarget();
        $admin = $this->admin();
        $this->startViewAs($admin, $empUser->id);

        // Path BUKAN /admin/* walau nama route mirip → tetap 403.
        $this->actingAs($admin)
            ->post('/__t/admin-ish')
            ->assertForbidden();
    }

    public function test_booking_cancel_forbidden_when_view_as_active()
    {
        [$perusahaan, $empUser, $employee] = $this->employeeTarget();
        $admin = $this->admin();

        $session = \App\Models\Perusahaan\PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id,
            'employee_id' => $employee->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->startViewAs($admin, $empUser->id);

        // Middleware dipasang di grup web; admin memang sudah 403 oleh role middleware
        // di grup perusahaan — kunci: response 403 (gagal tulis), bukan 2xx.
        $this->actingAs($admin)
            ->post(route('perusahaan.booking.cancel', ['session' => $session->id]))
            ->assertForbidden();
    }

    public function test_chat_store_forbidden_when_view_as_active()
    {
        [$perusahaan, $empUser, $employee] = $this->employeeTarget();
        $admin = $this->admin();

        $session = \App\Models\Perusahaan\PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id,
            'employee_id' => $employee->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->startViewAs($admin, $empUser->id);

        $this->actingAs($admin)
            ->post(route('perusahaan.chat.store', ['session' => $session->id]), ['body' => 'halo'])
            ->assertForbidden();
    }
}
