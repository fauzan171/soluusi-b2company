<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G.5 + G.8: /consultant/sessions harus menampilkan entry point "Usulkan opsi
 * jadwal" untuk sesi CAP yang belum dijadwalkan, dan TIDAK menampilkan tombol
 * Chat (room masih mati) sampai jadwal di-set.
 */
class ConsultantSessionsViewTest extends TestCase
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

    /** @return array{0: User, 1: Consultant, 2: Perusahaan, 3: PerusahaanEmployee} */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $this->user('emp@test.local', 'employee')->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$docUser, $consultant, $p, $emp];
    }

    public function test_unscheduled_session_shows_propose_slots_not_chat()
    {
        [$docUser, $consultant, $p, $emp] = $this->fixture();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee(route('consultant.sessions.propose-slots.form', $session))
            ->assertDontSee(route('consultant.chat', $session));
    }

    public function test_scheduled_session_shows_chat()
    {
        [$docUser, $consultant, $p, $emp] = $this->fixture();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee(route('consultant.chat', $session));
    }
}