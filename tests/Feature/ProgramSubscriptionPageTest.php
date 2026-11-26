<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingQuotaLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wb-T5: HR "Program & Subscription" — snapshot read-only + AggregatePrivacy min 10.
// D1: saldo kuota kontrak-level (tidak reset bulan). Aggregate-only by design.
class ProgramSubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeHrCompany(): array
    {
        $hrRoleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Sub', 'brand' => 'SubCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 100, 'is_active' => true,
        ]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Sub',
            'username' => 'hr_sub', 'email' => 'hr_sub@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$p, $hr];
    }

    private function makeGrowthProgram(Perusahaan $p, ?\DateTimeInterface $endsAt = null): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::create([
            'code' => 'growth-t5', 'name' => 'Growth', 'duration_days' => 180,
            'assessment_count' => 2, 'assessment_domains' => ['mh', 'nutrition'],
            'expert_chat_experts' => 2, 'webinar_count' => 3, 'quarterly_review' => false,
        ]);
        $s = 2 * $pkg->id; // unique per test snapshot
        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 200,
            'deep_support_mode' => 'mhcu',
            'starts_at' => today()->toDateString(),
            'ends_at' => ($endsAt ?? today()->addDays(179))->toDateString(),
            'expert_chat_quota' => 30,
            'deep_support_quota' => 15,
            'webinar_quota' => 3,
            'assessment_count' => 2,
            'monthly_price' => 20499999,
            'status' => 'active',
        ]);
    }

    public function test_hr_admin_sees_active_program_quota_cards(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $program = $this->makeGrowthProgram($p);

        // 12 chat used + 8 deep_support + 1 webinar (ledger tanpa sesi — pola schema test)
        foreach (range(1, 12) as $i) {
            WellbeingQuotaLedger::create([
                'program_id' => $program->id, 'bucket' => 'expert_chat', 'event' => 'used',
            ]);
        }
        foreach (range(1, 8) as $i) {
            WellbeingQuotaLedger::create([
                'program_id' => $program->id, 'bucket' => 'deep_support', 'event' => 'used',
            ]);
        }
        WellbeingQuotaLedger::create([
            'program_id' => $program->id, 'bucket' => 'webinar', 'event' => 'used',
        ]);

        $res = $this->actingAs($hr)->get(route('perusahaan.program-subscription'));

        $res->assertOk()
            ->assertSee('Paket aktif: Growth')
            ->assertSee('12 / 30 sesi')
            ->assertSee('8 / 15 sesi')
            ->assertSee('1 / 3')
            ->assertSee('Sisa kuota program')
            ->assertSee('exclude pajak');

        // Webinar quota use: no explicit count on the wireframe, but card label must render
        $res->assertSee('Webinar');
    }

    public function test_employee_role_gets_403(): void
    {
        [$p,] = $this->makeHrCompany();
        $this->makeGrowthProgram($p);

        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $emp = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'T5',
            'username' => 'emp_t5', 'email' => 'emp_t5@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);

        $this->actingAs($emp)->get(route('perusahaan.program-subscription'))->assertStatus(403);
    }

    public function test_empty_state_when_no_program(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('Program belum aktif')
            ->assertSee('Hubungi tim Soluusi');
    }

    public function test_near_expiry_alert_shows_within_30_days(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $this->makeGrowthProgram($p, today()->addDays(26));

        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('26 hari')
            ->assertSee('tidak dipindahtuangkan');
    }

    public function test_no_near_expiry_alert_when_far_from_expiry(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $this->makeGrowthProgram($p, today()->addDays(179));

        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertDontSee('tidak dipindahtuangkan');
    }

    public function test_quota_balance_does_not_reset_next_month(): void
    {
        $this->travelTo(now()->startOfMonth());
        [$p, $hr] = $this->makeHrCompany();
        $program = $this->makeGrowthProgram($p, today()->addMonths(5));

        foreach (range(1, 5) as $i) {
            WellbeingQuotaLedger::create([
                'program_id' => $program->id, 'bucket' => 'expert_chat', 'event' => 'used',
            ]);
        }

        $first = $this->actingAs($hr)->get(route('perusahaan.program-subscription'))->getContent();

        $this->travelTo(now()->addMonth()->startOfMonth());
        $second = $this->actingAs($hr)->get(route('perusahaan.program-subscription'))->getContent();

        $this->assertStringContainsString('5 / 30 sesi', $first);
        $this->assertStringContainsString('5 / 30 sesi', $second);
    }

    // wb-T5 R10: threshold parameterized — default 5 legacy, wellbeing pass 10.
    public function test_aggregate_privacy_min_threshold_parameterized(): void
    {
        $seven = [7];

        // legacy default = 5 → safe
        $this->assertTrue(\App\Support\AggregatePrivacy::safe($seven));
        // wellbeing min 10 → suppressed
        $this->assertFalse(\App\Support\AggregatePrivacy::safe($seven, 10));
        // message-level check: suppression berarti caller harus menyembunyikan grup ("data belum cukup")
        // grup 11 → keduanya aman
        $this->assertTrue(\App\Support\AggregatePrivacy::safe([11]));
        $this->assertTrue(\App\Support\AggregatePrivacy::safe([11], 10));
        // 0 tetap lolos di kedua threshold (grup kosong tidak bocor)
        $this->assertTrue(\App\Support\AggregatePrivacy::safe([0], 10));
    }
}
