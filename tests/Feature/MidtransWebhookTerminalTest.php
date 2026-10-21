<?php

namespace Tests\Feature;

use App\Http\Controllers\MidtransController;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\SplitSettlement;
use App\Models\Schedule;
use App\Services\Klinik\SplitSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Webhook Midtrans vs sesi yang sudah terminal (expire lalu settlement, refund).
 *
 * Dua bug produksi yang ditutup di sini:
 * - settlement atas sesi canceled menghidupkan kembali sesi yang slotnya sudah dilepas;
 * - refund tidak dikenal sama sekali, sehingga ledger tetap mengakui hutang split.
 */
class MidtransWebhookTerminalTest extends TestCase
{
    use RefreshDatabase;

    private string $key = 'uji-server-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['midtrans.server_key' => $this->key]);
    }

    private function makeSession(int $net = 250_000, int $markup = 50_000): ConsultationSession
    {
        $klinik = Klinik::create(['name' => 'Klinik U', 'brand' => 'Klinik U', 'total_rate' => 1_000_000]);
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => $net]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);
        $link = CheckoutLink::create([
            'token' => CheckoutLink::generateToken(),
            'klinik_id' => $klinik->id,
            'consultant_id' => $konsultan->id,
            'schedule_id' => $sched->id,
            'consultant_package_id' => $pkg->id,
            'net_price' => $net,
            'markup_amount' => $markup,
            'final_price' => $net + $markup,
            'expires_at' => now()->addDay(),
        ]);

        return ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'checkout_link_id' => $link->id,
            'order_number' => 'T-' . uniqid(),
            'payment' => '0',
            'amount' => $net + $markup,
            'order_at' => now(),
            'payment_status' => 'waiting',
            'status' => 'waiting',
        ]);
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
            'transaction_id' => 'trx-' . $status,
            'transaction_time' => '2026-10-21 02:00:00',
            'payment_type' => 'bank_transfer',
        ]);
    }

    public function test_settlement_atas_sesi_expired_tidak_menghidupkan_kembali_sesi()
    {
        $session = $this->makeSession();

        $this->notify($session, 'expire')->assertOk();
        $this->assertSame('canceled', $session->fresh()->status);

        // Notifikasi settlement susulan: slot sudah dilepas, sesi tidak boleh hidup lagi.
        $this->notify($session, 'settlement')->assertOk();

        $fresh = $session->fresh();
        $this->assertSame('canceled', $fresh->payment_status, 'status pembayaran tidak boleh kembali verified');
        $this->assertSame('canceled', $fresh->status, 'sesi tidak boleh kembali scheduled');
        $this->assertTrue((bool) $fresh->needs_ops_review, 'harus ditandai untuk review ops');
    }

    public function test_refund_membalik_ledger_dan_menandai_sesi_refunded()
    {
        $session = $this->makeSession();
        $this->notify($session, 'settlement')->assertOk();
        $this->assertDatabaseHas('split_settlements', ['session_id' => $session->id, 'status' => 'pending']);

        $this->notify($session, 'refund')->assertOk();

        $fresh = $session->fresh();
        $this->assertSame('refunded', $fresh->payment_status);
        $this->assertSame('canceled', $fresh->status);
        $this->assertSame('0', (string) $fresh->payment);
        $this->assertDatabaseHas('split_settlements', ['session_id' => $session->id, 'status' => 'reversed']);
    }

    public function test_refund_idempotent_dan_tidak_menggandakan_ledger()
    {
        $session = $this->makeSession();
        $this->notify($session, 'settlement')->assertOk();

        $this->notify($session, 'refund')->assertOk();
        $this->notify($session, 'refund')->assertOk();

        $this->assertSame(1, SplitSettlement::where('session_id', $session->id)->count());
        $this->assertSame('reversed', SplitSettlement::where('session_id', $session->id)->value('status'));
    }

    public function test_reverse_mengembalikan_null_saat_belum_ada_ledger()
    {
        // Refund sebelum settlement: tidak ada baris ledger, tidak boleh error.
        $session = $this->makeSession();
        $this->assertNull(SplitSettlementService::reverse($session));

        $this->notify($session, 'refund')->assertOk();
        $this->assertSame('refunded', $session->fresh()->payment_status);
        $this->assertSame(0, SplitSettlement::where('session_id', $session->id)->count());
    }

    public function test_ledger_reversed_tidak_dihitung_sebagai_settled()
    {
        $session = $this->makeSession();
        $this->notify($session, 'settlement')->assertOk();
        $this->notify($session, 'refund')->assertOk();

        $this->assertSame(0, SplitSettlement::where('status', 'settled')->count());
        $this->assertSame(1, SplitSettlement::where('status', 'reversed')->count());
    }
}