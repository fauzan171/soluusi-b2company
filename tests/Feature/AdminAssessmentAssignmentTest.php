<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// B2: wizard membuat assessment assignments (D3 core/extras, D4 add-on, D6
// rounds+window) dalam transaksi yang sama + halaman manajemen assignment per
// program (admin & operations saja) + grant MCMI-III wajib reason (D5).
class AdminAssessmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $r = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $r->id]);
    }

    private function perusahaan(int $employeeCount = 500, int $n = 1): Perusahaan
    {
        $p = Perusahaan::create([
            'name' => "PT B2 {$n}", 'brand' => "B2Corp{$n}",
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount, 'is_active' => true,
        ]);

        // growth/impact butuh 2 expert di pool; foundation 1.
        foreach (range(1, 2) as $i) {
            $role = Role::firstOrCreate(['name' => 'consultant']);
            $u = User::factory()->create(['role_id' => $role->id]);
            $consultant = \App\Models\Consultant::create([
                'user_id' => $u->id, 'uuid' => (string) Str::uuid(),
                'about' => 'Test', 'profit_sharing' => 0,
                'status' => \App\Models\Consultant::ACCEPT, 'is_active' => true,
            ]);
            \App\Models\ConsultantPool::create([
                'perusahaan_id' => $p->id, 'consultant_id' => $consultant->id, 'is_active' => true,
            ]);
        }

        return $p;
    }

    /** POST wizard lengkap (default growth band 401-600). */
    private function assign(Perusahaan $p, array $over = []): \Illuminate\Testing\TestResponse
    {
        $this->seed(WellbeingPackageSeeder::class);
        $this->seed(AssessmentInstrumentSeeder::class);
        $admin = $this->userWithRole('admin');

        return $this->actingAs($admin)->post(route('admin.wellbeing-programs.store'), array_merge([
            'perusahaan_id' => $p->id,
            'package_code' => 'growth',
            'band_min' => 401,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'confirm' => '1',
        ], $over));
    }

    /** JSON variant (validation errors as 422). */
    private function assignJson(Perusahaan $p, array $over = []): \Illuminate\Testing\TestResponse
    {
        $this->seed(WellbeingPackageSeeder::class);
        $this->seed(AssessmentInstrumentSeeder::class);
        $admin = $this->userWithRole('admin');

        return $this->actingAs($admin)->postJson(route('admin.wellbeing-programs.store'), array_merge([
            'perusahaan_id' => $p->id,
            'package_code' => 'growth',
            'band_min' => 401,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'confirm' => '1',
        ], $over));
    }

    private function assignments(CompanyWellbeingProgram $program)
    {
        return AssessmentAssignment::where('assignable_type', $program->getMorphClass())
            ->where('assignable_id', $program->id)->get();
    }

    // ------------------------------------------------------------------
    // D3 — core per package
    // ------------------------------------------------------------------

    public function test_foundation_nutrition_creates_exactly_one_core_assignment()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, [
            'package_code' => 'foundation',
            'band_min' => 401,
            'deep_support_mode' => 'one_on_one',
            'foundation_domain' => 'nutrition',
        ])->assertRedirect()->assertSessionHas('success');

        $program = $p->programs()->first();

        // snapshot domain = domain terpilih (fixes hardcoded ['mh'])
        $this->assertSame(['nutrition'], $program->assessment_domains);

        $rows = $this->assignments($program);
        $this->assertCount(1, $rows);

        $a = $rows->first();
        $this->assertSame('nutrition', $a->instrument_code);
        $this->assertSame(AssessmentAssignment::SOURCE_CORE, $a->source);
        $this->assertSame(1, $a->round);
        $this->assertSame('employee', $a->audience);
        $this->assertSame($program->starts_at->toDateString(), $a->opens_at->toDateString());
        $this->assertSame($program->ends_at->toDateString(), $a->closes_at->toDateString());
    }

    public function test_foundation_mh_extras_ignored_when_domain_nutrition()
    {
        $p = $this->perusahaan(500);

        // extras who5/pss10 hanya berlaku saat program punya domain mh (D3)
        $this->assign($p, [
            'package_code' => 'foundation',
            'band_min' => 401,
            'deep_support_mode' => 'one_on_one',
            'foundation_domain' => 'nutrition',
            'mh_extras' => ['who5', 'pss10'],
        ])->assertRedirect();

        $this->assertCount(1, $this->assignments($p->programs()->first()));
    }

    public function test_discovery_defaults_to_who5()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, [
            'package_code' => 'discovery',
            'band_min' => null,
            'deep_support_mode' => 'one_on_one',
        ])->assertRedirect();

        $program = $p->programs()->first();
        $this->assertSame(['mh'], $program->assessment_domains);

        $rows = $this->assignments($program);
        $this->assertCount(1, $rows);
        $this->assertSame('who5', $rows->first()->instrument_code);
        $this->assertSame($program->starts_at->toDateString(), $rows->first()->opens_at->toDateString());
        $this->assertSame($program->ends_at->toDateString(), $rows->first()->closes_at->toDateString());
    }

    public function test_discovery_dass21_when_chosen()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, [
            'package_code' => 'discovery',
            'band_min' => null,
            'deep_support_mode' => 'one_on_one',
            'discovery_instrument' => 'dass21',
        ])->assertRedirect();

        $this->assertSame('dass21', $this->assignments($p->programs()->first())->first()->instrument_code);
    }

    // ------------------------------------------------------------------
    // D6 — rounds & windows
    // ------------------------------------------------------------------

    public function test_growth_creates_dass21_nutrition_2_rounds()
    {
        $p = $this->perusahaan(500);
        $starts = now()->addDay()->toDateString();

        $this->assign($p, ['starts_at' => $starts])->assertRedirect();

        $program = $p->programs()->first();
        $rows = $this->assignments($program);
        // 2 core codes × 2 rounds
        $this->assertCount(4, $rows);

        foreach (['dass21', 'nutrition'] as $code) {
            $codeRows = $rows->where('instrument_code', $code)->sortBy('round')->values();
            $this->assertCount(2, $codeRows);

            // R1 baseline: starts .. starts+29d
            $this->assertSame($starts, $codeRows[0]->opens_at->toDateString());
            $this->assertSame(
                \Illuminate\Support\Carbon::parse($starts)->addDays(29)->toDateString(),
                $codeRows[0]->closes_at->toDateString()
            );
            // R2: ends-29d .. ends
            $this->assertSame(
                $program->ends_at->copy()->subDays(29)->toDateString(),
                $codeRows[1]->opens_at->toDateString()
            );
            $this->assertSame($program->ends_at->toDateString(), $codeRows[1]->closes_at->toDateString());
        }
    }

    public function test_impact_creates_dass21_nutrition_4_rounds_quarterly_windows()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, [
            'package_code' => 'impact',
            'band_min' => 401,
            'starts_at' => '2027-01-15',
        ])->assertRedirect();

        $program = $p->programs()->first();
        $rows = $this->assignments($program);
        $this->assertCount(8, $rows); // 2 codes × 4 rounds

        foreach (['dass21', 'nutrition'] as $code) {
            $codeRows = $rows->where('instrument_code', $code)->sortBy('round')->values();
            $this->assertCount(4, $codeRows);

            // D6: Rn opens starts + 3*(n-1) bulan, closes +29 hari
            foreach (range(1, 4) as $n) {
                $opens = \Illuminate\Support\Carbon::parse('2027-01-15')->addMonths(3 * ($n - 1));
                $this->assertSame($opens->toDateString(), $codeRows[$n - 1]->opens_at->toDateString());
                $this->assertSame(
                    $opens->copy()->addDays(29)->toDateString(),
                    $codeRows[$n - 1]->closes_at->toDateString()
                );
                $this->assertSame($n, $codeRows[$n - 1]->round);
            }
        }
    }

    // ------------------------------------------------------------------
    // D3 extras + D4 add-ons
    // ------------------------------------------------------------------

    public function test_mh_extras_created_as_core_no_charge()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, ['mh_extras' => ['who5', 'pss10']])->assertRedirect();

        $program = $p->programs()->first();
        // 2 core + 2 extras, semuanya × 2 rounds = 8
        $rows = $this->assignments($program);
        $this->assertCount(8, $rows);

        foreach (['who5', 'pss10'] as $code) {
            $extra = $rows->where('instrument_code', $code);
            $this->assertCount(2, $extra);
            $this->assertSame(AssessmentAssignment::SOURCE_CORE, $extra->first()->source);
            $this->assertNull($extra->first()->addon_price);
        }
    }

    public function test_addons_created_with_price_one_round_whole_period()
    {
        $p = $this->perusahaan(500);
        $starts = now()->addDay()->toDateString();
        $admin = AdminAction::orderByDesc('id')->limit(1)->get(); // warm nothing, hanya utk import

        $this->assign($p, [
            'addons' => [
                ['code' => 'bigfive', 'price' => 500000],
                ['code' => 'ergonomic', 'price' => 250000],
            ],
            'starts_at' => $starts,
        ])->assertRedirect();

        $program = $p->programs()->first();
        $addons = $this->assignments($program)->where('source', AssessmentAssignment::SOURCE_ADDON);
        $this->assertCount(2, $addons);

        $bigfive = $addons->firstWhere('instrument_code', 'bigfive');
        $this->assertEquals(500000, (float) $bigfive->addon_price);
        $this->assertSame(1, $bigfive->round);
        $this->assertSame($starts, $bigfive->opens_at->toDateString());
        $this->assertSame($program->ends_at->toDateString(), $bigfive->closes_at->toDateString());
    }

    public function test_mcmi3_addon_rejected_422()
    {
        $p = $this->perusahaan(500);

        $this->assignJson($p, [
            'addons' => [['code' => 'mcmi3', 'price' => 1000000]],
        ])->assertStatus(422)->assertJsonValidationErrors('addons.0.code');

        $this->assertSame(0, CompanyWellbeingProgram::count());
        $this->assertSame(0, AssessmentAssignment::count());
    }

    public function test_assignment_creation_audited_per_row()
    {
        $p = $this->perusahaan(500);

        $this->assign($p, ['starts_at' => now()->addDay()->toDateString()])->assertRedirect();

        $program = $p->programs()->first();
        $rows = $this->assignments($program);

        $this->assertSame(
            $rows->count(),
            AdminAction::where('action', 'assessment_assignment.created')
                ->where('target_type', $rows->first()->getMorphClass())
                ->whereIn('target_id', $rows->pluck('id'))
                ->count()
        );

        // payload: code, round, source — tanpa harga / data orang
        $payload = AdminAction::where('action', 'assessment_assignment.created')->first()->payload_json;
        $this->assertEquals(
            ['instrument_code' => 'dass21', 'round' => 1, 'source' => 'core'],
            $payload
        );
    }

    // ------------------------------------------------------------------
    // D5 — grant klinis wajib reason
    // ------------------------------------------------------------------

    private function employeeFixture(string $tag): array
    {
        $this->seed(AssessmentInstrumentSeeder::class);
        $role = Role::firstOrCreate(['name' => 'employee']);
        $p = Perusahaan::create([
            'name' => "PT Grant {$tag}", 'brand' => "Grant{$tag}",
            'sessions_per_employee_month' => 2, 'employee_count' => 10, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => $tag,
            'username' => 'grant_'.$tag, 'email' => "grant_{$tag}@b2.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => "Emp {$tag}", 'email' => "grant_{$tag}@b2.local", 'consent_at' => now(),
        ]);

        return [$p, $emp];
    }

    public function test_grant_requires_reason()
    {
        [, $emp] = $this->employeeFixture('Rsn');
        $admin = $this->userWithRole('admin');

        // tanpa reason → 422
        $this->actingAs($admin)->postJson(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id,
            'instrument_code' => 'mcmi3',
        ])->assertStatus(422)->assertJsonValidationErrors('reason');

        // dengan reason → tersimpan
        $this->actingAs($admin)->post(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id,
            'instrument_code' => 'mcmi3',
            'reason' => 'Indikasi klinis: skrining skala berat berulang, dirujuk psikolog.',
        ])->assertRedirect(route('admin.instruments.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('instrument_grants', [
            'employee_id' => $emp->id,
            'instrument_code' => 'mcmi3',
            'reason' => 'Indikasi klinis: skrining skala berat berulang, dirujuk psikolog.',
        ]);
    }

    public function test_grant_reason_max_500()
    {
        [, $emp] = $this->employeeFixture('Rsn2');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson(route('admin.instruments.grants.store'), [
            'employee_id' => $emp->id,
            'instrument_code' => 'mcmi3',
            'reason' => str_repeat('a', 501),
        ])->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->assertSame(0, InstrumentGrant::count());
    }

    // ------------------------------------------------------------------
    // Manajemen assignment per program — akses & CRUD
    // ------------------------------------------------------------------

    private function programWithAssignments(): CompanyWellbeingProgram
    {
        $p = $this->perusahaan(500);
        $this->assign($p, ['starts_at' => now()->addDay()->toDateString()])->assertRedirect();

        return $p->programs()->first();
    }

    public function test_management_page_admin_and_operations_allowed_others_403()
    {
        $program = $this->programWithAssignments();
        $url = route('admin.programs.assessments', $program);

        $this->actingAs($this->userWithRole('admin'))->get($url)->assertOk();
        $this->actingAs($this->userWithRole('operations'))->get($url)->assertOk();

        $this->actingAs($this->userWithRole('hr_admin'))->get($url)->assertForbidden();
        $this->actingAs($this->userWithRole('employee'))->get($url)->assertForbidden();
    }

    public function test_management_page_lists_rows_and_completers()
    {
        $program = $this->programWithAssignments();

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.programs.assessments', $program))
            ->assertOk()
            ->assertSee('dass21')
            ->assertSee('nutrition')
            ->assertSee('Core');
    }

    public function test_store_addon_assignment()
    {
        $program = $this->programWithAssignments();
        $ops = $this->userWithRole('operations');

        $this->actingAs($ops)->post(route('admin.programs.assessments.store', $program), [
            'instrument_code' => 'bigfive',
            'source' => 'addon',
            'addon_price' => 750000,
            'round' => 1,
            'opens_at' => $program->starts_at->toDateString(),
            'closes_at' => $program->ends_at->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_assignments', [
            'assignable_type' => $program->getMorphClass(),
            'assignable_id' => $program->id,
            'instrument_code' => 'bigfive',
            'source' => 'addon',
            'round' => 1,
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'assessment_assignment.created',
            'admin_id' => $ops->id,
        ]);
    }

    public function test_store_rejects_mcmi3_with_friendly_error()
    {
        $program = $this->programWithAssignments();

        $this->actingAs($this->userWithRole('admin'))
            ->postJson(route('admin.programs.assessments.store', $program), [
                'instrument_code' => 'mcmi3',
                'source' => 'addon',
                'addon_price' => 1000000,
                'round' => 1,
                'opens_at' => $program->starts_at->toDateString(),
                'closes_at' => $program->ends_at->toDateString(),
            ])->assertStatus(422)
            ->assertJsonValidationErrors('instrument_code');

        $this->assertSame(
            0,
            AssessmentAssignment::where('instrument_code', 'mcmi3')->count()
        );
    }

    public function test_store_rejects_duplicate_with_friendly_error()
    {
        $program = $this->programWithAssignments();

        $this->actingAs($this->userWithRole('admin'))
            ->postJson(route('admin.programs.assessments.store', $program), [
                'instrument_code' => 'dass21', // sudah ada round 1 dari wizard
                'source' => 'core',
                'round' => 1,
                'opens_at' => $program->starts_at->toDateString(),
                'closes_at' => $program->ends_at->toDateString(),
            ])->assertStatus(422)
            ->assertJsonValidationErrors('instrument_code');
    }

    public function test_update_window_valid_and_invalid()
    {
        $program = $this->programWithAssignments();
        $assignment = $this->assignments($program)->firstWhere('instrument_code', 'dass21');
        $admin = $this->userWithRole('admin');

        // invalid: opens > closes
        $this->actingAs($admin)->putJson(route('admin.programs.assessments.update', [$program, $assignment]), [
            'opens_at' => '2030-01-01',
            'closes_at' => '2029-01-01',
        ])->assertStatus(422)->assertJsonValidationErrors('closes_at');

        // invalid: di luar periode program
        $this->actingAs($admin)->putJson(route('admin.programs.assessments.update', [$program, $assignment]), [
            'opens_at' => now()->addDay()->toDateString(),
            'closes_at' => now()->addDays(400)->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('closes_at');

        // valid
        $opens = $program->starts_at->toDateString();
        $closes = $program->starts_at->copy()->addDays(10)->toDateString();
        $this->actingAs($admin)->put(route('admin.programs.assessments.update', [$program, $assignment]), [
            'opens_at' => $opens,
            'closes_at' => $closes,
        ])->assertRedirect()->assertSessionHas('success');

        $fresh = $assignment->fresh();
        $this->assertSame($opens, $fresh->opens_at->toDateString());
        $this->assertSame($closes, $fresh->closes_at->toDateString());
    }

    public function test_close_assignment()
    {
        $program = $this->programWithAssignments();
        $assignment = $this->assignments($program)->first();

        $this->actingAs($this->userWithRole('admin'))
            ->post(route('admin.programs.assessments.close', [$program, $assignment]))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('closed', $assignment->fresh()->status);
    }
}
