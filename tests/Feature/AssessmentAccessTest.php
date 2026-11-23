<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahStudent;
use App\Models\WellbeingPackage;
use App\Services\AssessmentAccess;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// A1: assignment-driven access — precedence, window, filter instrumen, backfill legacy.
class AssessmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private PerusahaanEmployee $emp;

    private Perusahaan $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $this->company = Perusahaan::create([
            'name' => 'PT A1', 'brand' => 'A1',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = \App\Models\User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A1',
            'username' => 'emp_a1', 'email' => 'emp@a1.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $user->id,
            'name' => 'Emp A1', 'email' => 'emp@a1.local', 'consent_at' => now(),
        ]);
    }

    private function program(Perusahaan $p): CompanyWellbeingProgram
    {
        $this->seed(\Database\Seeders\WellbeingPackageSeeder::class);
        $pkg = WellbeingPackage::where('code', 'growth')->firstOrFail();

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => WellbeingPackage::where('code', 'growth')->firstOrFail()->id,
            'employee_band_size' => $p->employee_count,
            'deep_support_mode' => 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'status' => 'active',
            'assessment_domains' => ['mh'],
        ]);
    }

    private function assign($assignable, string $code, array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => $assignable->getMorphClass(),
            'assignable_id' => $assignable->id,
            'instrument_code' => $code,
            'audience' => 'employee',
            'source' => 'core',
            'opens_at' => today()->subDay(),
            'closes_at' => null,
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // forEmployee — precedence + window + active filter
    // ------------------------------------------------------------------

    public function test_program_assignments_override_perusahaan_legacy()
    {
        // legacy who5 di perusahaan + core dass21 di program aktif
        $this->assign($this->company, 'who5', ['source' => 'legacy']);
        $program = $this->program($this->company);
        $this->assign($program, 'dass21');

        $codes = AssessmentAccess::forEmployee($this->emp)->pluck('instrument_code')->sort()->values()->all();

        $this->assertEquals(['dass21'], $codes);
    }

    public function test_legacy_shown_only_without_program()
    {
        $this->assign($this->company, 'who5', ['source' => 'legacy']);

        $codes = AssessmentAccess::forEmployee($this->emp)->pluck('instrument_code')->all();

        $this->assertEquals(['who5'], $codes);
    }

    public function test_hidden_when_not_yet_open_closed_or_past_close()
    {
        $this->assign($this->company, 'phq9', ['opens_at' => today()->addDay()]);
        $this->assign($this->company, 'gad7', ['status' => 'closed']);
        $this->assign($this->company, 'cbi', ['opens_at' => today()->subDays(10), 'closes_at' => today()->subDay()]);

        $this->assertCount(0, AssessmentAccess::forEmployee($this->emp));
    }

    public function test_inactive_instrument_hidden()
    {
        \App\Models\AssessmentInstrument::where('code', 'who5')->update(['is_active' => false]);
        $this->assign($this->company, 'who5');

        $this->assertCount(0, AssessmentAccess::forEmployee($this->emp));
    }

    // ------------------------------------------------------------------
    // forSekolahMember — student safety (D9)
    // ------------------------------------------------------------------

    public function test_student_never_gets_dass21_staff_can()
    {
        $sekolah = Sekolah::create(['name' => 'SMA A1', 'brand' => 'SMAA1', 'is_active' => true]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa A1',
            'email' => 'siswa@a1.local', 'consent_at' => now(),
        ]);
        $staff = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Staf A1',
            'email' => 'staf@a1.local', 'type' => 'staff', 'consent_at' => now(),
        ]);

        $this->assign($sekolah, 'who5', ['audience' => 'student']);
        $this->assign($sekolah, 'who5', ['audience' => 'staff']);
        $this->assign($sekolah, 'dass21', ['audience' => 'student']); // baris terlarang tetap ada
        $this->assign($sekolah, 'dass21', ['audience' => 'staff']);

        $studentCodes = AssessmentAccess::forSekolahMember($student)->pluck('instrument_code')->all();
        $staffCodes = AssessmentAccess::forSekolahMember($staff)->pluck('instrument_code')->all();

        $this->assertEquals(['who5'], $studentCodes);
        $this->assertContains('dass21', $staffCodes);
    }

    // ------------------------------------------------------------------
    // Grants + resolve
    // ------------------------------------------------------------------

    public function test_granted_codes_bypass_assignments()
    {
        $this->assertEmpty(AssessmentAccess::grantedCodes($this->emp));

        InstrumentGrant::create([
            'employee_id' => $this->emp->id, 'instrument_code' => 'mcmi3',
            'reason' => 'indikasi klinis (fake data)',
        ]);

        $this->assertEquals(['mcmi3'], AssessmentAccess::grantedCodes($this->emp));
    }

    public function test_resolve_returns_lowest_open_round_or_null()
    {
        $this->assertNull(AssessmentAccess::resolveForEmployee($this->emp, 'dass21'));

        $r2 = $this->assign($this->company, 'dass21', ['round' => 2]);
        $r1 = $this->assign($this->company, 'dass21', ['round' => 1]);

        $this->assertEquals($r1->id, AssessmentAccess::resolveForEmployee($this->emp, 'dass21')->id);
        $this->assertEquals($r2->id, AssessmentAccess::forEmployee($this->emp)->firstWhere('round', 2)->id);
    }

    public function test_resolve_for_sekolah_member()
    {
        $sekolah = Sekolah::create(['name' => 'SMA A1', 'brand' => 'SMAA1', 'is_active' => true]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa A1',
            'email' => 'siswa@a1.local', 'consent_at' => now(),
        ]);
        $a = $this->assign($sekolah, 'who5', ['audience' => 'student']);

        $this->assertEquals($a->id, AssessmentAccess::resolveForSekolahMember($student, 'who5')?->id);
        $this->assertNull(AssessmentAccess::resolveForSekolahMember($student, 'dass21'));
    }

    public function test_hasSubmitted()
    {
        $a = $this->assign($this->company, 'who5');

        $this->assertFalse(AssessmentAccess::hasSubmitted($a, $this->emp->id, 'perusahaan'));

        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'who5', 'answers' => [3, 3, 3, 3, 3],
            'score' => 60, 'taken_at' => now(), 'assessment_assignment_id' => $a->id,
        ]);

        $this->assertTrue(AssessmentAccess::hasSubmitted($a, $this->emp->id, 'perusahaan'));
    }

    public function test_duplicate_assignment_employee_rejected()
    {
        $a = $this->assign($this->company, 'who5');

        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'who5', 'answers' => [3, 3, 3, 3, 3],
            'score' => 60, 'taken_at' => now(), 'assessment_assignment_id' => $a->id,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'who5', 'answers' => [3, 3, 3, 3, 3],
            'score' => 60, 'taken_at' => now(), 'assessment_assignment_id' => $a->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Backfill (migration 000102)
    // ------------------------------------------------------------------

    public function test_backfill_legacy_and_core_idempotent()
    {
        // perusahaan TANPA program + sekolah, plus null instrument_code rows
        $this->company->update(['created_at' => now()->subDays(30)]);
        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => null, 'answers' => [3, 3, 3, 3, 3],
            'score' => 60, 'taken_at' => now(),
        ]);
        $sekolah = Sekolah::create(['name' => 'SMA A1', 'brand' => 'SMAA1', 'is_active' => true]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa A1',
            'email' => 'siswa@a1.local', 'consent_at' => now(),
        ]);
        SekolahAssessment::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'answers' => [3, 3, 3, 3, 3], 'score' => 60, 'taken_at' => now(),
        ]);
        // perusahaan DENGAN program aktif
        $p2 = Perusahaan::create([
            'name' => 'PT P2', 'brand' => 'P2',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $this->seed(\Database\Seeders\WellbeingPackageSeeder::class);
        $program = CompanyWellbeingProgram::create([
            'perusahaan_id' => $p2->id,
            'wellbeing_package_id' => WellbeingPackage::where('code', 'growth')->firstOrFail()->id,
            'employee_band_size' => $p2->employee_count,
            'deep_support_mode' => 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'status' => 'active',
            'assessment_domains' => ['mh'],
        ]);

        // migrasi sudah ran saat RefreshDatabase (DB kosong); panggil ulang up()
        // langsung utk backfill data yang dibuat setelahnya — idempotensi = re-run
        $backfill = require database_path('migrations/2026_11_24_000102_backfill_legacy_assessment_assignments.php');
        $backfill->up();
        $backfill->up(); // re-run: tidak boleh duplikat

        // tanpa program → tepat 1 legacy who5
        $this->assertSame(1, AssessmentAssignment::where('assignable_type', 'perusahaan')
            ->where('assignable_id', $this->company->id)->count());
        $this->assertDatabaseHas('assessment_assignments', [
            'assignable_type' => 'perusahaan', 'assignable_id' => $this->company->id,
            'instrument_code' => 'who5', 'audience' => 'employee', 'source' => 'legacy',
        ]);
        // legacy dibuat juga utk perusahaan ber-program (fallback saat program berakhir)
        $this->assertSame(1, AssessmentAssignment::where('assignable_type', 'perusahaan')
            ->where('assignable_id', $p2->id)->where('source', 'legacy')->count());
        // program mh → dass21 + who5 core
        $coreCodes = AssessmentAssignment::where('assignable_type', 'program')
            ->where('assignable_id', $program->id)->pluck('instrument_code')->sort()->values()->all();
        $this->assertEquals(['dass21', 'who5'], $coreCodes);
        // sekolah → who5 utk student + staff
        $this->assertSame(2, AssessmentAssignment::where('assignable_type', 'sekolah')
            ->where('assignable_id', $sekolah->id)->count());
        // null instrument_code backfill who5
        $this->assertSame('who5', $sekolah->assessments()->first()->instrument_code);
        $this->assertNull(PerusahaanAssessment::where('perusahaan_id', $this->company->id)->first()->instrument_code);
    }
}
