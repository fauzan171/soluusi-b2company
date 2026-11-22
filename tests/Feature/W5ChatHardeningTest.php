<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// W5: chat hardening (K11) — after_id incremental, sender_role, encrypted body, purge.
class W5ChatHardeningTest extends TestCase
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

    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT W5 Chat', 'brand' => 'W5Chat', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $owner = $this->user('owner@w5chat.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $owner->id,
            'name' => 'Owner', 'email' => 'owner@w5chat.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@w5chat.local', 'consultant');
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
        return [$owner, $docUser, $consultant, $session];
    }

    public function test_after_id_returns_only_newer_messages()
    {
        [$owner, $docUser, , $session] = $this->fixture();
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'satu'])->assertOk();
        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), ['body' => 'dua'])->assertOk();

        $all = $this->actingAs($owner)->getJson(route('perusahaan.chat', $session) . '?after=0')->assertOk()->json('messages');
        $this->assertCount(2, $all);
        $afterFirst = $this->actingAs($owner)
            ->getJson(route('perusahaan.chat', $session) . '?after=' . $all[0]['id'])
            ->assertOk()->json('messages');
        $this->assertCount(1, $afterFirst);
        $this->assertSame('dua', $afterFirst[0]['body']);
    }

    public function test_sender_role_recorded_on_messages()
    {
        [$owner, $docUser, , $session] = $this->fixture();
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'dari karyawan'])->assertOk();
        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), ['body' => 'dari konsultan'])->assertOk();

        $this->assertDatabaseHas('chat_messages', ['perusahaan_session_id' => $session->id, 'sender_role' => 'employee']);
        $this->assertDatabaseHas('chat_messages', ['perusahaan_session_id' => $session->id, 'sender_role' => 'consultant']);
    }

    public function test_body_encrypted_at_rest()
    {
        [$owner, , , $session] = $this->fixture();
        $this->actingAs($owner)->postJson(route('perusahaan.chat.store', $session), ['body' => 'rahasia terapi'])->assertOk();

        $raw = DB::table('chat_messages')->where('perusahaan_session_id', $session->id)->value('body');
        $this->assertNotSame('rahasia terapi', $raw);
        $this->assertStringNotContainsString('rahasia terapi', $raw);

        // Round-trip: model decrypts.
        $this->assertSame('rahasia terapi', $session->chatMessages()->first()->body);
    }

    public function test_encrypt_existing_command_is_idempotent_and_skips_encrypted()
    {
        [$owner, , , $session] = $this->fixture();

        // Simulasikan row legacy plaintext (bypass cast).
        $now = now();
        DB::table('chat_messages')->insert([
            'perusahaan_session_id' => $session->id, 'sender_id' => $owner->id,
            'sender_role' => 'employee', 'body' => 'plaintext lama', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('chat_messages')->insert([
            'perusahaan_session_id' => $session->id, 'sender_id' => $owner->id,
            'sender_role' => 'employee',
            'body' => \Illuminate\Support\Facades\Crypt::encryptString('sudah terenkripsi'), 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->artisan('chat:encrypt-existing')->assertExitCode(0);
        $this->artisan('chat:encrypt-existing')->assertExitCode(0); // idempotent

        $bodies = DB::table('chat_messages')->where('perusahaan_session_id', $session->id)
            ->where('sender_id', $owner->id)->pluck('body');
        $this->assertCount(2, $bodies);
        foreach ($bodies as $body) {
            $this->assertNotSame('plaintext lama', $body);
            $this->assertStringNotContainsString('plaintext lama', $body);
        }

        // Semua row tetap terbaca via model.
        $this->assertSame(2, $session->chatMessages()->where('sender_id', $owner->id)->count());
        $this->assertTrue($session->chatMessages()->pluck('body')->contains('plaintext lama'));
    }

    public function test_chat_purge_prunes_old_messages_after_session_end()
    {
        [, , , $session] = $this->fixture();
        // Purge hanya setelah sesi selesai (K11: 90 hari post-session).
        $session->update(['status' => 'done', 'completed_at' => now()->subDays(91)]);

        $now = now();
        $old = $now->copy()->subDays(91);
        DB::table('chat_messages')->insert([
            'perusahaan_session_id' => $session->id, 'sender_id' => 1,
            'sender_role' => 'employee', 'body' => \Illuminate\Support\Facades\Crypt::encryptString('pesan tua'),
            'created_at' => $old, 'updated_at' => $old, 'read_at' => $old,
        ]);
        DB::table('chat_messages')->insert([
            'perusahaan_session_id' => $session->id, 'sender_id' => 1,
            'sender_role' => 'employee', 'body' => \Illuminate\Support\Facades\Crypt::encryptString('pesan baru'),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->artisan('model:prune', ['--except' => []])->assertExitCode(0);

        // Pesan tua (91d, sesi done >90d) terhapus; pesan baru tetap.
        $this->assertSame(0, DB::table('chat_messages')
            ->where('perusahaan_session_id', $session->id)
            ->where('created_at', '<', now()->subDays(90))->count());
        $remaining = $session->chatMessages()->count();
        $this->assertSame(1, $remaining);
    }
}
