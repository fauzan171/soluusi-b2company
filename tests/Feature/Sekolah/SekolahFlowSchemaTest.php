<?php

namespace Tests\Feature\Sekolah;

use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\SessionSlotProposal;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task A.1 + A.2: kolom polymorphic chat/summary/proposals + field parity
// di sekolah_sessions. Test di tulis TDD dulu sebelum implementasi migrasi.
class SekolahFlowSchemaTest extends TestCase
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

    private function makeSekolahSession(): SekolahSession
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Schema Test', 'brand' => 'SMA Schema',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Schema',
            'email' => 'siswa@schema.test', 'kelas' => 'X IPA 1',
        ]);

        return SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    private function makePerusahaanSession(): PerusahaanSession
    {
        $p = Perusahaan::create([
            'name' => 'PT Schema', 'brand' => 'SchemaCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $owner = $this->user('owner@schema.test', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $owner->id,
            'name' => 'Owner', 'email' => 'owner@schema.test', 'consent_at' => now(),
        ]);

        return PerusahaanSession::create([
            'perusahaan_id' => $p->id,
            'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function test_chat_messages_has_sekolah_session_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('chat_messages', 'sekolah_session_id'));
        $this->assertTrue(Schema::hasColumn('chat_messages', 'perusahaan_session_id'));
    }

    public function test_session_summaries_has_sekolah_session_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('session_summaries', 'sekolah_session_id'));
        $this->assertTrue(Schema::hasColumn('session_summaries', 'perusahaan_session_id'));
    }

    public function test_session_slot_proposals_has_sekolah_session_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('session_slot_proposals', 'sekolah_session_id'));
    }

    public function test_chat_message_can_belong_to_sekolah_session(): void
    {
        $session = $this->makeSekolahSession();
        $sender = $this->user('sender@schema.test', 'student');

        $msg = ChatMessage::create([
            'sekolah_session_id' => $session->id,
            'sender_id' => $sender->id,
            'body' => 'Halo bu, saya mau curhat.',
        ]);

        $this->assertNull($msg->perusahaan_session_id);
        $this->assertTrue($msg->sekolahSession->is($session));
        $this->assertCount(1, $session->fresh()->chatMessages);
    }

    public function test_chat_message_perusahaan_session_id_nullable(): void
    {
        // Regresi: sebelumnya perusahaan_session_id NOT NULL -> gagal insert sekolah.
        $session = $this->makePerusahaanSession();
        $sender = $this->user('p-sender@schema.test', 'employee');

        $msg = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => $sender->id,
            'body' => 'Halo dok.',
        ]);

        $this->assertNull($msg->sekolah_session_id);
        $this->assertTrue($msg->session->is($session));
    }

    public function test_session_summary_can_belong_to_sekolah_session(): void
    {
        $session = $this->makeSekolahSession();

        $summary = SessionSummary::create([
            'sekolah_session_id' => $session->id,
            'summary' => 'Ringkasan AI',
            'client_advice' => 'Saran untuk siswa',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
        ]);

        $this->assertNull($summary->perusahaan_session_id);
        $this->assertTrue($summary->sekolahSession->is($session));
        $this->assertCount(1, $session->fresh()->sessionSummaries);
    }

    public function test_session_slot_proposal_can_belong_to_sekolah_session(): void
    {
        $session = $this->makeSekolahSession();
        $docUser = $this->user('doc@schema.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $proposal = SessionSlotProposal::create([
            'sekolah_session_id' => $session->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now(),
            'slots' => [['start' => now()->addDay()->toIso8601String(), 'end' => now()->addDay()->addHour()->toIso8601String()]],
            'status' => SessionSlotProposal::STATUS_PENDING,
        ]);

        $this->assertNull($proposal->session_id);
        $this->assertTrue($proposal->sekolahSession->is($session));
        $this->assertCount(1, $session->fresh()->slotProposals);
    }

    public function test_sekolah_session_has_parity_fields(): void
    {
        $this->assertTrue(Schema::hasColumn('sekolah_sessions', 'request_notes'));
        $this->assertTrue(Schema::hasColumn('sekolah_sessions', 'schedule_version'));
        $this->assertTrue(Schema::hasColumn('sekolah_sessions', 'requested_by_user_id'));
        $this->assertTrue(Schema::hasColumn('sekolah_sessions', 'requested_for_role'));
    }

    public function test_sekolah_session_fillable_includes_parity_fields(): void
    {
        $fillable = (new SekolahSession)->getFillable();
        foreach (['request_notes', 'schedule_version', 'requested_by_user_id', 'requested_for_role'] as $f) {
            $this->assertContains($f, $fillable, "SekolahSession::\$fillable missing '{$f}'");
        }
    }

    public function test_sekolah_session_requested_by_relation(): void
    {
        $session = $this->makeSekolahSession();
        $bk = $this->user('bk@schema.test', 'bk');

        $session->update([
            'request_notes' => 'Siswa tampak cemas sebelum ujian.',
            'schedule_version' => 1,
            'requested_by_user_id' => $bk->id,
            'requested_for_role' => 'bk',
        ]);

        $this->assertTrue($session->fresh()->requestedBy->is($bk));
        $this->assertSame('bk', $session->fresh()->requested_for_role);
    }

    public function test_sekolah_session_requested_for_role_defaults_to_self(): void
    {
        $session = $this->makeSekolahSession();
        $this->assertSame('self', $session->fresh()->requested_for_role);
    }

    public function test_sekolah_session_schedule_version_defaults_to_one(): void
    {
        $session = $this->makeSekolahSession();
        $this->assertSame(1, (int) $session->fresh()->schedule_version);
    }
}
