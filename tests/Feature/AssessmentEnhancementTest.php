<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// T3: assessment experience dekat reference KKP — free_text, PSS-10 reverse
// scoring, section per-domain, owner-only print page, home multi-instrument,
// HR top items (n>=10) + aggregate-only CSV export.
class AssessmentEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private PerusahaanEmployee $emp;

    private User $user;

    private Perusahaan $company;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $empRoleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $this->company = Perusahaan::create([
            'name' => 'PT T3', 'brand' => 'T3Co',
            'sessions_per_employee_month' => 2, 'employee_count' => 20, 'is_active' => true,
        ]);
        $this->user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'T3',
            'username' => 'emp_t3', 'email' => 'emp@t3.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $empRoleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $this->user->id,
            'name' => 'Emp T3', 'email' => 'emp@t3.local', 'consent_at' => now(),
        ]);

        $hrRoleId = Role::firstOrCreate(['name' => 'hr_admin'])->id;
        $this->hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Hr', 'lastname' => 'T3',
            'username' => 'hr_t3', 'email' => 'hr@t3.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        PerusahaanAdmin::create(['perusahaan_id' => $this->company->id, 'user_id' => $this->hr->id, 'role' => 'hr_admin']);
    }

    private function assign(string $code): AssessmentAssignment
    {
        return AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $this->company->id,
            'instrument_code' => $code, 'audience' => 'employee', 'source' => 'core',
            'opens_at' => today()->subDay(),
        ]);
    }

    private function pssAnswers(array $overrides = []): array
    {
        $answers = [];
        foreach (['PS1', 'PS2', 'PS3', 'PS4', 'PS5', 'PS6', 'PS7', 'PS8', 'PS9', 'PS10'] as $code) {
            $answers[$code] = 0;
        }
        return $overrides + $answers;
    }

    // --- 1. free_text stored + 1000-char bound ---

    public function test_free_text_stored_on_submit()
    {
        $this->assign('pss10');

        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
                'answers' => $this->pssAnswers(),
                'free_text' => 'Beban deadline proyek (fake data)',
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame('Beban deadline proyek (fake data)', $row->free_text);
    }

    public function test_free_text_over_1000_chars_fails_validation()
    {
        $this->assign('pss10');

        $res = $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
                'answers' => $this->pssAnswers(),
                'free_text' => str_repeat('x', 1001),
            ]);

        $res->assertSessionHasErrors('free_text');
        $this->assertSame(0, PerusahaanAssessment::where('employee_id', $this->emp->id)->count());
    }

    // --- 2. PSS-10 reverse scoring on items 4,5,7,8 ---

    public function test_pss10_reverse_scoring_matches_known_vector()
    {
        $this->assign('pss10');

        // All items = 0. Reverse items (PS4, PS5, PS7, PS8) contribute 4 each
        // → score 16, NOT 0.
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
                'answers' => $this->pssAnswers(),
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame(16, (int) $row->score);
    }

    public function test_pss10_all_max_scores_24()
    {
        $this->assign('pss10');

        // All items = 4 → reverse items contribute 0 each → 6 items × 4 = 24.
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
                'answers' => $this->pssAnswers([
                    'PS1' => 4, 'PS2' => 4, 'PS3' => 4, 'PS4' => 4, 'PS5' => 4,
                    'PS6' => 4, 'PS7' => 4, 'PS8' => 4, 'PS9' => 4, 'PS10' => 4,
                ]),
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame(24, (int) $row->score);
    }

    // --- 3. section per instrument domain ---

    public function test_section_stored_per_instrument_domain()
    {
        $this->assign('nutrition');

        // Nutrition items: 8 × weight 4; answer all 0 → reverse items contribute 4.
        $answers = array_combine(
            ['N1', 'N2', 'N3', 'N4', 'N5', 'N6', 'N7', 'N8'],
            array_fill(0, 8, 0)
        );
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'nutrition'), [
                'answers' => $answers,
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame('nutrition_lifestyle', $row->section);
    }

    public function test_section_defaults_to_mental_health_for_mh_domain()
    {
        $this->assign('pss10');

        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
                'answers' => $this->pssAnswers(),
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame('mental_health', $row->section);
    }

    // --- 4. owner-only print page ---

    public function test_owner_can_open_print_page()
    {
        $row = PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'pss10', 'answers' => $this->pssAnswers(),
            'score' => 16, 'section' => 'mental_health', 'status' => 'completed',
            'taken_at' => now(),
        ]);

        $res = $this->actingAs($this->user)
            ->get(route('perusahaan.assessment.print', $row));
        $res->assertOk();
        $res->assertSee('PSS-10');
        $res->assertSee('window.print()', false);
    }

    public function test_other_user_gets_403_on_print_page()
    {
        $row = PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'pss10', 'answers' => $this->pssAnswers(),
            'score' => 16, 'section' => 'mental_health', 'status' => 'completed',
            'taken_at' => now(),
        ]);

        // Another employee (HR user acting as hr role goes 403 via resolveEmployee).
        $emp2RoleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $user2 = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B',
            'username' => 'emp_b', 'email' => 'empb@t3.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $emp2RoleId,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $user2->id,
            'name' => 'Emp B', 'email' => 'empb@t3.local', 'consent_at' => now(),
        ]);

        $this->actingAs($user2)
            ->get(route('perusahaan.assessment.print', $row))
            ->assertForbidden();
    }

    // --- 5. home shows latest results of multiple instruments ---

    public function test_home_shows_latest_result_per_instrument()
    {
        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'who5', 'answers' => ['W1' => 4], 'score' => 80,
            'section' => 'mental_health', 'status' => 'completed', 'taken_at' => now()->subDay(),
        ]);
        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $this->emp->id,
            'instrument_code' => 'pss10', 'answers' => $this->pssAnswers(), 'score' => 16,
            'section' => 'mental_health', 'status' => 'completed', 'taken_at' => now(),
        ]);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk();

        // Both instrument latest results rendered.
        $res->assertSee('PSS-10');
        $res->assertSee('WHO-5');
    }

    // --- 6. HR: top items only when n>=10; CSV aggregates only ---

    private function employees(int $n, string $prefix = 'T3Agg'): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = PerusahaanEmployee::create([
                'perusahaan_id' => $this->company->id,
                'name' => "{$prefix} {$i}", 'email' => strtolower($prefix).".{$i}@t3.local", 'consent_at' => now(),
            ]);
        }
        return $out;
    }

    private function submitRows(array $emps, string $code, array $answers, ?int $assignmentId = null): void
    {
        foreach ($emps as $emp) {
            PerusahaanAssessment::create([
                'perusahaan_id' => $this->company->id, 'employee_id' => $emp->id,
                'instrument_code' => $code, 'answers' => $answers,
                'score' => array_sum($answers), 'section' => 'mental_health',
                'status' => 'completed', 'taken_at' => now(),
                'assessment_assignment_id' => $assignmentId,
                'free_text' => 'secret free text (fake)',
            ]);
        }
    }

    public function test_hr_report_top_items_hidden_when_n_below_10()
    {
        $a = $this->assign('pss10');
        $emps = $this->employees(9);
        // PS1 (non-reverse) high across the board → would be top item.
        $answers = $this->pssAnswers(['PS1' => 4]);
        $this->submitRows($emps, 'pss10', $answers, $a->id);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();
        $res->assertDontSee('Item paling menekan');
    }

    public function test_hr_report_top_items_shown_when_n_at_least_10()
    {
        $a = $this->assign('pss10');
        $emps = $this->employees(10);
        $answers = $this->pssAnswers(['PS1' => 4, 'PS2' => 2]);
        $this->submitRows($emps, 'pss10', $answers, $a->id);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();
        $res->assertSee('Item paling menekan');
        // PS1 adalah item teks penuh "terganggu oleh sesuatu yang terjadi secara tak terduga".
        $res->assertSee('terganggu oleh sesuatu');
    }

    public function test_hr_report_shows_free_text_fill_count_only()
    {
        $a = $this->assign('pss10');
        $emps = $this->employees(10);
        $this->submitRows($emps, 'pss10', $this->pssAnswers(), $a->id);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();
        $res->assertSee('10');
        $res->assertDontSee('secret free text');
    }

    public function test_csv_export_contains_aggregates_only()
    {
        $a = $this->assign('pss10');
        $emps = $this->employees(10);
        $this->submitRows($emps, 'pss10', $this->pssAnswers(['PS1' => 4]), $a->id);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment.export'));
        $res->assertOk();
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $res->getContent();
        $this->assertStringContainsString('PSS-10', $csv);
        $this->assertStringNotContainsString('emp@t3.local', $csv);
        $this->assertStringNotContainsString('T3Agg 1', $csv);
        $this->assertStringNotContainsString('secret free text', $csv);
    }

    public function test_csv_export_ok_when_round_below_10()
    {
        $a = $this->assign('pss10');
        $this->submitRows($this->employees(9), 'pss10', $this->pssAnswers(['PS1' => 4]), $a->id);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment.export'));
        $res->assertOk();
        $this->assertStringNotContainsString('terganggu oleh sesuatu', $res->getContent());
    }

    public function test_csv_export_shows_dash_not_zero_mean_without_completers()
    {
        $this->assign('pss10');

        $csv = $this->actingAs($this->hr)->get(route('perusahaan.report.assessment.export'))->getContent();
        $rows = array_map(fn ($l) => str_getcsv($l, ',', '"', ''), array_filter(explode("\n", $csv)));
        $cols = collect($rows)->first(fn ($r) => str_starts_with($r[0], 'PSS-10'));
        $this->assertNotNull($cols);
        $this->assertSame('—', $cols[4]);
    }

    public function test_dashboard_shows_dash_not_zero_when_rating_is_privacy_suppressed()
    {
        $emp = $this->employees(1)[0];
        \App\Models\Perusahaan\PerusahaanSession::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $emp->id,
            'status' => 'done', 'rating' => 5, 'scheduled_at' => now()->subDay(),
        ]);

        $res = $this->actingAs($this->hr)->get(route('perusahaan.dashboard'));
        $res->assertOk();
        $res->assertDontSee('0.0 / 5');
    }

    public function test_free_text_count_hidden_between_1_and_9()
    {
        $a = $this->assign('pss10');
        $emps = $this->employees(10);
        $this->submitRows(array_slice($emps, 0, 3), 'pss10', $this->pssAnswers(), $a->id);
        foreach (array_slice($emps, 3) as $emp) {
            PerusahaanAssessment::create([
                'perusahaan_id' => $this->company->id, 'employee_id' => $emp->id,
                'instrument_code' => 'pss10', 'answers' => $this->pssAnswers(), 'score' => 16,
                'section' => 'mental_health', 'status' => 'completed', 'taken_at' => now(),
                'assessment_assignment_id' => $a->id,
            ]);
        }

        $agg = \App\Services\Screening\InstrumentScoring::aggregate(
            $this->company, \App\Models\AssessmentInstrument::where('code', 'pss10')->first()
        );
        $this->assertNull($agg['freeTextCount']);
    }

    public function test_preview_hides_free_text_and_pages_are_no_store()
    {
        $this->assign('pss10');
        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'pss10'), [
            'answers' => $this->pssAnswers(), 'free_text' => 'private worry (fake)',
        ]);
        $row = PerusahaanAssessment::latest('id')->first();

        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.result', $row));
        $res->assertSee('private worry (fake)');
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->actingAs($this->user)
            ->get(route('perusahaan.assessment.print', $row))->headers->get('Cache-Control'));

        $html = view('perusahaan.assessment-print', [
            'employee' => $this->emp, 'perusahaan' => $this->company, 'assessment' => $row,
            'instrument' => null, 'bandLabel' => '', 'bandNote' => '', 'isPreview' => true,
        ])->render();
        $this->assertStringNotContainsString('private worry (fake)', $html);
    }
}
