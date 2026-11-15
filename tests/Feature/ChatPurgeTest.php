<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Purge chat_messages untuk sesi done ≥24 jam; summary & sesi tetap hidup.
 */
class ChatPurgeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function makeSession(string $status, ?\DateTimeInterface $completedAt, ?\DateTimeInterface $scheduledAt): PerusahaanSession
    {
        $p = Perusahaan::firstOrCreate(
            ['name' => 'PT Purge'],
            ['brand' => 'PurgeCo', 'sessions_per_employee_month' => 5,
                'employee_count' => 5, 'is_active' => true]
        );
        $user = $this->makeUser('u' . Str::random(6) . '@purge.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'E', 'email' => $user->email, 'consent_at' => now(),
        ]);

        return PerusahaanSession::create([
            'perusahaan_id' => $p->id,
            'employee_id' => $emp->id,
            'consultant_id' => null,
            'konsultan_type' => 'psikolog',
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'completed_at' => $completedAt,
        ]);
    }

    private function addMessages(PerusahaanSession $s, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('chat_messages')->insert([
                'perusahaan_session_id' => $s->id,
                'sender_id' => $s->employee->user_id,
                'body' => "pesan {$i}",
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_done_older_than_24h_is_purged(): void
    {
        $old = $this->makeSession('done', now()->subHours(25), now()->subDays(2));
        $this->addMessages($old, 3);

        $this->artisan('chat:purge-ended')->assertSuccessful();

        $this->assertSame(0, DB::table('chat_messages')->where('perusahaan_session_id', $old->id)->count());
    }

    public function test_done_under_24h_is_kept(): void
    {
        $recent = $this->makeSession('done', now()->subHour(), now()->subHours(2));
        $this->addMessages($recent, 2);

        $this->artisan('chat:purge-ended')->assertSuccessful();

        $this->assertSame(2, DB::table('chat_messages')->where('perusahaan_session_id', $recent->id)->count());
    }

    public function test_scheduled_session_is_kept(): void
    {
        $live = $this->makeSession('scheduled', null, now()->subDays(3));
        $this->addMessages($live, 2);

        $this->artisan('chat:purge-ended')->assertSuccessful();

        $this->assertSame(2, DB::table('chat_messages')->where('perusahaan_session_id', $live->id)->count());
    }

    public function test_session_summary_survives_purge(): void
    {
        $old = $this->makeSession('done', now()->subHours(25), now()->subDays(2));
        $this->addMessages($old, 2);
        SessionSummary::create([
            'perusahaan_session_id' => $old->id,
            'summary' => 'keep me',
            'client_advice' => 'keep me too',
            'input_hash' => hash('sha256', 'x'),
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        $this->artisan('chat:purge-ended')->assertSuccessful();

        $this->assertSame(0, DB::table('chat_messages')->where('perusahaan_session_id', $old->id)->count());
        $this->assertDatabaseHas('session_summaries', [
            'perusahaan_session_id' => $old->id,
            'summary' => 'keep me',
        ]);
    }
}
