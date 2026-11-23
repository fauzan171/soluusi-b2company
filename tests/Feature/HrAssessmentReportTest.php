<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use Database\Seeders\AssessmentInstrumentSeeder;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// B4: HR report per assignment — completion vs headcount, band distribution
// privacy threshold 10, round trend (growth/impact), assigned-only, no PII.
class HrAssessmentReportTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $company;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);
        $this->seed(WellbeingPackageSeeder::class);

        $roleId = \Illuminate\Support\Facades\DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $this->company = Perusahaan::create([
            'name' => 'PT B4', 'brand' => 'B4Co',
            'sessions_per_employee_month' => 2, 'employee_count' => 20, 'is_active' => true,
        ]);
        $this->hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Hr', 'lastname' => 'B4',
            'username' => 'hr_b4', 'email' => 'hr@b4.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        PerusahaanAdmin::create(['perusahaan_id' => $this->company->id, 'user_id' => $this->hr->id, 'role' => 'hr_admin']);
    }

    private function program(Perusahaan $p, string $packageCode = 'growth'): CompanyWellbeingProgram
    {
        $package = WellbeingPackage::where('code', $packageCode)->firstOrFail();

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $package->id,
            // snapshot cap dari katalog, seperti Admin\WellbeingProgramController
            'assessment_max_participants' => $package->assessment_max_participants,
            'employee_band_size' => $p->employee_count,
            'deep_support_mode' => 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'webinar_quota' => 2,
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

    private function employees(int $n, string $prefix = 'HrAgg'): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = PerusahaanEmployee::create([
                'perusahaan_id' => $this->company->id,
                'name' => "{$prefix} {$i}", 'email' => strtolower($prefix).".{$i}@b4.local", 'consent_at' => now(),
            ]);
        }
        return $out;
    }

    private function submit(array $employees, AssessmentAssignment $a, int $score): void
    {
        $instrument = AssessmentInstrument::where('code', $a->instrument_code)->firstOrFail();
        foreach ($employees as $i => $emp) {
            PerusahaanAssessment::create([
                'perusahaan_id' => $this->company->id,
                'employee_id' => $emp->id,
                'instrument_code' => $a->instrument_code,
                'assessment_assignment_id' => $a->id,
                'answers' => ['q' => 1],
                'score' => $score,
                'taken_at' => now(),
            ]);
            unset($instrument);
        }
    }

    // --- Completion + band distribution privacy (D10)

    public function test_nine_completers_hides_distribution_but_shows_completion()
    {
        $program = $this->program($this->company);
        $a = $this->assign($program, 'phq9');
        $emps = $this->employees(9);
        $this->submit($emps, $a, 9); // band "Ringan" (5-9)

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertSee('9 / 20');                                    // completion vs headcount
        $res->assertSee('Data belum cukup untuk ditampilkan');       // exact hidden-data text
        $res->assertDontSee('Ringan');                                // band count 9 < 10
    }

    public function test_ten_completers_show_distribution()
    {
        $program = $this->program($this->company);
        $a = $this->assign($program, 'phq9');
        $emps = $this->employees(10);
        $this->submit($emps, $a, 9);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertSee('10 / 20');
        $res->assertDontSee('Data belum cukup untuk ditampilkan');
        $res->assertSee('Ringan');
    }

    public function test_unassigned_instrument_never_shows()
    {
        $program = $this->program($this->company);
        $a = $this->assign($program, 'phq9');
        $emps = $this->employees(10);
        $this->submit($emps, $a, 9);

        // Old gad7 rows exist but gad7 is not assigned to the program.
        $gadEmps = $this->employees(10, 'OldGad');
        foreach ($gadEmps as $emp) {
            PerusahaanAssessment::create([
                'perusahaan_id' => $this->company->id, 'employee_id' => $emp->id,
                'instrument_code' => 'gad7', 'answers' => ['q' => 1], 'score' => 7, 'taken_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertDontSee('GAD-7');       // instrument name hidden
        $res->assertDontSee('gad7');
    }

    public function test_legacy_assignment_counts_old_null_rows()
    {
        // No program → legacy who5 assignment (like the backfill), historic rows NULL id.
        $a = $this->assign($this->company, 'who5', ['source' => 'legacy']);
        $emps = $this->employees(10);
        foreach ($emps as $emp) {
            PerusahaanAssessment::create([
                'perusahaan_id' => $this->company->id, 'employee_id' => $emp->id,
                'instrument_code' => 'who5', 'answers' => ['q' => 1], 'score' => 80, 'taken_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();
        $res->assertSee('10 / 20');
        $res->assertDontSee('Data belum cukup untuk ditampilkan');
        $res->assertSee('WHO-5');
    }

    // --- Round trend (D7): growth R1 vs R2, every round n>=10, disclaimer.

    public function test_growth_trend_shown_with_disclaimer_when_each_round_has_10()
    {
        $program = $this->program($this->company);
        $r1 = $this->assign($program, 'phq9', ['round' => 1]);
        $r2 = $this->assign($program, 'phq9', ['round' => 2]);

        $emps = $this->employees(10);
        $this->submit($emps, $r1, 9);
        $this->submit($emps, $r2, 10);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertSee('Perbedaan skor antarperiode tidak otomatis menunjukkan bahwa program menyebabkan perubahan.');
    }

    public function test_trend_hidden_when_any_round_below_10()
    {
        $program = $this->program($this->company);
        $r1 = $this->assign($program, 'phq9', ['round' => 1]);
        $r2 = $this->assign($program, 'phq9', ['round' => 2]);

        $emps = $this->employees(10);
        $this->submit($emps, $r1, 9);
        $few = array_slice($this->employees(4, 'Few'), 0, 4);
        $this->submit($few, $r2, 10);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertDontSee('Perbedaan skor antarperiode tidak otomatis menunjukkan bahwa program menyebabkan perubahan.');
    }

    // --- PII: never employee names/emails in report HTML.

    public function test_no_employee_names_or_emails_anywhere()
    {
        $program = $this->program($this->company);
        $a = $this->assign($program, 'phq9');
        $this->submit($this->employees(10), $a, 9);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();

        $res->assertDontSee('HrAgg 1');
        $res->assertDontSee('HrAgg 5');
        $res->assertDontSee('hragg.1@b4.local');
        $res->assertDontSee('hragg.7@b4.local');
    }

    // --- Program Progress: paid package → per-assignment completion (no "0 / 0" cap row).

    public function test_program_progress_shows_per_assignment_completion_for_paid_package()
    {
        $program = $this->program($this->company, 'growth');
        $a = $this->assign($program, 'phq9');
        $this->submit($this->employees(4), $a, 9);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.program-progress'));
        $res->assertOk()->assertSee('Progres Program');

        $rows = collect($res->viewData('progressRows'));
        $assessmentRow = $rows->first(fn ($r) => ($r['label'] ?? '') === 'Asesmen');
        $this->assertNull($assessmentRow, 'paid package: no single cap-based Asesmen row');

        $assignRows = $res->viewData('assignmentRows');
        $this->assertCount(1, $assignRows);
        $this->assertSame('PHQ-9 (Skrining Depresi)', $assignRows[0]['instrument']);
        $this->assertSame(4, $assignRows[0]['completers']);
        $this->assertSame(20, $assignRows[0]['registered']);
    }

    public function test_program_progress_discovery_keeps_cap()
    {
        $program = $this->program($this->company, 'discovery');
        $this->assertSame(40, (int) $program->assessment_max_participants);

        $a = $this->assign($program, 'phq9');
        $this->submit($this->employees(4), $a, 9);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.program-progress'));
        $res->assertOk();

        $rows = collect($res->viewData('progressRows'));
        $row = $rows->first(fn ($r) => ($r['label'] ?? '') === 'Asesmen');
        $this->assertNotNull($row);
        $this->assertSame(4, $row['used']);
        $this->assertSame(40, $row['total']);
        $this->assertSame(0, count($res->viewData('assignmentRows') ?? []));
    }
}
