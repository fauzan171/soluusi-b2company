<?php

namespace Tests\Feature;

use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C5 — Associate dashboard multi-vertikal: tenant klinik/perusahaan/sekolah
 * tampil di satu dashboard; request payout generalized; ownership tetap 403.
 */
class AssociateMultiVerticalTest extends TestCase
{
    use RefreshDatabase;

    private function associate(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'associate'])->id]);
    }

    public function test_associate_sees_tenants_from_all_three_verticals(): void
    {
        $associate = $this->associate();

        $klinik = Klinik::create([
            'name' => 'PT Klinik Uji',
            'brand' => 'Klinik Uji',
            'associate_id' => $associate->id,
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $perusahaan = Perusahaan::create([
            'name' => 'PT Perusahaan Uji',
            'brand' => 'Perusahaan Uji',
            'associate_id' => $associate->id,
            'pepm_rate' => 15000,
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $sekolah = Sekolah::create([
            'name' => 'SMA Sekolah Uji',
            'brand' => 'Sekolah Uji',
            'associate_id' => $associate->id,
            'mou_at' => now()->subDay(),
            'is_active' => true,
        ]);

        HapPervorma::create([
            'klinik_id' => $klinik->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);
        PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 600_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);
        SekolahPervorma::create([
            'sekolah_id' => $sekolah->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 300_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            // 3 vertical headers + tenant names
            ->assertSee('Klinik referral HPA')
            ->assertSee('Perusahaan referral HPA')
            ->assertSee('Sekolah referral HPA')
            ->assertSee('Klinik Uji')
            ->assertSee('Perusahaan Uji')
            ->assertSee('Sekolah Uji')
            // Payout section: amounts per vertical terlihat
            ->assertSee('Rp250.000')
            ->assertSee('Rp600.000')
            ->assertSee('Rp300.000');
    }

    public function test_associate_can_request_payout_per_vertical(): void
    {
        $associate = $this->associate();

        $perusahaan = Perusahaan::create([
            'name' => 'PT Uji Payout',
            'brand' => 'UP',
            'associate_id' => $associate->id,
            'pepm_rate' => 15000,
            'is_active' => true,
        ]);
        $sekolah = Sekolah::create([
            'name' => 'SMA Uji Payout',
            'brand' => 'SUP',
            'associate_id' => $associate->id,
            'is_active' => true,
        ]);

        $capPervorma = PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 600_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);
        $sapPervorma = SekolahPervorma::create([
            'sekolah_id' => $sekolah->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 300_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'cap', 'pervorma' => $capPervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNotNull($capPervorma->fresh()->requested_at);
        $this->assertSame($associate->id, $capPervorma->fresh()->requested_by);

        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'sap', 'pervorma' => $sapPervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNotNull($sapPervorma->fresh()->requested_at);
    }

    public function test_request_payout_for_other_associate_pervorma_returns_403(): void
    {
        $associate = $this->associate();
        $otherAssociate = $this->associate();

        $klinik = Klinik::create([
            'name' => 'PT Klinik Lain',
            'brand' => 'KL',
            'associate_id' => $otherAssociate->id,
            'is_active' => true,
        ]);
        $perusahaan = Perusahaan::create([
            'name' => 'PT Lain',
            'brand' => 'PL',
            'associate_id' => $otherAssociate->id,
            'pepm_rate' => 15000,
            'is_active' => true,
        ]);
        $sekolah = Sekolah::create([
            'name' => 'SMA Lain',
            'brand' => 'SL',
            'associate_id' => $otherAssociate->id,
            'is_active' => true,
        ]);

        $hapPervorma = HapPervorma::create([
            'klinik_id' => $klinik->id,
            'associate_id' => $otherAssociate->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);
        $capPervorma = PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id,
            'associate_id' => $otherAssociate->id,
            'stage' => 1,
            'amount' => 600_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);
        $sapPervorma = SekolahPervorma::create([
            'sekolah_id' => $sekolah->id,
            'associate_id' => $otherAssociate->id,
            'stage' => 1,
            'amount' => 300_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        // Pervorma milik associate LAIN → 403 untuk ketiga vertikal.
        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'hap', 'pervorma' => $hapPervorma->id]))
            ->assertForbidden();
        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'cap', 'pervorma' => $capPervorma->id]))
            ->assertForbidden();
        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'sap', 'pervorma' => $sapPervorma->id]))
            ->assertForbidden();

        // Vertical tidak dikenal → 404.
        $this->actingAs($associate)
            ->post(route('associate.pervormas.request-payout', ['vertical' => 'xxx', 'pervorma' => $hapPervorma->id]))
            ->assertNotFound();
    }
}
