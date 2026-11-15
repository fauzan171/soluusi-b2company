<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T7.3 — HR "Sesi aktif" harus tetap menampilkan sesi yang melintasi tengah malam.
 * Filter lama `scheduled_at >= startOfDay` menyembunyikan sesi 23:00 saat jam 01:00.
 */
class ActiveSessionsFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeHr(): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Overnight', 'brand' => 'ONCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'On',
            'username' => 'hr_on', 'email' => 'hr_on@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'name' => 'Emp A',
            'email' => 'emp_a@test.local', 'consent_at' => now(),
        ]);
        return [$p, $hr, $emp];
    }

    public function test_session_started_late_last_night_still_visible_after_midnight()
    {
        Carbon::setTestNow('2026-11-15 01:00:00');
        [$p, $hr, $emp] = $this->makeHr();

        // Sesi dijadwalkan 23:00 kemarin — masih "scheduled" (belum di-sweep).
        $overnight = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => Carbon::parse('2026-11-14 23:00:00'),
        ]);
        // Sesi lama banget (13 jam lalu) — tidak boleh dianggap aktif.
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => Carbon::parse('2026-11-14 11:00:00'),
        ]);

        $res = $this->actingAs($hr)->get(route('perusahaan.dashboard'));
        $res->assertOk();
        $active = collect($res->viewData('activeSessions'));
        $this->assertTrue($active->contains('id', $overnight->id), 'sesi 23:00 masih harus tampil jam 01:00');
        $this->assertSame(1, $active->count(), 'sesi 13 jam lalu tidak boleh muncul');

        Carbon::setTestNow();
    }

    public function test_far_future_session_still_listed()
    {
        Carbon::setTestNow('2026-11-15 09:00:00');
        [$p, $hr, $emp] = $this->makeHr();

        $future = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => Carbon::parse('2026-11-15 14:00:00'),
        ]);

        $res = $this->actingAs($hr)->get(route('perusahaan.dashboard'));
        $res->assertOk();
        $this->assertTrue(collect($res->viewData('activeSessions'))->contains('id', $future->id));

        Carbon::setTestNow();
    }
}
