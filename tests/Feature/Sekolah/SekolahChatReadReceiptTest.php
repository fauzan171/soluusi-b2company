<?php

namespace Tests\Feature\Sekolah;

use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// D.5 parity: read receipt di chat sekolah harus sama dengan CAP.
class SekolahChatReadReceiptTest extends TestCase
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

    /** @return array{0: SekolahSession, 1: User(siswa), 2: User(konsultan)} */
    private function fixture(): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Read', 'brand' => 'SMA Read',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $siswa = $this->user('siswa@read.test', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $siswa->id,
            'name' => 'Siswa Read', 'email' => 'siswa@read.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@read.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHour(),
        ]);

        return [$session, $siswa, $docUser];
    }

    public function test_polling_marks_peer_message_read(): void
    {
        [$session, $siswa, $docUser] = $this->fixture();
        $fromDoc = ChatMessage::create([
            'sekolah_session_id' => $session->id,
            'sender_id' => $docUser->id,
            'body' => 'Halo, bagaimana kabarmu?',
        ]);

        $this->actingAs($siswa)->getJson(route('sekolah.chat', $session).'?after=0')->assertOk();

        $this->assertNotNull($fromDoc->refresh()->read_at);
    }

    public function test_polling_does_not_mark_own_message_read(): void
    {
        [$session, $siswa] = $this->fixture();
        $mine = ChatMessage::create([
            'sekolah_session_id' => $session->id,
            'sender_id' => $siswa->id,
            'body' => 'Halo konselor.',
        ]);

        $this->actingAs($siswa)->getJson(route('sekolah.chat', $session).'?after=0')->assertOk();

        $this->assertNull($mine->refresh()->read_at);
    }

    public function test_payload_exposes_read_flag_for_own_messages(): void
    {
        [$session, $siswa, $docUser] = $this->fixture();
        $mine = ChatMessage::create([
            'sekolah_session_id' => $session->id,
            'sender_id' => $siswa->id,
            'body' => 'Pesan saya.',
            'read_at' => now(),
        ]);

        $this->actingAs($siswa)->getJson(route('sekolah.chat', $session).'?after=0')
            ->assertOk()
            ->assertJsonPath('messages.0.id', $mine->id)
            ->assertJsonPath('messages.0.read', true);
    }
}