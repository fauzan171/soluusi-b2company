<?php

namespace Tests\Feature;

use App\Models\EngagementCampaign;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv4: employee pages (Aktivitasku, Hasilku, Privasi & Consent)
// + admin pages (program index, employee browse, billing) render.
class Wv4PagesTest extends TestCase
{
    use RefreshDatabase;

    private int $n = 0;

    private function makeUser(string $role, string $username): User
    {
        $roleId = DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Page', 'lastname' => 'Wv4',
            'username' => $username, 'email' => "{$username}@test.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function makeCompany(): Perusahaan
    {
        $this->n++;

        return Perusahaan::create([
            'name' => "PT Page {$this->n}", 'brand' => "PageCo{$this->n}",
            'sessions_per_employee_month' => 2, 'employee_count' => 30, 'is_active' => true,
        ]);
    }

    private function makeEmployee(Perusahaan $p): User
    {
        $u = $this->makeUser('employee', "page_emp_{$this->n}");
        DB::table('perusahaan_employees')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $u->id, 'name' => 'Page Employee',
            'email' => "page_emp_{$this->n}@test.local", 'consent_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u;
    }

    private function makeAssessment(Perusahaan $p, int $employeeRowId, int $score): void
    {
        DB::table('perusahaan_assessments')->insert([
            'perusahaan_id' => $p->id, 'employee_id' => $employeeRowId,
            'instrument_code' => 'who5', 'answers' => '[]', 'score' => $score,
            'escalated' => false, 'status' => 'done', 'taken_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_employee_activities_page_lists_announcements(): void
    {
        $p = $this->makeCompany();
        $emp = $this->makeEmployee($p);
        $p->campaigns()->create([
            'title' => 'Pengumuman Libur', 'body' => 'Kantor libur tanggal 17.',
            'channel' => 'announcement', 'scheduled_at' => now(), 'sent_at' => now(),
        ]);

        $this->actingAs($emp)->get(route('perusahaan.employee.activities'))
            ->assertOk()
            ->assertSee('Pengumuman Libur')
            ->assertSee('Kantor libur');
    }

    public function test_employee_activities_page_omits_unsent_email_campaigns(): void
    {
        $p = $this->makeCompany();
        $emp = $this->makeEmployee($p);
        $p->campaigns()->create([
            'title' => 'Rahasia Email', 'body' => 'Belum terkirim.',
            'channel' => 'email', 'scheduled_at' => now()->addDay(), 'sent_at' => null,
        ]);

        $this->actingAs($emp)->get(route('perusahaan.employee.activities'))
            ->assertOk()
            ->assertDontSee('Rahasia Email');
    }

    public function test_employee_results_page_lists_assessment_history(): void
    {
        $p = $this->makeCompany();
        $emp = $this->makeEmployee($p);
        $rowId = DB::table('perusahaan_employees')->where('user_id', $emp->id)->value('id');
        $this->makeAssessment($p, $rowId, 80);

        $res = $this->actingAs($emp)->get(route('perusahaan.employee.results'));

        $res->assertOk()->assertSee('Riwayat hasil asesmen');
        $this->assertSame(1, substr_count($res->getContent(), 'Detail'));
    }

    public function test_employee_privacy_page_shows_consent_status(): void
    {
        $p = $this->makeCompany();
        $emp = $this->makeEmployee($p);

        $this->actingAs($emp)->get(route('perusahaan.employee.privacy'))
            ->assertOk()
            ->assertSee('Consent aktif')
            ->assertSee('pernah muncul di dashboard HR');
    }

    public function test_admin_program_wellbeing_index_lists_contracts(): void
    {
        $admin = $this->makeUser('admin', "page_admin_{$this->n}");
        $p = $this->makeCompany();

        $pkg = \App\Models\WellbeingPackage::create([
            'code' => "growth-p{$this->n}", 'name' => 'Growth', 'duration_days' => 180,
            'assessment_count' => 2, 'assessment_domains' => ['mh'],
            'expert_chat_experts' => 1, 'webinar_count' => 3, 'quarterly_review' => false,
        ]);
        \App\Models\CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id, 'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 100, 'deep_support_mode' => 'mhcu',
            'starts_at' => today()->toDateString(), 'ends_at' => today()->addDays(179)->toDateString(),
            'expert_chat_quota' => 30, 'deep_support_quota' => 15, 'webinar_quota' => 3,
            'assessment_count' => 2, 'monthly_price' => 9999999, 'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.wellbeing-programs.index'))
            ->assertOk()
            ->assertSee('Program Wellbeing')
            ->assertSee($p->brand)
            ->assertSee('Growth');
    }

    public function test_admin_employee_browse_renders_and_filters(): void
    {
        $admin = $this->makeUser('admin', "page_admin_b_{$this->n}");
        $p = $this->makeCompany();
        $emp = $this->makeEmployee($p);
        $other = $this->makeCompany(); // employee di perusahaan lain

        $base = $this->actingAs($admin)->get(route('admin.employees.index'));
        $base->assertOk()->assertSee('Browser karyawan')->assertSee('Page Employee');

        // Filter perusahaan: hanya milik $p.
        $filtered = $this->actingAs($admin)->get(route('admin.employees.index', ['perusahaan_id' => $other->id]));
        $filtered->assertOk()->assertDontSee('Page Employee');

        // Search tidak menghasilkan PII di luar filter.
        $this->actingAs($admin)->get(route('admin.employees.index', ['q' => 'nonexistent-xyz']))->assertOk();
    }

    public function test_admin_billing_page_lists_contract_and_renewal_badge(): void
    {
        $admin = $this->makeUser('admin', "page_admin_c_{$this->n}");
        $p = $this->makeCompany();

        $pkg = \App\Models\WellbeingPackage::create([
            'code' => "growth-b{$this->n}", 'name' => 'Growth', 'duration_days' => 180,
            'assessment_count' => 2, 'assessment_domains' => ['mh'],
            'expert_chat_experts' => 1, 'webinar_count' => 3, 'quarterly_review' => false,
        ]);
        \App\Models\CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id, 'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 100, 'deep_support_mode' => 'mhcu',
            'starts_at' => today()->subDays(160)->toDateString(),
            'ends_at' => today()->addDays(20)->toDateString(), // <30 hari → badge renewal
            'expert_chat_quota' => 30, 'deep_support_quota' => 15, 'webinar_quota' => 3,
            'assessment_count' => 2, 'monthly_price' => 5000000, 'status' => 'active',
            'notice_requested_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee($p->brand)
            ->assertSee('renewal <30d')
            ->assertSee('Diminta');
    }

    public function test_admin_assessment_instruments_page_renders(): void
    {
        $admin = $this->makeUser('admin', "page_admin_d_{$this->n}");

        $this->actingAs($admin)->get(route('admin.assessment-instruments.index'))
            ->assertOk()
            ->assertSee('Instrumen asesmen');
    }

    public function test_hr_engagement_and_utilization_pages_render(): void
    {
        $hr = $this->makeUser('hr_admin', "page_hr_{$this->n}");
        $p = $this->makeCompany();
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($hr)->get(route('perusahaan.engagement'))
            ->assertOk()->assertSee('Keterlibatan Karyawan');
        $this->actingAs($hr)->get(route('perusahaan.expert-utilization'))
            ->assertOk()->assertSee('Utilisasi Expert');
        $this->actingAs($hr)->get(route('perusahaan.program-progress'))
            ->assertOk()->assertSee('Progres Program');
        $this->actingAs($hr)->get(route('perusahaan.campaigns.index'))
            ->assertOk()->assertSee('Campaign Keterlibatan');
    }

    public function test_wellbeing_insight_page_shows_area_priority_map(): void
    {
        $hr = $this->makeUser('hr_admin', "page_hr_w_{$this->n}");
        $p = $this->makeCompany();
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($hr)->get(route('perusahaan.wellbeing'))
            ->assertOk()
            ->assertSee('Area Prioritas')
            ->assertSee('bukan penandaan karyawan');
    }
}
