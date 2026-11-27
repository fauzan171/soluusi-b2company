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
 * T1: alur lengkap booking karyawan perusahaan dari jam mingguan konsultan
 * pool → chat dua arah karyawan ↔ konsultan. End-to-end (bukan unit):
 * booking store → sesi terjadwal → chat room dua arah.
 */
class PerusahaanBookingChatFlowTest extends TestCase
{
    use RefreshDatabase;

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
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-26 09:00:00'));
        $p = Perusahaan::create([
            'name' => 'PT Flow', 'brand' => 'FlowCo', 'sessions_per_employee_month' => 5,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@flow.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@flow.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@flow.local', 'consultant');
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

    private function book(User $empUser, string $startsAt, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($empUser)->post(route('perusahaan.booking.store'), array_merge([
            'starts_at' => $startsAt, 'konsultan_type' => 'psikolog',
        ], $extra));
    }

    // ACC 1: employee lihat slot, book, dapat sesi yang chat-nya bisa dibuka.
    public function test_employee_books_slot_and_gets_chat_openable_session()
    {
        [$p, $emp, $empUser, $docUser, $consultant] = $this->fixture();

        // Slot mingguan konsultan pool tampil di form booking.
        $this->actingAs($empUser)->get(route('perusahaan.booking'))
            ->assertOk()
            ->assertSee('value="'.self::MONDAY.'"', false);

        $this->book($empUser, self::MONDAY)->assertRedirect(route('perusahaan.employee'));

        $session = PerusahaanSession::first();
        $this->assertSame($consultant->id, $session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertSame(self::MONDAY, $session->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertNotNull($session->schedule_id);

        // Sesi bisa dibuka chat-nya (policy view + room render).
        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))
            ->assertOk();
    }

    // ACC 2: pesan karyawan dibaca konsultan via route consultant; balasan dibaca karyawan.
    public function test_employee_and_consultant_chat_both_ways()
    {
        [$p, $emp, $empUser, $docUser, $consultant] = $this->fixture();
        $this->book($empUser, self::MONDAY)->assertRedirect();
        $session = PerusahaanSession::first();

        // Karyawan kirim; konsultan baca via route chat konsultan.
        $this->actingAs($empUser)->postJson(route('perusahaan.chat.store', $session), ['body' => 'Halo dok, saya mau konsultasi'])
            ->assertOk();
        $this->actingAs($docUser)->getJson(route('consultant.chat', $session).'?after=0')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Halo dok, saya mau konsultasi']);

        // Konsultan balas; karyawan baca balasan.
        $this->actingAs($docUser)->postJson(route('consultant.chat.store', $session), ['body' => 'Halo, silakan ceritakan keluhannya'])
            ->assertOk();
        $this->actingAs($empUser)->getJson(route('perusahaan.chat', $session).'?after=0')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Halo, silakan ceritakan keluhannya']);

        $this->assertSame(2, $session->chatMessages()->count());
    }

    // ACC 3a: konsultan di luar pool tidak bisa dibook.
    public function test_consultant_outside_pool_cannot_be_booked()
    {
        [$p, $emp, $empUser] = $this->fixture();
        $otherUser = $this->user('other@flow.local', 'consultant');
        $other = Consultant::create([
            'user_id' => $otherUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $this->book($empUser, self::MONDAY, ['consultant_id' => $other->id])
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(0, PerusahaanSession::count());
    }

    // ACC 3b: konsultan switched off (pool inactive) tidak bisa dibook.
    public function test_switched_off_pool_consultant_cannot_be_booked()
    {
        [$p, $emp, $empUser, $docUser, $consultant, $pool] = $this->fixture();
        $pool->update(['status' => 'inactive']);

        // Slot tidak ditawarkan lagi di form.
        $this->actingAs($empUser)->get(route('perusahaan.booking'))
            ->assertOk()
            ->assertDontSee('value="'.self::MONDAY.'"', false);

        // Booking langsung ditolak.
        $this->book($empUser, self::MONDAY)->assertSessionHasErrors('starts_at');
        $this->assertSame(0, PerusahaanSession::count());
    }

    // ACC 3c: konsultan is_active=false tidak bisa dibook.
    public function test_inactive_consultant_cannot_be_booked()
    {
        [$p, $emp, $empUser, , $consultant] = $this->fixture();
        $consultant->update(['is_active' => false]);

        $this->book($empUser, self::MONDAY)->assertSessionHasErrors('starts_at');
        $this->assertSame(0, PerusahaanSession::count());
    }

    // ACC 3d: slot yang sudah dibook tidak bisa dibook dua kali.
    public function test_already_booked_slot_cannot_be_booked_twice()
    {
        [$p, $emp, $empUser, $docUser, $consultant] = $this->fixture();

        $this->book($empUser, self::MONDAY)->assertRedirect();
        $this->book($empUser, self::MONDAY)->assertSessionHasErrors('starts_at');

        $this->assertSame(1, PerusahaanSession::count());
    }
}
