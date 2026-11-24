<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingProgramReview;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv2 — quarterly review periods (research2-infra §3): Impact-only, 4 rows
// period_no 1-4 dibuat di transaction yang sama dengan assign wizard,
// due_at = starts_at + 3/6/9/12 bulan. HR read-only. Admin mark completed.
class WellbeingProgramReviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $roleId = DB::table('roles')->where('name', 'admin')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'admin', 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => 'Rev',
            'username' => 'rev.'.uniqid(), 'email' => 'rev.'.uniqid().'@rev.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function company(string $brand, int $employeeCount = 500): Perusahaan
    {
        return Perusahaan::create([
            'name' => "PT {$brand}", 'brand' => $brand,
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount,
            'is_active' => true,
        ]);
    }

    private function hrAdmin(Perusahaan $p): User
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Rev',
            'username' => 'hr.'.uniqid(), 'email' => 'hr.'.uniqid().'@rev.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hr;
    }

    private function assignProgram(Perusahaan $p, string $code, User $admin, string $startsAt = '2027-01-15'): CompanyWellbeingProgram
    {
        // wv2 §6 expert gate: pool perusahaan >= expert_chat_experts katalog.
        foreach (range(1, $code === 'impact' || $code === 'growth' ? 2 : 1) as $i) {
            $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
                ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
            $u = User::create([
                'uuid' => Str::uuid(), 'firstname' => 'Doc', 'lastname' => 'Rev'.$i,
                'username' => 'rev.doc'.uniqid(), 'email' => 'rev.doc'.uniqid().'@rev.local',
                'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
            ]);
            $consultant = \App\Models\Consultant::create([
                'user_id' => $u->id, 'uuid' => (string) Str::uuid(),
                'about' => 'Test', 'profit_sharing' => 0,
                'status' => \App\Models\Consultant::ACCEPT, 'is_active' => true,
            ]);
            \App\Models\ConsultantPool::create([
                'perusahaan_id' => $p->id, 'consultant_id' => $consultant->id, 'is_active' => true,
            ]);
        }

        $res = $this->actingAs($admin)->post(route('admin.wellbeing-programs.store'), [
            'perusahaan_id' => $p->id,
            'package_code' => $code,
            'band_min' => $code === 'discovery' ? null : 50,
            'deep_support_mode' => $code === 'growth' || $code === 'impact' ? 'mhcu' : 'one_on_one',
            // B2: pilihan domain assessment utk foundation (wajib).
            'discovery_instrument' => 'who5',
            'foundation_domain' => 'mh',
            'starts_at' => $startsAt,
            'confirm' => 1,
        ]);

        if ($res->status() === 422 || $res->status() === 302 && session()->has('errors')) {
            $this->fail('assign gagal: '.json_encode(session('errors')?->all() ?? $res->status()));
        }
        $res->assertRedirect();

        return $p->programs()->latest('id')->first();
    }

    public function test_impact_assign_creates_4_review_rows_with_quarterly_due_dates()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $p = $this->company('RevImpact');

        $program = $this->assignProgram($p, 'impact', $admin, '2027-01-15');

        $reviews = WellbeingProgramReview::where('program_id', $program->id)->orderBy('period_no')->get();
        $this->assertCount(4, $reviews);

        // due_at = starts_at + 3/6/9/12 bulan
        $this->assertSame('2027-04-15', $reviews[0]->due_at->toDateString());
        $this->assertSame('2027-07-15', $reviews[1]->due_at->toDateString());
        $this->assertSame('2027-10-15', $reviews[2]->due_at->toDateString());
        $this->assertSame('2028-01-15', $reviews[3]->due_at->toDateString());

        foreach ($reviews as $r) {
            $this->assertSame('upcoming', $r->status);
        }
    }

    public function test_non_impact_packages_get_no_review_rows()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();

        foreach (['discovery', 'foundation', 'growth'] as $code) {
            $p = $this->company('Rev'.ucfirst($code));
            $program = $this->assignProgram($p, $code, $admin);
            $this->assertSame(0, WellbeingProgramReview::where('program_id', $program->id)->count(),
                "{$code} tidak boleh punya review rows");
        }
    }

    public function test_hr_page_shows_review_periods_for_impact()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $p = $this->company('RevHr');
        $hr = $this->hrAdmin($p);

        $program = $this->assignProgram($p, 'impact', $admin);

        $this->travelTo('2027-02-01');
        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('Review Kuartalan')
            ->assertSee('Periode 1')
            ->assertSee('15 Apr 2027');
    }

    public function test_admin_can_mark_review_completed_with_summary()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $p = $this->company('RevComplete');
        $program = $this->assignProgram($p, 'impact', $admin);

        $review = WellbeingProgramReview::where('program_id', $program->id)
            ->where('period_no', 1)->first();

        $this->actingAs($admin)
            ->post(route('admin.wellbeing-reviews.complete', $review), [
                'summary' => 'Skor WHO-5 naik 12%, eskalasi 2 karyawan sudah ditindaklanjuti.',
            ])
            ->assertRedirect();

        $fresh = $review->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('Skor WHO-5 naik 12%, eskalasi 2 karyawan sudah ditindaklanjuti.', $fresh->summary);
        $this->assertEquals($admin->id, $fresh->created_by);
    }

    public function test_review_unique_per_program_period()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->admin();
        $p = $this->company('RevDup');
        $program = $this->assignProgram($p, 'impact', $admin);

        $this->expectException(\Illuminate\Database\QueryException::class);
        WellbeingProgramReview::create([
            'program_id' => $program->id, 'period_no' => 1,
            'due_at' => '2027-04-15', 'status' => 'upcoming',
        ]);
    }
}
