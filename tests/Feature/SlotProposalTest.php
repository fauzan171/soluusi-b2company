<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSlotProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlotProposalTest extends TestCase
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

    /** [$perusahaan, $emp, $consultantUser, $consultant] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $hrUser = $this->user('hr@test.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hrUser->id, 'name' => 'HR']);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$p, $emp, $docUser, $consultant];
    }

    /** Sesi sudah di-ACC HR (consultant assigned), jadwal belum di-set. */
    private function accSession(Perusahaan $p, PerusahaanEmployee $emp, Consultant $consultant): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);
    }

    private function slots(int $count, int $startDays = 1): array
    {
        return collect(range(0, $count - 1))->map(fn ($i) => [
            'start' => now()->addDays($startDays + $i)->setTime(9, 0)->toDateTimeString(),
            'end' => now()->addDays($startDays + $i)->setTime(10, 0)->toDateTimeString(),
        ])->all();
    }

    public function test_consultant_proposes_slots_saved_pending()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(3),
        ])->assertRedirect();

        $proposal = SessionSlotProposal::first();
        $this->assertNotNull($proposal);
        $this->assertSame($session->id, $proposal->session_id);
        $this->assertSame($consultant->id, $proposal->consultant_id);
        $this->assertSame('pending', $proposal->status);
        $this->assertCount(3, $proposal->slots);
    }

    public function test_propose_requires_min_two_slots()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(1),
        ])->assertSessionHasErrors('slots');

        $this->assertSame(0, SessionSlotProposal::count());
    }

    public function test_propose_max_three_slots_enforced()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(4),
        ])->assertSessionHasErrors('slots');

        $this->assertSame(0, SessionSlotProposal::count());
    }

    public function test_propose_rejects_past_slots()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => [
                ['start' => now()->subDay()->toDateTimeString(), 'end' => now()->subDay()->addHour()->toDateTimeString()],
                ['start' => now()->addDay()->toDateTimeString(), 'end' => now()->addDay()->addHour()->toDateTimeString()],
            ],
        ])->assertSessionHasErrors('slots.0.start');

        $this->assertSame(0, SessionSlotProposal::count());
    }

    public function test_propose_rejects_overlap_with_existing_booking()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);

        // Booking existing konsultan: besok 09:00-10:00
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'ended_at' => now()->addDay()->setTime(10, 0),
        ]);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => [
                ['start' => now()->addDay()->setTime(9, 30)->toDateTimeString(), 'end' => now()->addDay()->setTime(10, 30)->toDateTimeString()],
                ['start' => now()->addDays(2)->setTime(9, 0)->toDateTimeString(), 'end' => now()->addDays(2)->setTime(10, 0)->toDateTimeString()],
            ],
        ])->assertSessionHasErrors('slots');

        $this->assertSame(0, SessionSlotProposal::count());
    }

    public function test_propose_rejected_when_session_already_scheduled()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);
        $session->update(['scheduled_at' => now()->addDays(3)]);

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(2),
        ])->assertStatus(422);
    }

    public function test_other_consultant_cannot_propose()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);
        $otherUser = $this->user('other@test.local', 'consultant');
        Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $this->actingAs($otherUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(2),
        ])->assertForbidden();
    }

    public function test_employee_cannot_propose()
    {
        [$p, $emp, $docUser, $consultant] = $this->fixture();
        $session = $this->accSession($p, $emp, $consultant);
        $empUser = User::where('email', 'emp@test.local')->first();

        $this->actingAs($empUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => $this->slots(2),
        ])->assertForbidden();
    }
}
