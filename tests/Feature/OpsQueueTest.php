<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A7 — Ops work queue terpadu: satu halaman read-only gabungan 4 antrean
 * (sesi ops review, klinik pending approval, pervorma payout due,
 * aktivasi perusahaan/sekolah), filter ?type=, aksi link ke route existing.
 */
class OpsQueueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $roleModel = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function flaggedSession(string $no): ConsultationSession
    {
        $klinik = Klinik::create(['name' => 'Klinik Q', 'brand' => 'Klinik Q', 'total_rate' => 1_000_000]);
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => 250_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);

        return ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'order_number' => $no,
            'payment' => '0',
            'amount' => 300_000,
            'order_at' => now(),
            'payment_status' => 'waiting',
            'status' => 'done',
            'needs_ops_review' => true,
            'konsultan_done_at' => now()->subHours(30),
        ]);
    }

    /** Fixture 4 tipe antrean → semua tampil di /admin/ops-queue. */
    public function test_semua_tipe_antrean_muncul()
    {
        $session = $this->flaggedSession('OPSQ-1');
        $klinikPending = Klinik::create(['name' => 'Klinik Baru', 'brand' => 'Klinik Baru', 'total_rate' => 1_000_000, 'is_active' => false]);
        $pervorma = HapPervorma::create([
            'klinik_id' => $session->klinik_id,
            'associate_id' => $this->admin()->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now(),
        ]);
        $perusahaanPending = Perusahaan::create(['name' => 'PT Baru', 'brand' => 'PT Baru', 'is_active' => false]);
        $sekolahPending = Sekolah::create(['name' => 'Sekolah Baru', 'brand' => 'Sekolah Baru', 'is_active' => false]);

        $res = $this->actingAs($this->admin())->get('/admin/ops-queue');

        $res->assertOk()
            ->assertSee('OPSQ-1')
            ->assertSee('Klinik Baru')
            ->assertSee('Stage 1')
            ->assertSee('PT Baru')
            ->assertSee('Sekolah Baru');
    }

    /** Filter ?type= hanya menampilkan satu tipe. */
    public function test_filter_type_membatasi_tampilan()
    {
        $session = $this->flaggedSession('OPSQ-2');
        Klinik::create(['name' => 'Klinik pending', 'brand' => 'Klinik pending', 'total_rate' => 1_000_000, 'is_active' => false]);
        Perusahaan::create(['name' => 'PT pending', 'brand' => 'PT pending', 'is_active' => false]);

        // type=sessions → hanya sesi, tanpa tenant pending.
        $this->actingAs($this->admin())
            ->get('/admin/ops-queue?type=sessions')
            ->assertOk()
            ->assertSee('OPSQ-2')
            ->assertDontSee('PT pending');

        // type=perusahaan → hanya PT pending, tanpa sesi.
        $this->actingAs($this->admin())
            ->get('/admin/ops-queue?type=perusahaan')
            ->assertOk()
            ->assertSee('PT pending')
            ->assertDontSee('OPSQ-2');
    }

    /** Aksi tombol link ke route existing — tidak ada write path baru. */
    public function test_aksi_menggunakan_route_existing()
    {
        $session = $this->flaggedSession('OPSQ-3');
        $klinikPending = Klinik::create(['name' => 'Klinik K', 'brand' => 'Klinik K', 'total_rate' => 1_000_000, 'is_active' => false]);
        $pervorma = HapPervorma::create([
            'klinik_id' => $session->klinik_id, 'stage' => 2, 'amount' => 250_000, 'status' => 'due', 'due_at' => now(),
        ]);
        $perusahaanPending = Perusahaan::create(['name' => 'PT A', 'brand' => 'PT A', 'is_active' => false]);
        $sekolahPending = Sekolah::create(['name' => 'Sekolah B', 'brand' => 'Sekolah B', 'is_active' => false]);

        $html = $this->actingAs($this->admin())->get('/admin/ops-queue')->getContent();

        $this->assertStringContainsString(route('admin.sessions.verify', $session), $html);
        $this->assertStringContainsString(route('admin.kliniks.approve', $klinikPending), $html);
        $this->assertStringContainsString(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]), $html);
        $this->assertStringContainsString(route('admin.perusahaans.activate', $perusahaanPending), $html);
        $this->assertStringContainsString(route('admin.sekolahs.activate', $sekolahPending), $html);
    }

    /** Badge count di admin.dashboard = total item antrean. */
    public function test_badge_count_dashboard_sama_dengan_total_antrean()
    {
        // Queue kosong → badge 0 dan tidak render elemen badge.
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('ops-queue-badge-zero');

        // 2 item antrean (1 sesi + 1 klinik pending) → badge 2.
        $session = $this->flaggedSession('OPSQ-4');
        Klinik::create(['name' => 'Klinik L', 'brand' => 'Klinik L', 'total_rate' => 1_000_000, 'is_active' => false]);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Ops antrean: 2');
    }

    /** Non-admin tidak boleh akses. */
    public function test_non_admin_ditolak()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/ops-queue')->assertStatus(403);
    }
}
