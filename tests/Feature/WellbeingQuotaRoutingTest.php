<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Models\WellbeingPackage;
use App\Models\CompanyWellbeingProgram;
use App\Models\WellbeingQuotaLedger;
use App\Services\WellbeingQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * wb-T2 — routing kuota wellbeing: program aktif → ledger wellbeing bucket;
 * tanpa program → QuotaService legacy identik (716 test tetap hijau tanpa edit).
 * Idempotent UNIQUE(session,event), hangus kontrak, bucket terpisah, A vs B.
 */
class WellbeingQuotaRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'employee'): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function fixture(array $programAttrs = []): array
    {
        static $n = 0;
        $n++;
        $p = Perusahaan::create([
            'name' => 'PT WB'.$n, 'brand' => 'WBCo'.$n, 'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = $this->user("emp{$n}@wb.local");
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp'.$n, 'email' => "emp{$n}@wb.local", 'consent_at' => now(),
        ]);

        return [$p, $emp, $user, $programAttrs];
    }

    private function program(Perusahaan $p, array $overrides = []): CompanyWellbeingProgram
    {
        $pkg = WellbeingPackage::firstOrCreate(['code' => 'growth'], [
            'name' => 'Growth', 'duration_days' => 180, 'is_free' => false,
            'assessment_count' => 2, 'assessment_max_participants' => null,
            'assessment_domains' => ['mh', 'nutrition'],
            'expert_chat_experts' => 2, 'expert_chat_quota' => 0,
            'deep_support_quota' => 15, 'deep_support_mode' => 'mhcu',
            'webinar_count' => 3, 'quarterly_review' => false, 'is_active' => true,
        ]);

        return CompanyWellbeingProgram::create(array_merge([
            'perusahaan_id' => $p->id,
            'wellbeing_package_id' => $pkg->id,
            'pricing_band_id' => null,
            'employee_band_size' => 5,
            'deep_support_mode' => 'mhcu',
            'starts_at' => today()->subDay(),
            'ends_at' => today()->addMonths(6),
            'expert_chat_quota' => 5,
            'deep_support_quota' => 10,
            'webinar_quota' => 3,
            'assessment_count' => 2,
            'assessment_max_participants' => null,
            'status' => 'active',
        ], $overrides));
    }

    private function makeSession(Perusahaan $p, PerusahaanEmployee $emp, string $consultationType = 'chat'): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now(), 'consultation_type' => $consultationType,
        ]);
    }

    private function tryReserve($p, $emp, $session, string $bucket)
    {
        try {
            WellbeingQuotaService::reserve($emp, $session, $bucket);
            return null;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $e;
        }
    }

    public function test_active_program_reserves_wellbeing_ledger_only()
    {
        [$p, $emp, , ] = $this->fixture();
        $program = $this->program($p);
        $session = $this->makeSession($p, $emp, 'chat');

        WellbeingQuotaService::reserve($emp, $session, WellbeingQuotaService::bucketFor('chat'));

        $this->assertDatabaseHas('wellbeing_quota_ledger', [
            'program_id' => $program->id, 'perusahaan_session_id' => $session->id,
            'bucket' => 'expert_chat', 'event' => 'reserved',
            'perusahaan_employee_id' => $emp->id,
        ]);
        // routing single-branch: 0 row baru di ledger legacy
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_reserve_decrements_contract_balance()
    {
        [$p, $emp, , ] = $this->fixture();
        $program = $this->program($p, ['expert_chat_quota' => 2]);

        $s1 = $this->makeSession($p, $emp, 'chat');
        WellbeingQuotaService::reserve($emp, $s1, 'expert_chat');

        $this->assertSame(1, WellbeingQuotaService::bucketBalance($program, 'expert_chat'));

        // bulan berganti TIDAK mereset (kontrak-level, tanpa period_ym)
        $this->travel(1)->months();
        $this->assertSame(1, WellbeingQuotaService::bucketBalance($program->fresh(), 'expert_chat'));
    }

    public function test_reserve_aborts_after_contract_ends()
    {
        [$p, $emp, , ] = $this->fixture();
        $this->program($p);


        // program lewat ends_at → semua reserve abort 403
        $program = CompanyWellbeingProgram::first();
        $program->update(['ends_at' => today()->subDay()]);

        $session = $this->makeSession($p, $emp, 'chat');
        $e = $this->tryReserve($p, $emp, $session, 'expert_chat');

        $this->assertNotNull($e);
        $this->assertSame(403, $e->getStatusCode());
        $this->assertDatabaseMissing('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_expert_chat_and_deep_support_buckets_are_separate()
    {
        [$p, $emp, , ] = $this->fixture();
        $program = $this->program($p, ['expert_chat_quota' => 1, 'deep_support_quota' => 1]);

        $chat = $this->makeSession($p, $emp, 'chat');
        $one = $this->makeSession($p, $emp, 'one_on_one');

        WellbeingQuotaService::reserve($emp, $chat, 'expert_chat');
        WellbeingQuotaService::reserve($emp, $one, 'deep_support');

        // chat habis, deep_support juga 0? tidak — masing2 snapshot sendiri
        $this->assertSame(0, WellbeingQuotaService::bucketBalance($program, 'expert_chat'));
        $this->assertSame(0, WellbeingQuotaService::bucketBalance($program, 'deep_support'));

        // habis di satu bucket tidak menyita bucket lain (kuota lebih besar)
        $program->update(['deep_support_quota' => 3]);
        $s3 = $this->makeSession($p, $emp, 'one_on_one');
        $this->assertNull($this->tryReserve($p, $emp, $s3, 'deep_support')); // lolos
        $s4 = $this->makeSession($p, $emp, 'chat');
        $e = $this->tryReserve($p, $emp, $s4, 'expert_chat'); // chat tetap habis
        $this->assertSame(403, $e->getStatusCode());
    }

    public function test_reserve_idempotent_via_unique_session_event()
    {
        [$p, $emp, , ] = $this->fixture();
        $this->program($p);
        $session = $this->makeSession($p, $emp, 'chat');

        $first = WellbeingQuotaService::reserve($emp, $session, 'expert_chat');
        $second = WellbeingQuotaService::reserve($emp, $session, 'expert_chat');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', 'reserved')->count());
    }

    public function test_option_a_vs_b_changes_deep_support_snapshot()
    {
        // Option A (mhcu): 1-on-1 konsumsi deep_support snapshot dari band MHCU;
        // Option B (one_on_one): snapshot default katalog. Service membaca snapshot
        // program — bucket target sama, SALDO yang berbeda.
        [$pA, $empA, , ] = $this->fixture();
        $progA = $this->program($pA, ['deep_support_mode' => 'mhcu', 'deep_support_quota' => 15]);
        [$pB, $empB, , ] = $this->fixture();
        $progB = $this->program($pB, ['deep_support_mode' => 'one_on_one', 'deep_support_quota' => 10]);

        $this->assertSame('mhcu', $progA->deep_support_mode);
        $this->assertSame('one_on_one', $progB->deep_support_mode);

        $sA = $this->makeSession($pA, $empA, 'one_on_one');
        WellbeingQuotaService::reserve($empA, $sA, 'deep_support');
        $sB = $this->makeSession($pB, $empB, 'one_on_one');
        WellbeingQuotaService::reserve($empB, $sB, 'deep_support');

        // keduanya masuk bucket deep_support (konsumsi 1), saldo sisa beda per snapshot
        $this->assertSame(14, WellbeingQuotaService::bucketBalance($progA, 'deep_support'));
        $this->assertSame(9, WellbeingQuotaService::bucketBalance($progB, 'deep_support'));
    }

    public function test_no_show_sweep_forfeits_in_wellbeing_ledger_only()
    {
        [$p, $emp, , ] = $this->fixture();
        $this->program($p);
        $session = $this->makeSession($p, $emp, 'chat');
        $session->update(['scheduled_at' => now()->subHours(25)]);
        WellbeingQuotaService::reserve($emp, $session, 'expert_chat');

        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);

        $this->assertSame('no_show', $session->fresh()->status);
        $this->assertDatabaseHas('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'forfeited',
        ]);
        // anti double-ledger: legacy tak tersentuh
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
        // hangus TERHITUNG terpakai (R11)
        $this->assertSame(4, WellbeingQuotaService::bucketBalance(CompanyWellbeingProgram::first(), 'expert_chat'));
    }

    public function test_sweep_legacy_session_still_uses_old_path()
    {
        // perusahaan tanpa program → sweep menulis consultation_quota_ledger (identik)
        [$p, $emp, , ] = $this->fixture();
        $pkg = Package::create(['name' => 'Legacy', 'monthly_quota' => 2, 'is_active' => true]);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
        $session = $this->makeSession($p, $emp, 'chat');
        $session->update(['scheduled_at' => now()->subHours(25)]);
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $session->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);

        $this->artisan('sessions:sweep-no-show')->assertExitCode(0);

        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'forfeited',
        ]);
        $this->assertDatabaseMissing('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_active_program_routes_request_consultation_to_wellbeing()
    {
        [$p, $emp, $user, ] = $this->fixture();
        $this->program($p);
        $p->update(['consultant_request_enabled' => true]);

        $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Butuh bicara.',
            'mode' => 'online',
        ])->assertRedirect();

        $session = PerusahaanSession::where('employee_id', $emp->id)->first();
        $this->assertDatabaseHas('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'bucket' => 'expert_chat', 'event' => 'reserved',
        ]);
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_no_program_request_consultation_stays_legacy()
    {
        [$p, $emp, $user, ] = $this->fixture();
        $p->update(['consultant_request_enabled' => true]);
        $pkg = Package::create(['name' => 'Legacy', 'monthly_quota' => 2, 'is_active' => true]);
        EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)->postJson(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Butuh bicara.',
            'mode' => 'online',
        ])->assertRedirect();

        $session = PerusahaanSession::where('employee_id', $emp->id)->first();
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
        ]);
        $this->assertDatabaseMissing('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_active_program_booking_routes_by_consultation_type()
    {
        [$p, $emp, $user, ] = $this->fixture();
        $this->program($p, ['expert_chat_quota' => 2, 'deep_support_quota' => 2]);
        $slot = $this->programSlot('perusahaan', $p->id);

        // chat → bucket expert_chat
        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id, 'konsultan_type' => 'psikolog',
            'consultation_type' => 'chat',
        ])->assertRedirect(route('perusahaan.employee'));

        $chat = PerusahaanSession::where('employee_id', $emp->id)->first();
        $this->assertDatabaseHas('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $chat->id, 'bucket' => 'expert_chat', 'event' => 'reserved',
        ]);
        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $chat->id,
        ]);

        // one_on_one → bucket deep_support (butuh ruang fisik bebas)
        \App\Models\Perusahaan\Room::create([
            'perusahaan_id' => $p->id, 'name' => 'Ruang WB', 'status' => 'active',
        ]);
        $slot2 = $this->programSlot('perusahaan', $p->id, 'nutrisionis');
        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot2->id, 'konsultan_type' => 'nutrisionis',
            'consultation_type' => 'one_on_one',
        ])->assertRedirect(route('perusahaan.employee'));

        $one = PerusahaanSession::where('employee_id', $emp->id)->orderByDesc('id')->first();
        $this->assertDatabaseHas('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $one->id, 'bucket' => 'deep_support', 'event' => 'reserved',
        ]);
    }

    public function test_no_program_booking_stays_legacy_path()
    {
        [$p, $emp, $user, ] = $this->fixture();
        $pkg = Package::create(['name' => 'Legacy', 'monthly_quota' => 2, 'is_active' => true]);
        EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id, 'konsultan_type' => 'psikolog',
            'consultation_type' => 'chat',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = PerusahaanSession::where('employee_id', $emp->id)->first();
        $this->assertDatabaseHas('consultation_quota_ledger', [
            'perusahaan_session_id' => $session->id, 'event' => 'reserved',
        ]);
        $this->assertDatabaseMissing('wellbeing_quota_ledger', [
            'perusahaan_session_id' => $session->id,
        ]);
    }

    public function test_exhausted_bucket_message_names_program()
    {
        [$p, $emp, , ] = $this->fixture();
        $this->program($p, ['expert_chat_quota' => 0]);

        $session = $this->makeSession($p, $emp, 'chat');
        $e = $this->tryReserve($p, $emp, $session, 'expert_chat');

        $this->assertSame(403, $e->getStatusCode());
        $this->assertSame(
            'Kuota kontrak kuota chat sudah habis (Growth). Hubungi HR untuk info program.',
            $e->getMessage()
        );
    }
}
