<?php

namespace Tests\Feature\Sekolah;

use App\Jobs\GenerateSessionSummary;
use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task C.2: SessionSummaryGenerator service — job thin wrapper dual-vertical.
// Perusahaan path tetap hijau (SessionSummaryTest), sekolah path FK benar.
class SessionSummaryVerticalTest extends TestCase
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

    /** @return array{0: Sekolah, 1: User, 2: User, 3: SekolahSession} */
    private function sekolahFixture(bool $aiEnabled = true): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Vert', 'brand' => 'SMA Vert',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
            'ai_enabled' => $aiEnabled,
        ]);
        $studentUser = $this->user('siswa@vert.test', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $studentUser->id,
            'name' => 'Siswa Vert', 'email' => 'siswa@vert.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = $this->user('doc@vert.test', 'consultant');
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
            'status' => 'done',
            'scheduled_at' => now()->subHour(),
        ]);

        return [$sekolah, $studentUser, $docUser, $session];
    }

    public function test_sekolah_job_stores_summary_with_sekolah_fk(): void
    {
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [, $studentUser, , $session] = $this->sekolahFixture();

        DB::table('chat_messages')->insert([
            ['sekolah_session_id' => $session->id, 'sender_id' => $studentUser->id,
                'body' => 'Saya cemas menghadapi ujian.', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Http::fake([
            'tokengateway.dev/*' => Http::response(json_encode([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'summary' => 'Sesi membahas cemas ujian siswa.',
                        'client_advice' => 'Buat jadwal belajar rutin.',
                    ])],
                ]],
            ]) . "\ndata: [DONE]", 200),
        ]);

        (new GenerateSessionSummary($session->id, 'sekolah'))->handle();

        $row = SessionSummary::where('sekolah_session_id', $session->id)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->perusahaan_session_id);
        $this->assertSame('completed', $row->status);
        $this->assertSame('Sesi membahas cemas ujian siswa.', $row->summary);
    }

    public function test_sekolah_ai_disabled_marks_failed(): void
    {
        Http::fake();
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [, , , $session] = $this->sekolahFixture(aiEnabled: false);

        (new GenerateSessionSummary($session->id, 'sekolah'))->handle();

        $row = SessionSummary::where('sekolah_session_id', $session->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('failed', $row->status);
        $this->assertSame('ai_disabled', $row->error_code);
        Http::assertNothingSent();
    }

    public function test_sekolah_idempotent_same_hash_no_second_row(): void
    {
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [, , , $session] = $this->sekolahFixture();

        // Transcript kosong → hash json_encode([]) — sama dengan perusahaan path.
        SessionSummary::create([
            'sekolah_session_id' => $session->id,
            'summary' => 'existing',
            'client_advice' => 'existing advice',
            'input_hash' => hash('sha256', json_encode([])),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        (new GenerateSessionSummary($session->id, 'sekolah'))->handle();

        $this->assertSame(1, SessionSummary::where('sekolah_session_id', $session->id)->count());
    }

    public function test_default_vertical_is_perusahaan_backcompat(): void
    {
        // Job tanpa argumen vertical resolve model perusahaan — call site lama
        // ChatController dispatch($session->id) tidak berubah perilakunya.
        $j = new GenerateSessionSummary(999);
        $this->assertSame('perusahaan', $j->vertical);

        // id yang tidak ada → no-op, tidak crash worker.
        $j->handle();
        $this->assertSame(0, SessionSummary::count());
    }
}
