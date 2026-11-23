<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Schedule;
use App\Services\Klinik\HapPervormaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA10-F3 claw-back: refund sesi menurunkan completedCounts → stage HAP yang
 * tak lagi tercapai dibatalkan; stage yang masih tercapai tetap due; refund
 * sesi pra-MoU (tak pernah dihitung) tidak mengubah apa pun.
 *
 * Refund lewat webhook Midtrans asli (signature + gross_amount match) supaya
 * jalur produksi yang diuji, bukan pemanggilan service langsung.
 */
class HapPervormaClawbackTest extends TestCase
{
    use RefreshDatabase;

    private string $key = 'uji-server-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['midtrans.server_key' => $this->key]);
    }

    private function makeKlinik(): Klinik
    {
        return Klinik::create([
            'name' => 'Klinik Clawback',
            'brand' => 'Klinik Clawback',
            'total_rate' => 1_000_000,
            'mou_at' => now()->subDays(10),
            'is_active' => true,
        ]);
    }

    /**
     * Sesi completed-verified milik klinik. $verifiedAt null = sekarang
     * (dalam window MoU); set tanggal lama untuk sesi pra-MoU.
     */
    private function makeVerifiedSession(Klinik $klinik, ?string $verifiedAt = null): ConsultationSession
    {
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => 250_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);

        $session = ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'order_number' => 'CB-' . uniqid(),
            'payment' => '0',
            'amount' => 300_000,
            'order_at' => now(),
            'payment_status' => 'waiting',
            'status' => 'waiting',
            'completion_verified_at' => $verifiedAt ?? now(),
        ]);

        return $session;
    }

    private function notify(ConsultationSession $session, string $status): \Illuminate\Testing\TestResponse
    {
        $amount = (string) $session->amount;
        return $this->postJson('/api/midtrans-callback', [
            'order_id' => $session->order_number,
            'status_code' => '200',
            'gross_amount' => $amount,
            'signature_key' => hash('sha512', $session->order_number . '200' . $amount . $this->key),
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'transaction_id' => 'trx-' . $status . '-' . uniqid(),
            'transaction_time' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'bank_transfer',
        ]);
    }

    public function test_refund_menurunkan_count_di_bawah_threshold_membatalkan_stage(): void
    {
        $klinik = $this->makeKlinik();

        // 5 sesi verified → stage 1 (trigger 5) cair.
        $sessions = [];
        foreach (range(1, 5) as $i) {
            $sessions[$i] = $this->makeVerifiedSession($klinik);
            HapPervormaService::evaluate($sessions[$i]);
        }
        $stage1 = HapPervorma::where('klinik_id', $klinik->id)->where('stage', 1)->sole();
        $this->assertSame('due', $stage1->status);

        // Refund satu sesi → count 4 < trigger 5 → stage 1 dibatalkan + audit.
        $this->notify($sessions[5], 'refund')->assertOk();

        $this->assertSame('cancelled', $stage1->fresh()->status);
        $this->assertSame(1, \App\Models\AuditLog::where('action', 'hap.payout_cancelled')->count());
        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => 'hap_pervorma',
            'subject_id' => $stage1->id,
        ]);
    }

    public function test_refund_dengan_count_masih_di_atas_threshold_tetap_due(): void
    {
        $klinik = $this->makeKlinik();

        // 6 sesi → stage 1 cair; refund 1 → count 5 masih >= trigger.
        $sessions = [];
        foreach (range(1, 6) as $i) {
            $sessions[$i] = $this->makeVerifiedSession($klinik);
            HapPervormaService::evaluate($sessions[$i]);
        }
        $stage1 = HapPervorma::where('klinik_id', $klinik->id)->where('stage', 1)->sole();
        $this->assertSame('due', $stage1->status);

        $this->notify($sessions[6], 'refund')->assertOk();

        $this->assertSame('due', $stage1->fresh()->status);
        $this->assertSame(0, \App\Models\AuditLog::where('action', 'hap.payout_cancelled')->count());
    }

    public function test_refund_hanya_membatalkan_stage_yang_tidak_lagi_tercapai(): void
    {
        $klinik = $this->makeKlinik();

        // 45 sesi → stage 1, 2, 3 cair.
        $sessions = [];
        foreach (range(1, 45) as $i) {
            $sessions[$i] = $this->makeVerifiedSession($klinik);
        }
        HapPervormaService::evaluate($sessions[45]);
        $this->assertSame(3, HapPervorma::where('klinik_id', $klinik->id)->count());
        $this->assertSame(3, HapPervorma::where('klinik_id', $klinik->id)->where('status', 'due')->count());

        // Refund 7 sesi → count 38 < trigger stage 3 (40), stage 1+2 tetap due.
        foreach (range(39, 45) as $i) {
            $this->notify($sessions[$i], 'refund')->assertOk();
        }

        $this->assertSame('cancelled', HapPervorma::where('klinik_id', $klinik->id)->where('stage', 3)->sole()->status);
        $this->assertSame('due', HapPervorma::where('klinik_id', $klinik->id)->where('stage', 1)->sole()->status);
        $this->assertSame('due', HapPervorma::where('klinik_id', $klinik->id)->where('stage', 2)->sole()->status);
    }

    public function test_refund_sesi_pra_mou_tidak_mengubah_apapun(): void
    {
        $klinik = $this->makeKlinik();

        // 5 sesi dalam window (stage 1 cair) + 1 sesi pra-MoU (tak pernah dihitung).
        foreach (range(1, 5) as $i) {
            $session = $this->makeVerifiedSession($klinik);
            HapPervormaService::evaluate($session);
        }
        $preMou = $this->makeVerifiedSession($klinik, now()->subDays(30)->format('Y-m-d H:i:s'));
        $stage1 = HapPervorma::where('klinik_id', $klinik->id)->where('stage', 1)->sole();
        $this->assertSame('due', $stage1->status);

        $this->notify($preMou, 'refund')->assertOk();

        // completedCounts tidak berubah (sesi pra-MoU memang dikecualikan).
        $this->assertSame('due', $stage1->fresh()->status);
        $this->assertSame(0, \App\Models\AuditLog::where('action', 'hap.payout_cancelled')->count());
    }

    public function test_clawback_idempotent_dan_paid_tidak_disentuh(): void
    {
        $klinik = $this->makeKlinik();

        foreach (range(1, 5) as $i) {
            $session = $this->makeVerifiedSession($klinik);
            HapPervormaService::evaluate($session);
        }
        $stage1 = HapPervorma::where('klinik_id', $klinik->id)->where('stage', 1)->sole();
        $stage1->update(['status' => 'paid', 'paid_at' => now()]);

        $session = ConsultationSession::where('klinik_id', $klinik->id)->latest('id')->first();
        $this->notify($session, 'refund')->assertOk();
        $this->notify($session, 'refund')->assertOk();

        // Payout sudah dibayar — immutable, claw-back out of scope (ops manual).
        $this->assertSame('paid', $stage1->fresh()->status);
    }
}
