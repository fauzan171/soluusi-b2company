<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionExpirySweepTest extends TestCase
{
    use RefreshDatabase;

    private function perusahaanFixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Sweep', 'brand' => 'SweepCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $user = User::factory()->create(['email' => 'sweep@test.local']);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'sweep@test.local', 'consent_at' => now(),
        ]);

        return [$p, $emp];
    }

    private function sekolahFixture(): array
    {
        $s = Sekolah::create([
            'name' => 'SMA Sweep', 'brand' => 'SweepSchool', 'sessions_per_student_month' => 2,
            'student_count' => 3, 'is_active' => true,
        ]);
        $user = User::factory()->create(['email' => 'sweep-sekolah@test.local']);
        $student = SekolahStudent::create([
            'sekolah_id' => $s->id, 'user_id' => $user->id,
            'name' => 'Siswa', 'email' => 'sweep-sekolah@test.local',
            'kelas' => 'XII-1', 'parent_consent_at' => now(),
        ]);

        return [$s, $student];
    }

    public function test_zombie_checked_in_sessions_auto_completed_after_4h()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        // Zombie: check-in tapi tidak pernah diakhiri, 5 jam lewat jadwal.
        $zombie = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(5),
            'checked_in_at' => now()->subHours(5),
        ]);
        // Sesi check-in baru (1 jam lalu) — tidak boleh disentuh.
        $fresh = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHour(),
            'checked_in_at' => now()->subHour(),
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $zombie->refresh();
        $this->assertSame('done', $zombie->status);
        $this->assertNotNull($zombie->completed_at);
        // Sesi terjadi → kuota TETAP terpakai (sweep tidak me-restore).
        $this->assertTrue((bool) $zombie->quota_consumed);

        $this->assertSame('scheduled', $fresh->refresh()->status);
    }

    public function test_expired_scheduled_sessions_are_auto_cancelled_and_quota_restored()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();
        [$s, $student] = $this->sekolahFixture();

        $expiredPerusahaan = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(25),
        ]);
        $expiredSekolah = SekolahSession::create([
            'sekolah_id' => $s->id, 'student_id' => $student->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(25),
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $expiredPerusahaan->refresh();
        $expiredSekolah->refresh();
        $this->assertSame('cancelled', $expiredPerusahaan->status);
        $this->assertSame('cancelled', $expiredSekolah->status);
        $this->assertSame('Auto-cancelled: no check-in', $expiredPerusahaan->cancel_reason);
        $this->assertSame('Auto-cancelled: no check-in', $expiredSekolah->cancel_reason);
        // Quota restored: auto-cancel bukan late-cancel, kuota tidak terpakai.
        $this->assertFalse((bool) $expiredPerusahaan->quota_consumed);
        $this->assertFalse((bool) $expiredSekolah->quota_consumed);
        $this->assertNotNull($expiredPerusahaan->cancelled_at);
        $this->assertNotNull($expiredSekolah->cancelled_at);
    }

    public function test_recent_scheduled_sessions_are_not_cancelled()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        $recent = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(2),
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $recent->refresh();
        $this->assertSame('scheduled', $recent->status);
    }

    public function test_checked_in_or_terminal_sessions_are_not_cancelled()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        $checkedIn = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(30), 'checked_in_at' => now()->subHours(30),
        ]);
        $done = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'done',
            'scheduled_at' => now()->subHours(30),
        ]);
        $awaitingAcc = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        // Checked-in lama: tidak di-cancel, tapi auto-complete (zombie sweep).
        $this->assertSame('done', $checkedIn->refresh()->status);
        $this->assertNull($checkedIn->cancel_reason);
        $this->assertSame('done', $done->refresh()->status);
        $this->assertSame('scheduled', $awaitingAcc->refresh()->status);
    }

    public function test_booking_claims_are_released_for_cancelled_sessions()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        $consultant = \App\Models\Consultant::factory()->create();
        $slot = \App\Models\Schedule::factory()->create(['consultant_id' => $consultant->id]);
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(25),
        ]);
        DB::table('booking_claims')->insert([
            'schedule_id' => $slot->id, 'owner_type' => 'perusahaan', 'owner_id' => $session->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame(0, DB::table('booking_claims')
            ->where('owner_type', 'perusahaan')->where('owner_id', $session->id)->count());
    }

    public function test_sweep_does_not_cancel_session_checked_in_after_cutoff_race()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        // Sesi melewati cutoff TAPI sudah check-in — harus tidak tersentuh.
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(30),
            'checked_in_at' => now()->subHours(30)->addMinutes(5),
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $session->refresh();
        // Bukan cancelled: sudah check-in. Zombie sweep menutup sebagai done.
        $this->assertSame('done', $session->status);
        $this->assertNull($session->cancel_reason);
    }

    public function test_sweep_rejects_pending_proposals_of_cancelled_sessions()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();

        $consultant = \App\Models\Consultant::factory()->create();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(25),
        ]);
        $proposal = \App\Models\SessionSlotProposal::create([
            'session_id' => $session->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now()->subHours(20),
            'slots' => [['start' => now()->addDay()->toDateTimeString(), 'end' => now()->addDay()->addHour()->toDateTimeString()]],
            'status' => 'pending',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertSame('rejected', $proposal->fresh()->status);
    }

    public function test_stale_pending_request_auto_cancelled_after_72h()
    {
        config()->set('booking.pending_cancel_hours', 72);
        [$p, $emp] = $this->perusahaanFixture();

        // Pending request macet > 72 jam (scheduled + no slot + no consultant).
        $stale = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
            'quota_consumed' => true,
        ]);
        // Backdate created_at melewati cutoff.
        DB::table('perusahaan_sessions')->where('id', $stale->id)
            ->update(['created_at' => now()->subHours(80)]);

        // Pending request baru — tidak boleh tersentuh.
        $fresh = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $stale->refresh();
        $this->assertSame('cancelled', $stale->status);
        $this->assertFalse((bool) $stale->quota_consumed);
        $this->assertStringContainsString('kedaluwarsa', $stale->cancel_reason);
        $this->assertSame('scheduled', $fresh->refresh()->status);
    }

    public function test_stale_pending_request_rejects_pending_proposals()
    {
        config()->set('booking.pending_cancel_hours', 72);
        [$p, $emp] = $this->perusahaanFixture();

        $consultant = \App\Models\Consultant::factory()->create();
        $stale = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null,
        ]);
        DB::table('perusahaan_sessions')->where('id', $stale->id)
            ->update(['created_at' => now()->subHours(80)]);
        $proposal = \App\Models\SessionSlotProposal::create([
            'session_id' => $stale->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now()->subHours(70),
            'slots' => [['start' => now()->addDay()->toDateTimeString(), 'end' => now()->addDay()->addHour()->toDateTimeString()]],
            'status' => 'pending',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('rejected', $proposal->fresh()->status);
    }

    public function test_sekolah_sweep_does_not_reject_perusahaan_proposals_with_same_id()
    {
        config()->set('booking.auto_cancel_hours', 24);
        [$p, $emp] = $this->perusahaanFixture();
        [$s, $student] = $this->sekolahFixture();

        $consultant = \App\Models\Consultant::factory()->create();

        // Sesi sekolah expired yang id-nya akan sama dengan sesi perusahaan.
        $expiredSekolah = SekolahSession::create([
            'sekolah_id' => $s->id, 'student_id' => $student->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHours(25),
        ]);

        // Sesi perusahaan sehat dengan id numerik sama + proposal pending.
        // Samakan id secara eksplisit untuk membuktikan tidak ada cross-vertical collision.
        DB::table('perusahaan_sessions')->insert([
            'id' => $expiredSekolah->id,
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $proposal = \App\Models\SessionSlotProposal::create([
            'session_id' => $expiredSekolah->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now(),
            'slots' => [['start' => now()->addDay()->toDateTimeString(), 'end' => now()->addDay()->addHour()->toDateTimeString()]],
            'status' => 'pending',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        $this->assertSame('cancelled', $expiredSekolah->fresh()->status);
        // Proposal milik sesi perusahaan TIDAK boleh ikut ke-reject.
        $this->assertSame('pending', $proposal->fresh()->status);
        $this->assertSame('scheduled', PerusahaanSession::find($expiredSekolah->id)->status);
    }
}
