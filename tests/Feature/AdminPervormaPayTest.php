<?php

namespace Tests\Feature;

use App\Models\AdminAction;
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
 * C6 — Admin pervorma pay 3 vertikal: route {vertical}/{pervorma} menangani
 * hap/sap/cap; pay ter-audit + idempotent (double-pay ditolak); filter
 * ?vertical= pada admin list (pervormaSessions) berfungsi.
 */
class AdminPervormaPayTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    private function associate(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'associate'])->id]);
    }

    public function test_pay_hap_pervorma_ter_audit_dan_status_paid(): void
    {
        $admin = $this->admin();
        $klinik = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU', 'total_rate' => 1_000_000]);
        $pervorma = HapPervorma::create([
            'klinik_id' => $klinik->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('paid', $pervorma->fresh()->status);
        $this->assertNotNull($pervorma->fresh()->paid_at);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'pervorma.pay',
            'target_id' => $pervorma->id,
        ]);
        $row = AdminAction::where('action', 'pervorma.pay')->latest('id')->first();
        $this->assertSame('hap', $row->payload_json['vertical'] ?? null);
    }

    public function test_pay_sap_pervorma_ter_audit_dan_status_paid(): void
    {
        $admin = $this->admin();
        $associate = $this->associate();
        $sekolah = Sekolah::create([
            'name' => 'SMA Uji', 'brand' => 'SU', 'associate_id' => $associate->id, 'is_active' => true,
        ]);
        $pervorma = SekolahPervorma::create([
            'sekolah_id' => $sekolah->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 300_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'sap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('paid', $pervorma->fresh()->status);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'pervorma.pay',
            'target_id' => $pervorma->id,
        ]);
        $row = AdminAction::where('action', 'pervorma.pay')->latest('id')->first();
        $this->assertSame('sap', $row->payload_json['vertical'] ?? null);
    }

    public function test_pay_cap_pervorma_ter_audit_dan_status_paid(): void
    {
        $admin = $this->admin();
        $associate = $this->associate();
        $perusahaan = Perusahaan::create([
            'name' => 'PT Uji', 'brand' => 'PU', 'associate_id' => $associate->id, 'pepm_rate' => 15000, 'is_active' => true,
        ]);
        $pervorma = PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 600_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'cap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('paid', $pervorma->fresh()->status);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'pervorma.pay',
            'target_id' => $pervorma->id,
        ]);
        $row = AdminAction::where('action', 'pervorma.pay')->latest('id')->first();
        $this->assertSame('cap', $row->payload_json['vertical'] ?? null);
    }

    public function test_double_pay_ditolak_422(): void
    {
        $admin = $this->admin();
        $associate = $this->associate();
        $perusahaan = Perusahaan::create([
            'name' => 'PT X', 'brand' => 'PX', 'associate_id' => $associate->id, 'pepm_rate' => 15000, 'is_active' => true,
        ]);
        $pervorma = PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id,
            'associate_id' => $associate->id,
            'stage' => 1,
            'amount' => 600_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'cap', 'pervorma' => $pervorma->id]))
            ->assertRedirect();

        // Bayar kedua kali → 422, paid_at tidak berubah, tidak ada audit row baru.
        $firstPaidAt = $pervorma->fresh()->paid_at;
        $auditCountBefore = AdminAction::where('action', 'pervorma.pay')->count();

        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'cap', 'pervorma' => $pervorma->id]))
            ->assertStatus(422);

        $this->assertEquals($firstPaidAt, $pervorma->fresh()->paid_at);
        $this->assertSame($auditCountBefore, AdminAction::where('action', 'pervorma.pay')->count());
    }

    public function test_unknown_vertical_404(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->post(route('admin.pervormas.pay', ['vertical' => 'xyz', 'pervorma' => 1]))
            ->assertNotFound();
    }

    public function test_pervorma_sessions_filter_vertical_hap_default(): void
    {
        $admin = $this->admin();
        // Default ?vertical= kosong → mode hap, tidak error.
        $this->actingAs($admin)
            ->get(route('admin.pervorma-sessions'))
            ->assertOk();
    }

    public function test_pervorma_sessions_filter_vertical_sap_menampilkan_hanya_sap(): void
    {
        $admin = $this->admin();
        $associate = $this->associate();

        $sekolah = Sekolah::create([
            'name' => 'SMA A', 'brand' => 'SAPSchool', 'associate_id' => $associate->id, 'is_active' => true,
        ]);
        $perusahaan = Perusahaan::create([
            'name' => 'PT B', 'brand' => 'CAPCo', 'associate_id' => $associate->id, 'pepm_rate' => 15000, 'is_active' => true,
        ]);

        SekolahPervorma::create([
            'sekolah_id' => $sekolah->id, 'associate_id' => $associate->id,
            'stage' => 1, 'amount' => 300_000, 'status' => 'due', 'due_at' => now()->addWeek(),
        ]);
        PerusahaanPervorma::create([
            'perusahaan_id' => $perusahaan->id, 'associate_id' => $associate->id,
            'stage' => 1, 'amount' => 600_000, 'status' => 'due', 'due_at' => now()->addWeek(),
        ]);

        // SAP view: row SAP Rp300.000 tampil; row CAP Rp600.000 tidak (layout
        // tenant-switcher bisa menampilkan SEMUA tenant brand — pakai amount
        // sebagai pembeda karena amount unik per row).
        $this->actingAs($admin)
            ->get(route('admin.pervorma-sessions', ['vertical' => 'sap']))
            ->assertOk()
            ->assertSee('Rp300.000')
            ->assertDontSee('Rp600.000');

        // CAP view: sebaliknya.
        $this->actingAs($admin)
            ->get(route('admin.pervorma-sessions', ['vertical' => 'cap']))
            ->assertOk()
            ->assertSee('Rp600.000')
            ->assertDontSee('Rp300.000');
    }

    public function test_pervorma_sessions_filter_status(): void
    {
        $admin = $this->admin();
        $associate = $this->associate();
        $sekolah = Sekolah::create([
            'name' => 'SMA B', 'brand' => 'SB', 'associate_id' => $associate->id, 'is_active' => true,
        ]);

        SekolahPervorma::create([
            'sekolah_id' => $sekolah->id, 'associate_id' => $associate->id,
            'stage' => 1, 'amount' => 300_000, 'status' => 'due', 'due_at' => now()->addWeek(),
        ]);
        SekolahPervorma::create([
            'sekolah_id' => $sekolah->id, 'associate_id' => $associate->id,
            'stage' => 2, 'amount' => 400_000, 'status' => 'paid', 'due_at' => now()->addWeek(), 'paid_at' => now(),
        ]);

        // status=paid → hanya row paid yang terlihat.
        $this->actingAs($admin)
            ->get(route('admin.pervorma-sessions', ['vertical' => 'sap', 'status' => 'paid']))
            ->assertOk()
            ->assertSee('Rp400.000')
            ->assertDontSee('Rp300.000');
    }
}
