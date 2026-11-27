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

/**
 * B2cConsultantContact::refresh — isi consultants.notify_email dari B2C.
 * Pola sama dengan B2cConsultantSyncTest: koneksi b2c di-override ke sqlite
 * :memory: dengan skema minimal.
 */
class ConsultantNotifyEmailSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make('config')->set('database.connections.b2c', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('b2c');
    }

    private function localConsultant(): Consultant
    {
        $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::create([
            'uuid' => (string) Str::uuid(), 'username' => 'u'.Str::random(6),
            'email' => 'b2c-99@b2b.local', 'password' => bcrypt('x'),
            'firstname' => 'C', 'lastname' => '', 'role_id' => $roleId, 'is_active' => 1,
        ]);

        return Consultant::create([
            'user_id' => $user->id, 'uuid' => (string) Str::uuid(),
            'about' => 'x', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => 1,
            'b2c_consultant_id' => 1, // single seeded B2C row id=1
        ]);
    }

    public function test_refresh_fills_notify_email_from_b2c(): void
    {
        DB::connection('b2c')->getSchemaBuilder()->create('consultants', function ($t) {
            $t->increments('id');
            $t->string("email");
            $t->softDeletes();
        });
        DB::connection('b2c')->table('consultants')->insert(['id' => 1, 'email' => 'real@b2c.example']);

        $consultant = $this->localConsultant();

        $updated = \App\Services\B2cConsultantContact::refresh();

        $this->assertSame(1, $updated);
        $this->assertSame('real@b2c.example', $consultant->fresh()->notify_email);
        $this->assertSame('real@b2c.example', $consultant->fresh()->notificationEmail());
    }

    public function test_placeholder_b2b_local_in_b2c_is_skipped(): void
    {
        DB::connection('b2c')->getSchemaBuilder()->create('consultants', function ($t) {
            $t->increments('id');
            $t->string("email");
            $t->softDeletes();
        });
        DB::connection('b2c')->table('consultants')->insert(['id' => 1, 'email' => 'b2c-1@b2b.local']);

        $consultant = $this->localConsultant();

        \App\Services\B2cConsultantContact::refresh();

        $this->assertNull($consultant->fresh()->notify_email);
        $this->assertNull($consultant->fresh()->notificationEmail());
    }

    public function test_refresh_is_idempotent(): void
    {
        DB::connection('b2c')->getSchemaBuilder()->create('consultants', function ($t) {
            $t->increments('id');
            $t->string("email");
            $t->softDeletes();
        });
        DB::connection('b2c')->table('consultants')->insert(['id' => 1, 'email' => 'real@b2c.example']);
        $this->localConsultant();

        $first = \App\Services\B2cConsultantContact::refresh();
        $second = \App\Services\B2cConsultantContact::refresh();

        $this->assertSame(1, $first);
        $this->assertSame(0, $second); // unchanged row tidak di-update lagi.
    }

    public function test_command_runs(): void
    {
        $this->artisan('consultants:refresh-notify-emails')->assertSuccessful();
    }
}
