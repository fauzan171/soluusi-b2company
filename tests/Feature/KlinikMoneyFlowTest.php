<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Schedule;
use App\Models\Klinik\HapPervorma;
use App\Services\Klinik\HapPervormaService;
use App\Services\Klinik\MarkupService;
use App\Services\Klinik\SplitSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B2B money-flow core: markup cap + split ledger + HAP 3-stage.
 * Non-trivial logic → ONE runnable check (Ponytail).
 */
class KlinikMoneyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSession(Klinik $klinik, int $net, int $markup): ConsultationSession
    {
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
            'payment_status' => 'verified',
            'status' => 'scheduled',
        ]);
    }

    public function test_markup_cap_per_price_range()
    {
        // Spec §2.2: 250-299K cap 10%, 300-349K 15%, 350-399K 20%, ≥400K 25%.
        $this->assertTrue(MarkupService::isValid(270_000, 299_000));   // margin 29K ≤ 10%×299K
        $this->assertFalse(MarkupService::isValid(250_000, 300_000));  // margin 50K > 15%×300K
        $this->assertTrue(MarkupService::isValid(300_000, 340_000));   // 40K ≤ 15%×340K=51K
        $this->assertTrue(MarkupService::isValid(300_000, 400_000));   // 100K = 25%×400K (boleh, ≤ cap)
        $this->assertFalse(MarkupService::isValid(300_000, 410_000));  // 110K > 25%×410K
        $this->assertFalse(MarkupService::isValid(300_000, 250_000));  // final < net
    }

    public function test_split_ledger_idempotent_dan_hap_3_stage_trigger()
    {
        $klinik = Klinik::create(['name' => 'Klinik T', 'brand' => 'Klinik T', 'total_rate' => 1_000_000]);

        // 5 sesi completed-verified → Stage 1 cair (flat 250K).
        foreach (range(1, 5) as $i) {
            $session = $this->makeSession($klinik, 250_000, 50_000);
            $split = SplitSettlementService::record($session);
            $this->assertEquals(250_000, $split->net_amount);
            $this->assertEquals(50_000, $split->klinik_margin);

            // Idempotent — webhook retry tidak double-row.
            $again = SplitSettlementService::record($session);
            $this->assertEquals($split->id, $again->id);

            $session->konsultan_done_at = now();
            $session->customer_confirmed_at = now();
            $session->completion_verified_at = now();
            $session->status = 'done';
            $session->save();
            HapPervormaService::evaluate($session);
        }

        $this->assertDatabaseCount('split_settlements', 5);
        $this->assertDatabaseHas('hap_pervormas', ['klinik_id' => $klinik->id, 'stage' => 1, 'amount' => 250_000]);
        $this->assertDatabaseMissing('hap_pervormas', ['klinik_id' => $klinik->id, 'stage' => 2]);

        // Re-evaluate → tidak double (unique klinik_id+stage).
        HapPervormaService::evaluate($session);
        $this->assertEquals(1, HapPervorma::where('klinik_id', $klinik->id)->count());
    }

    public function test_sesi_refund_tidak_menghitung_komisi_hap()
    {
        // completion_verified_at tidak pernah direset saat refund, jadi tanpa
        // filter payment_status klinik tetap membayar pervorma atas transaksi
        // yang uangnya sudah dikembalikan.
        $klinik = Klinik::create(['name' => 'Klinik RF', 'brand' => 'Klinik RF', 'total_rate' => 1_000_000]);

        $sessions = [];
        foreach (range(1, 5) as $i) {
            $s = $this->makeSession($klinik, 250_000, 50_000);
            $s->konsultan_done_at = now();
            $s->customer_confirmed_at = now();
            $s->completion_verified_at = now();
            $s->status = 'done';
            $s->save();
            $sessions[] = $s;
        }

        // 4 sesi sah + 1 direfund → tidak mencapai trigger 5, stage 1 batal cair.
        $sessions[4]->payment_status = ConsultationSession::PAYMENT_STATUS_REFUNDED;
        $sessions[4]->save();

        $created = HapPervormaService::evaluate($sessions[0]);

        $this->assertSame([], $created);
        $this->assertDatabaseMissing('hap_pervormas', ['klinik_id' => $klinik->id]);
        // Sesi yang direfund juga tidak di-tag ke stage mana pun.
        $this->assertNull($sessions[4]->fresh()->hap_pervorma_stage);

        // Sesi sah tetap dihitung (4 < 5 → belum cair, tapi ordinal tetap benar).
        $this->assertSame(1, $sessions[0]->fresh()->hap_pervorma_stage);
    }
}
