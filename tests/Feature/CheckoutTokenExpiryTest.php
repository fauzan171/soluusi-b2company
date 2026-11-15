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
 * P0.1: token checkout (status page + confirm-done) bukan bearer permanen untuk
 * PII guest TANPA sesi berbayar. Link expired + unpaid → 410. Namun link yang
 * sudah menghasilkan sesi BERBAYAR tetap bisa dibuka (dual-confirm §9.1:
 * sesi bisa terjadi hari-kedua, jauh setelah expiry 1 jam — customer harus
 * tetap bisa confirm-done).
 */
class CheckoutTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(\DateTimeInterface $expiresAt, string $paymentStatus = ConsultationSession::PAYMENT_STATUS_VERIFIED): CheckoutLink
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
            'payment' => $paymentStatus === ConsultationSession::PAYMENT_STATUS_VERIFIED ? '275000' : '0',
            'amount' => 275_000,
            'order_at' => now(),
            'payment_status' => $paymentStatus,
            'status' => $paymentStatus === ConsultationSession::PAYMENT_STATUS_VERIFIED
                ? ConsultationSession::STATUS_SCHEDULED
                : ConsultationSession::STATUS_WAITING,
        ]);

        return $link;
    }

    public function test_status_page_gone_setelah_token_expired_tanpa_pembayaran()
    {
        $link = $this->makeLink(now()->subMinute(), ConsultationSession::PAYMENT_STATUS_WAITING);

        $this->get(route('checkout.status', $link->token))->assertStatus(410);
    }

    public function test_confirm_done_gone_setelah_token_expired_tanpa_pembayaran()
    {
        $link = $this->makeLink(now()->subMinute(), ConsultationSession::PAYMENT_STATUS_WAITING);

        $this->post(route('checkout.confirmDone', $link->token))->assertStatus(410);
    }

    public function test_status_page_ok_setelah_token_expired_dengan_sesi_berbayar()
    {
        $link = $this->makeLink(now()->subMinute());

        $this->get(route('checkout.status', $link->token))->assertOk();
    }

    public function test_confirm_done_ok_setelah_token_expired_dengan_sesi_berbayar()
    {
        $link = $this->makeLink(now()->subMinute());

        $this->post(route('checkout.confirmDone', $link->token))
            ->assertSessionHas('success');
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
