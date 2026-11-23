<?php

namespace Tests\Feature;

use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// B6: simpan versi S&K saat registrasi (perusahaan + klinik) dan
// field wajib Jabatan pendaftar di form perusahaan.
class RegistrationTermsVersionTest extends TestCase
{
    use RefreshDatabase;

    private function perusahaanPayload(array $over = []): array
    {
        return array_merge([
            'org_name' => 'PT Andalan Utama',
            'hr_email' => 'hr@andalan.example.com',
            'hr_name' => 'Budi Santoso',
            'employee_band' => '151-400',
            'phone' => '081234567890',
            'package_code' => 'growth',
            'terms' => '1',
        ], $over);
    }

    public function test_registrasi_perusahaan_tanpa_jabatan_ditolak()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        $this->from(route('perusahaan.register'))
            ->post(route('perusahaan.register.store'), $this->perusahaanPayload(['registrant_position' => null]))
            ->assertRedirect(route('perusahaan.register'))
            ->assertSessionHasErrors('registrant_position');

        $this->assertDatabaseMissing('perusahaans', ['name' => 'PT Andalan Utama']);
    }

    public function test_registrasi_perusahaan_menyimpan_jabatan_dan_versi_sk()
    {
        Role::firstOrCreate(['name' => 'hr_admin']);

        $this->post(route('perusahaan.register.store'), $this->perusahaanPayload([
            'registrant_position' => 'HR Manager',
        ]))->assertRedirect(route('loginIndex'));

        $perusahaan = Perusahaan::where('name', 'PT Andalan Utama')->first();
        $this->assertNotNull($perusahaan);
        $this->assertSame('HR Manager', $perusahaan->registrant_position);
        $this->assertSame(config('terms.perusahaan_version'), $perusahaan->terms_version);
        $this->assertNotNull($perusahaan->terms_agreed_at);
    }

    public function test_registrasi_klinik_menyimpan_versi_sk()
    {
        Role::firstOrCreate(['name' => 'klinik_admin']);
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI JAKARTA']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'KOTA JAKARTA SELATAN']);

        $this->post('/daftar-mitra', [
            'org_name' => 'Klinik Sehat Prima',
            'brand' => 'SehatPrima',
            'facility_type' => 'klinik_pratama',
            'regency_id' => '3171',
            'service_interest' => 'psikolog',
            'pic_name' => 'Citra Lestari',
            'email' => 'citra@sehatprima.example.com',
            'phone' => '081234567890',
            'password' => 'SI_SECRET_PASSWORD_XK3QMP7D',
            'password_confirmation' => 'SI_SECRET_PASSWORD_XK3QMP7D',
            'terms' => '1',
        ])->assertRedirect();

        $klinik = Klinik::where('name', 'Klinik Sehat Prima')->first();
        $this->assertNotNull($klinik);
        $this->assertSame(config('terms.klinik_version'), $klinik->terms_version);
        $this->assertNotNull($klinik->terms_agreed_at);
    }
}
