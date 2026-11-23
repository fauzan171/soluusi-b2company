<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// wv3: (1) public /daftar-perusahaan lead-capture + scroll-gate terms,
// (2) Program Order printable snapshot, (3) klinik checkpoint-40 view.
class Wv3PerusahaanRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'org_name' => 'PT Cipta Sejahtera',
            'hr_email' => 'hr@ciptasejahtera.test',
            'hr_name' => 'Dewi Kartika',
            'employee_band' => '151-400',
            'phone' => '081234567890',
            'package_code' => 'growth',
            'terms' => '1',
        ], $over);
    }

    // ------------------------------------------------------------------
    // 1. Public registration /daftar-perusahaan
    // ------------------------------------------------------------------

    public function test_halaman_daftar_perusahaan_menampilkan_form_dan_terms()
    {
        $this->get(route('perusahaan.register'))
            ->assertOk()
            ->assertSee('Daftar Sekarang')
            ->assertSee('S&K PERUSAHAAN')
            ->assertSee('Pilih rentang jumlah karyawan');
    }

    public function test_daftar_perusahaan_membuat_perusahaan_pending_dan_hr_admin()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        $this->post(route('perusahaan.register.store'), $this->payload())
            ->assertRedirect(route('loginIndex'))
            ->assertSessionHas('success');

        $perusahaan = Perusahaan::where('name', 'PT Cipta Sejahtera')->first();
        $this->assertNotNull($perusahaan);
        $this->assertFalse((bool) $perusahaan->is_active);                 // pending ops approval
        $this->assertSame('pending', $perusahaan->status);
        $this->assertNotNull($perusahaan->terms_agreed_at);                // S&K disetujui
        $this->assertSame(151, $perusahaan->employee_count);               // band floor disimpan

        $hr = User::where('email', 'hr@ciptasejahtera.test')->first();
        $this->assertNotNull($hr);
        $this->assertFalse((bool) $hr->is_active);                         // no auto-login: akun non-aktif
        $this->assertSame('hr_admin', Role::find($hr->role_id)->name);

        $this->assertDatabaseHas('perusahaan_admins', [
            'perusahaan_id' => $perusahaan->id,
            'user_id' => $hr->id,
            'role' => 'hr_admin',
        ]);
    }

    public function test_daftar_perusahaan_tanpa_terms_ditolak()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        $this->from(route('perusahaan.register'))
            ->post(route('perusahaan.register.store'), $this->payload(['terms' => '']))
            ->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('perusahaans', ['name' => 'PT Cipta Sejahtera']);
        $this->assertDatabaseMissing('users', ['email' => 'hr@ciptasejahtera.test']);
    }

    public function test_daftar_perusahaan_email_duplikat_dan_band_invalid_ditolak()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        // email sudah dipakai user lain
        $existing = User::factory()->create(['email' => 'hr@ciptasejahtera.test']);
        $this->from(route('perusahaan.register'))
            ->post(route('perusahaan.register.store'), $this->payload())
            ->assertSessionHasErrors('hr_email');

        // band tidak valid
        $this->from(route('perusahaan.register'))
            ->post(route('perusahaan.register.store'), $this->payload([
                'hr_email' => 'hr2@ciptasejahtera.test',
                'employee_band' => '10-20',
            ]))
            ->assertSessionHasErrors('employee_band');

        $this->assertDatabaseMissing('perusahaans', ['name' => 'PT Cipta Sejahtera']);
        $this->assertSame(0, PerusahaanAdmin::count());
    }

    public function test_daftar_perusahaan_dibatasi_throttle()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        foreach (range(1, 5) as $i) {
            $this->post(route('perusahaan.register.store'), $this->payload([
                'org_name' => "PT Coba {$i}",
                'hr_email' => "coba{$i}@test.test",
                'hr_name' => "Nama {$i}",
            ]));
        }

        // request ke-6 dalam window yang sama → 429 (throttle:5,1)
        $this->post(route('perusahaan.register.store'), $this->payload([
            'org_name' => 'PT Coba 6',
            'hr_email' => 'coba6@test.test',
            'hr_name' => 'Nama 6',
        ]))->assertStatus(429);

        $this->assertSame(5, Perusahaan::count());
    }

    // ------------------------------------------------------------------
    // 2. Program Order printable
    // ------------------------------------------------------------------

    private function program(): CompanyWellbeingProgram
    {
        $this->seed(WellbeingPackageSeeder::class);
        $perusahaan = Perusahaan::create([
            'name' => 'PT Order Snapshot', 'brand' => 'OrderSnapshot',
            'employee_count' => 500, 'is_active' => true, 'status' => 'active',
        ]);
        $package = WellbeingPackage::where('code', 'growth')->first();
        $band = WellbeingPricingBand::where('wellbeing_package_id', $package->id)
            ->where('band_min', 401)->first();

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $perusahaan->id,
            'wellbeing_package_id' => $package->id,
            'pricing_band_id' => $band->id,
            'employee_band_size' => 500,
            'deep_support_mode' => 'mhcu',
            'starts_at' => now()->addDay()->toDateString(),
            'ends_at' => now()->addDay()->copy()->addDays(180)->subDay()->toDateString(),
            'expert_chat_quota' => 0,
            'deep_support_quota' => 15,
            'webinar_quota' => 3,
            'assessment_count' => 2,
            'monthly_price' => $band->monthly_price,
            'assessment_domains' => ['mh', 'nutrition'],
            'selected_employee_ids' => null,
            'status' => 'active',
        ]);
    }

    public function test_program_order_menampilkan_snapshot_kontrak()
    {
        $program = $this->program();

        $this->actingAs($this->admin())
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertOk()
            ->assertSee('Program Order')
            ->assertSee('PT Order Snapshot')
            ->assertSee('Growth')
            ->assertSee('401–600 karyawan')
            ->assertSee('Rp 20.499.999 + pajak')
            ->assertSee('500 karyawan')
            ->assertSee('15 sesi (MHCU)')
            ->assertSee('3 webinar')
            ->assertSee('Mental Health, Nutrition')
            ->assertSee('Semua karyawan')
            ->assertSee('window.print()');
    }

    public function test_program_order_tidak_bisa_diakses_non_admin()
    {
        $program = $this->program();
        $role = Role::firstOrCreate(['name' => 'hr_admin']);
        $hr = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($hr)
            ->get(route('admin.wellbeing-programs.order', $program))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // 3. Klinik checkpoint-40 view
    // ------------------------------------------------------------------

    private function makeCompletedSession(Klinik $klinik, string $paymentStatus = 'verified'): ConsultationSession
    {
        $consultant = Consultant::factory()->create();
        $package = ConsultantPackage::factory()->create(['consultant_id' => $consultant->id]);
        $schedule = Schedule::factory()->create(['consultant_id' => $consultant->id]);

        $session = ConsultationSession::create([
            'schedule_id' => $schedule->id,
            'consultant_id' => $consultant->id,
            'consultant_package_id' => $package->id,
            'klinik_id' => $klinik->id,
            'order_number' => 'WV3-' . uniqid(),
            'payment' => '250000',
            'amount' => 250000,
            'order_at' => now(),
            'payment_status' => $paymentStatus,
            'status' => 'scheduled',
        ]);
        $session->completion_verified_at = now();
        $session->status = 'done';
        $session->save();

        return $session;
    }

    private function klinikDenganSesi(int $selesai, int $nonaktif = 0): Klinik
    {
        $klinik = Klinik::create([
            'name' => "Klinik WV3 {$selesai}", 'brand' => "KlinikWV3 {$selesai}",
            'mou_at' => now()->subDay(), 'is_active' => true,
        ]);
        foreach (range(1, $selesai) as $i) {
            $this->makeCompletedSession($klinik);
        }
        foreach (range(1, $nonaktif) as $i) {
            $this->makeCompletedSession($klinik, ConsultationSession::PAYMENT_STATUS_REFUNDED);
        }

        return $klinik;
    }

    public function test_klinik_checkpoints_menampilkan_progress_dan_badge_tercapai()
    {
        $admin = $this->admin();
        $belum = $this->klinikDenganSesi(18);
        $tercapai = $this->klinikDenganSesi(42);

        $this->actingAs($admin)->get(route('admin.klinik-checkpoints'))
            ->assertOk()
            ->assertSee('Checkpoint 40 Konsultasi')
            ->assertSee('18 / 40')
            ->assertSee('42 / 40')
            ->assertSee('Checkpoint tercapai')
            ->assertSee('Berjalan');
    }

    public function test_klinik_checkpoints_menghitung_sesi_completed_verified_saja()
    {
        $admin = $this->admin();
        // 40 verified + 2 refunded → checkpoint tercapai (refund dikecualikan,
        // definisi sama dengan HapPervormaService::completedCounts).
        $klinik = $this->klinikDenganSesi(40, 2);

        $response = $this->actingAs($admin)->get(route('admin.klinik-checkpoints'));
        $response->assertOk()->assertSee('40 / 40');

        // klinik dengan hanya 2 sesi refunded → 0, tidak tercapai
        $kosong = $this->klinikDenganSesi(0, 2);
        $this->actingAs($admin)->get(route('admin.klinik-checkpoints'))
            ->assertOk()
            ->assertSee('0 / 40');
    }

    public function test_klinik_checkpoints_tidak_bisa_diakses_non_admin()
    {
        $role = Role::firstOrCreate(['name' => 'klinik_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.klinik-checkpoints'))
            ->assertForbidden();
    }

    public function test_kolom_terms_agreed_at_perusahaan_tersedia()
    {
        // migrasi wv3 aditif — kolom terms_agreed_at ada di perusahaans.
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('perusahaans', 'terms_agreed_at')
        );
    }
}
