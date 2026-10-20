<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ponytail: one smoke per Ponytail rule — fails kalau scaffold/domain core break.
class ScaffoldSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scaffold_boots_and_core_domain_writes()
    {
        // Config midtrans termuat (key boleh kosong di local dev sandbox-less).
        $this->assertArrayHasKey('server_key', config('midtrans'));
        $this->assertNotNull(config('whitelabel.default_brand'));

        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id]); // fillable typo 'consultan_id' preserved (parity) — kolom real consultant_id
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);
        $ses = ConsultationSession::create([
            'consultant_id' => $konsultan->id,
            'schedule_id' => $sched->id,
            'consultant_package_id' => $pkg->id,
            'order_number' => 'SMOKE-1',
            'payment' => '0',
            'amount' => 0,
            'order_at' => now(),
            'payment_status' => 'waiting',
            'status' => 'waiting',
            // Model C guest PII
            'customer_name' => 'Smoke Customer',
            'customer_email' => 'smoke@example.com',
            'customer_gender' => 'female',
            'complaint' => 'smoke complaint',
        ]);
        $this->assertDatabaseHas('consultation_sessions', ['id' => $ses->id, 'status' => 'waiting']);
    }
}
