<?php

namespace Tests\Feature;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\WellbeingQuotaLedger;
use App\Models\WellbeingWebinar;
use App\Services\WellbeingQuotaService;
use Database\Seeders\WellbeingPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv2 — webinar entity + ledger consumption (research2-infra §2):
// admin create/list webinar per program, mark done → ledger row bucket
// 'webinar' event 'used' (no session), manual forfeited for no-show;
// kuota snapshot 0/1/3/6 hangus saat ends_at. Append-only ledger.
class WellbeingWebinarFlowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'admin'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => 'Wv',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function company(string $brand, int $employeeCount = 500): Perusahaan
    {
        return Perusahaan::create([
            'name' => "PT {$brand}", 'brand' => $brand,
            'sessions_per_employee_month' => 2, 'employee_count' => $employeeCount,
            'is_active' => true,
        ]);
    }

    private function program(Perusahaan $p, string $code = 'growth'): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::where('code', $code)->firstOrFail();
        $admin = $this->user('wv.admin'.uniqid().'@wv.local');

        return CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => $p->employee_count,
            'deep_support_mode' => $pkg->deep_support_mode ?: 'one_on_one',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 10,
            'deep_support_quota' => 10,
            'webinar_quota' => $pkg->webinar_count,
            'assessment_count' => $pkg->assessment_count,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Admin CRUD + mark done ledger flow
    // ------------------------------------------------------------------

    public function test_admin_can_create_webinar_for_program()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.crud@wv.local');
        $p = $this->company('WvCrud');
        $program = $this->program($p);

        $res = $this->actingAs($admin)->post(route('admin.wellbeing-webinars.store', $program), [
            'title' => 'Stres Kerja 101',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'notes' => 'Zoom link dikirim HR',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('wellbeing_webinars', [
            'program_id' => $program->id,
            'title' => 'Stres Kerja 101',
            'status' => 'planned',
            'consultant_id' => null,
        ]);
        // belum ada ledger row saat masih planned
        $this->assertSame(0, WellbeingQuotaLedger::where('program_id', $program->id)->count());
    }

    public function test_admin_sees_webinar_list_per_program()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.list@wv.local');
        $p = $this->company('WvList');
        $program = $this->program($p);

        WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Burnout Prevention',
            'scheduled_at' => now()->addDays(3), 'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.wellbeing-webinars.index', $program))
            ->assertOk()
            ->assertSee('Burnout Prevention')
            ->assertSee($p->brand);
    }

    public function test_mark_done_writes_used_ledger_row_with_actor()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.done@wv.local');
        $p = $this->company('WvDone');
        $program = $this->program($p, 'impact'); // webinar_quota 6

        $webinar = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Nutrition at Work',
            'scheduled_at' => now()->subDay(), 'status' => 'planned',
        ]);

        $res = $this->actingAs($admin)
            ->post(route('admin.wellbeing-webinars.mark-done', [$program, $webinar]));

        $res->assertRedirect();
        $this->assertTrue($webinar->fresh()->status === 'done');
        $row = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', 'webinar')->where('event', 'used')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->perusahaan_session_id); // per-row counting, bukan per sesi
        $this->assertEquals($admin->id, $row->actor_id);
        $this->assertSame(1, $program->quotaLedgers()->where('bucket', 'webinar')->count());
    }

    public function test_mark_done_idempotent_no_double_ledger()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.idem@wv.local');
        $p = $this->company('WvIdem');
        $program = $this->program($p, 'growth'); // webinar_quota 3

        $webinar = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Ergonomic Desk Setup',
            'scheduled_at' => now()->subDay(), 'status' => 'planned',
        ]);

        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $webinar]));
        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $webinar]));

        $this->assertSame(1, $program->quotaLedgers()->where('bucket', 'webinar')->count());
    }

    public function test_mark_done_blocked_when_quota_exhausted()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.cap@wv.local');
        $p = $this->company('WvCap');
        $program = $this->program($p, 'foundation'); // webinar_quota 1

        $a = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'W1', 'scheduled_at' => now(), 'status' => 'planned',
        ]);
        $b = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'W2', 'scheduled_at' => now(), 'status' => 'planned',
        ]);

        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $a]))
            ->assertRedirect();
        // kuota 1 sudah terpakai → webinar kedua gagal, tanpa ledger row baru
        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $b]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, $program->quotaLedgers()->where('bucket', 'webinar')->count());
        $this->assertTrue($b->fresh()->status === 'planned');
    }

    public function test_mark_done_noop_past_ends_at_quota_hangus()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.hangus@wv.local');
        $p = $this->company('WvHangus');
        $program = $this->program($p, 'growth');
        $program->update(['ends_at' => today()->subDay()]); // kontrak lewat → saldo 0

        $webinar = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Too Late', 'scheduled_at' => now(), 'status' => 'planned',
        ]);

        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $webinar]))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, $program->quotaLedgers()->where('bucket', 'webinar')->count());
    }

    public function test_cancel_webinar_writes_no_ledger_and_cancelled_cannot_be_marked_done()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.cancel@wv.local');
        $p = $this->company('WvCancel');
        $program = $this->program($p, 'growth');

        $webinar = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Cancelled', 'scheduled_at' => now(), 'status' => 'planned',
        ]);

        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.cancel', [$program, $webinar]))
            ->assertRedirect();
        $this->assertTrue($webinar->fresh()->status === 'cancelled');
        $this->assertSame(0, $program->quotaLedgers()->count());

        // cancelled → mark-done ditolak (bukan idempotent backdoor)
        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $webinar]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_no_show_forfeit_writes_forfeited_row_without_session()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.forfeit@wv.local');
        $p = $this->company('WvForfeit');
        $program = $this->program($p, 'growth');

        $this->actingAs($admin)
            ->post(route('admin.wellbeing-webinars.forfeit', $program), [
                'webinar_id' => WellbeingWebinar::create([
                    'program_id' => $program->id, 'title' => 'No Show', 'scheduled_at' => now()->subDay(),
                ])->id,
                'reason' => 'peserta tidak hadir',
            ])
            ->assertRedirect();

        $row = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', 'webinar')->where('event', 'forfeited')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->perusahaan_session_id);
        $this->assertStringContainsString('webinar:'.$row->id.' no-show', (string) $row->reason);
        $this->assertStringContainsString('peserta tidak hadir', (string) $row->reason);
    }

    /** REV HIGH-1: double-submit forfeit tidak boleh menggandakan row ledger. */
    public function test_forfeit_is_idempotent_on_double_submit()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.forfeit2@wv.local');
        $p = $this->company('WvForfeit2');
        $program = $this->program($p, 'growth');
        $webinar = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Double', 'scheduled_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.wellbeing-webinars.forfeit', $program), [
                'webinar_id' => $webinar->id, 'reason' => 'peserta tidak hadir',
            ])->assertRedirect();

        // submit kedua (double-click / stale tab) — status sudah cancelled.
        $this->actingAs($admin)
            ->post(route('admin.wellbeing-webinars.forfeit', $program), [
                'webinar_id' => $webinar->id, 'reason' => 'coba lagi',
            ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', 'webinar')->where('event', 'forfeited')->count());
    }

    // ------------------------------------------------------------------
    // Kuota snapshot 1/3/6 decrement per package
    // ------------------------------------------------------------------

    /** @dataProvider webinarQuotaProvider */
    public function test_webinar_quota_snapshot_decrements_per_package(string $code, int $quota)
    {
        $this->seed(WellbeingPackageSeeder::class);
        $admin = $this->user('wv.q'.$code.'@wv.local');
        $p = $this->company('WvQ'.ucfirst($code));
        $program = $this->program($p, $code);

        $this->assertSame($quota, $program->webinar_quota);

        $w = WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Q', 'scheduled_at' => now(), 'status' => 'planned',
        ]);
        $this->actingAs($admin)->post(route('admin.wellbeing-webinars.mark-done', [$program, $w]));

        $this->assertSame(1, $program->quotaLedgers()->where('bucket', 'webinar')->count());
    }

    public static function webinarQuotaProvider(): array
    {
        return [
            'foundation quota 1' => ['foundation', 1],
            'growth quota 3' => ['growth', 3],
            'impact quota 6' => ['impact', 6],
        ];
    }

    // ------------------------------------------------------------------
    // HR page: webinar list read-only
    // ------------------------------------------------------------------

    public function test_hr_page_lists_program_webinars()
    {
        $this->seed(WellbeingPackageSeeder::class);
        $hrRoleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Wv',
            'username' => 'hr_wv', 'email' => 'hr_wv@wv.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        $p = $this->company('WvHr');
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $program = $this->program($p, 'growth');

        WellbeingWebinar::create([
            'program_id' => $program->id, 'title' => 'Sleep Hygiene Workshop',
            'scheduled_at' => now()->addDays(10), 'status' => 'planned',
        ]);

        $this->actingAs($hr)->get(route('perusahaan.program-subscription'))
            ->assertOk()
            ->assertSee('Sleep Hygiene Workshop')
            ->assertSee('Jadwal Webinar');
    }
}
