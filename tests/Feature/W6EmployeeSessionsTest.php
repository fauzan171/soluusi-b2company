<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Perusahaan\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// W6: employee dashboard "Sesi Saya" — kartu per sesi scheduled (countdown,
// durasi, entry per consultation_type) + tombol "Minta Konsultasi" (BOOKING
// slot, bukan request konsultan) disabled saat kuota habis / belum consent.
class W6EmployeeSessionsTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaan;
    private User $user;
    private PerusahaanEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT W6', 'brand' => 'W6Co', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $roleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $this->user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'W6',
            'username' => 'emp_w6', 'email' => 'emp_w6@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->employee = PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->user->id,
            'name' => 'Emp W6', 'email' => 'emp_w6@test.local', 'consent_at' => now(),
        ]);
    }

    private function makeSession(array $attrs = []): PerusahaanSession
    {
        return PerusahaanSession::create(array_merge([
            'perusahaan_id' => $this->perusahaan->id,
            'employee_id' => $this->employee->id,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ], $attrs));
    }

    public function test_sesi_saya_renders_cards_for_all_three_types_scoped_to_own_sessions()
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-22 09:00:00'));

        $room = Room::create(['perusahaan_id' => $this->perusahaan->id, 'name' => 'Ruang Senja']);
        $this->makeSession([
            'consultation_type' => 'one_on_one', 'room_id' => $room->id,
            'scheduled_at' => now()->addDays(1)->addHours(2),
            'ended_at' => now()->addDays(1)->addHours(2)->addMinutes(90),
            'topic_category' => 'Topik A',
        ]);
        $this->makeSession([
            'consultation_type' => 'online_meeting',
            'meeting_url' => 'https://meet.google.com/w6-test-url',
            'scheduled_at' => now()->addDays(3), 'topic_category' => 'Topik B',
        ]);
        $this->makeSession([
            'consultation_type' => 'chat',
            'scheduled_at' => now()->addDays(4), 'topic_category' => 'Topik C',
        ]);

        // Sesi karyawan lain (perusahaan sama) — tidak boleh terlihat.
        $coworker = PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id,
            'name' => 'Coworker', 'email' => 'coworker@test.local', 'consent_at' => now(),
        ]);
        $this->makeSession([
            'employee_id' => $coworker->id, 'consultation_type' => 'chat',
            'topic_category' => 'Topik Rahasia Coworker',
        ]);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));

        $res->assertOk()->assertSee('Sesi Saya');
        // one_on_one: nama ruangan + durasi + countdown (locale id, Asia/Jakarta).
        $res->assertSee('Ruang Senja');
        $res->assertSee('90 menit');
        $res->assertSee('Dalam 1 hari 2 jam');
        // online_meeting: tombol Gabung masih tertutup (2 hari lagi).
        $res->assertDontSee('Gabung');
        $res->assertDontSee('https://meet.google.com/w6-test-url');
        // chat: tombol Masuk Chat.
        $res->assertSee('Masuk Chat');
        // Row scoping by employee_id.
        $res->assertDontSee('Topik Rahasia Coworker');
        $res->assertSee('Topik A')->assertSee('Topik B')->assertSee('Topik C');

        $this->travelBack();
    }

    public function test_online_meeting_join_button_only_visible_within_window()
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 09:00:00'));

        $this->makeSession([
            'consultation_type' => 'online_meeting',
            'meeting_url' => 'https://meet.google.com/w6-window',
            'scheduled_at' => now()->addHour(),          // 10:00
            'ended_at' => now()->addHours(3),            // 12:00
        ]);

        // T-40 menit: window belum terbuka (buka T-10).
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 09:20:00'));
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertDontSee('Gabung')->assertDontSee('https://meet.google.com/w6-window');

        // T-5 menit: window terbuka.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 09:55:00'));
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertSee('Gabung')->assertSee('https://meet.google.com/w6-window');

        // Sesi berjalan: masih bisa gabung.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 11:30:00'));
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertSee('Gabung')->assertSee('https://meet.google.com/w6-window');

        // Setelah sesi selesai: kartu & link hilang.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-23 12:05:00'));
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertDontSee('Gabung')->assertDontSee('https://meet.google.com/w6-window');

        $this->travelBack();
    }

    public function test_minta_konsultasi_button_enabled_when_quota_available()
    {
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));

        // CTA booking slot aktif: anchor ke booking form muncul di body dashboard.
        $res->assertOk()->assertSee('href="'.route('perusahaan.booking').'"', false);
    }

    public function test_minta_konsultasi_button_disabled_when_quota_exhausted()
    {
        // Kuota legacy 1, sudah terpakai 1 sesi bulan ini → saldo 0.
        $this->perusahaan->update(['sessions_per_employee_month' => 1]);
        $this->makeSession(['consultation_type' => 'chat', 'topic_category' => 'Pemakai Kuota']);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));

        $res->assertOk()
            ->assertSee('Sisa kuota bulan ini habis — hubungi HR untuk penambahan.')
            // CTA body ter-disable: bukan anchor booking, tapi button disabled.
            ->assertSee('title="Sisa kuota bulan ini habis — hubungi HR untuk penambahan."', false);
    }

    public function test_minta_konsultasi_button_disabled_when_consent_not_given()
    {
        $this->employee->update(['consent_at' => null]);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));

        $res->assertOk()->assertSee('Selesaikan persetujuan program kamu dulu lewat HR.');
    }

    public function test_other_company_employee_cannot_access_sessions_or_see_cross_tenant_data()
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-22 09:00:00'));

        $foreign = $this->makeSession([
            'consultation_type' => 'chat',
            'topic_category' => 'Rahasia Perusahaan A',
            'scheduled_at' => now()->addDays(1),
        ]);

        // Karyawan perusahaan B.
        $perusahaanB = Perusahaan::create([
            'name' => 'PT B', 'brand' => 'BCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $userB = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B',
            'username' => 'emp_b_w6', 'email' => 'emp_b_w6@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => $this->user->role_id,
        ]);
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaanB->id, 'user_id' => $userB->id,
            'name' => 'Emp B', 'email' => 'emp_b_w6@test.local', 'consent_at' => now(),
        ]);

        // Route sesi milik karyawan lain → 403 (scoping employee_id).
        $this->actingAs($userB)
            ->get(route('perusahaan.chat', $foreign))
            ->assertStatus(403);

        // Dashboard sendiri: tidak bocor data perusahaan A.
        $res = $this->actingAs($userB)->get(route('perusahaan.employee'));
        $res->assertOk()->assertSee('Sesi Saya')->assertDontSee('Rahasia Perusahaan A');

        $this->travelBack();
    }
}
