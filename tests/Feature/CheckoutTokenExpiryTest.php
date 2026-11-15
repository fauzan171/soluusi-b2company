<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0.1: token checkout (status page + confirm-done) bukan bearer permanen.
 * Setelah checkout_links.expires_at lewat, token tidak boleh lagi membuka PII guest.
 */
class CheckoutTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(\DateTimeInterface $expiresAt): CheckoutLink
    {
        $klinik = Klinik::create(['name' => 'Klinik E', 'brand' => 'Klinik E', 'total_rate' => 1_000_000]);
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => 250_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);
        $link = CheckoutLink::create([
            'token' => CheckoutLink::generateToken(),
            'klinik_id' => $klinik->id,
            'consultant_id' => $konsultan->id,
            'schedule_id' => $sched->id,
            'consultant_package_id' => $pkg->id,
            'net_price' => 250_000,
            'markup_amount' => 25_000,
            'final_price' => 275_000,
            'used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'checkout_link_id' => $link->id,
            'order_number' => 'T-' . uniqid(),
            'payment' => '275000',
            'amount' => 275_000,
            'order_at' => now(),
            'payment_status' => ConsultationSession::PAYMENT_STATUS_VERIFIED,
            'status' => ConsultationSession::STATUS_SCHEDULED,
        ]);

        return $link;
    }

    public function test_status_page_gone_setelah_token_expired()
    {
        $link = $this->makeLink(now()->subMinute());

        $this->get(route('checkout.status', $link->token))->assertStatus(410);
    }

    public function test_confirm_done_gone_setelah_token_expired()
    {
        $link = $this->makeLink(now()->subMinute());

        $this->post(route('checkout.confirmDone', $link->token))->assertStatus(410);
    }

    public function test_status_page_200_untuk_token_valid()
    {
        $link = $this->makeLink(now()->addHour());

        $this->get(route('checkout.status', $link->token))->assertOk();
    }

    public function test_confirm_done_200_untuk_token_valid()
    {
        $link = $this->makeLink(now()->addHour());

        $this->post(route('checkout.confirmDone', $link->token))
            ->assertSessionHas('success');
    }
}
