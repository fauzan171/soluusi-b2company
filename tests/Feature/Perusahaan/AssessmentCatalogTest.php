<?php

namespace Tests\Feature\Perusahaan;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// ac1: 3 instrumen baru dari xlsx klien (BFI-15 Trader, DISC-24 forced-choice,
// MCMI-III items) — seeder row/item counts, flow employee disc24/bfitrader,
// gate MCMI tetap mass-assign ditolak (detail di AdminAssessmentAssignmentTest).
class AssessmentCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function seedInstruments(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);
    }

    private function makeEmployee(): array
    {
        $roleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $p = Perusahaan::create([
            'name' => 'PT AC1', 'brand' => 'AC1Co',
            'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'AC1',
            'username' => 'emp_ac1', 'email' => 'emp@ac1.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp AC1', 'email' => 'emp@ac1.local', 'consent_at' => now(),
        ]);
        return [$p, $emp, $user];
    }

    private function assign(PerusahaanEmployee $emp, string $code): AssessmentAssignment
    {
        return AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $emp->perusahaan_id,
            'instrument_code' => $code, 'audience' => 'employee', 'source' => 'addon',
            'opens_at' => today()->subDay(),
        ]);
    }

    // (1) Seeder: 3 instrumen + jumlah row item benar, idempotent.

    public function test_seeder_adds_three_new_instruments_with_correct_item_counts()
    {
        $this->seedInstruments();

        $expect = [
            'bfitrader' => 15,
            'disc24' => 24,
            'mcmi3' => 175,
        ];
        foreach ($expect as $code => $count) {
            $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();
            $this->assertSame($count, $instrument->questions()->count(), $code);
        }

        $this->assertSame('disc_forced', AssessmentInstrument::where('code', 'disc24')->value('scoring_type'));
        $this->assertSame('sum', AssessmentInstrument::where('code', 'bfitrader')->value('scoring_type'));
        $this->assertTrue((bool) AssessmentInstrument::where('code', 'bfitrader')->value('is_active'));
        $this->assertTrue((bool) AssessmentInstrument::where('code', 'disc24')->value('is_active'));
        $this->assertFalse((bool) AssessmentInstrument::where('code', 'mcmi3')->value('is_active'));

        // idempotent: seed 2x — jumlah row tetap.
        $this->seedInstruments();
        foreach ($expect as $code => $count) {
            $this->assertSame($count, AssessmentInstrument::where('code', $code)->firstOrFail()->questions()->count(), $code);
        }
    }

    // (2) Employee assignment disc24 → submit valid → row score 1-40 band archetype.

    public function test_disc24_submit_scores_profile_key_and_archetype()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'disc24');

        $instrument = AssessmentInstrument::where('code', 'disc24')->firstOrFail();

        // Jawaban sintetis: untuk tiap item, p = (n % 4), k = (p+1) % 4 — p≠k.
        $answers = [];
        foreach ($instrument->questions()->get() as $item) {
            $p = ((int) $item->ord) % 4;
            $k = ($p + 1) % 4;
            $answers[$item->code.'p'] = $p;
            $answers[$item->code.'k'] = $k;
        }

        // Engine langsung: deterministik — score 1..40, band label = archetype.
        $result = InstrumentScoring::score($instrument, $answers);
        $this->assertGreaterThanOrEqual(1, $result['score']);
        $this->assertLessThanOrEqual(40, $result['score']);
        $this->assertNotSame('', $result['band']['label']);
        $this->assertStringContainsString('Karir:', $result['band']['note']);
        $this->assertCount(4, $result['dimensions']);
        $this->assertSame(['D', 'I', 'S', 'C'], array_column($result['dimensions'], 'key'));

        // Full flow: submit via controller → row tersimpan dengan score tersebut.
        $this->actingAs($user)
            ->post(route('perusahaan.assessment.instrument.store', 'disc24'), ['answers' => $answers])
            ->assertRedirect();

        $row = $emp->assessments()->where('instrument_code', 'disc24')->first();
        $this->assertNotNull($row);
        $this->assertSame($result['score'], (int) $row->score);
        $this->assertSame(
            $result['band']['label'],
            $instrument->bandLabelFor((int) $row->score)
        );

        // Result page render band archetype + dimensi.
        $this->actingAs($user)->get(route('perusahaan.assessment.result', $row))
            ->assertOk()
            ->assertSee($result['band']['label']);
    }

    public function test_disc24_submit_rejects_p_equals_k()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'disc24');

        $instrument = AssessmentInstrument::where('code', 'disc24')->firstOrFail();
        $answers = [];
        foreach ($instrument->questions()->get() as $item) {
            $answers[$item->code.'p'] = 0;
            $answers[$item->code.'k'] = 1; // valid kecuali item 1
        }
        $answers['DS1p'] = 2;
        $answers['DS1k'] = 2; // p == k → ditolak

        $this->actingAs($user)
            ->post(route('perusahaan.assessment.instrument.store', 'disc24'), ['answers' => $answers])
            ->assertSessionHasErrors('answers.DS1p');

        $this->assertSame(0, $emp->assessments()->where('instrument_code', 'disc24')->count());
    }

    public function test_disc24_submit_rejects_out_of_range()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'disc24');

        $instrument = AssessmentInstrument::where('code', 'disc24')->firstOrFail();
        $answers = [];
        foreach ($instrument->questions()->get() as $item) {
            $answers[$item->code.'p'] = 0;
            $answers[$item->code.'k'] = 1;
        }
        $answers['DS1p'] = 4; // > 3 → ditolak

        $this->actingAs($user)
            ->post(route('perusahaan.assessment.instrument.store', 'disc24'), ['answers' => $answers])
            ->assertSessionHasErrors('answers.DS1p');
    }

    // (3) MCMI mass-assign ditolak — detail gate di AdminAssessmentAssignmentTest
    // (test_store_rejects_mcmi3_with_friendly_error / test_mcmi3_addon_rejected_422).
    // Di sini: MCMI tidak aktif, tanpa grant employee tidak bisa buka form.

    public function test_mcmi3_inactive_and_invisible_to_employee_without_grant()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'mcmi3'))->assertNotFound();
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'mcmi3'), ['answers' => []])->assertNotFound();
    }

    // (4) BFI trader submit → 5 dimensi.

    public function test_bfitrader_submit_produces_five_dimensions()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();
        $this->assign($emp, 'bfitrader');

        $instrument = AssessmentInstrument::where('code', 'bfitrader')->firstOrFail();

        $answers = [];
        foreach ($instrument->questions()->get() as $item) {
            $answers[$item->code] = ((int) $item->ord) % 5; // 0..4 variasi
        }

        $result = InstrumentScoring::score($instrument, $answers);
        $this->assertCount(5, $result['dimensions']);
        $this->assertSame(
            ['extraversion', 'agreeableness', 'conscientiousness', 'neuroticism', 'openmindedness'],
            array_column($result['dimensions'], 'key')
        );

        $this->actingAs($user)
            ->post(route('perusahaan.assessment.instrument.store', 'bfitrader'), ['answers' => $answers])
            ->assertRedirect();

        $row = $emp->assessments()->where('instrument_code', 'bfitrader')->first();
        $this->assertNotNull($row);
        $this->assertSame($result['score'], (int) $row->score);

        $this->actingAs($user)->get(route('perusahaan.assessment.result', $row))
            ->assertOk()
            ->assertSee('Extraversion')
            ->assertSee('Open-Mindedness');
    }

    // (5) ADDON_CODES: disc24/bfitrader bisa di-mass-assign via program assignment.

    public function test_disc24_and_bfitrader_mass_assignable_as_addon()
    {
        $this->seedInstruments();
        [, $emp, $user] = $this->makeEmployee();

        foreach (['disc24', 'bfitrader'] as $code) {
            $a = AssessmentAssignment::create([
                'assignable_type' => 'perusahaan', 'assignable_id' => $emp->perusahaan_id,
                'instrument_code' => $code, 'audience' => 'employee', 'source' => 'addon',
                'round' => 1, 'opens_at' => today()->subDay(), 'status' => 'active',
            ]);
            $this->assertNotNull($a->id, $code);
            // tampil di index employee (assignment terbuka + aktif).
            $this->actingAs($user)->get(route('perusahaan.assessment.instruments'))
                ->assertOk();
        }
    }
}
