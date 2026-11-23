<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv2 — expert-count gate (research2-infra §6): assign wizard wajib pool
// konsultan perusahaan >= expert_chat_experts katalog (Foundation 1,
// Growth/Impact 2; Discovery 0 = tanpa gate). HR page soft warning live
// dari katalog (no snapshot).
class WellbeingExpertGateTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'admin'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => 'Gate',
            'username' => Str::slug($email), 'email' => $email,
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

    private function hrAdmin(Perusahaan $p, int $n): User
    {
        $hr = $this->user("gate.hr{$n}@gate.local", 'hr_admin');
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hr;
    }

    private function poolConsultant(Perusahaan $p): void
    {
        $u = $this->user('gate.doc'.uniqid().'@gate.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $u->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'perusahaan_id' => $p->id, 'consultant_id' => $consultant->id, 'is_active' => true,
        ]);
    }

    private function assign(Perusahaan $p, string $code, User $admin): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($admin)->post(route('admin.wellbeing-programs.store'), [
            'perusahaan_id' => $p->id,
            'package_code' => $code,
            'band_min' => 50,
            'deep_support_mode' => $code === 'growth' || $code === 'impact' ? 'mhcu' : 'one_on_one',
            'starts_at' => now()->addDay()->toDateString(),
            'confirm' => 1,
        ]);
    }

    // ------------------------------------------------------------------
    // Gate at wizard store
    // ------------------------------------------------------------------

    public function test_foundation_blocks_when_pool_empty()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.f0@gate.local');
        $p = $this->company('GateF0');

        $this->assign($p, 'foundation', $admin)
            ->assertSessionHasErrors();

        $this->assertSame(0, $p->programs()->count(), 'program tidak boleh terbuat');
        $errors = session('errors')->all();
        $this->assertStringContainsString('Assign minimal 1 expert melalui Consultant Assignment', implode(' ', $errors));
    }

    public function test_growth_blocks_when_only_1_expert_in_pool()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.g1@gate.local');
        $p = $this->company('GateG1');
        $this->poolConsultant($p); // 1 expert, growth butuh 2

        $this->assign($p, 'growth', $admin)
            ->assertSessionHasErrors();

        $this->assertSame(0, $p->programs()->count());
        $this->assertStringContainsString('Assign minimal 2 expert melalui Consultant Assignment', implode(' ', session('errors')->all()));
    }

    public function test_foundation_passes_with_1_expert_and_growth_with_2()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.ok@gate.local');

        // Foundation: 1 expert cukup
        $p1 = $this->company('GateOkF');
        $this->poolConsultant($p1);
        $this->assign($p1, 'foundation', $admin)->assertRedirect();
        $this->assertSame(1, $p1->programs()->count());

        // Growth: 2 expert cukup
        $p2 = $this->company('GateOkG');
        $this->poolConsultant($p2);
        $this->poolConsultant($p2);
        $this->assign($p2, 'growth', $admin)->assertRedirect();
        $this->assertSame(1, $p2->programs()->count());
    }

    public function test_discovery_has_no_expert_gate()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.d0@gate.local');
        $p = $this->company('GateD0'); // pool kosong, discovery expert_chat_experts = 0

        $this->assign($p, 'discovery', $admin)->assertRedirect();
        $this->assertSame(1, $p->programs()->count());
    }

    // ------------------------------------------------------------------
    // HR page: soft warning read live from package (no snapshot)
    // ------------------------------------------------------------------

    public function test_hr_page_warns_when_pool_below_package_experts()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.hrwa@gate.local');
        $p = $this->company('GateHrWarn');
        $this->poolConsultant($p); // 1 expert — growth butuh 2

        // assign lewat wizard (pool gate lolos karena 2 sebenarnya? tidak: hanya 1) —
        // buat program langsung supaya pool tidak menghalangi skenario warning
        $this->assign($p, 'foundation', $admin)->assertRedirect(); // foundation 1 expert: lolos
        $hr = $this->hrAdmin($p, 1);

        // ganti program ke growth via pakai seed catalog — cukup pakai foundation
        // dan turunkan pool di bawah angka paket: hapus 1 pool → 0 < 1
        ConsultantPool::where('perusahaan_id', $p->id)->delete();

        $this->travelTo(now()->addDay());
        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('Jumlah expert chat belum lengkap');
    }

    public function test_hr_page_no_warning_when_pool_meets_experts()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gate.hrnb@gate.local');
        $p = $this->company('GateHrNoWarn');
        $this->poolConsultant($p); // 1 expert — foundation 1: cukup

        $this->assign($p, 'foundation', $admin)->assertRedirect();
        $hr = $this->hrAdmin($p, 2);

        $this->travelTo(now()->addDay());
        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertDontSee('Jumlah expert chat belum lengkap');
    }
}
