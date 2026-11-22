<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantAvailabilityException;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// K4: slot computed-on-read dari work_hours template minus exceptions minus
// booked (perusahaan_sessions + schedules/booking existing). No materialized rows.
class W3AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private Consultant $consultant;
    private Perusahaan $perusahaan;
    private ConsultantPool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant', 'created_at' => now(), 'updated_at' => now()]);
        $docUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Doc', 'lastname' => 'Test',
            'username' => 'doc_w3', 'email' => 'doc_w3@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $this->perusahaan = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $this->pool = ConsultantPool::create([
            'perusahaan_id' => $this->perusahaan->id,
            'consultant_id' => $this->consultant->id,
            'status' => 'active',
        ]);

        // Template: Senin 09:00-12:00 WIB, slot 60 menit.
        ConsultantWorkHour::create([
            'consultant_pool_id' => $this->pool->id,
            'weekday' => 1, // Monday
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'slot_minutes' => 60,
        ]);
    }

    public function test_generates_slots_from_weekly_template()
    {
        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $to = $from->copy()->addDays(7);

        $slots = AvailabilityService::slotsFor($this->consultant, $this->perusahaan, $from, $to);

        $this->assertCount(3, $slots); // 09:00, 10:00, 11:00 WIB Monday
        $this->assertEquals('09:00', $slots[0]->start->timezone('Asia/Jakarta')->format('H:i'));
        $this->assertEquals(60, $slots->first()->minutes);
    }

    public function test_full_day_exception_blocks_all_slots_that_date()
    {
        $monday = \Illuminate\Support\Carbon::parse('next monday', 'Asia/Jakarta');
        ConsultantAvailabilityException::create([
            'consultant_id' => $this->consultant->id,
            'date' => $monday->toDateString(),
            'start_time' => null, 'end_time' => null, // full-day
        ]);

        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $slots = AvailabilityService::slotsFor($this->consultant, $this->perusahaan, $from, $from->copy()->addDays(7));

        $this->assertCount(0, $slots);
    }

    public function test_partial_exception_blocks_only_overlapping_slots()
    {
        $monday = \Illuminate\Support\Carbon::parse('next monday', 'Asia/Jakarta');
        ConsultantAvailabilityException::create([
            'consultant_id' => $this->consultant->id,
            'date' => $monday->toDateString(),
            'start_time' => '10:30:00', 'end_time' => '11:30:00',
        ]);

        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $slots = AvailabilityService::slotsFor($this->consultant, $this->perusahaan, $from, $from->copy()->addDays(7));

        // 09:00-10:00 free, 10:00-11:00 overlaps (10:30-11:30), 11:00-12:00 overlaps → 1 slot left.
        $this->assertCount(1, $slots);
        $this->assertEquals('09:00', $slots[0]->start->timezone('Asia/Jakarta')->format('H:i'));
    }

    public function test_booked_session_excludes_slot()
    {
        $monday = \Illuminate\Support\Carbon::parse('next monday', 'Asia/Jakarta');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->consultant->user_id,
            'name' => 'Emp', 'email' => 'doc_w3@test.local', 'consent_at' => now(),
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $this->perusahaan->id, 'employee_id' => $emp->id,
            'consultant_id' => $this->consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => $monday->copy()->setTime(10, 0),
            'ended_at' => $monday->copy()->setTime(11, 0),
        ]);

        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $slots = AvailabilityService::slotsFor($this->consultant, $this->perusahaan, $from, $from->copy()->addDays(7));

        $this->assertCount(2, $slots); // 09:00 + 11:00 remain
        $this->assertFalse($slots->contains(fn ($s) => $s->start->timezone('Asia/Jakarta')->format('H:i') === '10:00'));
    }

    public function test_ended_pool_contract_excludes_slots()
    {
        $this->pool->update(['status' => 'ended']);

        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $slots = AvailabilityService::slotsFor($this->consultant, $this->perusahaan, $from, $from->copy()->addDays(7));

        $this->assertCount(0, $slots);
    }

    public function test_utc7_boundary_is_exact()
    {
        // WIB Monday 00:00 == Sunday 17:00 UTC, always (fixed UTC+7, no DST).
        $from = \Illuminate\Support\Carbon::parse('next monday 00:00', 'Asia/Jakarta');
        $this->assertEquals('Sunday 17:00', $from->utc()->englishDayOfWeek . ' ' . $from->utc()->format('H:i'));
    }

    public function test_overlap_predicate_half_open()
    {
        // 10:00-11:00 vs 11:00-12:00 adjacent = no overlap (half-open [)).
        $this->assertFalse(AvailabilityService::overlaps(
            \Illuminate\Support\Carbon::parse('2026-11-23 10:00', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 11:00', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 11:00', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 12:00', 'Asia/Jakarta'),
        ));
        // 10:00-11:30 vs 11:00-12:00 = overlap.
        $this->assertTrue(AvailabilityService::overlaps(
            \Illuminate\Support\Carbon::parse('2026-11-23 10:00', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 11:30', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 11:00', 'Asia/Jakarta'),
            \Illuminate\Support\Carbon::parse('2026-11-23 12:00', 'Asia/Jakarta'),
        ));
    }
}
