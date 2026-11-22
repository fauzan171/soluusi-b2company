<?php

namespace Tests\Feature;

use App\Models\AssessmentInstrument;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Perusahaan\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * W8 — matriks isolasi lintas tenant (K15) dalam SATU file: perusahaan A
 * memiliki semua artifact mission (ledger, pool, booking, room, meeting-link,
 * chat, notes/results/reviews, assessment); user perusahaan B (employee, HR,
 * konsultan B) memukul SEMUA route mission terhadap data A → wajib 403/404,
 * dan dashboard B tidak boleh membocorkan data A.
 */
class W8CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => Str::slug($email),
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function tenant(string $name, string $slug): array
    {
        $p = Perusahaan::create([
            'name' => $name, 'brand' => $slug, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user("emp@{$slug}.local", 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => "Emp {$slug}", 'email' => "emp@{$slug}.local", 'consent_at' => now(),
        ]);
        $hr = $this->user("hr@{$slug}.local", 'hr_admin');
        PerusahaanAdmin::create([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'name' => "HR {$slug}", 'email' => "hr@{$slug}.local",
        ]);
        $docUser = $this->user("doc@{$slug}.local", 'consultant');
        $docUser->update(['firstname' => 'Dok', 'lastname' => ucfirst($slug)]);
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $empUser, $emp, $hr, $docUser, $consultant];
    }

    private function sessionFor(array $tenant, array $attrs = []): PerusahaanSession
    {
        [$p, , $emp, , , $consultant] = $tenant;

        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
            ...$attrs,
        ]);
    }

    private function seedInstrument(): AssessmentInstrument
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AssessmentInstrumentSeeder::class]);

        return AssessmentInstrument::where('code', 'who5')->firstOrFail();
    }

    // --- Directory / pool (K15) ---------------------------------------------

    public function test_directory_of_tenant_b_does_not_list_tenant_a_consultants()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        [, $userB] = $b;

        $res = $this->actingAs($userB)->get(route('perusahaan.consultants'));

        $res->assertOk()->assertDontSee('Dok Pta');
        $res->assertOk()->assertSee('Dok Ptb');
    }

    public function test_tenant_b_employee_cannot_view_tenant_a_consultant_detail()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        [, , , , , $consultantA] = $a;
        [, $userB] = $b;

        // Konsultan A tidak ada di pool perusahaan B → 403.
        $this->actingAs($userB)
            ->get(route('perusahaan.consultants.show', $consultantA))
            ->assertForbidden();
    }

    // --- Booking / session lifecycle ----------------------------------------

    public function test_tenant_b_employee_cannot_cancel_or_reschedule_tenant_a_session()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, $userB] = $b;

        $this->actingAs($userB)
            ->post(route('perusahaan.booking.cancel', $sessionA), ['reason' => 'intrusi'])
            ->assertForbidden();
        $this->actingAs($userB)
            ->post(route('perusahaan.booking.reschedule', $sessionA), ['schedule_id' => 1])
            ->assertForbidden();

        $this->assertSame('scheduled', $sessionA->fresh()->status);
    }

    public function test_tenant_b_employee_cannot_start_chat_with_tenant_a_consultant()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        [, , , , , $consultantA] = $a;
        [, $userB] = $b;

        // Pool B tidak punya konsultan A: employee B diarahkan ke booking, TANPA sesi baru milik tenant A.
        $this->actingAs($userB)
            ->post(route('perusahaan.consultants.start-chat', $consultantA))
            ->assertRedirect(route('perusahaan.booking'));

        $this->assertDatabaseMissing('perusahaan_sessions', ['consultant_id' => $consultantA->id]);
    }

    // --- Quota ledger / refund ----------------------------------------------

    public function test_tenant_b_hr_cannot_refund_tenant_a_ledger()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, , $empA] = $a;
        [, , , $hrB] = $b;

        \App\Services\QuotaService::reserve($empA, $sessionA);
        $ledgerA = \App\Models\Perusahaan\ConsultationQuotaLedger::where('perusahaan_session_id', $sessionA->id)
            ->where('event', 'reserved')->firstOrFail();

        $this->actingAs($hrB)
            ->postJson(route('perusahaan.quota.refund', $ledgerA), ['reason' => 'intrusi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('consultation_quota_ledger', [
            'perusahaan_session_id' => $sessionA->id, 'event' => 'refunded',
        ]);
    }

    // --- Rooms ---------------------------------------------------------------

    public function test_tenant_b_hr_cannot_toggle_tenant_a_room()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        [$pA] = $a;
        [, , , $hrB] = $b;

        $roomA = Room::create(['perusahaan_id' => $pA->id, 'name' => 'Rahasia Ruang A', 'status' => 'active']);

        $this->actingAs($hrB)
            ->post(route('perusahaan.rooms.toggle', $roomA))
            ->assertForbidden();

        $this->assertSame('active', $roomA->fresh()->status);
    }

    // --- Meeting link --------------------------------------------------------

    public function test_tenant_b_hr_cannot_set_meeting_link_on_tenant_a_session()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, , , $hrB] = $b;

        // TenantScope global (prod T2.4): sesi tenant lain tidak visible saat
        // binding → 404, leak keberadaan sesi nol. Lebih privat dari 403.
        $this->actingAs($hrB)
            ->postJson(route('perusahaan.sessions.meeting-link', $sessionA), [
                'meeting_url' => 'https://meet.google.com/intrusi-b',
            ])->assertNotFound();

        $this->assertDatabaseMissing('perusahaan_sessions', [
            'id' => $sessionA->id, 'meeting_url' => 'https://meet.google.com/intrusi-b',
        ]);
    }

    public function test_tenant_b_consultant_cannot_set_meeting_link_on_tenant_a_session()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, , , , $docUserB] = $b;

        $this->actingAs($docUserB)
            ->postJson(route('consultant.sessions.meeting-link', $sessionA), [
                'meeting_url' => 'https://meet.google.com/intrusi-b',
            ])->assertForbidden();

        $this->assertNull($sessionA->fresh()->meeting_url);
    }

    // --- Chat ----------------------------------------------------------------

    public function test_tenant_b_employee_cannot_read_or_post_in_tenant_a_chat()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, $userB] = $b;

        $this->actingAs($userB)->get(route('perusahaan.chat', $sessionA))->assertForbidden();
        $this->actingAs($userB)
            ->postJson(route('perusahaan.chat.store', $sessionA), ['body' => 'intrusi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('chat_messages', ['perusahaan_session_id' => $sessionA->id]);
    }

    public function test_tenant_b_consultant_cannot_read_or_post_in_tenant_a_chat()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, , , , $docUserB] = $b;

        $this->actingAs($docUserB)->get(route('consultant.chat', $sessionA))->assertForbidden();
        $this->actingAs($docUserB)
            ->postJson(route('consultant.chat.store', $sessionA), ['body' => 'intrusi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('chat_messages', ['perusahaan_session_id' => $sessionA->id]);
    }

    // --- Notes / results / reviews ------------------------------------------

    public function test_tenant_b_consultant_cannot_write_note_or_result_on_tenant_a_session()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a);
        [, , , , $docUserB] = $b;

        $this->actingAs($docUserB)
            ->postJson(route('consultant.notes.store', $sessionA), ['body' => 'intrusi'])
            ->assertForbidden();
        $this->actingAs($docUserB)
            ->postJson(route('consultant.results.store', $sessionA), ['summary' => 'intrusi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('consultation_notes', ['perusahaan_session_id' => $sessionA->id]);
        $this->assertDatabaseMissing('consultation_results', ['perusahaan_session_id' => $sessionA->id]);
    }

    public function test_tenant_b_employee_cannot_review_or_rate_tenant_a_session()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $sessionA = $this->sessionFor($a, ['status' => 'done', 'completed_at' => now()]);
        [, $userB] = $b;

        $this->actingAs($userB)
            ->postJson(route('perusahaan.reviews.store', $sessionA), ['rating' => 5])
            ->assertForbidden();
        $this->actingAs($userB)
            ->postJson(route('perusahaan.chat.rate', $sessionA), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseMissing('consultation_reviews', ['perusahaan_session_id' => $sessionA->id]);
        $this->assertNull($sessionA->fresh()->rating);
    }

    // --- Assessment ----------------------------------------------------------

    public function test_tenant_b_employee_cannot_view_tenant_a_assessment_result()
    {
        $this->seedInstrument();
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        [$pA, , $empA] = $a;
        [, $userB] = $b;

        $assessmentA = PerusahaanAssessment::create([
            'perusahaan_id' => $pA->id, 'employee_id' => $empA->id,
            'instrument_code' => 'who5',
            'answers' => ['W1' => 5, 'W2' => 5, 'W3' => 5, 'W4' => 5, 'W5' => 5],
            'score' => 100, 'taken_at' => now(),
        ]);

        $this->actingAs($userB)
            ->get(route('perusahaan.assessment.result', $assessmentA))
            ->assertForbidden();
    }

    // --- Roster / dashboard no-leak -----------------------------------------

    public function test_tenant_b_employee_dashboard_does_not_leak_tenant_a_data()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $this->sessionFor($a, ['topic_category' => 'Rahasia Topik A']);
        [, $userB] = $b;

        $res = $this->actingAs($userB)->get(route('perusahaan.employee'));

        $res->assertOk()->assertDontSee('Rahasia Topik A')->assertDontSee('Dok Pta');
    }

    public function test_tenant_b_hr_dashboard_does_not_leak_tenant_a_data()
    {
        $a = $this->tenant('PT A', 'pta');
        $b = $this->tenant('PT B', 'ptb');
        $this->sessionFor($a, ['topic_category' => 'Rahasia Topik A']);
        [, , , $hrB] = $b;

        $res = $this->actingAs($hrB)->get(route('perusahaan.dashboard'));

        $res->assertOk()->assertDontSee('Rahasia Topik A')->assertDontSee('PT A');
    }
}
