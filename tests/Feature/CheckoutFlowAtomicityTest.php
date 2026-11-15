<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Schedule;
use App\Services\Klinik\CheckoutFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T4.2: reserveSession race-safe — dua pemanggilan berurutan (model untuk dua
 * request bersamaan; claimLink atomic WHERE used_at IS NULL) atas link yang
 * sama menghasilkan TEPAT satu session dan link terklaim sekali.
 */
class CheckoutFlowAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $klinik = Klinik::create(['name' => 'Klinik A', 'brand' => 'Klinik A', 'total_rate' => 1_000_000]);
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
            'expires_at' => now()->addHour(),
        ]);

        return [$link, $pkg, $sched];
    }

    private function guestData(): array
    {
        return [
            'customer_name' => 'Tamu Uji',
            'customer_email' => 'tamu@example.test',
            'customer_phone' => '081200000000',
            'customer_dob' => '1990-01-01',
            'customer_regency_id' => '3171', // kolom char(4), FK tidak ditegakkan di layer ini
            'customer_gender' => 'male',
        ];
    }

    public function test_double_submit_link_sama_hanya_satu_sesi_tercipta()
    {
        [$link, $pkg, $sched] = $this->fixtures();
        $data = $this->guestData();

        $first = CheckoutFlow::reserveSession($data, $link, $pkg, 'KB-' . $link->id . '-A', 275_000, null, 0, 'https://pay.test/a');
        $second = CheckoutFlow::reserveSession($data, $link->fresh(), $pkg, 'KB-' . $link->id . '-B', 275_000, null, 0, 'https://pay.test/b');

        $this->assertNotNull($first, 'Pemanggilan pertama harus sukses');
        $this->assertNull($second, 'Pemanggilan kedua harus gagal (link terklaim / slot terkunci)');
        $this->assertSame(1, ConsultationSession::where('schedule_id', $sched->id)->count());
        $this->assertNotNull($link->fresh()->used_at);
    }

    public function test_reserve_gagal_ketika_slot_sudah_blocking_tanpa_membakar_link()
    {
        [$link, $pkg, $sched] = $this->fixtures();

        ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $link->consultant_id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $link->klinik_id,
            'order_number' => 'X-1',
            'payment' => '0',
            'amount' => 275_000,
            'order_at' => now(),
            'payment_status' => ConsultationSession::PAYMENT_STATUS_WAITING,
            'status' => ConsultationSession::STATUS_WAITING,
        ]);

        $result = CheckoutFlow::reserveSession($this->guestData(), $link, $pkg, 'KB-' . $link->id . '-C', 275_000, null, 0, 'https://pay.test/c');

        $this->assertNull($result);
        $this->assertNull($link->fresh()->used_at, 'Slot-blocking check berjalan SEBELUM claim — link tidak terbakar');
    }
}
