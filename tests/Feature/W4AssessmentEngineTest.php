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
use App\Services\Screening\InstrumentScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// W4: engine assessment definition-driven — parity WHO-5, golden PHQ-9/GAD-7/CBI,
// privacy HR (agregat + eskalasi count-only), seeder idempotent (brief W4).
class W4AssessmentEngineTest extends TestCase
{
    use RefreshDatabase;

    private function seedInstruments(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);
    }

    private function makeEmployee(int $quota = 2): array
    {
        $roleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $p = Perusahaan::create([
            'name' => 'PT W4', 'brand' => 'W4Co',
            'sessions_per_employee_month' => $quota,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'W4',
            'username' => 'emp_w4', 'email' => 'emp@w4.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp W4', 'email' => 'emp@w4.local', 'consent_at' => now(),
        ]);
        return [$p, $emp, $user];
    }

    private function makeHr(Perusahaan $p): User
    {
        $roleId = Role::firstOrCreate(['name' => 'hr_admin'])->id;
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Hr', 'lastname' => 'W4',
            'username' => 'hr_w4', 'email' => 'hr@w4.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $user->id, 'role' => 'hr_admin']);
        return $user;
    }

    private function answersFor(string $code, int $n, callable $fn): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[$code.$i] = $fn($i);
        }
        return $out;
    }

    /** B1: employee butuh assignment terbuka utk kerjakan instrumen (D2). */
    private function assign(PerusahaanEmployee $emp, string $code): \App\Models\AssessmentAssignment
    {
        return \App\Models\AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $emp->perusahaan_id,
            'instrument_code' => $code, 'audience' => 'employee', 'source' => 'core',
            'opens_at' => today()->subDay(),
        ]);
    }

    // --- Parity: WHO-5 path lama (formula controller) vs engine baru — same answers → same band.

    public function test_who5_parity_old_formula_vs_new_engine()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'who5')->firstOrFail();

        $answers = ['W1' => 3, 'W2' => 4, 'W3' => 2, 'W4' => 5, 'W5' => 1];
        $result = InstrumentScoring::score($instrument, $answers);

        // Path lama: array_sum × 4 + band() model (EmployeeController.php:125, PerusahaanAssessment.php:21-25).
        $legacyScore = array_sum([3, 4, 2, 5, 1]) * 4;
        $row = new PerusahaanAssessment(['score' => $legacyScore]);

        $this->assertSame($legacyScore, $result['score']);
        $this->assertSame($row->band(), $result['band']['label']);
        $this->assertSame(60, $result['score']);
        $this->assertSame('Perlu Perhatian', $result['band']['label']);

        // Boundary parity: 75 → Sehat, 49 → Berisiko Tinggi (sama dengan model lama).
        foreach ([0 => 'Berisiko Tinggi', 49 => 'Berisiko Tinggi', 50 => 'Perlu Perhatian', 74 => 'Perlu Perhatian', 75 => 'Sehat', 100 => 'Sehat'] as $score => $label) {
            $this->assertSame($label, AssessmentInstrument::where('code', 'who5')->firstOrFail()->bandLabelFor($score), "score {$score}");
        }
    }

    // --- Golden cases (research-c): PHQ-9 bands + eskalasi item 9.

    public function test_phq9_golden_scoring_and_item9_escalation()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'phq9')->firstOrFail();

        // P9=0 supaya isolasi skor total; eskalasi item-9 diuji terpisah di bawah.
        $all2 = $this->answersFor('P', 9, fn ($i) => $i === 9 ? 0 : 2);
        $r = InstrumentScoring::score($instrument, $all2);
        $this->assertSame(16, $r['score']);
        $this->assertSame('Agak Berat', $r['band']['label']);
        $this->assertFalse($r['escalated']);

        // Item 9 (suicidal ideation) > 0 → eskalasi WAJIB regardless total skor.
        $item9Only = $this->answersFor('P', 9, fn ($i) => $i === 9 ? 1 : 0);
        $r = InstrumentScoring::score($instrument, $item9Only);
        $this->assertSame(1, $r['score']);
        $this->assertSame('Minimal', $r['band']['label']);
        $this->assertTrue($r['escalated']);
    }

    public function test_gad7_golden_scoring()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'gad7')->firstOrFail();

        $r = InstrumentScoring::score($instrument, $this->answersFor('G', 7, fn () => 1));
        $this->assertSame(7, $r['score']);
        $this->assertSame('Ringan', $r['band']['label']);

        $r = InstrumentScoring::score($instrument, $this->answersFor('G', 7, fn () => 2));
        $this->assertSame(14, $r['score']);
        $this->assertSame('Sedang', $r['band']['label']);
    }

    public function test_cbi_golden_scoring_with_reverse_item()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'cbi')->firstOrFail();

        // 19 item, mean × 25. C7 reverse (positif) → kontribusi bound - value.
        $all3 = $this->answersFor('C', 19, fn () => 3);
        $r = InstrumentScoring::score($instrument, $all3);
        $this->assertSame(72, $r['score']); // raw 55 → 55/19×25 = 72
        $this->assertSame('Sedang', $r['band']['label']);

        $withReverse = $this->answersFor('C', 19, fn ($i) => $i === 7 ? 0 : 3);
        $r = InstrumentScoring::score($instrument, $withReverse);
        $this->assertSame(76, $r['score']); // raw 58 → 76 → band Tinggi (≥75)
        $this->assertSame('Tinggi', $r['band']['label']);
    }

    // --- Seeder idempotent.

    public function test_instrument_seeder_is_idempotent()
    {
        $this->seedInstruments();
        $instruments = AssessmentInstrument::count();
        $questions = \App\Models\ScreeningQuestion::whereNotNull('instrument_code')->count();
        $this->assertSame(13, $instruments);
        $this->assertSame(328, $questions); // 5+9+7+19 + wv1 (21+10+15+12+8+8) + ac1 (15+24+175)

        $this->seedInstruments();
        $this->assertSame($instruments, AssessmentInstrument::count());
        $this->assertSame($questions, \App\Models\ScreeningQuestion::whereNotNull('instrument_code')->count());
    }

    // --- Employee flow: pilih instrumen → kerjakan → hasil individual.

    public function test_employee_flow_store_and_see_own_result()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'phq9');

        $this->actingAs($user)->get(route('perusahaan.assessment.instruments'))->assertOk()->assertSee('PHQ-9');

        $answers = $this->answersFor('P', 9, fn ($i) => $i === 9 ? 2 : 1);
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'phq9'), ['answers' => $answers])
            ->assertRedirect();

        $row = $emp->assessments()->where('instrument_code', 'phq9')->first();
        $this->assertNotNull($row);
        $this->assertSame(10, $row->score); // 8×1 + 2
        $this->assertTrue((bool) $row->escalated);

        $this->actingAs($user)->get(route('perusahaan.assessment.result', $row))
            ->assertOk()
            ->assertSee('Sedang') // band 10-14
            ->assertSee('119'); // eskalasi → hotline tampil
    }

    public function test_employee_cannot_open_other_employee_result()
    {
        $this->seedInstruments();
        [$p, $empA, $userA] = $this->makeEmployee();
        $userB = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B',
            'username' => 'emp_b', 'email' => 'empb@w4.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => Role::firstOrCreate(['name' => 'employee'])->id,
        ]);
        $empB = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $userB->id,
            'name' => 'Emp B', 'email' => 'empb@w4.local', 'consent_at' => now(),
        ]);

        $row = PerusahaanAssessment::create([
            'perusahaan_id' => $p->id, 'employee_id' => $empA->id,
            'instrument_code' => 'who5', 'answers' => ['W1' => 5, 'W2' => 5, 'W3' => 5, 'W4' => 5, 'W5' => 5],
            'score' => 100, 'taken_at' => now(),
        ]);

        $this->actingAs($userB)->get(route('perusahaan.assessment.result', $row))->assertForbidden();
        $this->actingAs($userA)->get(route('perusahaan.assessment.result', $row))->assertOk();
    }

    public function test_store_rejects_invalid_answers()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'gad7');

        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'gad7'), [
            'answers' => ['G1' => 1, 'G2' => 1], // size salah
        ])->assertSessionHasErrors('answers');

        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'gad7'), [
            'answers' => $this->answersFor('G', 7, fn () => 9), // range salah (0-3)
        ])->assertSessionHasErrors('answers.G1');
    }

    // --- Privacy: HR hanya agregat per instrumen; n≥10 suppression (wv1 R10); eskalasi count-only.

    public function test_hr_aggregate_suppression_and_escalation_count_only()
    {
        $this->seedInstruments();
        [$p, , ] = $this->makeEmployee();
        $hr = $this->makeHr($p);

        // B4: report is now per-assignment; add a legacy phq9 assignment so the
        // PHQ-9 rows (NULL assessment_assignment_id) still map to a section.
        // No privacy assertion weakened — same rows, same aggregate.
        AssessmentAssignment::create([
            'assignable_type' => 'perusahaan',
            'assignable_id' => $p->id,
            'instrument_code' => 'phq9',
            'audience' => AssessmentAssignment::AUDIENCE_EMPLOYEE,
            'source' => AssessmentAssignment::SOURCE_LEGACY,
            'round' => 1,
            'status' => 'active',
            'opens_at' => now()->subDay(),
        ]);

        // 10 karyawan PHQ-9 satu band (Ringan, skor 9) + 2 eskalasi → safe() lolos;
        // 3 GAD-7 → suppressed. Band minoritas <10 juga tersupresi (whole-family).
        for ($i = 1; $i <= 10; $i++) {
            $emp = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'name' => "Agg {$i}",
                'email' => "agg{$i}@w4.local", 'consent_at' => now(),
            ]);
            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'instrument_code' => 'phq9',
                'answers' => $this->answersFor('P', 9, fn ($j) => ($i <= 2 && $j === 9) ? 1 : 1),
                'score' => 9,
                'escalated' => $i <= 2,
                'taken_at' => now(),
            ]);
        }
        for ($i = 1; $i <= 3; $i++) {
            $emp = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'name' => "Gad {$i}",
                'email' => "gad{$i}@w4.local", 'consent_at' => now(),
            ]);
            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'instrument_code' => 'gad7',
                'answers' => $this->answersFor('G', 7, fn () => 1),
                'score' => 7, 'taken_at' => now(),
            ]);
        }


        $res = $this->actingAs($hr)->get(route('perusahaan.report.assessment'));
        $res->assertOk();
        $body = $res->getContent();

        // M1: eskalasi 1-9 disembunyikan (re-identifikasi), n=10 band tetap tampil.
        $this->assertStringContainsString('Eskalasi: &lt;10 (disembunyikan)', $body);
        $this->assertStringNotContainsString('Agg 1', $body);             // tidak ada identitas individu
        $this->assertStringNotContainsString('Agg 3', $body);

        // GAD-7 n=3 → suppressed (AggregatePrivacy::safe gagal → null).
        $gad = AssessmentInstrument::where('code', 'gad7')->firstOrFail();
        $agg = \App\Services\Screening\InstrumentScoring::aggregate($p, $gad);
        $this->assertNull($agg['bands']);
        $this->assertNull($agg['escalatedCount']);
    }

    public function test_hr_aggregate_bands_when_n_meets_threshold()
    {
        $this->seedInstruments();
        [$p, , ] = $this->makeEmployee();
        $hr = $this->makeHr($p);

        for ($i = 1; $i <= 10; $i++) {
            $emp = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'name' => "Gad {$i}",
                'email' => "gad{$i}@w4.local", 'consent_at' => now(),
            ]);
            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'instrument_code' => 'gad7',
                'answers' => $this->answersFor('G', 7, fn () => 2),
                'score' => 14, 'taken_at' => now(),
            ]);
        }

        $gad = AssessmentInstrument::where('code', 'gad7')->firstOrFail();
        $agg = InstrumentScoring::aggregate($p, $gad);
        $this->assertNotNull($agg['bands']);
        $this->assertSame(10, $agg['assessed']);
        $this->assertSame(10, $agg['bands']['Sedang']);
        $this->assertSame(0, $agg['escalatedCount']);
    }
}
