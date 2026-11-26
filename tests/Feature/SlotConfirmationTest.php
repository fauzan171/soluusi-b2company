<?php

namespace Tests\Feature;

use App\Mail\Perusahaan\SlotConfirmedMail;
use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSlotProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlotConfirmationTest extends TestCase
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

    /** [$empUser, $emp, $session, $proposal] */
    private function fixture(bool $otherCompany = false): array
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
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $sessionEmp = $emp;
        if ($otherCompany) {
            $p2 = Perusahaan::create([
                'name' => 'PT Other', 'brand' => 'OtherCo', 'sessions_per_employee_month' => 2,
                'employee_count' => 3, 'is_active' => true,
            ]);
            $otherUser = $this->user('other-emp@test.local', 'employee');
            $sessionEmp = PerusahaanEmployee::create([
                'perusahaan_id' => $p2->id, 'user_id' => $otherUser->id,
                'name' => 'Other', 'email' => 'other-emp@test.local', 'consent_at' => now(),
            ]);
        }

        $session = PerusahaanSession::create([
            'perusahaan_id' => $sessionEmp->perusahaan_id, 'employee_id' => $sessionEmp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $start1 = now()->addDay()->setTime(9, 0);
        $start2 = now()->addDays(2)->setTime(13, 0);
        $proposal = SessionSlotProposal::create([
            'session_id' => $session->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now(),
            'slots' => [
                ['start' => $start1->toDateTimeString(), 'end' => $start1->copy()->addHour()->toDateTimeString()],
                ['start' => $start2->toDateTimeString(), 'end' => $start2->copy()->addHour()->toDateTimeString()],
            ],
            'status' => 'pending',
        ]);

        return [$empUser, $emp, $session, $proposal];
    }

    public function test_employee_sees_pending_proposals_on_dashboard()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();

        $this->actingAs($empUser)->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertSee('Pilih Jadwal')
            ->assertSee($proposal->slots[0]['start'] ? now()->addDay()->setTime(9, 0)->translatedFormat('d M Y') : '', false);
    }

    public function test_employee_confirms_slot_books_session_and_rejects_others()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertRedirect();

        $session->refresh();
        $this->assertNotNull($session->scheduled_at);
        $this->assertEquals(now()->addDay()->setTime(9, 0)->toDateTimeString(), $session->scheduled_at->toDateTimeString());
        $this->assertEquals(now()->addDay()->setTime(10, 0)->toDateTimeString(), $session->ended_at->toDateTimeString());
        $this->assertSame('accepted', $proposal->fresh()->status);

        // Proposal lain untuk sesi yang sama → auto-rejected.
        $other = SessionSlotProposal::create([
            'session_id' => $session->id,
            'consultant_id' => $proposal->consultant_id,
            'proposed_at' => now(),
            'slots' => [['start' => now()->addDays(3)->toDateTimeString(), 'end' => now()->addDays(3)->addHour()->toDateTimeString()]],
            'status' => 'pending',
        ]);
        // Re-confirm path: sudah tidak bisa lagi (sesi sudah booked).
        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $other), [
            'slot_index' => 0,
        ])->assertStatus(422);
    }

    public function test_employee_cannot_confirm_other_employees_proposal()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture(otherCompany: true);

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertForbidden();
    }

    public function test_invalid_slot_index_rejected()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 9,
        ])->assertSessionHasErrors('slot_index');

        $this->assertNull($session->fresh()->scheduled_at);
    }

    // Audit UX item 6: konsultan menerima email konfirmasi (waktu sesi saja,
    // tanpa data assessment/klinis) saat karyawan mengonfirmasi slot proposal.
    public function test_confirming_slot_notifies_consultant_by_mail()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();
        Mail::fake();

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertRedirect();

        Mail::assertSent(SlotConfirmedMail::class, fn ($m) => $m->hasTo('doc@test.local'));
    }

    // Calendar invite: karyawan + konsultan dapat .ics (METHOD:REQUEST) supaya
    // Gmail/Outlook/Apple bisa langsung "Add to calendar". Waktu dalam UTC.
    public function test_confirming_slot_sends_ics_invite_to_employee_and_consultant()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();
        Mail::fake();

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertRedirect();

        Mail::assertSent(SlotConfirmedMail::class, 2);
        Mail::assertSent(SlotConfirmedMail::class, fn ($m) => $m->hasTo('emp@test.local'));

        $start = now()->addDay()->setTime(9, 0)->utc()->format('Ymd\THis\Z');
        $end = now()->addDay()->setTime(10, 0)->utc()->format('Ymd\THis\Z');
        Mail::assertSent(SlotConfirmedMail::class, function ($m) use ($start, $end, $session) {
            $ics = $m->ics;
            return str_contains($ics, 'METHOD:REQUEST')
                && str_contains($ics, "DTSTART:$start")
                && str_contains($ics, "DTEND:$end")
                && str_contains($ics, "UID:perusahaan-session-{$session->id}@")
                && str_contains($ics, "\r\nEND:VCALENDAR\r\n");
        });
    }

    public function test_consultant_with_placeholder_local_email_is_skipped()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();
        $session->consultant->user->update(['email' => 'b2c-7@b2b.local']);
        Mail::fake();

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertRedirect();

        Mail::assertSent(SlotConfirmedMail::class, 1);
        Mail::assertNotSent(SlotConfirmedMail::class, fn ($m) => $m->hasTo('b2c-7@b2b.local'));
    }

    public function test_confirm_rejects_past_slot()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();

        // Mutasi proposal agar berisi slot di masa lalu (stale).
        $past = now()->subHours(2);
        $proposal->update(['slots' => [
            ['start' => $past->toDateTimeString(), 'end' => $past->copy()->addHour()->toDateTimeString()],
        ]]);

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal->fresh()), [
            'slot_index' => 0,
        ])->assertStatus(422);

        $this->assertNull($session->fresh()->scheduled_at);
        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_confirm_rejects_when_consultant_booked_between_propose_and_confirm()
    {
        [$empUser, $emp, $session, $proposal] = $this->fixture();

        // Simulasi double-book race: konsultan ke-book di slot yang sama setelah proposal dikirim.
        $overlapStart = now()->addDay()->setTime(9, 30);
        PerusahaanSession::create([
            'perusahaan_id' => $session->perusahaan_id, 'employee_id' => $emp->id,
            'consultant_id' => $proposal->consultant_id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => $overlapStart,
            'ended_at' => $overlapStart->copy()->addHour(),
        ]);

        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertStatus(422);

        $this->assertNull($session->fresh()->scheduled_at);
        $this->assertSame('pending', $proposal->fresh()->status);
    }
}
