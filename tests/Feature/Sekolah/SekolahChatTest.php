<?php

namespace Tests\Feature\Sekolah;

use App\Jobs\GenerateSessionSummary;
use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task C.1: chat siswa <-> konsultan untuk SekolahSession — mirror ChatController
// (CAP). Pesan masuk chat_messages.sekolah_session_id; konsultan start/end;
// end → SAP pervorma + summary job vertical='sekolah'; siswa rate 1-5.
class SekolahChatTest extends TestCase
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

    /** @return array{0: Sekolah, 1: User(siswa), 2: User(konsultan), 3: SekolahSession} */
    private function fixture(bool $scheduled = true): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Chat', 'brand' => 'SMA Chat',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $siswa = $this->user('siswa@chat.test', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $siswa->id,
            'name' => 'Siswa Chat', 'email' => 'siswa@chat.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@chat.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => $scheduled ? now()->subHour() : null,
        ]);

        return [$sekolah, $siswa, $docUser, $session];
    }

    public function test_student_can_open_chat_room(): void
    {
        [, $siswa, , $session] = $this->fixture();

        $this->actingAs($siswa)->get(route('sekolah.chat', $session))
            ->assertOk()
            ->assertSee('Chat Konselor', false);
    }

    public function test_student_can_send_message(): void
    {
        [, $siswa, , $session] = $this->fixture();

        $this->actingAs($siswa)->postJson(route('sekolah.chat.store', $session), ['body' => 'Halo konselor'])
            ->assertOk();

        // body terenkripsi at-rest (K16) — assert via model, bukan raw DB row.
        $this->assertSame('Halo konselor',
            \App\Models\ChatMessage::where('sekolah_session_id', $session->id)
                ->where('sender_id', $siswa->id)->first()?->body);
    }

    public function test_consultant_can_start_session(): void
    {
        [, , $docUser, $session] = $this->fixture();

        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.start', $session))
            ->assertRedirect();

        $this->assertNotNull($session->fresh()->checked_in_at);
    }

    public function test_consultant_end_dispatches_summary_and_pervorma(): void
    {
        Bus::fake();
        config()->set('pervorma.stages', []);
        [, , $docUser, $session] = $this->fixture();

        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.end', $session))
            ->assertRedirect();

        $this->assertSame('done', $session->fresh()->status);
        Bus::assertDispatched(GenerateSessionSummary::class, fn ($j) => $j->sessionId === $session->id && $j->vertical === 'sekolah');
        $this->assertNotNull($session->fresh()->completed_at);
    }

    public function test_student_rate_after_done(): void
    {
        [, $siswa, , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($siswa)->post(route('sekolah.chat.rate', $session), ['rating' => 5])
            ->assertRedirect();

        $this->assertSame(5, (int) $session->fresh()->rating);
    }

    public function test_chat_locked_until_scheduled(): void
    {
        [, $siswa, , $session] = $this->fixture(scheduled: false);

        // Store ditolak — sesi belum terjadwal.
        $this->actingAs($siswa)->postJson(route('sekolah.chat.store', $session), ['body' => 'x'])
            ->assertStatus(422);

        $this->assertSame(0, ChatMessage::where('sekolah_session_id', $session->id)->count());
    }

    public function test_other_student_cannot_chat(): void
    {
        [, , , $session] = $this->fixture();
        $intruder = $this->user('intruder@chat.test', 'student');

        $this->actingAs($intruder)->postJson(route('sekolah.chat.store', $session), ['body' => 'x'])
            ->assertStatus(403);
    }

    public function test_bk_cannot_read_chat(): void
    {
        [$sekolah, , , $session] = $this->fixture();
        $bk = $this->user('bk@chat.test', 'bk');
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $bk->id, 'role' => 'bk']);

        // BK tidak pernah punya akses konten sesi — privacy by design.
        $this->actingAs($bk)->get(route('sekolah.chat', $session))
            ->assertStatus(403);
    }

    public function test_sap_room_has_no_cap_only_forms(): void
    {
        [, , $docUser, $session] = $this->fixture();

        $this->actingAs($docUser)->get(route('consultant.sekolah.chat', $session))
            ->assertOk()
            ->assertDontSee('Catatan sesi')
            ->assertDontSee('/note"', false)
            ->assertDontSee('/result"', false)
            ->assertDontSee('Ulasan karyawan')
            ->assertDontSee('meeting-link', false);
    }

    public function test_done_room_is_read_only_for_participants(): void
    {
        [, $siswa, $docUser, $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($siswa)->get(route('sekolah.chat', $session))
            ->assertOk()
            ->assertSee(route('sekolah.chat.rate', $session), false)
            ->assertSee('Kirim ulasan');
        $this->actingAs($docUser)->get(route('consultant.sekolah.chat', $session))->assertOk();

        // Posting/start/end tetap butuh sesi aktif.
        $this->actingAs($siswa)->postJson(route('sekolah.chat.store', $session), ['body' => 'x'])->assertForbidden();
        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.start', $session))->assertForbidden();
        $this->actingAs($docUser)->post(route('consultant.sekolah.chat.end', $session))->assertForbidden();
        $this->assertSame(0, ChatMessage::where('sekolah_session_id', $session->id)->count());

        $intruder = $this->user('intruder2@chat.test', 'student');
        $this->actingAs($intruder)->get(route('sekolah.chat', $session))->assertForbidden();
    }

    public function test_student_rates_done_session_once(): void
    {
        [, $siswa, , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($siswa)->post(route('sekolah.chat.rate', $session), ['rating' => 4])->assertRedirect();
        $this->actingAs($siswa)->post(route('sekolah.chat.rate', $session), ['rating' => 1])->assertStatus(422);
        $this->assertSame(4, (int) $session->fresh()->rating);

        $this->actingAs($siswa)->get(route('sekolah.chat', $session))
            ->assertOk()
            ->assertDontSee('Kirim ulasan')
            ->assertSee('Rating:');
    }

    public function test_end_redirects_to_room_with_rating_form(): void
    {
        Bus::fake();
        config()->set('pervorma.stages', []);
        [, $siswa, , $session] = $this->fixture();

        $this->actingAs($siswa)->post(route('sekolah.chat.end', $session))
            ->assertRedirect(route('sekolah.chat', $session));
    }
}
