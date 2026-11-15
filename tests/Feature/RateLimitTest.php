<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Throttle pada endpoint spam-prone (T1.5).
 *
 * Test cache = ArrayStore dan RateLimiter di-cache sebagai singleton, jadi
 * counter tidak persist antar request. Workaround: rebind instance RateLimiter
 * baru (dengan store persisten) sebelum setiap request via beforeServing, dan
 * bersihkan properti internalnya antar attempt. Test ini menguji wiring
 * middleware throttle → 429 setelah limit, bukan RateLimiter itu sendiri.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rebind RateLimiter dengan ArrayStore persisten lintas-request.
     * Default test cache = array driver dan RateLimiter singleton membawa
     * store sendiri — counter hilang antar request. Middleware Throttle
     * di-resolve dari container per request, jadi rebind instance baru
     * (yang membungkus store yang sama) cukup.
     */
    private function bindPersistentThrottler(ArrayStore $store): void
    {
        $this->app->forgetInstance(RateLimiter::class);
        $this->app->instance(RateLimiter::class, new RateLimiter(new \Illuminate\Cache\Repository($store)));
    }

    private function makeEmployee(): User
    {
        $role = Role::firstOrCreate(['name' => 'employee']);
        $p = Perusahaan::create([
            'name' => 'PT Rate', 'brand' => 'RateCo',
            'sessions_per_employee_month' => 99,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Rate', 'lastname' => 'Limit',
            'username' => 'rate_test', 'email' => 'rate@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Rate Limit', 'email' => 'rate@test.local', 'consent_at' => now(),
        ]);

        return $user;
    }

    public function test_booking_hit_ke_11_di_throttle_429()
    {
        $user = $this->makeEmployee();
        $store = new ArrayStore();

        $throttled = null;
        for ($i = 1; $i <= 11; $i++) {
            $this->bindPersistentThrottler($store);
            $res = $this->actingAs($user)->post(route('perusahaan.booking.store'), [
                'konsultan_type' => 'psikolog',
            ]);
            if ($i === 11) {
                $throttled = $res;
            } else {
                // Validasi gagal (schedule_id kosong) = lolos throttle, request tercatat.
                $res->assertSessionHasErrors('schedule_id');
            }
        }

        $throttled->assertStatus(429);
    }
}
