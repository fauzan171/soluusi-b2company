<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sync profil kaya konsultan B2C→B2B (photo/headline/profile JSON) + render
 * di katalog HR. Koneksi 'b2c' di-fake via sqlite :memory: (MySQL prod read-only).
 */
class B2cConsultantProfileSyncTest extends TestCase
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

    private function seedFullB2c(): int
    {
        $b2c = DB::connection('b2c');
        $sb = $b2c->getSchemaBuilder();

        $sb->create('users', function ($t) {
            $t->increments('id');
            $t->string('email');
            $t->string('firstname');
            $t->string('lastname');
            $t->string('avatar')->nullable();
        });
        $sb->create('consultants', function ($t) {
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
        $sb->create('categories', function ($t) {
            $t->increments('id');
            $t->string('code_category');
            $t->string('name');
        });
        $sb->create('consultant_categories', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->unsignedInteger('category_id');
        });
        $sb->create('education', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
        $sb->create('departements', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
        $sb->create('consultant_education', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->unsignedInteger('education_id');
            $t->unsignedInteger('departement_id')->nullable();
            $t->string('degree')->nullable();
            $t->timestampTz('start_date')->nullable();
            $t->timestampTz('end_date')->nullable();
            $t->text('description')->nullable();
        });
        $sb->create('certificates', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->string('name');
            $t->string('number');
            $t->string('url')->nullable();
            $t->integer('year');
            $t->timestampTz('expired_at')->nullable();
        });
        $sb->create('skills', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
        $sb->create('consultant_skills', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->unsignedInteger('skill_id');
        });
        $sb->create('consultant_packages', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->string('name');
            $t->integer('price');
            $t->integer('duration');
            $t->integer('quota')->nullable();
            $t->softDeletes();
        });
        $sb->create('companies', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
        $sb->create('consultant_companies', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('consultant_id');
            $t->unsignedInteger('company_id');
            $t->timestampTz('start_at')->nullable();
            $t->timestampTz('end_at')->nullable();
            $t->string('position')->nullable();
            $t->string('location')->nullable();
            $t->text('description')->nullable();
        });

        $uid = $b2c->table('users')->insertGetId(['email' => 'alpha@b2c.local', 'firstname' => 'Alpha', 'lastname' => '', 'avatar' => '42_avatar.png']);
        $cid = $b2c->table('consultants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'name' => 'Alpha', 'email' => 'alpha@b2c.local',
            'about' => 'Psikolog klinis', 'profit_sharing' => 20, 'user_id' => $uid,
            'status' => 'accept', 'is_active' => 1, 'specialty' => 'Anak & remaja',
        ]);

        $catId = $b2c->table('categories')->insertGetId(['code_category' => 'psikologi', 'name' => 'Psikologi']);
        $b2c->table('consultant_categories')->insert(['consultant_id' => $cid, 'category_id' => $catId]);

        $eduId = $b2c->table('education')->insertGetId(['name' => 'Universitas Indonesia']);
        $depId = $b2c->table('departements')->insertGetId(['name' => 'Psikologi Klinis']);
        $b2c->table('consultant_education')->insert([
            'consultant_id' => $cid, 'education_id' => $eduId, 'departement_id' => $depId,
            'degree' => 'S2', 'start_date' => '2010-01-01', 'end_date' => '2012-01-01', 'description' => 'Cum laude',
        ]);
        $b2c->table('certificates')->insert([
            'consultant_id' => $cid, 'name' => 'HIPKI', 'number' => '123-ABC', 'url' => 'https://b2c.local/cert/1', 'year' => 2015,
        ]);
        $skillId = $b2c->table('skills')->insertGetId(['name' => 'Cognitive Behavioral Therapy']);
        $b2c->table('consultant_skills')->insert(['consultant_id' => $cid, 'skill_id' => $skillId]);
        $b2c->table('consultant_packages')->insert([
            'consultant_id' => $cid, 'name' => 'Paket 4 sesi', 'price' => 400000, 'duration' => 60, 'quota' => 4,
        ]);
        // Satu package soft-deleted — tidak boleh ikut.
        $delPkg = $b2c->table('consultant_packages')->insertGetId([
            'consultant_id' => $cid, 'name' => 'Paket lama', 'price' => 100000, 'duration' => 30, 'quota' => 1,
        ]);
        $b2c->table('consultant_packages')->where('id', $delPkg)->update(['deleted_at' => now()]);

        $compId = $b2c->table('companies')->insertGetId(['name' => 'PT Sejahtera']);
        $b2c->table('consultant_companies')->insert([
            'consultant_id' => $cid, 'company_id' => $compId, 'position' => 'HR Consultant',
            'start_at' => '2015-01-01', 'end_at' => '2018-01-01', 'location' => 'Jakarta',
        ]);

        return $cid;
    }

    public function test_sync_persists_rich_profile(): void
    {
        $cid = $this->seedFullB2c();
        config(['services.b2c.asset_base' => 'https://b2c.example']);

        $out = \App\Services\B2cConsultantSync::sync();
        $this->assertSame(1, $out['created']);

        $c = Consultant::where('b2c_consultant_id', $cid)->first();
        $this->assertSame('https://b2c.example/storage/avatars/42_avatar.png', $c->photo_url);
        $this->assertSame('Anak & remaja', $c->headline);

        $profile = $c->profile;
        $this->assertSame([['code' => 'psikologi', 'name' => 'Psikologi']], $profile['categories']);
        $this->assertSame('Universitas Indonesia', $profile['education'][0]['institution']);
        $this->assertSame('Psikologi Klinis', $profile['education'][0]['department']);
        $this->assertSame('S2', $profile['education'][0]['degree']);
        $this->assertSame('HIPKI', $profile['certificates'][0]['name']);
        $this->assertSame('123-ABC', $profile['certificates'][0]['number']);
        $this->assertSame('Cognitive Behavioral Therapy', $profile['skills'][0]);
        $this->assertSame('PT Sejahtera', $profile['experience'][0]['company']);
        $this->assertSame('HR Consultant', $profile['experience'][0]['position']);
        $this->assertSame('Paket 4 sesi', $profile['packages'][0]['name']);
        $this->assertCount(1, $profile['packages']); // soft-deleted excluded
        $this->assertSame(400000, (int) $profile['packages'][0]['price']);

        // Re-run: idempotent (updated, no duplicate).
        $out = \App\Services\B2cConsultantSync::sync();
        $this->assertSame(0, $out['created']);
        $this->assertSame(1, $out['updated']);
    }

    public function test_sync_skips_non_wellbeing_only_consultants(): void
    {
        $cid = $this->seedFullB2c();
        $b2c = DB::connection('b2c');
        $uid = $b2c->table('users')->insertGetId(['email' => 'lawyer@b2c.local', 'firstname' => 'Lawyer', 'lastname' => '']);
        $lid = $b2c->table('consultants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'name' => 'Lawyer', 'email' => 'lawyer@b2c.local',
            'about' => 'x', 'profit_sharing' => 0, 'user_id' => $uid, 'status' => 'accept', 'is_active' => 1,
        ]);
        $hukum = $b2c->table('categories')->insertGetId(['code_category' => 'hukum', 'name' => 'Hukum']);
        $b2c->table('consultant_categories')->insert(['consultant_id' => $lid, 'category_id' => $hukum]);

        \App\Services\B2cConsultantSync::sync();

        $this->assertNotNull(Consultant::where('b2c_consultant_id', $cid)->first());
        $this->assertNull(Consultant::where('b2c_consultant_id', $lid)->first());
    }

    public function test_sync_clears_stale_photo_when_b2c_avatar_removed(): void
    {
        $cid = $this->seedFullB2c();
        config(['services.b2c.asset_base' => 'https://b2c.example']);
        \App\Services\B2cConsultantSync::sync();

        // Avatar dihapus di B2C → photo_url B2B harus dibersihkan.
        DB::connection('b2c')->table('users')->where('firstname', 'Alpha')->update(['avatar' => null]);
        \App\Services\B2cConsultantSync::sync();

        $c = Consultant::where('b2c_consultant_id', $cid)->first();
        $this->assertNull($c->photo_url);
    }

    public function test_hr_catalog_and_detail_render_profile(): void
    {
        $cid = $this->seedFullB2c();
        config(['services.b2c.asset_base' => 'https://b2c.example']);
        \App\Services\B2cConsultantSync::sync();

        // HR login: pakai helper dari ConsultantCatalogTest style.
        $roleId = DB::table('roles')->where('name', 'hr_admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $hr = User::create([
            'uuid' => (string) Str::uuid(), 'firstname' => 'H', 'lastname' => 'R',
            'username' => 'hr-test', 'email' => 'hr@test.local', 'password' => bcrypt('x'),
            'role_id' => $roleId, 'is_active' => 1,
        ]);
        $p = \App\Models\Perusahaan\Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        \App\Models\Perusahaan\PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hr->id, 'name' => 'HR']);

        $consultant = Consultant::where('b2c_consultant_id', $cid)->first();

        // Katalog: photo + headline.
        $this->actingAs($hr)->get(route('perusahaan.consultants.catalog'))
            ->assertOk()
            ->assertSee('https://b2c.example/storage/avatars/42_avatar.png')
            ->assertSee('Anak & remaja');

        // Detail: headline, skills, pendidikan, sertifikasi, pengalaman, paket.
        $this->actingAs($hr)->get(route('perusahaan.consultants.detail', $consultant))
            ->assertOk()
            ->assertSee('Anak & remaja')
            ->assertSee('Cognitive Behavioral Therapy')
            ->assertSee('Universitas Indonesia')
            ->assertSee('HIPKI')
            ->assertSee('PT Sejahtera')
            ->assertSee('Paket 4 sesi');
    }

    public function test_type_from_category_maps_b2c_codes(): void
    {
        // Mapping konatif: kategori B2C → konsultan_type B2B (default psikolog
        // untuk yang tidak jelas — lihat report mapping ambigu).
        $this->assertSame('psikolog', \App\Models\ConsultantPool::typeFromCategory('Psikologi'));
        $this->assertSame('karir', \App\Models\ConsultantPool::typeFromCategory('Karir'));
        $this->assertSame('nutrisionis', \App\Models\ConsultantPool::typeFromCategory('Gizi'));
        $this->assertSame('fisioterapis', \App\Models\ConsultantPool::typeFromCategory('Fisioterapi'));
        // Unknown → default psikolog (perilaku lama dijaga).
        $this->assertSame('psikolog', \App\Models\ConsultantPool::typeFromCategory('Bisnis'));
        $this->assertSame('psikolog', \App\Models\ConsultantPool::typeFromCategory('Musik'));
        $this->assertSame('psikolog', \App\Models\ConsultantPool::typeFromCategory(null));
    }
}
