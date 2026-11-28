<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\ConsultantWorkHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SLOTS: booking karyawan dari jam kerja mingguan konsultan.
 * Schedule row per-slot dibuat on-the-fly di BookingService::bookFromHours —
 * jadwal = work hours (pool tenant) − sudah dibook (semua tenant konsultan).
 */
class WorkHourSlotBookingTest extends TestCase
{
    use RefreshDatabase;

    /** Tanggal deterministik: Senin 2026-11-30, template weekday=1. */
    private const MONDAY = '2026-11-30 09:00:00';

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    /** [$p, $emp, $empUser, $docUser, $consultant, $pool] — pool + jam Senin 09-12. */
    private function fixture(array $hours = [['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']]): array
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-26 09:00:00')); // Kamis
        $p = Perusahaan::create([
            'name' => 'PT Slots', 'brand' => 'SlotsCo', 'sessions_per_employee_month' => 5,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@slots.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@slots.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@slots.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Psikolog', 'code_category' => Str::random(12), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('consultant_categories')->insert(['consultant_id' => $consultant->id, 'category_id' => $categoryId, 'created_at' => now(), 'updated_at' => now()]);
        $pool = ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);
        foreach ($hours as $h) {
            ConsultantWorkHour::create([
                'consultant_pool_id' => $pool->id, 'weekday' => $h['weekday'],
                'start_time' => $h['start_time'], 'end_time' => $h['end_time'],
                'slot_minutes' => $h['slot_minutes'] ?? 60, 'consultation_types' => null,
            ]);
        }

        return [$p, $emp, $empUser, $docUser, $consultant, $pool];
    }

    /** Slot Senin 09:00 (atau offset menit) sebagai input starts_at. */
    private function monday(int $minutes = 0): string
    {
        return \Illuminate\Support\Carbon::parse(self::MONDAY)->addMinutes($minutes)->format('Y-m-d H:i:s');
    }

    private function book(PerusahaanEmployee $emp, User $user, string $startsAt, string $type = 'psikolog', ?string $consultationType = null, int $consultantId = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(route('perusahaan.booking.store'), array_filter([
            'consultant_id' => $consultantId, 'starts_at' => $startsAt,
            'konsultan_type' => $type, 'consultation_type' => $consultationType,
        ]));
    }

    // (a) work hours → slot konkret di form
    public function test_employee_sees_concrete_slots_from_work_hours()
    {
        [$p, $emp, $empUser] = $this->fixture();

        $res = $this->actingAs($empUser)->get(route('perusahaan.booking'));

        $res->assertOk();
        $res->assertSee('value="' . $this->monday() . '"', false);
        $res->assertSee('value="' . $this->monday(60) . '"', false);
        $res->assertSee('value="' . $this->monday(120) . '"', false);
        $res->assertDontSee('value="' . $this->monday(180) . '"', false); // 12:00 di luar jam
    }

    // (b) satu slot dibook → hilang untuk semua, slot lain tetap
    public function test_booked_slot_disappears_but_others_remain()
    {
        [$p, $emp, $empUser, $docUser, $consultant] = $this->fixture();
        $this->book($emp, $empUser, $this->monday(60))->assertRedirect(route('perusahaan.employee'));

        // (d) tanpa pilih konsultan → pool di-infer dari slot yang tersisa
        $res = $this->actingAs($empUser)->get(route('perusahaan.booking'));
        $res->assertOk();
        $res->assertDontSee('value="' . $this->monday(60) . '"', false);
        $res->assertSee('value="' . $this->monday() . '"', false);
        $res->assertSee('value="' . $this->monday(120) . '"', false);

        // Sesi tersimpan lewat jalur state data lengkap
        $s = PerusahaanSession::first();
        $this->assertSame($consultant->id, $s->consultant_id);
        $this->assertSame($this->monday(60), $s->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame('scheduled', $s->status);
        $this->assertNotNull($s->schedule_id);
        $this->assertDatabaseHas('booking_claims', ['schedule_id' => $s->schedule_id]);
    }

    // regresi review B2: slot terakhir window (11:00-12:00) harus bisa di-book
    public function test_last_slot_of_window_can_be_booked()
    {
        [$p, $emp, $empUser] = $this->fixture();

        $this->book($emp, $empUser, $this->monday(120))->assertRedirect(route('perusahaan.employee'));
        $this->assertSame(1, PerusahaanSession::count());
    }

    // (c) double-submit slot sama → kedua ditolak
    public function test_double_booking_same_slot_second_rejected()
    {
        [$p, $emp, $empUser, , $consultant] = $this->fixture();

        $this->book($emp, $empUser, $this->monday())->assertRedirect(route('perusahaan.employee'));
        $this->book($emp, $empUser, $this->monday())->assertSessionHasErrors("starts_at");

        $this->assertSame(1, PerusahaanSession::count());
    }

    // (d) konsultan di luar pool → tanpa hours: tidak ada slot; booking ditolak
    public function test_consultant_not_in_pool_rejected()
    {
        [$p, $emp, $empUser, , $consultant] = $this->fixture();
        $otherUser = $this->user('other@slots.local', 'consultant');
        $other = Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        // Slot jam ini hanya milik konsultan pool — konsultan lain ditolak
        $this->book($emp, $empUser, $this->monday(), 'psikolog', null, $other->id)->assertSessionHasErrors("starts_at");

        // Tanpa jam kerja sama sekali → form tidak menawarkan slot apa pun
        $p2 = Perusahaan::create([
            'name' => 'PT Empty', 'brand' => 'EmptyCo', 'sessions_per_employee_month' => 5,
            'employee_count' => 1, 'is_active' => true,
        ]);
        $u2 = $this->user('emp2@slots.local', 'employee');
        $e2 = PerusahaanEmployee::create([
            'perusahaan_id' => $p2->id, 'user_id' => $u2->id,
            'name' => 'E2', 'email' => 'emp2@slots.local', 'consent_at' => now(),
        ]);
        $res = $this->actingAs($u2)->get(route('perusahaan.booking'));
        $res->assertOk();
        $res->assertSee('Belum ada slot tersedia', false);
    }

    // (e) mode online dan offline dua-duanya bisa dibook
    public function test_offline_and_online_modes_both_book()
    {
        [$p, $emp, $empUser, , $consultant] = $this->fixture();
        // one_on_one butuh room fisik bebas di perusahaan (RoomService::pickFreeRoom).
        \App\Models\Perusahaan\Room::create(['perusahaan_id' => $p->id, 'name' => 'Ruang A', 'status' => 'active']);

        $this->book($emp, $empUser, $this->monday(), 'psikolog', 'chat')->assertRedirect();
        $this->assertSame('online', PerusahaanSession::first()->mode);

        $this->book($emp, $empUser, $this->monday(60), 'psikolog', 'one_on_one')->assertRedirect();
        $this->assertSame('offline', PerusahaanSession::latest('id')->first()->mode);
    }

    // (f) validasi jam konsultan: overlap & end<=start ditolak
    public function test_work_hours_validation_rejects_overlap_and_bad_range()
    {
        [$p, , , $docUser, $consultant, $pool] = $this->fixture();

        $base = ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00'];
        // overlap: 11:00-13:00 vs 09:00-12:00
        $this->actingAs($docUser)->post(route('consultant.work-hours.store'), [
            'pool_id' => $pool->id,
            'hours' => [$base, ['weekday' => 1, 'start_time' => '11:00', 'end_time' => '13:00']],
        ])->assertSessionHasErrors('hours');

        $this->assertSame(1, $pool->workHours()->count());

        // end <= start
        $this->actingAs($docUser)->post(route('consultant.work-hours.store'), [
            'pool_id' => $pool->id,
            'hours' => [['weekday' => 2, 'start_time' => '10:00', 'end_time' => '10:00']],
        ])->assertSessionHasErrors('hours');

        $this->assertSame(1, $pool->workHours()->count());
    }

    // (f) replace set berfungsi (Senin 09-12 → Selasa 13-15)
    public function test_work_hours_store_replaces_set()
    {
        [$p, , , $docUser, $consultant, $pool] = $this->fixture();

        $this->actingAs($docUser)->post(route('consultant.work-hours.store'), [
            'pool_id' => $pool->id,
            'hours' => [['weekday' => 2, 'start_time' => '13:00', 'end_time' => '15:00']],
        ])->assertRedirect();

        $hours = $pool->workHours()->get();
        $this->assertSame(1, $hours->count());
        $this->assertSame(2, (int) $hours->first()->weekday);
        $this->assertStringStartsWith('13:00', (string) $hours->first()->start_time);
    }

    // (g) cross-tenant: jam konsultan di pool A tidak muncul di pool B
    public function test_cross_tenant_hours_not_shared()
    {
        [$p, $emp, $empUser, $docUser, $consultant] = $this->fixture();

        $pB = Perusahaan::create([
            'name' => 'PT B', 'brand' => 'BCo', 'sessions_per_employee_month' => 5,
            'employee_count' => 1, 'is_active' => true,
        ]);
        $uB = $this->user('empb@slots.local', 'employee');
        $eB = PerusahaanEmployee::create([
            'perusahaan_id' => $pB->id, 'user_id' => $uB->id,
            'name' => 'EB', 'email' => 'empb@slots.local', 'consent_at' => now(),
        ]);

        $res = $this->actingAs($uB)->get(route('perusahaan.booking'));
        $res->assertOk();
        $res->assertDontSee('value="' . $this->monday() . '"', false);

        // Pool B tidak punya hours konsultan ini → booking ditolak
        $this->book($eB, $uB, $this->monday(), 'psikolog', null, $consultant->id)->assertSessionHasErrors("starts_at");
    }

    // day boundary: slot menjelang tengah malam tidak bocor ke hari berikut
    public function test_day_boundary_slot_stays_on_its_date()
    {
        [$p, $emp, $empUser, , $consultant] = $this->fixture([[
            'weekday' => 1, 'start_time' => '23:00', 'end_time' => '23:59',
            'slot_minutes' => 30,
        ]]);

        $res = $this->actingAs($empUser)->get(route('perusahaan.booking'));
        $res->assertOk();
        // 23:00-23:30 muat; 23:30-00:00 melewati end_time → tidak dirender.
        $res->assertSee('value="2026-11-30 23:00:00"', false);
        $res->assertDontSee('value="2026-11-30 23:30:00"', false);
        $res->assertDontSee('value="2026-12-01 00:00:00"', false);
    }
}
