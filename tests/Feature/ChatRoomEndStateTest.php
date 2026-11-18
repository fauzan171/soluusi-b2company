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

/**
 * Room end-state parity (D.5/D.6/D.7): read receipts, sesi berakhir saat
 * room terbuka, dan salinan khusus untuk transkrip yang sudah dipurge.
 */
class ChatRoomEndStateTest extends TestCase
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

    /** [$empUser, $emp, $docUser, $consultant, $session] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Room', 'brand' => 'RoomCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@room.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@room.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@room.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        return [$empUser, $emp, $docUser, $consultant, $session];
    }

    public function test_polling_marks_peer_messages_read(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        $fromConsultant = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => $docUser->id,
            'body' => 'Halo, ada yang bisa saya bantu?',
        ]);

        $this->actingAs($empUser)->getJson(route('perusahaan.chat', $session) . '?after=0')
            ->assertOk();

        $this->assertNotNull($fromConsultant->refresh()->read_at);
    }

    public function test_polling_does_not_mark_own_messages_read(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        $mine = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => $empUser->id,
            'body' => 'Halo dok.',
        ]);

        $this->actingAs($empUser)->getJson(route('perusahaan.chat', $session) . '?after=0')
            ->assertOk();

        $this->assertNull($mine->refresh()->read_at);
    }

    public function test_polling_only_marks_delivered_messages_read(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        $older = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => $docUser->id,
            'body' => 'Pesan sebelumnya.',
        ]);
        // Pesan ini yang ikut payload (after = id pesan lama di atas).
        $delivered = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => $docUser->id,
            'body' => 'Pesan terbaru.',
        ]);

        $this->actingAs($empUser)->getJson(
            route('perusahaan.chat', $session) . '?after=' . $older->id
        )->assertOk();

        $this->assertNotNull($delivered->refresh()->read_at);
        $this->assertNull($older->refresh()->read_at);
    }

    public function test_done_and_rating_shown_after_reload(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))
            ->assertOk()
            ->assertSee('Rating:')
            // Copy retensi hanya dirender server-side saat transkrip memang dipurge.
            ->assertDontSee('sudah dihapus otomatis 24 jam')
            ->assertSee('data-purged="0"', false);
    }

    public function test_never_chatted_session_shows_empty_state_not_purge_copy(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        // Sesi selesai >24 jam lalu TANPA satu pesan pun — bukan kasus purge.
        $session->update(['status' => 'done', 'completed_at' => now()->subDays(2)]);

        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))
            ->assertOk()
            ->assertSee('data-purged="0"', false)
            ->assertDontSee('sudah dihapus otomatis 24 jam');
    }

    public function test_purged_transcript_shows_retention_copy(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()->subDays(2),
            'transcript_purged_at' => now()]);

        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))
            ->assertOk()
            ->assertSee('kebijakan retensi')
            ->assertSee('sudah dihapus otomatis 24 jam')
            ->assertSee('data-purged="1"', false);
    }
    public function test_pending_request_room_does_not_offer_end_button(): void
    {
        [$empUser, $emp, $docUser, $consultant, $session] = $this->fixture();
        // Menunggu penjadwalan: room boleh dibuka, tapi G.7 menolak "akhiri sesi".
        $session->update(['status' => 'scheduled', 'scheduled_at' => null]);

        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))
            ->assertOk()
            ->assertDontSee('Akhiri sesi');
    }
}