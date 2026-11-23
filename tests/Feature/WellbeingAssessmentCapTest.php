<?php

namespace Tests\Feature;

use App\Models\AssessmentInstrument;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Services\Screening\InstrumentScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * wb-T2 — R3 cap assessment Discovery: max 40 peserta distinct employee di
 * program aktif; retake employee sama lolos; max=null / tanpa program → bebas.
 */
class WellbeingAssessmentCapTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        $roleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => 'Cap',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function seedInstruments(): AssessmentInstrument
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);

        return AssessmentInstrument::where('code', 'who5')->firstOrFail();
    }

    private function company(?array $programOverrides = []): array
    {
        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => 'PT Cap'.$n, 'brand' => 'CapCo'.$n,
            'sessions_per_employee_month' => 2, 'employee_count' => 500, 'is_active' => true,
        ]);

        if ($programOverrides !== null) {
            $pkg = WellbeingPackage::firstOrCreate(['code' => 'discovery'], [
                'name' => 'Discovery', 'duration_days' => 14, 'is_free' => true,
                'assessment_count' => 1, 'assessment_max_participants' => 40,
                'assessment_domains' => ['mh', 'wellbeing'],
                'expert_chat_experts' => 0, 'expert_chat_quota' => 0,
                'deep_support_quota' => 8, 'deep_support_mode' => 'one_on_one',
                'webinar_count' => 0, 'quarterly_review' => false, 'is_active' => true,
            ]);
            CompanyWellbeingProgram::create(array_merge([
                'perusahaan_id' => $p->id,
                'wellbeing_package_id' => $pkg->id,
                'employee_band_size' => 500,
                'deep_support_mode' => 'one_on_one',
                'starts_at' => today()->subDay(),
                'ends_at' => today()->addDays(13),
                'expert_chat_quota' => 0,
                'deep_support_quota' => 8,
                'assessment_count' => 1,
                'assessment_max_participants' => 40,
                'status' => 'active',
            ], $programOverrides));
        }

        return [$p];
    }

    private function employee(Perusahaan $p, int $i): array
    {
        $user = $this->user("emp{$i}@cap{$p->id}.local");
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp '.$i, 'email' => "emp{$i}@cap{$p->id}.local", 'consent_at' => now(),
        ]);

        return [$user, $emp];
    }

    private function submit($user): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->postJson(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => ['W1' => 3, 'W2' => 3, 'W3' => 3, 'W4' => 3, 'W5' => 3],
        ]);
    }

    public function test_41st_distinct_participant_aborts_but_retake_passes()
    {
        $instrument = $this->seedInstruments();
        [$p] = $this->company();
        $this->assertCount(5, InstrumentScoring::items($instrument));

        // 40 employee beda → semua lolos
        for ($i = 1; $i <= 40; $i++) {
            [$user] = $this->employee($p, $i);
            $this->submit($user)->assertRedirect();
        }
        $this->assertSame(40, PerusahaanAssessment::where('perusahaan_id', $p->id)
            ->distinct('employee_id')->count('employee_id'));

        // ke-41 (employee baru) → abort 403
        [$user41] = $this->employee($p, 41);
        $this->submit($user41)->assertStatus(403)
            ->assertJson(['message' => 'Kuota peserta assessment penuh. Hubungi HR untuk info program.']);
        $this->assertSame(40, PerusahaanAssessment::where('perusahaan_id', $p->id)->count());

        // employee #1 retake → lolos (distinct, tidak menambah count)
        $emp1 = PerusahaanEmployee::where('email', "emp1@cap{$p->id}.local")->first();
        $res = $this->actingAs($emp1->user)->postJson(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => ['W1' => 2, 'W2' => 2, 'W3' => 2, 'W4' => 2, 'W5' => 2],
        ]);
        $res->assertRedirect();
        $this->assertSame(41, PerusahaanAssessment::where('perusahaan_id', $p->id)->count());
        $this->assertSame(40, PerusahaanAssessment::where('perusahaan_id', $p->id)
            ->distinct('employee_id')->count('employee_id'));
    }

    public function test_null_max_participants_is_unlimited()
    {
        $this->seedInstruments();
        [$p] = $this->company(['assessment_max_participants' => null]);

        for ($i = 1; $i <= 45; $i++) {
            [$user] = $this->employee($p, $i);
            $this->submit($user)->assertRedirect();
        }
        $this->assertSame(45, PerusahaanAssessment::where('perusahaan_id', $p->id)->count());
    }

    public function test_company_without_program_is_unlimited()
    {
        $this->seedInstruments();
        [$p] = $this->company(null);

        for ($i = 1; $i <= 45; $i++) {
            [$user] = $this->employee($p, $i);
            $this->submit($user)->assertRedirect();
        }
        $this->assertSame(45, PerusahaanAssessment::where('perusahaan_id', $p->id)->count());
    }
}
