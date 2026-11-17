<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D.3: satu kalkulasi kuota untuk semua jalur (pending review ikut memakai kuota).
 * D.2: reschedule ditolak untuk baris pending-review; cancel pending mengembalikan kuota.
 */
class QuotaConsistencyTest extends TestCase
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

    /** [$perusahaan, $user, $employee] */
    private function fixture(int $quota = 1): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => $quota,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $user = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);

        return [$p, $user, $emp];
    }

    private function pendingSession(Perusahaan $p, PerusahaanEmployee $emp): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog', 'mode' => 'online',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);
    }

    public function test_pending_request_blocks_new_booking(): void
    {
        [$p, $user, $emp] = $this->fixture(1);
        $slot = $this->programSlot('perusahaan', $p->id);
        $this->pendingSession($p, $emp);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertStatus(403);

        $this->assertSame(1, $emp->sessions()->count());
    }
}
