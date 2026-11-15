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
 * Dummy payment routes (uang tidak nyata) wajib 404 di production (T1.4).
 */
class DummyPaymentGateTest extends TestCase
{
    use RefreshDatabase;

    private function dummyLink(): CheckoutLink
    {
        $klinik = Klinik::create(['name' => 'Klinik G', 'brand' => 'Klinik G', 'total_rate' => 1_000_000]);
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
            'expires_at' => now()->addDay(),
        ]);

        ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'checkout_link_id' => $link->id,
            'order_number' => 'T-' . uniqid(),
            'payment' => '0',
            'amount' => 275_000,
            'order_at' => now(),
            'payment_status' => ConsultationSession::PAYMENT_STATUS_WAITING,
            'status' => ConsultationSession::STATUS_WAITING,
        ]);

        return $link;
    }

    public function test_dummy_payment_404_di_production()
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $link = $this->dummyLink();

        $this->get(route('checkout.dummyPayment', $link->token))->assertNotFound();
        $this->post(route('checkout.dummyPayment.success', $link->token))->assertNotFound();
    }

    public function test_dummy_payment_200_di_local()
    {
        $this->app['env'] = 'local';
        $link = $this->dummyLink();

        $this->get(route('checkout.dummyPayment', $link->token))
            ->assertOk()
            ->assertSee('Pembayaran Dummy');
    }
}
