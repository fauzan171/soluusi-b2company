<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D.4: sesi yang sudah di-assign konsultan tapi tak pernah dijadwalkan
 * (consultant_id set, scheduled_at NULL) menggantung selamanya — tidak cocok
 * dengan sweep expired (butuh scheduled_at) maupun sweep pending (butuh
 * consultant_id NULL). Sweep ini membatalkannya tanpa komisi & tanpa summary.
 */
class StaleUnscheduledSweepTest extends TestCase
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

    /** [$perusahaan, $emp, $consultant] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Stale', 'brand' => 'StaleCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
            'mou_at' => now(), // kalau evaluate('cap') jalan, row pervorma pasti tercipta
        ]);
        $empUser = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$p, $emp, $consultant];
    }

    private function assignedUnscheduled(Perusahaan $p, PerusahaanEmployee $emp, Consultant $c): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $c->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);
    }

    public function test_stale_assigned_unscheduled_session_is_cancelled_without_pervorma_or_summary()
    {
        [$p, $emp, $consultant] = $this->fixture();
        $stale = $this->assignedUnscheduled($p, $emp, $consultant);
        DB::table('perusahaan_sessions')->where('id', $stale->id)
            ->update(['updated_at' => now()->subDays(8)]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $stale->refresh();
        $this->assertSame('cancelled', $stale->status);
        $this->assertNotNull($stale->cancel_reason);
        $this->assertNotNull($stale->cancelled_at);
        $this->assertFalse((bool) $stale->quota_consumed);
        // Bukan sesi yang terjadi: tanpa komisi, tanpa summary.
        $this->assertSame(0, PerusahaanPervorma::count());
        $this->assertSame(0, SessionSummary::where('perusahaan_session_id', $stale->id)->count());
    }

    public function test_fresh_assigned_unscheduled_session_is_untouched()
    {
        [$p, $emp, $consultant] = $this->fixture();
        $fresh = $this->assignedUnscheduled($p, $emp, $consultant);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame('scheduled', $fresh->refresh()->status);
        $this->assertNull($fresh->cancel_reason);
    }

    public function test_properly_scheduled_session_is_untouched_by_this_sweep()
    {
        [$p, $emp, $consultant] = $this->fixture();
        $scheduled = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        DB::table('perusahaan_sessions')->where('id', $scheduled->id)
            ->update(['updated_at' => now()->subDays(8)]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame('scheduled', $scheduled->refresh()->status);
        $this->assertNull($scheduled->cancel_reason);
    }
}