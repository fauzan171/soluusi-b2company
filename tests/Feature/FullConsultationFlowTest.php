<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
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

/**
 * F2.4 — full consultation flow end-to-end via HTTP.
 * Jalan: employee request → HR approve → consultant propose → employee confirm →
 * chat → consultant start → consultant end → employee rate. Plus reject & sweep path.
 */
class FullConsultationFlowTest extends TestCase
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

    /** [$p, $empUser, $emp, $hrUser, $docUser, $consultant] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Flow', 'brand' => 'FlowCo', 'sessions_per_employee_month' => 3,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@flow.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@flow.local', 'consent_at' => now(),
        ]);
        $hrUser = $this->user('hr@flow.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hrUser->id, 'name' => 'HR']);
        $docUser = $this->user('doc@flow.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $empUser, $emp, $hrUser, $docUser, $consultant];
    }

    public function test_full_consultation_flow_end_to_end()
    {
        [$p, $empUser, $emp, $hrUser, $docUser, $consultant] = $this->fixture();

        // 1. Employee request consultation → pending HR review
        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Stress',
            'body' => 'Butuh sesi konsultasi.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = PerusahaanSession::firstOrFail();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertNull($session->consultant_id);
        $this->assertSame('Butuh sesi konsultasi.', $session->request_notes);

        // 2. HR approve → assign consultant (auto dari pool)
        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertRedirect();

        $session->refresh();
        $this->assertSame($consultant->id, $session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at); // konsultan masih harus propose slot

        // Audit: hr_review.approve tercatat
        $approveLog = AdminAction::where('action', 'hr_review.approve')->latest('id')->first();
        $this->assertNotNull($approveLog);
        $this->assertSame($hrUser->id, $approveLog->admin_id);
        $this->assertSame($session->id, $approveLog->target_id);
        $this->assertSame($consultant->id, $approveLog->payload_json['consultant_id']);

        // 3. Consultant propose 2 slots
        $slot1Start = now()->addDays(2)->setTime(9, 0);
        $slot1End = $slot1Start->copy()->addHour();
        $slot2Start = now()->addDays(3)->setTime(13, 0);
        $slot2End = $slot2Start->copy()->addHour();

        $this->actingAs($docUser)->post(route('consultant.sessions.propose-slots', $session), [
            'slots' => [
                ['start' => $slot1Start->toDateTimeString(), 'end' => $slot1End->toDateTimeString()],
                ['start' => $slot2Start->toDateTimeString(), 'end' => $slot2End->toDateTimeString()],
            ],
        ])->assertRedirect();

        $proposal = SessionSlotProposal::firstOrFail();
        $this->assertSame('pending', $proposal->status);
        $this->assertSame($session->id, $proposal->session_id);
        $this->assertCount(2, $proposal->slots);

        // 4. Employee confirm slot #1 → session terjadwal, proposal accepted
        $this->actingAs($empUser)->post(route('perusahaan.slots.confirm', $proposal), [
            'slot_index' => 0,
        ])->assertRedirect(route('perusahaan.employee'));

        $session->refresh();
        $this->assertSame('scheduled', $session->status);
        $this->assertEquals($slot1Start->toDateTimeString(), $session->scheduled_at->toDateTimeString());
        $this->assertEquals($slot1End->toDateTimeString(), $session->ended_at->toDateTimeString());
        $this->assertSame('accepted', $proposal->fresh()->status);

        // 5. Employee chat ke consultant
        $this->actingAs($empUser)->postJson(route('perusahaan.chat.store', $session), [
            'body' => 'Halo dok, saya siap untuk sesi besok.',
        ])->assertOk();
        $this->assertDatabaseHas('chat_messages', [
            'perusahaan_session_id' => $session->id,
            'sender_id' => $empUser->id,
        ]);

        // 6. Consultant start session (check-in)
        $this->actingAs($docUser)->post(route('consultant.chat.start', $session))
            ->assertRedirect(route('consultant.chat', $session));
        $this->assertNotNull($session->fresh()->checked_in_at);
        $this->assertSame('scheduled', $session->fresh()->status); // status tetap scheduled sampai end

        // 7. Consultant reply
        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), [
            'body' => 'Baik, sampai jumpa besok.',
        ])->assertOk();
        $this->assertSame(2, $session->chatMessages()->count());

        // 8. Consultant end session → done
        $this->actingAs($docUser)->post(route('consultant.chat.end', $session))
            ->assertRedirect(route('consultant.chat', $session));
        $session->refresh();
        $this->assertSame('done', $session->status);
        $this->assertNotNull($session->completed_at);

        // 9. Employee rate → stored
        $this->actingAs($empUser)->post(route('perusahaan.chat.rate', $session), [
            'rating' => 5,
        ])->assertRedirect(route('perusahaan.employee'));
        $this->assertSame(5, $session->fresh()->rating);

        // 10. Dashboard counts reflect: usedMonth = 1 (done), scheduledMonth = 0
        $monthStart = now()->startOfMonth();
        $usedMonth = $emp->sessions()->where('status', 'done')
            ->where('completed_at', '>=', $monthStart)->count();
        $this->assertSame(1, $usedMonth);

        // HR dashboard review queue empty (tidak ada lagi yang pending)
        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertViewHas('requests', fn ($requests) => $requests->count() === 0);
    }

    public function test_hr_reject_path_restores_quota_and_employee_can_request_again()
    {
        [$p, $empUser, $emp, $hrUser, $docUser, $consultant] = $this->fixture();

        // Employee request
        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Mohon sesi.', 'mode' => 'online',
        ])->assertRedirect();
        $session = PerusahaanSession::firstOrFail();

        // HR reject
        $this->actingAs($hrUser)->post(route('perusahaan.review.reject', $session), [
            'reason' => 'Kuota dialihkan.',
        ])->assertRedirect();

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertStringContainsString('Kuota dialihkan', $session->cancel_reason);
        $this->assertNotNull($session->cancelled_at);
        $this->assertFalse((bool) $session->quota_consumed); // kuota kembali

        // Audit: hr_review.reject tercatat
        $rejectLog = AdminAction::where('action', 'hr_review.reject')->latest('id')->first();
        $this->assertNotNull($rejectLog);
        $this->assertSame($hrUser->id, $rejectLog->admin_id);
        $this->assertSame('Kuota dialihkan.', $rejectLog->payload_json['reason']);

        // Employee bisa request lagi (quota kembali)
        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Request ulang.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertSame(2, PerusahaanSession::count());
        $newSession = PerusahaanSession::latest('id')->first();
        $this->assertSame('scheduled', $newSession->status);
        $this->assertNull($newSession->consultant_id);
    }

    public function test_stale_pending_request_auto_cancelled_via_sweep()
    {
        config()->set('booking.pending_cancel_hours', 72);
        [$p, $empUser, $emp, $hrUser] = $this->fixture();

        // Employee request → pending
        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Pending request.', 'mode' => 'online',
        ])->assertRedirect();

        $session = PerusahaanSession::firstOrFail();
        $this->assertTrue((bool) $session->quota_consumed || $session->quota_consumed === null); // tergantung default

        // Backdate created_at > 72 jam
        DB::table('perusahaan_sessions')->where('id', $session->id)
            ->update(['created_at' => now()->subHours(80)]);

        // Sweep
        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertFalse((bool) $session->quota_consumed); // restored
        $this->assertStringContainsString('kedaluwarsa', $session->cancel_reason);
        $this->assertNotNull($session->cancelled_at);

        // Employee bisa request lagi setelah sweep
        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Setelah sweep.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));
        $this->assertSame(2, PerusahaanSession::count());
    }
}
