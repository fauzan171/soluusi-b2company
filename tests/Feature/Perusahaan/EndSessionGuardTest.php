<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G.7: sesi yang sudah di-assign konsultan tapi BELUM dijadwalkan (scheduled_at
 * NULL) tidak boleh diakhiri. End prematur = komisi + summary + kuota terbakar
 * untuk konsultasi yang tidak pernah terjadi.
 */
class EndSessionGuardTest extends TestCase
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

    /** [$perusahaan, $empUser, $emp, $docUser, $consultant, $session] */
    private function fixture(?string $scheduledAt = null): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Guard', 'brand' => 'GuardCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
            // CAP stage-1 trigger = mou_at: kalau evaluate('cap') terpanggil, row
            // pervorma PASTI tercipta. Jadi assert "no pervorma" bermakna.
            'mou_at' => now(),
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

        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt === null ? null : now()->addDay(),
        ]);

        return [$p, $empUser, $emp, $docUser, $consultant, $session];
    }

    public function test_consultant_cannot_end_assigned_but_unscheduled_session()
    {
        [, , , $docUser, , $session] = $this->fixture();

        $this->actingAs($docUser)->post(route('consultant.chat.end', $session))
            ->assertStatus(422);

        $session->refresh();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->completed_at);
        $this->assertSame(0, PerusahaanPervorma::count());
    }

    public function test_employee_cannot_end_assigned_but_unscheduled_session()
    {
        [, $empUser, , , , $session] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.chat.end', $session))
            ->assertStatus(422);

        $session->refresh();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->completed_at);
        $this->assertSame(0, PerusahaanPervorma::count());
    }

    public function test_both_parties_can_end_a_scheduled_session()
    {
        [, $empUser, , $docUser, , $session] = $this->fixture('scheduled');

        $this->actingAs($docUser)->post(route('consultant.chat.end', $session))
            ->assertRedirect(route('consultant.chat', $session));
        $this->assertSame('done', $session->refresh()->status);

        // Sesi kedua untuk membuktikan sisi employee juga lolos.
        $session2 = PerusahaanSession::create([
            'perusahaan_id' => $session->perusahaan_id, 'employee_id' => $session->employee_id,
            'consultant_id' => $session->consultant_id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);
        $this->actingAs($empUser)->post(route('perusahaan.chat.end', $session2))
            ->assertRedirect(route('perusahaan.chat', $session2));
        $this->assertSame('done', $session2->refresh()->status);
    }
}