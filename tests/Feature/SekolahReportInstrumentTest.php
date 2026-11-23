<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// B4: sekolah report per instrument (assigned only), per audience, threshold 5,
// kelas breakdown only for who5 — no student names (D9).
class SekolahReportInstrumentTest extends TestCase
{
    use RefreshDatabase;

    private Sekolah $sekolah;

    private User $bk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $roleId = DB::table('roles')->insertGetId(['name' => 'bk', 'created_at' => now(), 'updated_at' => now()]);
        $this->sekolah = Sekolah::create([
            'name' => 'SMA B4', 'brand' => 'B4High',
            'sessions_per_student_month' => 2, 'student_count' => 20, 'is_active' => true,
        ]);
        $this->bk = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'BK', 'lastname' => 'B4',
            'username' => 'bk_b4', 'email' => 'bk@b4.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        DB::table('sekolah_admins')->insert([
            'sekolah_id' => $this->sekolah->id, 'user_id' => $this->bk->id, 'role' => 'bk',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assign(string $code, string $audience, array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => $this->sekolah->getMorphClass(),
            'assignable_id' => $this->sekolah->id,
            'instrument_code' => $code,
            'audience' => $audience,
            'source' => 'core',
            'opens_at' => today()->subDay(),
            'closes_at' => null,
        ], $overrides));
    }

    private function students(int $n, string $kelas = 'XII IPA 1'): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = SekolahStudent::create([
                'sekolah_id' => $this->sekolah->id,
                'name' => "Sis B4 {$i}", 'kelas' => $kelas,
                'parent_name' => 'Ortu', 'parent_consent_at' => now(),
            ]);
        }
        return $out;
    }

    private function staff(int $n): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = SekolahStudent::create([
                'sekolah_id' => $this->sekolah->id, 'type' => 'staff',
                'name' => "Staf B4 {$i}", 'email' => "staf.b4.{$i}@b4.local",
                'consent_at' => now(),
            ]);
        }
        return $out;
    }

    private function submit(array $people, AssessmentAssignment $a, int $score, ?string $kelas = null): void
    {
        foreach ($people as $person) {
            SekolahAssessment::create([
                'sekolah_id' => $this->sekolah->id,
                'student_id' => $person->id,
                'kelas' => $kelas ?? ($person->kelas ?? null),
                'instrument_code' => $a->instrument_code,
                'assessment_assignment_id' => $a->id,
                'answers' => ['q' => 1],
                'score' => $score,
                'taken_at' => now(),
            ]);
        }
    }

    public function test_per_instrument_aggregate_with_kelas_breakdown_for_who5()
    {
        $a = $this->assign('who5', 'student', ['source' => 'legacy']);
        $this->submit($this->students(6, 'XII IPA 1'), $a, 80);
        $this->submit($this->students(5, 'XII IPA 2'), $a, 80);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $sections = collect($res->viewData('instrumentSections'));
        $who5 = $sections->firstWhere('code', 'who5');

        $this->assertNotNull($who5);
        $this->assertSame('student', $who5['audience']);
        $this->assertSame(11, $who5['assessed']);

        // Kelas breakdown only for who5: XII IPA 1 (6) shown, XII IPA 2 (5) shown.
        $kelas = collect($who5['kelas']);
        $this->assertSame(6, $kelas['XII IPA 1']);
        $this->assertSame(5, $kelas['XII IPA 2']);
    }

    public function test_threshold_five_suppresses_band_distribution()
    {
        $a = $this->assign('who5', 'student', ['source' => 'legacy']);
        $this->submit($this->students(4), $a, 80); // n=4 < 5

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $who5 = collect($res->viewData('instrumentSections'))->firstWhere('code', 'who5');
        $this->assertNotNull($who5);
        $this->assertSame(4, $who5['assessed']);  // completion visible
        $this->assertNull($who5['bands']);        // distribution hidden
        $this->assertNull($who5['kelas']);
        $res->assertSee('Data belum cukup untuk ditampilkan');
    }

    public function test_non_who5_instrument_has_no_kelas_breakdown()
    {
        // pss10 allowed for students; kelas breakdown only for who5.
        $a = $this->assign('pss10', 'student');
        $this->submit($this->students(6, 'XII IPA 1'), $a, 20);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $pss10 = collect($res->viewData('instrumentSections'))->firstWhere('code', 'pss10');
        $this->assertNotNull($pss10);
        $this->assertSame(6, $pss10['assessed']);
        $this->assertArrayNotHasKey('kelas', $pss10);
        $this->assertNotNull($pss10['bands']);
    }

    public function test_staff_audience_section_separate_from_students()
    {
        $studentA = $this->assign('who5', 'student', ['source' => 'legacy']);
        $staffA = $this->assign('dass21', 'staff');
        $this->submit($this->students(6), $studentA, 80);
        $this->submit($this->staff(6), $staffA, 30);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $sections = collect($res->viewData('instrumentSections'));
        $who5 = $sections->first(fn ($s) => $s['code'] === 'who5' && $s['audience'] === 'student');
        $dass = $sections->first(fn ($s) => $s['code'] === 'dass21' && $s['audience'] === 'staff');

        $this->assertNotNull($who5);
        $this->assertNotNull($dass);
        $this->assertSame(6, $dass['assessed']);
        $this->assertNotNull($dass['bands']);
    }

    public function test_unassigned_instrument_rows_never_show()
    {
        // gad7 rows exist but no gad7 assignment → hidden.
        $a = $this->assign('who5', 'student', ['source' => 'legacy']);
        $this->submit($this->students(6), $a, 80);

        $old = SekolahAssessment::create([
            'sekolah_id' => $this->sekolah->id, 'student_id' => $this->students(1)[0]->id,
            'instrument_code' => 'gad7', 'answers' => ['q' => 1], 'score' => 7, 'taken_at' => now(),
        ]);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $this->assertNull(collect($res->viewData('instrumentSections'))->firstWhere('code', 'gad7'));
        $res->assertDontSee('GAD-7');
        unset($old);
    }

    public function test_legacy_null_rows_counted_for_who5()
    {
        $a = $this->assign('who5', 'student', ['source' => 'legacy']);
        foreach ($this->students(6) as $student) {
            SekolahAssessment::create([
                'sekolah_id' => $this->sekolah->id, 'student_id' => $student->id,
                'instrument_code' => 'who5', 'answers' => ['q' => 1], 'score' => 80, 'taken_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $who5 = collect($res->viewData('instrumentSections'))->firstWhere('code', 'who5');
        $this->assertSame(6, $who5['assessed']);
        $this->assertNotNull($who5['bands']);
    }

    public function test_no_student_names_or_emails_anywhere()
    {
        $a = $this->assign('who5', 'student', ['source' => 'legacy']);
        $this->submit($this->students(6), $a, 80);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $res->assertDontSee('Sis B4 1');
        $res->assertDontSee('Sis B4 3');
    }

    public function test_student_assignment_for_disallowed_instrument_is_not_reported()
    {
        // D9: dass21 for students is not allowed — even if an (invalid) assignment
        // row exists, it must not appear in the BK report.
        $a = $this->assign('dass21', 'student');
        $this->submit($this->students(6), $a, 30);

        $res = $this->actingAs($this->bk)->get(route('sekolah.report'));
        $res->assertOk();

        $this->assertNull(collect($res->viewData('instrumentSections'))
            ->first(fn ($s) => $s['code'] === 'dass21' && $s['audience'] === 'student'));
    }
}
