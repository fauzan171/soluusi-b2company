<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingQuotaLedger;
use App\Services\WellbeingQuotaService;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

// wb-T6: sisa scenario gap research-tests §3 — B4 (estimasi di luar semua band,
// pesan error eksak), C2 (2 perusahaan paralel — kuota & tampilan HR terisolasi),
// C3 (program ended via daily command → gate wellbeing tertutup, fallback legacy),
// D3 (kuota pool kontrak: employee A habis → employee B juga gagal),
// E2 (kolom katalog expert_chat_experts Foundation 1 / Growth 2 / Impact 2
// + tampilan jumlah expert di HR "Program & Subscription").
// A4 sudah tercakup WellbeingCatalogAdminTest::test_band_paket_gratis_discovery_ditolak
// + WellbeingProgramAssignmentTest::test_biz3_discovery_gratis_employee_count_30_sukses.
class WellbeingScenarioGapTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'employee'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => 'Gap',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function company(string $brand, int $employeeCount = 500): Perusahaan
    {
        $p = Perusahaan::create([
            'name' => "PT {$brand}", 'brand' => $brand,
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount,
            'is_active' => true,
        ]);

        // wv2 §6: expert gate — assign growth/impact butuh pool >= 2 expert.
        foreach (range(1, 2) as $i) {
            $u = $this->user("expert{$i}@".Str::slug($brand).'.local', 'consultant');
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

    /** @return array{0: User, 1: PerusahaanEmployee} */
    private function employee(Perusahaan $p, int $i): array
    {
        $email = "emp{$i}@".Str::slug($p->brand).'.local';
        $user = $this->user($email);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => "Emp {$i} {$p->brand}", 'email' => $email, 'consent_at' => now(),
        ]);

        return [$user, $emp];
    }

    private function hrAdmin(Perusahaan $p, int $n): User
    {
        $hr = $this->user("hr{$n}@gap.local", 'hr_admin');
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $hr;
    }

    private function program(Perusahaan $p, string $code = 'growth', array $overrides = []): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::where('code', $code)->firstOrFail();
        // created_by wajib: expire command mencatat AdminAction (FK admin_id).
        $overrides += ['created_by' => $overrides['created_by']
            ?? User::where('role_id', DB::table('roles')->where('name', 'admin')->value('id'))->value('id')
            ?? $this->user('gap.admin'.uniqid().'@gap.local', 'admin')->id];

        return CompanyWellbeingProgram::create(array_merge([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => $p->employee_count,
            'deep_support_mode' => $pkg->deep_support_mode ?: 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'webinar_quota' => $pkg->webinar_count,
            'assessment_count' => $pkg->assessment_count,
            'status' => 'active',
        ], $overrides));
    }

    private function makeSession(Perusahaan $p, PerusahaanEmployee $emp): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now(), 'consultation_type' => 'chat',
        ]);
    }

    // ------------------------------------------------------------------
    // B4 — employee_count di luar semua band: pesan error jelas & eksak
    // ------------------------------------------------------------------

    public function test_b4_employee_count_1200_outside_all_bands_message_exact()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('gap.b4@gap.local', 'admin');
        $p = $this->company('GapB4', 1200);

        $res = $this->actingAs($admin)->postJson(route('admin.wellbeing-programs.store'), [
            'perusahaan_id' => $p->id,
            'package_code' => 'growth',
            'band_min' => 801,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'confirm' => '1',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors('band_min');
        $this->assertSame(
            'Jumlah karyawan terdaftar 1200 di luar semua band harga (maksimum 1000). Hubungi Finance untuk kontrak custom.',
            $res->json('errors.band_min.0')
        );
        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    // ------------------------------------------------------------------
    // C2 — 2 perusahaan paralel: kuota bucket & tampilan HR terisolasi
    // ------------------------------------------------------------------

    public function test_c2_parallel_companies_quota_and_hr_page_isolated()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $pA = $this->company('GapC2A');
        $pB = $this->company('GapC2B');
        $progA = $this->program($pA, 'growth', ['expert_chat_quota' => 2]);
        $progB = $this->program($pB, 'growth', ['expert_chat_quota' => 5]);
        [, $empA] = $this->employee($pA, 1);

        // tenant A menghabiskan seluruh bucket chat kontraknya
        foreach (range(1, 2) as $i) {
            WellbeingQuotaService::reserve($empA, $this->makeSession($pA, $empA), WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        }
        $this->assertSame(0, WellbeingQuotaService::bucketBalance($progA, 'expert_chat'));

        // bucket tenant B tidak tersentuh aktivitas tenant A
        $this->assertSame(5, WellbeingQuotaService::bucketBalance($progB, 'expert_chat'));
        $this->assertSame(2, WellbeingQuotaLedger::where('program_id', $progA->id)->count());
        $this->assertSame(0, WellbeingQuotaLedger::where('program_id', $progB->id)->count());

        // employee tenant B tetap bisa reserve dengan kuota kontraknya sendiri
        [, $empB] = $this->employee($pB, 1);
        WellbeingQuotaService::reserve($empB, $this->makeSession($pB, $empB), WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        $this->assertSame(4, WellbeingQuotaService::bucketBalance($progB, 'expert_chat'));

        // HR tenant A hanya melihat snapshot program A (2/2), bukan angka B (x/5)
        $res = $this->actingAs($this->hrAdmin($pA, 10))->get(route('perusahaan.program-subscription'));
        $res->assertOk()
            ->assertSee('2 / 2 sesi')
            ->assertDontSee('/ 5 sesi')
            ->assertDontSee('4 / 5 sesi');
    }

    // ------------------------------------------------------------------
    // C3 — program ended via daily command: gate wellbeing tertutup (D2
    // hangus + reserve 403), booking HTTP kembali ke jalur legacy
    // ------------------------------------------------------------------

    public function test_c3_expired_program_closes_wellbeing_gate_falls_back_legacy()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $p = $this->company('GapC3');
        $program = $this->program($p, 'growth', ['ends_at' => today()->addDays(30)]);
        [$user, $emp] = $this->employee($p, 1);

        // gate terbuka: reserve wellbeing OK
        WellbeingQuotaService::reserve($emp, $this->makeSession($p, $emp), WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        $this->assertSame(9, WellbeingQuotaService::bucketBalance($program, 'expert_chat'));

        // lewat ends_at → daily command mengakhiri program
        $this->travel(31)->days();
        $this->artisan('wellbeing:expire-programs')->assertSuccessful();
        $this->assertSame('ended', $program->refresh()->status);

        // sisa kuota hangus (D2) + reserve wellbeing ditolak 403
        $this->assertSame(0, WellbeingQuotaService::bucketBalance($program, 'expert_chat'));
        $s2 = $this->makeSession($p, $emp);
        try {
            WellbeingQuotaService::reserve($emp, $s2, WellbeingQuotaService::BUCKET_EXPERT_CHAT);
            $this->fail('reserve setelah program ended harus 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Tidak ada program wellbeing aktif.', $e->getMessage());
        }
        $s2->update(['status' => 'cancelled']); // jangan dihitung unledgered legacy

        // booking HTTP kembali ke legacy: ledger lama tertulis, wellbeing tidak
        $slot = $this->programSlot('perusahaan', $p->id);
        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id, 'konsultan_type' => 'psikolog',
            'consultation_type' => 'chat',
        ])->assertRedirect(route('perusahaan.employee'));

        $legacy = PerusahaanSession::where('employee_id', $emp->id)->orderByDesc('id')->first();
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $legacy->id, 'event' => 'reserved',
        ]);
        $this->assertDatabaseMissing('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $legacy->id,
        ]);
    }

    // ------------------------------------------------------------------
    // D3 — kuota pool kontrak: employee A menghabiskan → employee B gagal
    // ------------------------------------------------------------------

    public function test_d3_employee_a_exhausts_pool_employee_b_rejected()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $p = $this->company('GapD3');
        $program = $this->program($p, 'growth', ['expert_chat_quota' => 1, 'deep_support_quota' => 1]);
        [, $empA] = $this->employee($p, 1);
        [, $empB] = $this->employee($p, 2);

        // employee A memakai satu-satunya kuota chat kontrak
        WellbeingQuotaService::reserve($empA, $this->makeSession($p, $empA), WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        $this->assertSame(0, WellbeingQuotaService::bucketBalance($program, 'expert_chat'));

        // employee B tidak bisa memakai sisa kuota employee A (pool kontrak bersama)
        try {
            WellbeingQuotaService::reserve($empB, $this->makeSession($p, $empB), WellbeingQuotaService::BUCKET_EXPERT_CHAT);
            $this->fail('reserve employee B harus 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(
                'Kuota kontrak kuota chat sudah habis (Growth). Hubungi HR untuk info program.',
                $e->getMessage()
            );
        }
        $this->assertSame(0, WellbeingQuotaLedger::where('perusahaan_employee_id', $empB->id)->count());
    }

    // ------------------------------------------------------------------
    // E2 — expert_chat_experts katalog + tampilan HR (no gate: lihat TODO
    // di program-subscription.blade.php — entitas penugasan expert backlog)
    // ------------------------------------------------------------------

    public function test_e2_expert_chat_experts_catalog_values_and_hr_display()
    {
        $this->seed(WellbeingPackageSeeder::class);

        // sumber data: kolom katalog wellbeing_packages.expert_chat_experts
        $this->assertSame(0, WellbeingPackage::where('code', 'discovery')->value('expert_chat_experts'));
        $this->assertSame(1, WellbeingPackage::where('code', 'foundation')->value('expert_chat_experts'));
        $this->assertSame(2, WellbeingPackage::where('code', 'growth')->value('expert_chat_experts'));
        $this->assertSame(2, WellbeingPackage::where('code', 'impact')->value('expert_chat_experts'));

        // HR page: Foundation → 1 expert
        $p1 = $this->company('GapE2a');
        $this->program($p1, 'foundation', [
            'expert_chat_quota' => 5, 'deep_support_quota' => 0, 'monthly_price' => 7999999,
        ]);
        $this->actingAs($this->hrAdmin($p1, 20))->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('Paket aktif: Foundation')
            // Persempit ke baris dd katalog: partial S&K (item 9 audit UX) memuat
            // "2 expert" di deskripsi paket Growth/Impact — angka dd-lah yang
            // wajib menampilkan expert_chat_experts Foundation = 1.
            ->assertSee('<dd class="font-semibold text-pine-900">1 expert</dd>', false)
            ->assertDontSee('<dd class="font-semibold text-pine-900">2 expert</dd>', false);

        // HR page: Growth → 2 expert
        $p2 = $this->company('GapE2b');
        $this->program($p2, 'growth', ['monthly_price' => 20499999]);
        $this->actingAs($this->hrAdmin($p2, 21))->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('<dd class="font-semibold text-pine-900">2 expert</dd>', false);
    }
}
