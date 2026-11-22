<?php

namespace Tests\Feature;

use App\Jobs\GenerateSessionSummary;
use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Per-session AI summary: dispatch saat end, redaction, idempotency, isolation.
 */
class SessionSummaryTest extends TestCase
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

    /** @return array{0: Perusahaan, 1: User, 2: PerusahaanEmployee, 3: User, 4: Consultant, 5: PerusahaanSession} */
    private function fixture(bool $aiEnabled = true): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Sum', 'brand' => 'SumCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'ai_enabled' => $aiEnabled,
        ]);
        $owner = $this->user('owner@sum.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $owner->id,
            'name' => 'Owner', 'email' => 'owner@sum.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@sum.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id,
            'employee_id' => $emp->id,
            'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->subHour(),
            'checked_in_at' => now()->subHour(),
        ]);

        return [$p, $owner, $emp, $docUser, $consultant, $session];
    }

    public function test_employee_end_dispatches_summary_job(): void
    {
        Bus::fake();
        [, $owner, , , , $session] = $this->fixture();

        $this->actingAs($owner)->post(route('perusahaan.chat.end', $session))
            ->assertRedirect();

        Bus::assertDispatched(GenerateSessionSummary::class, fn ($j) => $j->sessionId === $session->id);
    }

    public function test_consultant_end_dispatches_summary_job(): void
    {
        Bus::fake();
        [, , , $docUser, , $session] = $this->fixture();

        $this->actingAs($docUser)->post(route('consultant.chat.end', $session))
            ->assertRedirect();

        Bus::assertDispatched(GenerateSessionSummary::class, fn ($j) => $j->sessionId === $session->id);
    }

    public function test_ai_disabled_tenant_marks_failed_without_http(): void
    {
        Http::fake();
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [$p, , , , , $session] = $this->fixture(aiEnabled: false);

        (new GenerateSessionSummary($session->id))->handle();

        $row = SessionSummary::where('perusahaan_session_id', $session->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('failed', $row->status);
        $this->assertSame('ai_disabled', $row->error_code);
        Http::assertNothingSent();
    }

    public function test_provider_null_marks_failed_without_http(): void
    {
        Http::fake();
        config()->set('ai.provider', 'null');

        [, , , , , $session] = $this->fixture(aiEnabled: true);

        (new GenerateSessionSummary($session->id))->handle();

        $row = SessionSummary::where('perusahaan_session_id', $session->id)->first();
        $this->assertSame('failed', $row->status);
        $this->assertSame('ai_disabled', $row->error_code);
        Http::assertNothingSent();
    }

    public function test_happy_path_stores_redacted_summary(): void
    {
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [$p, $owner, , , , $session] = $this->fixture();

        // PII di transcript: honorific + email + phone harus di-redact.
        // Insert via model — body terenkripsi at-rest (K16), generator decrypt saat baca.
        \App\Models\ChatMessage::create([
            'perusahaan_session_id' => $session->id, 'sender_id' => $owner->id,
            'body' => 'Pak Budi bilang email saya budi@x.com dan telp 0812-3456-7890',
        ]);
        \App\Models\ChatMessage::create([
            'perusahaan_session_id' => $session->id, 'sender_id' => $owner->id,
            'body' => 'Saya merasa cemas akhir-akhir ini.',
        ]);

        Http::fake([
            'tokengateway.dev/*' => Http::response(json_encode([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'summary' => 'Sesi membahas kecemasan klien.',
                        'client_advice' => 'Coba teknik napas 4-7-8 dan tidur cukup.',
                    ])],
                ]],
            ]) . "\ndata: [DONE]", 200),
        ]);

        (new GenerateSessionSummary($session->id))->handle();

        $row = SessionSummary::where('perusahaan_session_id', $session->id)->first();
        $this->assertSame('completed', $row->status);
        $this->assertSame('Sesi membahas kecemasan klien.', $row->summary);
        $this->assertSame('Coba teknik napas 4-7-8 dan tidur cukup.', $row->client_advice);
        $this->assertNotNull($row->generated_at);

        Http::assertSent(function ($req) {
            $payload = json_decode($req->body(), true);
            $userMsg = $payload['messages'][1]['content'] ?? '';
            // PII tidak boleh lolos.
            if (str_contains($userMsg, 'budi@x.com') || str_contains($userMsg, '0812')) {
                return false;
            }
            if (str_contains($userMsg, 'Pak Budi')) {
                return false;
            }
            return str_contains($userMsg, '[nama dihapus]')
                && str_contains($userMsg, '[email dihapus]')
                && str_contains($userMsg, '[kontak dihapus]');
        });
    }

    public function test_malformed_ai_response_marks_failed_no_exception(): void
    {
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [, , , , , $session] = $this->fixture();

        Http::fake([
            'tokengateway.dev/*' => Http::response('{"choices":[{"message":{"content":"not json"}}]}', 200),
        ]);

        (new GenerateSessionSummary($session->id))->handle();

        $row = SessionSummary::where('perusahaan_session_id', $session->id)->first();
        $this->assertSame('failed', $row->status);
        $this->assertNotNull($row->error_code);
    }

    public function test_idempotent_when_input_hash_matches(): void
    {
        Http::fake();
        config()->set('ai.provider', 'tokengateway');
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');

        [, , , , , $session] = $this->fixture();

        // Pre-compute hash atas transcript kosong — sama dengan yang akan dihitung job.
        $hash = hash('sha256', json_encode([]));
        SessionSummary::create([
            'perusahaan_session_id' => $session->id,
            'summary' => 'existing',
            'client_advice' => 'existing advice',
            'input_hash' => $hash,
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        (new GenerateSessionSummary($session->id))->handle();

        $this->assertSame(1, SessionSummary::where('perusahaan_session_id', $session->id)->count());
        Http::assertNothingSent();
    }

    public function test_employee_sees_own_advice_on_dashboard(): void
    {
        [, $owner, $emp, , , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        SessionSummary::create([
            'perusahaan_session_id' => $session->id,
            'summary' => 'consultant view',
            'client_advice' => 'Saran untuk kamu: tarik napas.',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertSee('Saran dari sesi kamu', false)
            ->assertSee('Saran untuk kamu: tarik napas.', false);
    }

    public function test_other_employee_advice_not_leaked(): void
    {
        [, $owner, , , , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        SessionSummary::create([
            'perusahaan_session_id' => $session->id,
            'summary' => 'consultant view',
            'client_advice' => 'RAHASIA-OWNER-ONLY-XYZ',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        // User lain di tenant yang sama tidak boleh lihat advice ini.
        $other = $this->user('other@sum.local', 'employee');
        PerusahaanEmployee::create([
            'perusahaan_id' => $session->perusahaan_id, 'user_id' => $other->id,
            'name' => 'Other', 'email' => 'other@sum.local', 'consent_at' => now(),
        ]);

        $this->actingAs($other)->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertDontSee('RAHASIA-OWNER-ONLY-XYZ', false);
    }
}
