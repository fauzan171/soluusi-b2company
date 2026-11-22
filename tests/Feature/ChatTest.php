<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// ponytail: satu check per invariant keamanan chat — owner-only + validasi body.
class ChatTest extends TestCase
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

    private function makeSession(PerusahaanEmployee $emp, ?int $consultantId = null): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $emp->perusahaan_id,
            'employee_id' => $emp->id,
            'consultant_id' => $consultantId,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Chat', 'brand' => 'ChatCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $owner = $this->user('owner@chat.local', 'employee');
        $other = $this->user('other@chat.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $owner->id,
            'name' => 'Owner', 'email' => 'owner@chat.local', 'consent_at' => now(),
        ]);
        $otherEmp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $other->id,
            'name' => 'Other', 'email' => 'other@chat.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@chat.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$owner, $other, $emp, $otherEmp, $docUser, $consultant];
    }

    public function test_owner_can_chat_and_consultant_sees_it()
    {
        [$owner, , $emp, , $docUser, $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'Halo dok'])
            ->assertOk();

        // W5: body terenkripsi at-rest — cek via model (round-trip) + pastikan
        // nilai DB mentah bukan plaintext.
        $this->assertSame('Halo dok', $session->chatMessages()->first()->body);
        $this->assertStringNotContainsString('Halo dok',
            \Illuminate\Support\Facades\DB::table('chat_messages')->where('perusahaan_session_id', $session->id)->value('body'));

        $this->actingAs($docUser)->getJson(route('consultant.chat', $session))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Halo dok']);

        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), ['body' => 'Halo juga'])
            ->assertOk();

        $this->assertSame(2, $session->chatMessages()->count());
    }

    public function test_other_employee_and_other_consultant_blocked()
    {
        [$owner, $other, $emp, $otherEmp, , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        // Employee lain: 403 di room dan store.
        $this->actingAs($other)->getJson(route('perusahaan.chat', $session))->assertForbidden();
        $this->actingAs($other)->postJson(route('perusahaan.chat.store', $session), ['body' => 'x'])->assertForbidden();

        // Karyawan non-pemilik sesi lain pun 403.
        $foreign = $this->makeSession($otherEmp, $consultant->id);
        $this->actingAs($owner)->getJson(route('perusahaan.chat', $foreign))->assertForbidden();

        // Guest: 401/redirect.
        auth()->logout();
        $this->get(route('perusahaan.chat', $session))->assertRedirect(route('login'));

        $this->assertSame(0, $session->chatMessages()->count());
    }

    /** F7: payload harus urut lama→baru (UI append), window = 200 TERBARU, has_more benar. */
    public function test_payload_is_ascending_and_pages_backwards()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        for ($i = 1; $i <= 250; $i++) {
            ChatMessage::create([
                'perusahaan_session_id' => $session->id,
                'sender_id' => $owner->id,
                'body' => 'm'.$i,
            ]);
        }

        $first = $this->actingAs($owner)->getJson(route('perusahaan.chat', $session).'?after=0')
            ->assertOk()->json();

        $this->assertSame('m51', $first['messages'][0]['body'], 'window = 200 pesan TERBARU');
        $this->assertSame('m250', $first['messages'][199]['body'], 'payload urut lama→baru');
        $this->assertTrue($first['has_more'], 'masih ada riwayat lebih lama');

        $older = $this->actingAs($owner)->getJson(
            route('perusahaan.chat', $session).'?before_id='.$first['messages'][0]['id']
        )->assertOk()->json();

        $this->assertCount(50, $older['messages']);
        $this->assertSame('m1', $older['messages'][0]['body']);
        $this->assertSame('m50', $older['messages'][49]['body']);
        $this->assertFalse($older['has_more'], 'riwayat habis');
    }

    public function test_empty_and_oversize_body_rejected()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => ''])
            ->assertStatus(422);
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => str_repeat('a', 2001)])
            ->assertStatus(422);

        $this->assertSame(0, $session->chatMessages()->count());
    }

    public function test_chat_blocked_while_awaiting_consultant_schedule()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        // Gate ACC: 'scheduled' + scheduled_at NULL = belum di-ACC konsultan → tidak boleh chat.
        $session = $this->makeSession($emp, $consultant->id);
        $session->update(['scheduled_at' => null]);

        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'halo'])
            ->assertForbidden();

        $this->assertSame(0, $session->chatMessages()->count());
    }

    public function test_chat_closed_when_session_has_no_consultant()
    {
        [$owner, , $emp] = $this->fixture();
        $session = $this->makeSession($emp, null);

        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'halo'])
            ->assertForbidden();
    }

    public function test_employee_can_end_session_and_give_rating()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        // End session
        $this->actingAs($owner)->post(route('perusahaan.chat.end', $session))
            ->assertRedirect(route('perusahaan.chat', $session));
        $session->refresh();
        $this->assertSame('done', $session->status);
        $this->assertNotNull($session->completed_at);

        // Chat store blocked after end
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'should fail'])
            ->assertForbidden();

        // Rate
        $this->actingAs($owner)->post(route('perusahaan.chat.rate', $session), ['rating' => 5])
            ->assertRedirect(route('perusahaan.employee'));
        $this->assertSame(5, $session->fresh()->rating);
    }

    public function test_rating_only_once_and_only_after_done()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        // Cannot rate before done
        $this->actingAs($owner)->post(route('perusahaan.chat.rate', $session), ['rating' => 4])
            ->assertForbidden();

        $session->update(['status' => 'done', 'completed_at' => now()]);

        // Rate once
        $this->actingAs($owner)->post(route('perusahaan.chat.rate', $session), ['rating' => 4])
            ->assertRedirect();
        $this->assertSame(4, $session->fresh()->rating);

        // Rate again → 422
        $this->actingAs($owner)->post(route('perusahaan.chat.rate', $session), ['rating' => 1])
            ->assertStatus(422);
    }

    public function test_other_employee_cannot_end_or_rate()
    {
        [$owner, $other, $emp, $otherEmp, , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($other)->post(route('perusahaan.chat.end', $session))
            ->assertForbidden();
        $this->actingAs($other)->post(route('perusahaan.chat.rate', $session), ['rating' => 1])
            ->assertForbidden();
    }

    public function test_first_message_sends_email_to_recipient()
    {
        \Illuminate\Support\Facades\Mail::fake();

        [$owner, , $emp, , $docUser, $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        // Pesan pertama dari employee → email ke konsultan
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'halo dok']);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ChatNewMessage::class, fn ($m) => $m->hasTo($docUser->email));

        // Pesan kedua: tidak kirim lagi
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), ['body' => 'iya']);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_employee_end_rejects_cancelled_session()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);
        $session->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => 'test']);

        $this->actingAs($owner)->post(route('perusahaan.chat.end', $session))
            ->assertStatus(422);

        $this->assertSame('cancelled', $session->fresh()->status);
    }

    public function test_poll_payload_includes_session_status()
    {
        [$owner, , $emp, , , $consultant] = $this->fixture();
        $session = $this->makeSession($emp, $consultant->id);

        $this->actingAs($owner)->getJson(route('perusahaan.chat', $session, ['after' => 0]))
            ->assertOk()
            ->assertJsonPath('status', 'scheduled')
            ->assertJsonPath('can_send', true);

        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($owner)->getJson(route('perusahaan.chat', $session, ['after' => 0]))
            ->assertOk()
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('can_send', false);
    }
}