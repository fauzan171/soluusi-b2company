<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// ponytail: negative-only — owner-side positives sudah dicover ChatTest/HpaKlinikPortalTest.
class SessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function consultantUser(string $email): array
    {
        $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::factory()->create(['role_id' => $roleId, 'email' => $email]);
        $consultant = Consultant::factory()->create(['user_id' => $user->id, 'status' => 'accept']);

        return [$user, $consultant];
    }

    public function test_other_consultant_cannot_check_in_or_mark_done()
    {
        [$ownerUser, $owner] = $this->consultantUser('owner@pol.local');
        [$otherUser] = $this->consultantUser('other@pol.local');

        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $owner->id, 'price' => 100_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $owner->id]);
        $session = ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $owner->id,
            'consultant_package_id' => $pkg->id,
            'order_number' => 'POL-1',
            'payment' => '0',
            'amount' => 100_000,
            'order_at' => now(),
            'payment_status' => 'verified',
            'status' => 'scheduled',
        ]);

        $this->actingAs($otherUser)->post(route('consultant.sessions.checkIn', $session))
            ->assertForbidden();
        $this->actingAs($otherUser)->post(route('consultant.sessions.markDone', $session))
            ->assertForbidden();

        $this->assertNull($session->fresh()->konsultan_checked_in_at);
        $this->assertNull($session->fresh()->konsultan_done_at);
    }
}
