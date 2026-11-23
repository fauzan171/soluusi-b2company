<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// wb-T4: wizard Assign Program superadmin — snapshot kontrak, guard 1 program
// aktif, 30-day notice termination + daily expire command (bizrules R2/R6/R9/§3/§4).
class WellbeingProgramAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function hrAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'hr_admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function perusahaan(int $employeeCount = 500, int $n = 1): Perusahaan
    {
        $p = Perusahaan::create([
            'name' => "PT WB Assign {$n}", 'brand' => "WBAssign{$n}",
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount, 'is_active' => true,
        ]);

        // wv2 §6: expert gate — payload default growth butuh 2 expert di pool.
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

    private function seedCatalog(): void
    {
        $this->seed(WellbeingPackageSeeder::class);
    }

    /** POST payload lengkap wizard untuk perusahaan+paket band 401-600. */
    private function payload(Perusahaan $p, array $over = []): array
    {
        return array_merge([
            'perusahaan_id' => $p->id,
            'package_code' => 'growth',
            'band_min' => 401,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'confirm' => '1',
        ], $over);
    }

    /** B2: payload foundation — tambah domain assessment (wajib). */
    private function foundationPayload(Perusahaan $p, array $over = []): array
    {
        return $this->payload($p, array_merge([
            'package_code' => 'foundation',
            'deep_support_mode' => 'one_on_one',
            'foundation_domain' => 'mh',
        ], $over));
    }

    // ------------------------------------------------------------------
    // C1 — happy path: snapshot eksak + AdminAction + redirect/flash
    // ------------------------------------------------------------------

    public function test_c1_assign_growth_band_401_option_a_creates_snapshot()
    {
        $this->seedCatalog();
        $admin = $this->admin();
        $p = $this->perusahaan(500);
        $starts = now()->addDay()->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.wellbeing-programs.store'), $this->payload($p, ['starts_at' => $starts]));

        $response->assertRedirect(route('admin.wellbeing-programs.create'))
            ->assertSessionHas('success');

        $growth = WellbeingPackage::where('code', 'growth')->first();
        $band = WellbeingPricingBand::where('wellbeing_package_id', $growth->id)->where('band_min', 401)->first();

        $program = CompanyWellbeingProgram::query()
            ->where('perusahaan_id', $p->id)->first();
        $this->assertNotNull($program);
        $this->assertSame('active', $program->status);
        $this->assertSame($growth->id, $program->wellbeing_package_id);
        $this->assertSame($band->id, $program->pricing_band_id);
        $this->assertSame(500, $program->employee_band_size);
        $this->assertSame('mhcu', $program->deep_support_mode);
        $this->assertSame($starts, $program->starts_at->toDateString());
        // ends = start + duration_days - 1 (growth 180 hari)
        $this->assertSame(
            $program->starts_at->copy()->addDays(180)->subDay()->toDateString(),
            $program->ends_at->toDateString()
        );
        // snapshot: harga band 401-600 growth + MHCU 15 (option A band menang)
        $this->assertEquals(20499999, (float) $program->monthly_price);
        $this->assertSame(15, $program->deep_support_quota);
        $this->assertSame(0, $program->expert_chat_quota);
        $this->assertSame(3, $program->webinar_quota);
        $this->assertSame(2, $program->assessment_count);
        $this->assertSame(['mh', 'nutrition'], $program->assessment_domains);
        $this->assertSame($admin->id, $program->created_by);

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'wellbeing_program.activated',
            'target_type' => $program->getMorphClass(),
            'target_id' => $program->id,
        ]);
    }

    // ------------------------------------------------------------------
    // R2 — option A/B wajib utk growth/impact, enum terbatas
    // ------------------------------------------------------------------

    public function test_r2_growth_tanpa_mode_ditolak_422()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->payload($p, ['deep_support_mode' => null]))
            ->assertStatus(422);

        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    public function test_r2_nilai_di_luar_enum_ditolak_422()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->payload($p, ['deep_support_mode' => 'both']))
            ->assertStatus(422);

        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    // ------------------------------------------------------------------
    // §4 — guard 1 program aktif per perusahaan
    // ------------------------------------------------------------------

    public function test_guard_satu_program_aktif_per_perusahaan()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertRedirect();

        // assign kedua saat masih aktif → 422
        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->foundationPayload($p))
            ->assertStatus(422);
        $this->assertSame(1, $p->programs()->count());

        // setelah ended → sukses
        $p->programs()->first()->update(['status' => 'ended']);
        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->foundationPayload($p))
            ->assertRedirect();

        $this->assertSame(2, $p->programs()->count());
    }

    // ------------------------------------------------------------------
    // §3 — band dari employee_count + validasi band
    // ------------------------------------------------------------------

    public function test_biz3_employee_count_di_luar_band_error()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(1200); // di luar semua band

        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->payload($p, ['band_min' => 801]))
            ->assertStatus(422);

        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    public function test_biz3_employee_count_30_paket_berbayar_ditolak()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(30);

        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->payload($p, ['band_min' => 50]))
            ->assertStatus(422);

        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    public function test_biz3_discovery_gratis_employee_count_30_sukses()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(30);

        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p, [
                'package_code' => 'discovery', 'band_min' => null, 'deep_support_mode' => 'one_on_one',
            ]))
            ->assertRedirect();

        $program = $p->programs()->first();
        $this->assertNotNull($program);
        $this->assertNull($program->pricing_band_id); // discovery: band null
        $this->assertNull($program->monthly_price);
        $this->assertSame(8, $program->deep_support_quota);
        $this->assertSame(30, $program->employee_band_size);
    }

    // ------------------------------------------------------------------
    // R4 — discovery selected_employee_ids max 8
    // ------------------------------------------------------------------

    public function test_r4_discovery_selected_employees_max_8()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(30);
        $ids = [];
        for ($i = 0; $i < 9; $i++) {
            $user = User::create([
                'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => "D{$i}",
                'username' => "d{$i}@wb.local", 'email' => "d{$i}@wb.local",
                'password' => bcrypt('password'), 'is_active' => true, 'role_id' => Role::firstOrCreate(['name' => 'employee'])->id,
            ]);
            $ids[] = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'user_id' => $user->id,
                'name' => "Emp D{$i}", 'email' => "d{$i}@wb.local",
            ])->id;
        }

        // 9 karyawan → 422
        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.store'), $this->payload($p, [
                'package_code' => 'discovery', 'band_min' => null, 'deep_support_mode' => 'one_on_one',
                'selected_employee_ids' => $ids,
            ]))
            ->assertStatus(422);

        // 8 karyawan → sukses, snapshot ids
        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p, [
                'package_code' => 'discovery', 'band_min' => null, 'deep_support_mode' => 'one_on_one',
                'selected_employee_ids' => array_slice($ids, 0, 8),
            ]))
            ->assertRedirect();

        $program = $p->programs()->first();
        $this->assertEquals(array_slice($ids, 0, 8), $program->selected_employee_ids);
    }

    // ------------------------------------------------------------------
    // R9 — snapshot kebal perubahan katalog
    // ------------------------------------------------------------------

    public function test_r9_snapshot_tidak_berubah_saat_harga_band_master_diubah()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertRedirect();

        $program = $p->programs()->first();
        WellbeingPricingBand::where('id', $program->pricing_band_id)
            ->update(['monthly_price' => 999, 'mhcu_quota' => 1]);

        $program->refresh();
        $this->assertEquals(20499999, (float) $program->monthly_price);
        $this->assertSame(15, $program->deep_support_quota);
    }

    // ------------------------------------------------------------------
    // R6 — 30-day notice termination + daily expire
    // ------------------------------------------------------------------

    public function test_r6_terminasi_dibawah_30_hari_ditolak()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);
        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertRedirect();
        $program = $p->programs()->first();

        $this->actingAs($this->admin())
            ->postJson(route('admin.wellbeing-programs.request-termination', $program), [
                'effective_date' => now()->addDays(20)->toDateString(),
            ])
            ->assertStatus(422);

        $program->refresh();
        $this->assertNull($program->notice_requested_at);
        $this->assertNull($program->terminated_at);
        $this->assertSame('active', $program->status);
    }

    public function test_r6_terminasi_30_hari_program_tetap_aktif_lalu_expired()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);
        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertRedirect();
        $program = $p->programs()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.request-termination', $program), [
                'effective_date' => now()->addDays(30)->toDateString(),
            ])
            ->assertRedirect()->assertSessionHas('success');

        $program->refresh();
        $this->assertSame('active', $program->status); // masih aktif sampai efektif
        $this->assertNotNull($program->notice_requested_at);
        $this->assertNotNull($program->terminated_at);

        // command: tanggal efektif belum tiba → tidak ada yang berubah
        $this->artisan('wellbeing:expire-programs')->assertSuccessful();
        $this->assertSame('active', $program->refresh()->status);

        // tanggal efektif tiba → ended + AdminAction
        $this->travel(31)->days();
        $this->artisan('wellbeing:expire-programs')->assertSuccessful();

        $program->refresh();
        $this->assertSame('ended', $program->status);
        $this->assertDatabaseHas('admin_actions', [
            'action' => 'wellbeing_program.ended',
            'target_id' => $program->id,
        ]);
    }

    public function test_r6_command_juga_mengakhiri_program_lewat_ends_at()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);
        $this->actingAs($this->admin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertRedirect();
        $program = $p->programs()->first();

        // program tanpa notice tapi starts_at/ends_at sudah lewat → ended
        $program->update([
            'starts_at' => now()->subDays(200)->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('wellbeing:expire-programs')->assertSuccessful();
        $this->assertSame('ended', $program->refresh()->status);
    }

    // ------------------------------------------------------------------
    // A3 — hr_admin tidak boleh assign
    // ------------------------------------------------------------------

    public function test_a3_hr_admin_post_store_403()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $this->actingAs($this->hrAdmin())
            ->post(route('admin.wellbeing-programs.store'), $this->payload($p))
            ->assertStatus(403);

        $this->assertSame(0, CompanyWellbeingProgram::count());
    }

    // ------------------------------------------------------------------
    // View — GET create wizard
    // ------------------------------------------------------------------

    public function test_view_create_admin_200_dengan_band_highlight()
    {
        $this->seedCatalog();
        $p = $this->perusahaan(500);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.create', ['perusahaan_id' => $p->id, 'package_code' => 'growth', 'step' => 2]));

        $response->assertOk();
        // harga band 401-600 growth tampil readonly + label "+ pajak"
        $response->assertSee('20.499.999');
        $response->assertSee('pajak');
        $response->assertSee('readonly', false);
    }

    public function test_view_create_hijau_untuk_admin()
    {
        $this->seedCatalog();

        $this->actingAs($this->hrAdmin())
            ->get(route('admin.wellbeing-programs.create'))
            ->assertStatus(403);

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.create'))
            ->assertOk();
    }
}
