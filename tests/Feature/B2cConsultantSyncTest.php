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
 * Sync konsultan B2C→B2B. Koneksi 'b2c' di-test via SQLite in-memory kedua
 * yang skemanya disamain (5 tabel) — MySQL read-only di prod.
 */
class B2cConsultantSyncTest extends TestCase
{
    use RefreshDatabase;

    // Override koneksi b2c ke sqlite :memory: SEBELUM koneksi pertama kali
    // dipakai (koneksi Laravel lazy — purge untuk buang instance mysql lama).
    protected function setUp(): void
    {
        parent::setUp();
        // Override koneksi b2c (mysql di config) ke sqlite :memory: untuk test.
        // Koneksi Laravel lazy — purge untuk buang instance yang sudah ter-resolve.
        $this->app->make('config')->set('database.connections.b2c', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('b2c');
    }

    private array $b2cIds = []; // name → consultants.id aktual di DB b2c

    private function seedB2c(array $rows): void
    {
        $b2c = DB::connection('b2c');
        $b2c->getSchemaBuilder()->create('users', function ($t) {
            $t->increments('id');
            $t->string('email');
            $t->string('firstname');
            $t->string('lastname');
        });
        $b2c->getSchemaBuilder()->create('consultants', function ($t) {
            $t->increments('id');
            $t->string('uuid');
            $t->string('name');
            $t->string('slug')->nullable();
            $t->string('email');
            $t->text('about')->nullable();
            $t->decimal('profit_sharing', 5, 2)->default(0);
            $t->unsignedInteger('user_id');
            $t->string('status')->default('accept');
            $t->boolean('is_active')->default(1);
            $t->string('category')->nullable();
            $t->string('specialty')->nullable();
            $t->softDeletes();
        });
        $b2c->getSchemaBuilder()->create('consultant_categories', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->unsignedInteger('category_id');
        });
        $b2c->getSchemaBuilder()->create('categories', function ($t) {
            $t->increments('id');
            $t->string('code_category');
            $t->string('name');
        });

        foreach ($rows as $r) {
            $uid = $b2c->table('users')->insertGetId(['email' => $r['email'], 'firstname' => $r['name'], 'lastname' => '']);
            $cid = $b2c->table('consultants')->insertGetId([
                'uuid' => $r['uuid'], 'name' => $r['name'], 'email' => $r['email'],
                'about' => $r['about'] ?? null, 'profit_sharing' => $r['profit'] ?? 0,
                'user_id' => $uid, 'status' => $r['status'] ?? 'accept',
                'is_active' => $r['is_active'] ?? 1, 'category' => $r['category'] ?? null,
            ]);
            $this->b2cIds[$r['name']] = $cid;
            if (! empty($r['deleted'])) {
                $b2c->table('consultants')->where('id', $cid)->update(['deleted_at' => now()]);
            }
        }
    }

    public function test_is_active_column_exists(): void
    {
        // Sanity: skema test harus punya consultants.is_active (migration 2024_01_17).
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('consultants', 'is_active'));
        // Default kolom harus 1 (true) — row baru tanpa is_active aktif.
        $rid = DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $u = User::create(['uuid' => (string) Str::uuid(), 'username' => 'x', 'email' => 'x@x.local', 'password' => 'x', 'firstname' => 'X', 'lastname' => '', 'role_id' => $rid, 'is_active' => 1]);
        $c = Consultant::create(['about' => 'x', 'status' => 'accept', 'profit_sharing' => 0, 'user_id' => $u->id, 'b2c_consultant_id' => 999]);
        $this->assertSame(1, (int) $c->fresh()->is_active);
    }

    public function test_sync_creates_updates_and_deactivates(): void
    {
        // Konsultan 101 masih aktif, 102 jadi reject, 103 di-softdelete.
        $this->seedB2c([
            ['email' => 'a@b2c.local', 'name' => 'Alpha', 'uuid' => (string) Str::uuid(), 'profit' => 20],
            ['email' => 'b@b2c.local', 'name' => 'Bravo', 'uuid' => (string) Str::uuid(), 'status' => 'reject'],
            ['email' => 'c@b2c.local', 'name' => 'Charlie', 'uuid' => (string) Str::uuid(), 'deleted' => true],
        ]);

        $out = \App\Services\B2cConsultantSync::sync();
        $alpha = Consultant::where('b2c_consultant_id', $this->b2cIds['Alpha'])->first();
        $this->assertNotNull($alpha);
        $this->assertSame('Alpha', $alpha->user->firstname);
        $this->assertSame(20, (int) $alpha->profit_sharing);
        $this->assertSame(1, (int) $alpha->is_active);

        // Re-run: idempotent, no duplicate.
        $out = \App\Services\B2cConsultantSync::sync();
        $this->assertSame(0, $out['created']);
        $this->assertSame(1, $out['updated']);
        $this->assertSame(1, Consultant::where('b2c_consultant_id', $this->b2cIds['Alpha'])->count());

        // Update di B2C (nama berubah) → sync bawa ke B2B.
        DB::connection('b2c')->table('consultants')->where('id', $this->b2cIds['Alpha'])->update(['name' => 'Alpha Two']);
        \App\Services\B2cConsultantSync::sync();
        $this->assertSame('Alpha Two', $alpha->fresh()->user->firstname);

        // Konsultan 102/103 tidak pernah dibuat.
        $this->assertNull(Consultant::where('b2c_consultant_id', $this->b2cIds['Bravo'])->first());
        $this->assertNull(Consultant::where('b2c_consultant_id', $this->b2cIds['Charlie'])->first());
    }

    public function test_sync_deactivates_when_b2c_drops_consultant(): void
    {
        $this->seedB2c([['email' => 'a@b2c.local', 'name' => 'Alpha', 'uuid' => (string) Str::uuid()]]);
        \App\Services\B2cConsultantSync::sync();

        // B2C nonaktifkan (status jadi reject).
        DB::connection('b2c')->table('consultants')->where('id', $this->b2cIds['Alpha'])->update(['status' => 'reject']);
        $out = \App\Services\B2cConsultantSync::sync();

        $alpha = Consultant::where('b2c_consultant_id', $this->b2cIds['Alpha'])->first();
        $this->assertSame(1, $out['deactivated']);
        $this->assertFalse((bool) $alpha->is_active);
        $this->assertSame(0, User::where('email', 'b2c-'.$this->b2cIds['Alpha'].'@b2b.local')->value('is_active'));
    }

    public function test_command_runs_and_reports(): void
    {
        $this->seedB2c([['email' => 'a@b2c.local', 'name' => 'Alpha', 'uuid' => (string) Str::uuid()]]);

        $this->artisan('consultants:sync-b2c')->assertSuccessful();
        $this->assertSame(1, Consultant::where('b2c_consultant_id', $this->b2cIds['Alpha'])->count());
    }
}
