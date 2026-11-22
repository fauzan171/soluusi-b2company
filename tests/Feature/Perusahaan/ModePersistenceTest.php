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
 * Phase G.2: mode konsultasi (online/offline) tersimpan di request HR dan self-booking.
 */
class ModePersistenceTest extends TestCase
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
    private function fixture(int $quota = 2): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => $quota,
            'employee_count' => 3, 'is_active' => true,
            'consultant_request_enabled' => true,
        ]);
        $user = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);

        return [$p, $user, $emp];
    }

    public function test_request_consultation_persists_offline_mode(): void
    {
        [$p, $user] = $this->fixture();

        $this->actingAs($user)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Saya butuh sesi tatap muka.',
            'mode' => 'offline',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertSame('offline', PerusahaanSession::firstOrFail()->mode);
    }

    public function test_request_consultation_requires_mode(): void
    {
        [$p, $user] = $this->fixture();

        $this->actingAs($user)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Tanpa mode.',
        ])->assertSessionHasErrors('mode');

        $this->assertSame(0, PerusahaanSession::count());
    }

    public function test_booking_store_persists_slot_mode_offline(): void
    {
        [$p, $user] = $this->fixture();
        $slot = $this->programSlot('perusahaan', $p->id);
        $slot->update(['mode' => 'offline']);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertSame('offline', PerusahaanSession::firstOrFail()->mode);
    }
}