<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard admin harus membedakan dua sumber flag needs_ops_review.
 * Tanpa ini ops cuma melihat daftar baris tanpa tahu tindakannya apa.
 */
class AdminOpsReviewDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $roleModel = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function flaggedSession(string $paymentStatus, ?string $doneAt, string $no): ConsultationSession
    {
        $klinik = Klinik::create(['name' => 'Klinik F', 'brand' => 'Klinik F', 'total_rate' => 1_000_000]);
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
            'payment_status' => $paymentStatus,
            'status' => 'canceled',
            'needs_ops_review' => true,
            'konsultan_done_at' => $doneAt,
        ]);
    }

    public function test_dashboard_menampilkan_alasan_pembayaran_setelah_sesi_ditutup()
    {
        $this->flaggedSession('canceled', null, 'OPS-X1');

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Pembayaran masuk setelah sesi ditutup', false)
            ->assertSee('OPS-X1');
    }

    public function test_dashboard_menampilkan_alasan_menunggu_konfirmasi()
    {
        $this->flaggedSession('verified', now()->subHours(30)->toDateTimeString(), 'OPS-X2');

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Menunggu konfirmasi', false)
            ->assertSee('OPS-X2');
    }

    public function test_dashboard_menampilkan_alasan_pada_sesi_refunded()
    {
        $this->flaggedSession('refunded', null, 'OPS-X3');

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Pembayaran masuk setelah sesi ditutup', false);
    }
}