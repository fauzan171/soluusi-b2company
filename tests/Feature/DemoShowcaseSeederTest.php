<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Database\Seeders\DemoShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// DemoShowcaseSeeder: idempotent (rerun = counts unchanged), semua email
// @demo.com, password seragam, program aktif, konsultan pool ada, purge bersih.
class DemoShowcaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AssessmentInstrumentSeeder::class);
        $this->seed(\Database\Seeders\WellbeingPackageSeeder::class);
    }

    private function counts(): array
    {
        return [
            'users' => User::count(),
            'employees' => PerusahaanEmployee::count(),
            'programs' => CompanyWellbeingProgram::count(),
            'pools' => ConsultantPool::count(),
        ];
    }

    public function test_seeder_is_idempotent_and_data_shape_correct(): void
    {
        $this->seed(DemoShowcaseSeeder::class);
        $first = $this->counts();

        $this->seed(DemoShowcaseSeeder::class);
        $second = $this->counts();

        $this->assertSame($first, $second, 'rerun must not change row counts');

        // Semua user yang dibuat seeder berakhiran @demo.com (diff pre/post seed).
        $this->assertGreaterThan(0, User::where('email', 'like', '%@demo.com')->count());
        $demoEmails = [
            'hr@demo.com', 'karyawan01@demo.com', 'karyawan15@demo.com',
            'sekolah@demo.com', 'siswa01@demo.com', 'siswa12@demo.com',
            'klinik@demo.com', 'chat-b2b@demo.com', 'tester@demo.com',
        ];
        foreach ($demoEmails as $email) {
            $this->assertNotNull(User::where('email', $email)->first(), "missing user {$email}");
        }

        // Password seragam — HR login works (Hash::check).
        $hr = User::where('email', 'hr@demo.com')->firstOrFail();
        $this->assertTrue(Hash::check(DemoShowcaseSeeder::PASSWORD, $hr->password));
        $karyawan = User::where('email', 'karyawan01@demo.com')->firstOrFail();
        $this->assertTrue(Hash::check(DemoShowcaseSeeder::PASSWORD, $karyawan->password));

        // Perusahaan demo: >=10 employees (privacy n>=10), active program.
        $p = Perusahaan::where('name', DemoShowcaseSeeder::PERUSAHAAN_NAME)->firstOrFail();
        $this->assertGreaterThanOrEqual(10, $p->employees()->count());
        $this->assertTrue($p->is_active);
        $program = CompanyWellbeingProgram::where('perusahaan_id', $p->id)
            ->where('status', 'active')->firstOrFail();
        $this->assertTrue($program->starts_at->lte(today()));
        $this->assertTrue($program->ends_at->gte(today()));

        // Asesmen who5 linked ke assignment program (round 1) — supaya
        // terhitung sebagai completers di ReportController.
        $who5Assignment = \App\Models\AssessmentAssignment::where('assignable_type', 'program')
            ->where('assignable_id', $program->id)
            ->where('instrument_code', 'who5')
            ->where('round', 1)
            ->firstOrFail();
        $this->assertSame(
            13,
            \App\Models\Perusahaan\PerusahaanAssessment::where('perusahaan_id', $p->id)
                ->where('instrument_code', 'who5')
                ->where('assessment_assignment_id', $who5Assignment->id)
                ->where('status', 'completed')
                ->count()
        );

        // Konsultan demo ada di pool perusahaan demo.
        $poolConsultantIds = ConsultantPool::where('perusahaan_id', $p->id)->pluck('consultant_id');
        $this->assertGreaterThanOrEqual(2, $poolConsultantIds->count());
        foreach (['chat-b2b@demo.com', 'tester@demo.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertNotNull(
                DB::table('consultants')->where('user_id', $user->id)->first(),
                "{$email} has no consultant row"
            );
        }
    }

    public function test_purge_removes_all_demo_rows(): void
    {
        // Control user non-demo: harus selamat dari purge (blast radius check).
        $control = User::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'control@example.test',
            'username' => 'control_user',
            'firstname' => 'Control',
            'lastname' => 'User',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->seed(DemoShowcaseSeeder::class);
        $this->assertGreaterThan(0, User::where('email', 'like', '%@demo.com')->count());

        DemoShowcaseSeeder::purge();

        $this->assertSame(0, User::where('email', 'like', '%@demo.com')->count());
        $this->assertNull(Perusahaan::where('name', DemoShowcaseSeeder::PERUSAHAAN_NAME)->first());
        $this->assertSame(0, PerusahaanEmployee::where('email', 'like', '%@demo.com')->count());
        $this->assertSame(0, DB::table('consultants')->where(function ($q) {
            $q->whereIn('user_id', User::where('email', 'like', '%@demo.com')->pluck('id'));
        })->count());
        $this->assertNotNull(User::find($control->id), 'non-demo user must survive purge');
    }

    /** app()->isProduction() baca binding container 'env', bukan config('app.env'). */
    private function asProduction(): void
    {
        app()->forgetInstance('env');
        app()->instance('env', 'production');
    }

    private function asTesting(): void
    {
        app()->forgetInstance('env');
        app()->instance('env', 'testing');
    }

    /**
     * expectOutputString-style helper: PHPUnit fail() melempar Exception yang
     * extends RuntimeException, jadi catch harus exclude PHPUnit Failure.
     */
    private function assertSeederGuarded(callable $fn): void
    {
        try {
            $fn();
            $this->fail('must throw RuntimeException in production without DEMO_SEED_ALLOW_PROD');
        } catch (\RuntimeException $e) {
            if ($e instanceof \PHPUnit\Framework\Exception) {
                throw $e;
            }
            $this->assertStringContainsString('DEMO_SEED_ALLOW_PROD', $e->getMessage());
        }
    }

    public function test_run_and_purge_throw_in_production_without_flag(): void
    {
        $this->asProduction();

        // call langsung, bukan $this->seed() (artisan db:seed menelan hasil).
        $this->assertSeederGuarded(fn () => (new DemoShowcaseSeeder())->setContainer(app())->run());
        $this->assertSeederGuarded(fn () => DemoShowcaseSeeder::purge());

        $this->assertSame(0, User::where('email', 'like', '%@demo.com')->count(), 'no demo rows may be created');
    }

    public function test_run_allowed_in_production_with_flag(): void
    {
        $this->asProduction();
        config(['demo_seed.allow_prod' => true]);

        try {
            (new DemoShowcaseSeeder())->setContainer(app())->run();
            $this->assertGreaterThan(0, User::where('email', 'like', '%@demo.com')->count());
        } finally {
            $this->asTesting();
            DemoShowcaseSeeder::purge();
        }
    }
}
