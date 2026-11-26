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
 * realdata:clean — hapus dummy production, keep konsultan B2C + referensi.
 * Semua data test FAKE (fake emails, nama generik).
 */
class RealdataCleanCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $adminRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRoleId = DB::table('roles')->updateOrInsert(
            ['name' => 'admin']
        ) ? DB::table('roles')->where('name', 'admin')->value('id')
          : DB::table('roles')->where('name', 'admin')->value('id');

        // Superadmin (KEEP).
        User::firstOrCreate(
            ['email' => 'admin@klinik-konsultan.local'],
            ['uuid' => (string) Str::uuid(), 'username' => 'superadmin',
             'firstname' => 'Super', 'lastname' => 'Admin',
             'password' => bcrypt('fake-password'), 'is_active' => 1,
             'role_id' => $this->adminRoleId],
        );
    }

    /** Konsultan hasil sync B2C (b2c_consultant_id NOT NULL) — KEEP. */
    private function b2cConsultant(int $b2cId): Consultant
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(), 'username' => 'b2c-'.$b2cId,
            'email' => 'b2c-'.$b2cId.'@b2b.local',
            'password' => bcrypt(Str::random(32)),
            'firstname' => 'Fake Konsultan B2C', 'lastname' => '',
            'role_id' => DB::table('roles')->where('name', 'consultant')->value('id'),
            'is_active' => 1,
        ]);

        return Consultant::create([
            'user_id' => $user->id, 'about' => 'fake', 'uuid' => (string) Str::uuid(),
            'profit_sharing' => 0, 'status' => Consultant::ACCEPT,
            'is_active' => 1, 'b2c_consultant_id' => $b2cId,
        ]);
    }

    /** Konsultan lokal dummy (b2c_consultant_id NULL) — DELETE. */
    private function localConsultant(): Consultant
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(), 'username' => 'local-'.Str::random(6),
            'email' => 'fake-local-'.Str::random(6).'@example.test',
            'password' => bcrypt(Str::random(32)),
            'firstname' => 'Fake Local Konsultan', 'lastname' => '',
            'role_id' => DB::table('roles')->where('name', 'consultant')->value('id'),
            'is_active' => 1,
        ]);

        return Consultant::create([
            'user_id' => $user->id, 'about' => 'fake', 'uuid' => (string) Str::uuid(),
            'profit_sharing' => 0, 'status' => Consultant::ACCEPT,
            'is_active' => 1, 'b2c_consultant_id' => null,
        ]);
    }

    /** Tenant perusahaan dummy + employee + session. */
    private function dummyTenant(): Perusahaan
    {
        $p = Perusahaan::create([
            'name' => 'PT Fake Dummy', 'brand' => 'PT Fake Dummy',
        ]);

        PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'name' => 'Fake Employee',
            'email' => 'emp-'.Str::random(6).'@demo-karyawan.test',
        ]);

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'consultation_type' => 'chat',
            'status' => 'done', 'scheduled_at' => now(),
        ]);

        return $p;
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->b2cConsultant(9001);
        $this->localConsultant();
        $this->dummyTenant();

        $this->artisan('realdata:clean', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertSame(1, Consultant::whereNotNull('b2c_consultant_id')->count());
        $this->assertSame(1, Consultant::whereNull('b2c_consultant_id')->count());
        $this->assertSame(1, Perusahaan::count());
        $this->assertSame(1, PerusahaanSession::count());
    }

    public function test_execute_removes_dummy_keeps_b2c(): void
    {
        $keep = $this->b2cConsultant(9002);
        $keepUserId = $keep->user_id;
        $this->b2cConsultant(9003); // konsultan ke-2, juga keep

        $local = $this->localConsultant();
        $this->dummyTenant();

        // Assessment dummy terkait tenant.
        DB::table('perusahaan_assessments')->insert([
            'perusahaan_id' => 1, 'employee_id' => null, 'taken_at' => now(),
            'answers' => json_encode([1, 2, 3, 4, 5]), 'score' => 60,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('screening_answers')->insert([
            'assessable_type' => 'App\\Models\\Perusahaan\\PerusahaanAssessment',
            'assessable_id' => 1, 'question_code' => 'Q1', 'value' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('realdata:clean', ['--dry-run' => false])
            ->expectsConfirmation('Lanjut hapus data di atas?', 'yes')
            ->assertSuccessful();

        // Dummy hilang.
        $this->assertSame(0, Perusahaan::count());
        $this->assertSame(0, PerusahaanEmployee::count());
        $this->assertSame(0, PerusahaanSession::count());
        $this->assertSame(0, DB::table('perusahaan_assessments')->count());
        $this->assertSame(0, DB::table('screening_answers')->count());
        $this->assertSame(0, Consultant::whereNull('b2c_consultant_id')->count());
        $this->assertNull(User::find($local->user_id));

        // B2C synced + superadmin KEEP.
        $this->assertSame(2, Consultant::whereNotNull('b2c_consultant_id')->count());
        $this->assertNotNull(User::find($keepUserId));
        $this->assertNotNull(User::where('email', 'admin@klinik-konsultan.local')->first());
        $this->assertSame(0, User::where('email', 'like', '%@demo-karyawan.test')->count());
    }

    public function test_execute_declined_confirmation_rolls_back(): void
    {
        $this->localConsultant();
        $this->dummyTenant();

        $this->artisan('realdata:clean', ['--dry-run' => false])
            ->expectsConfirmation('Lanjut hapus data di atas?', 'no')
            ->assertSuccessful();

        $this->assertSame(1, Perusahaan::count());
        $this->assertSame(1, Consultant::whereNull('b2c_consultant_id')->count());
    }

    public function test_orphan_non_admin_user_removed(): void
    {
        $this->b2cConsultant(9004); // linked user — keep

        $orphan = User::create([
            'uuid' => (string) Str::uuid(), 'username' => 'orphan-'.Str::random(6),
            'email' => 'orphan-'.Str::random(6).'@example.test',
            'password' => bcrypt(Str::random(32)), 'firstname' => 'Fake', 'lastname' => 'Orphan',
            'role_id' => $this->adminRoleId, 'is_active' => 1,
        ]);

        $this->artisan('realdata:clean', ['--dry-run' => false])
            ->expectsConfirmation('Lanjut hapus data di atas?', 'yes')
            ->assertSuccessful();

        $this->assertNull(User::find($orphan->id));
        $this->assertNotNull(User::where('email', 'admin@klinik-konsultan.local')->first());
    }
}
