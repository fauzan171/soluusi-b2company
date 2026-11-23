<?php

namespace Tests\Feature;

use App\Models\AssessmentInstrument;
use App\Models\CompanyWellbeingProgram;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv1: library instrumen assessment (DASS-21/PSS-10/Big Five/DISC/Nutrition/
// Ergonomic/MCMI-III stub) + domain gating per program + grant MCMI per employee.
class Wv1InstrumentLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function seedInstruments(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);
    }

    private function makeEmployee(string $tag): array
    {
        $roleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $p = Perusahaan::create([
            'name' => "PT Wv1 {$tag}", 'brand' => "Wv1{$tag}",
            'sessions_per_employee_month' => 2, 'employee_count' => 50, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => $tag,
            'username' => "emp_{$tag}", 'email' => "emp@{$tag}.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => "Emp {$tag}", 'email' => "emp@{$tag}.local", 'consent_at' => now(),
        ]);
        return [$p, $emp, $user];
    }

    private function admin(): User
    {
        $roleId = Role::firstOrCreate(['name' => 'admin'])->id;
        return User::factory()->create(['role_id' => $roleId]);
    }

    /** Program aktif dengan assessment_domains snapshot tertentu. */
    private function program(Perusahaan $p, ?array $domains, array $over = []): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::firstOrCreate(['code' => 'foundation'], [
            'name' => 'Foundation', 'duration_days' => 90, 'is_free' => false,
            'assessment_count' => 2, 'assessment_max_participants' => null,
            'assessment_domains' => ['mh'], 'expert_chat_experts' => 1, 'expert_chat_quota' => 1,
            'deep_support_quota' => 8, 'deep_support_mode' => 'one_on_one',
            'webinar_count' => 0, 'quarterly_review' => false, 'is_active' => true,
        ]);
        return CompanyWellbeingProgram::create(array_merge([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 50,
            'deep_support_mode' => 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addDays(89),
            'expert_chat_quota' => 1,
            'deep_support_quota' => 8,
            'assessment_count' => 2,
            'assessment_max_participants' => null,
            'assessment_domains' => $domains,
            'status' => 'active',
        ], $over));
    }

    private function answers(string $prefix, int $n, callable $fn): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[$prefix.$i] = $fn($i);
        }
        return $out;
    }

    // --- Seeder: 7 instrumen baru + domain + dimensions -----------------------

    public function test_seeder_adds_seven_instruments_with_domains_and_backfill()
    {
        $this->seedInstruments();

        $this->assertSame(11, AssessmentInstrument::count());
        // backfill legacy (migration wv1)
        $this->assertSame('wellbeing', AssessmentInstrument::where('code', 'who5')->value('domain'));
        foreach (['phq9', 'gad7', 'cbi'] as $code) {
            $this->assertSame('mh', AssessmentInstrument::where('code', $code)->value('domain'), $code);
        }
        // 7 baru
        $expect = [
            'dass21' => 'mh', 'pss10' => 'mh', 'bigfive' => null,
            'disc' => null, 'nutrition' => 'nutrition', 'ergonomic' => 'ergonomic',
            'mcmi3' => 'clinical',
        ];
        foreach ($expect as $code => $domain) {
            $row = AssessmentInstrument::where('code', $code)->firstOrFail();
            $this->assertSame($domain, $row->domain, $code);
        }
        // MCMI-III stub: clinical indication-based, TIDAK mass-test
        $this->assertFalse(AssessmentInstrument::where('code', 'mcmi3')->value('is_active'));
        // jumlah item: 40 lama + 21+10+15+12+8+8 baru (MCMI stub tanpa item)
        $this->assertSame(114, \App\Models\ScreeningQuestion::whereNotNull('instrument_code')->count());
        // idempotent
        $this->seedInstruments();
        $this->assertSame(11, AssessmentInstrument::count());
        $this->assertSame(114, \App\Models\ScreeningQuestion::whereNotNull('instrument_code')->count());
    }

    public function test_wv1_schema_columns_and_grants_table()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('assessment_instruments', 'domain'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('assessment_instruments', 'dimensions'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('instrument_grants'));
    }

    // --- Scoring: multi-dimensi ------------------------------------------------

    public function test_dass21_dimension_scores_and_bands()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'dass21')->firstOrFail();

        // semua item = 1 → tiap subscale 7 → ×2 = 14.
        $answers = $this->answers('D', 21, fn () => 1);
        $r = InstrumentScoring::score($instrument, $answers);
        $this->assertSame(21, $r['score']); // total mentah sum
        $this->assertCount(3, $r['dimensions']);
        $dims = collect($r['dimensions'])->keyBy('key');
        $this->assertSame(14, $dims['depression']['score']);
        $this->assertSame(14, $dims['anxiety']['score']);
        $this->assertSame(14, $dims['stress']['score']);
        $this->assertSame('Sedang', $dims['depression']['band']['label']);
        $this->assertSame('Sedang', $dims['anxiety']['band']['label']);
        $this->assertSame('Normal', $dims['stress']['band']['label']);

        // depresi semua 3 → 42 sangat berat.
        $answers = $this->answers('D', 21, fn () => 3);
        $r = InstrumentScoring::score($instrument, $answers);
        $this->assertSame(42, collect($r['dimensions'])->keyBy('key')['depression']['score']);
        $this->assertSame('Sangat Berat', collect($r['dimensions'])->keyBy('key')['depression']['band']['label']);
    }

    public function test_pss10_reverse_items_golden()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'pss10')->firstOrFail();

        // Item 4,5,7,8 positif → reverse. Semua 2 → reverse kontribusi 4-2=2.
        $r = InstrumentScoring::score($instrument, $this->answers('PS', 10, fn () => 2));
        $this->assertSame(20, $r['score']);
        $this->assertSame('Sedang', $r['band']['label']);

        // Semua 0 → 4 reverse item × 4 = 16.
        $r = InstrumentScoring::score($instrument, $this->answers('PS', 10, fn () => 0));
        $this->assertSame(16, $r['score']);
    }

    public function test_bigfive_per_dimension_scores_with_reverse()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'bigfive')->firstOrFail();

        // Keterbukaan: BF1=4, BF2 (reverse)=0 → 4, BF3=4 → 12; lainnya 0.
        $answers = $this->answers('BF', 15, fn ($i) => in_array($i, [1, 3]) ? 4 : 0);
        $r = InstrumentScoring::score($instrument, $answers);
        $this->assertCount(5, $r['dimensions']);
        $dims = collect($r['dimensions'])->keyBy('key');
        $this->assertSame(12, $dims['openness']['score']);
        $this->assertSame(4, $dims['extraversion']['score']); // BF8 reverse 0 → 4
        $this->assertSame(4, $dims['neuroticism']['score']); // BF15 reverse 0 → 4
    }

    public function test_disc_dimension_scores()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'disc')->firstOrFail();

        $r = InstrumentScoring::score($instrument, $this->answers('DI', 12, fn () => 4));
        $dims = collect($r['dimensions'])->keyBy('key');
        $this->assertCount(4, $r['dimensions']);
        $this->assertSame(12, $dims['dominance']['score']);
        $this->assertSame(12, $dims['influence']['score']);
    }

    public function test_nutrition_scoring_and_band()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'nutrition')->firstOrFail();

        $r = InstrumentScoring::score($instrument, $this->answers('N', 8, fn () => 2));
        $this->assertSame(16, $r['score']);
        $this->assertSame('Perlu Perhatian', $r['band']['label']);
    }

    public function test_ergonomic_scoring_and_band()
    {
        $this->seedInstruments();
        $instrument = AssessmentInstrument::where('code', 'ergonomic')->firstOrFail();

        $r = InstrumentScoring::score($instrument, $this->answers('ER', 8, fn () => 3));
        $this->assertSame(24, $r['score']);
        $this->assertSame('Sedang', $r['band']['label']);
    }

    // --- Domain gate per program aktif ----------------------------------------

    public function test_domain_gate_blocks_nutrition_under_foundation_mh_program()
    {
        $this->seedInstruments();
        [$p, , $user] = $this->makeEmployee('GateA');
        $this->program($p, ['mh']);

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'nutrition'))->assertNotFound();
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'nutrition'), ['answers' => []])->assertNotFound();

        // domain mh tetap boleh
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'phq9'))->assertOk();
    }

    public function test_domain_gate_allows_null_domain_instrument_under_program()
    {
        $this->seedInstruments();
        [$p, , $user] = $this->makeEmployee('GateB');
        $this->program($p, ['mh']);

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'bigfive'))->assertOk();
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'disc'))->assertOk();
    }

    public function test_discovery_program_with_wellbeing_domain_allows_who5()
    {
        $this->seedInstruments();
        [$p, , $user] = $this->makeEmployee('GateC');
        $this->program($p, ['mh', 'wellbeing']);

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'who5'))->assertOk();
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'nutrition'))->assertNotFound();
    }

    public function test_program_with_null_domains_imposes_no_gate()
    {
        $this->seedInstruments();
        [$p, , $user] = $this->makeEmployee('GateD');
        $this->program($p, null);

        // domains null = kontrak lama tanpa snapshot domain → bebas (parity cap test).
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'nutrition'))->assertOk();
    }

    public function test_without_program_all_active_instruments_open()
    {
        $this->seedInstruments();
        [, , $user] = $this->makeEmployee('GateE');

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'dass21'))->assertOk();
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'nutrition'))->assertOk();
    }

    // --- MCMI-III: inactive + grant override ----------------------------------

    public function test_mcmi3_is_404_for_everyone_without_grant()
    {
        $this->seedInstruments();
        [, , $user] = $this->makeEmployee('McmiA');

        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'mcmi3'))->assertNotFound();
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'mcmi3'), ['answers' => []])->assertNotFound();
    }

    public function test_mcmi3_grant_gives_access_then_revoke_blocks()
    {
        $this->seedInstruments();
        [$p, $emp, $user] = $this->makeEmployee('McmiB');
        $admin = $this->admin();

        // grant oleh superadmin
        $this->actingAs($admin)->post(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id,
            'instrument_code' => 'mcmi3',
        ])->assertRedirect(route('admin.instruments.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('instrument_grants', [
            'employee_id' => $emp->id, 'instrument_code' => 'mcmi3', 'granted_by' => $admin->id,
        ]);

        // karyawan ber-grant boleh walau instrumen inactive + domain clinical.
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'mcmi3'))->assertOk();
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'mcmi3'), ['answers' => []])
            ->assertRedirect();

        // revoke → 404 lagi
        $grant = InstrumentGrant::where('employee_id', $emp->id)->where('instrument_code', 'mcmi3')->first();
        $this->actingAs($admin)->delete(route('admin.instruments.grants.destroy', $grant))
            ->assertRedirect(route('admin.instruments.index'));
        $this->actingAs($user)->get(route('perusahaan.assessment.instrument.form', 'mcmi3'))->assertNotFound();
    }

    public function test_grant_validation_rejects_unknown_employee_and_duplicate()
    {
        $this->seedInstruments();
        [$p, $emp] = $this->makeEmployee('McmiC');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.instruments.grants.store'), [
            'employee_id' => 999999, 'instrument_code' => 'mcmi3',
        ])->assertSessionHasErrors('employee_id');

        $this->actingAs($admin)->post(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id, 'instrument_code' => 'mcmi3',
        ])->assertSessionHas('success');

        // duplikat → error (unique employee+instrument)
        $this->actingAs($admin)->post(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id, 'instrument_code' => 'mcmi3',
        ])->assertSessionHasErrors('employee_id');
    }

    // --- Admin instruments page ----------------------------------------------

    public function test_admin_instrument_list_toggle_and_role_guard()
    {
        $this->seedInstruments();
        $admin = $this->admin();
        [, , $empUser] = $this->makeEmployee('AdmA');

        $res = $this->actingAs($admin)->get(route('admin.instruments.index'));
        $res->assertOk()
            ->assertSee('DASS-21')
            ->assertSee('MCMI-III')
            ->assertSee('mcmi3')
            ->assertSee('21'); // DASS-21 item count

        // toggle aktif/nonaktif — instrumen non-klinis bebas toggle
        $dass = AssessmentInstrument::where('code', 'dass21')->first();
        $this->actingAs($admin)->post(route('admin.instruments.toggle', $dass))->assertRedirect();
        $this->assertFalse((bool) $dass->fresh()->is_active);
        $this->actingAs($admin)->post(route('admin.instruments.toggle', $dass))->assertRedirect();
        $this->assertTrue((bool) $dass->fresh()->is_active);

        // SEC MED-1: MCMI (clinical) tidak bisa diaktifkan massal — 403.
        $mcmi = AssessmentInstrument::where('code', 'mcmi3')->first();
        $this->assertFalse((bool) $mcmi->fresh()->is_active);
        $this->actingAs($admin)->post(route('admin.instruments.toggle', $mcmi))->assertForbidden();
        $this->assertFalse((bool) $mcmi->fresh()->is_active);

        // employee tidak boleh masuk admin area
        $this->actingAs($empUser)->get(route('admin.instruments.index'))->assertForbidden();
    }

    // --- Aggregate privacy threshold 10 (R10) --------------------------------

    public function test_hr_aggregate_threshold_is_now_10()
    {
        $this->seedInstruments();
        [$p, , ] = $this->makeEmployee('AggA');
        $gad = AssessmentInstrument::where('code', 'gad7')->firstOrFail();

        $mk = function (int $i) use ($p) {
            $emp = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'name' => "Agg {$i}",
                'email' => "agg{$i}@wv1.local", 'consent_at' => now(),
            ]);
            PerusahaanAssessment::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'instrument_code' => 'gad7',
                'answers' => $this->answers('G', 7, fn () => 1),
                'score' => 7, 'taken_at' => now(),
            ]);
        };
        for ($i = 1; $i <= 9; $i++) { $mk($i); }
        $agg = InstrumentScoring::aggregate($p, $gad);
        $this->assertNull($agg['bands']); // n=9 < 10 → suppressed

        $mk(10);
        $agg = InstrumentScoring::aggregate($p, $gad);
        $this->assertNotNull($agg['bands']);
        $this->assertSame(10, $agg['assessed']);
    }

    // --- Employee UX: index filter + result multi-dimensi ---------------------

    public function test_index_hides_domain_blocked_instruments_and_shows_granted()
    {
        $this->seedInstruments();
        [$p, $emp, $user] = $this->makeEmployee('IdxA');
        $this->program($p, ['mh']);
        InstrumentGrant::create([
            'employee_id' => $emp->id, 'instrument_code' => 'mcmi3', 'granted_by' => $this->admin()->id,
        ]);

        $res = $this->actingAs($user)->get(route('perusahaan.assessment.instruments'));
        $res->assertOk()
            ->assertSee('PHQ-9')
            ->assertDontSee('Gizi & Pola Makan')
            ->assertDontSee('Ergonomi')
            ->assertSee('MCMI-III'); // granted tampil walau inactive
    }

    public function test_result_view_shows_dimension_scores_and_recommendations()
    {
        $this->seedInstruments();
        [, , $user] = $this->makeEmployee('ResA');

        $answers = array_merge(
            $this->answers('D', 21, fn ($i) => $i <= 7 ? 2 : 1),
        );
        $this->actingAs($user)->post(route('perusahaan.assessment.instrument.store', 'dass21'), ['answers' => $answers])
            ->assertRedirect();

        $row = PerusahaanAssessment::where('instrument_code', 'dass21')->first();
        $res = $this->actingAs($user)->get(route('perusahaan.assessment.result', $row));
        $res->assertOk()
            ->assertSee('Depresi')
            ->assertSee('Kecemasan')
            ->assertSee('Stres')
            ->assertSee('Rekomendasi')
            ->assertSee('14') // subscale depresi ×2
            ->assertSee('konsultasi'); // teks rekomendasi domain
    }
}
