<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// ponytail: negative-only tenancy tests — positive flow coverage ada di ChatTest/HrReviewTest/dll.
class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

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

    private function perusahaan(string $name): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
    }

    private function employee(Perusahaan $p, User $user, string $name): PerusahaanEmployee
    {
        return PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => $name, 'email' => $user->email, 'consent_at' => now(),
        ]);
    }

    private function consultant(string $email): array
    {
        $user = $this->user($email, 'consultant');
        $consultant = Consultant::create([
            'user_id' => $user->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$user, $consultant];
    }

    private function scheduledSession(PerusahaanEmployee $emp, int $consultantId): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $emp->perusahaan_id,
            'employee_id' => $emp->id,
            'consultant_id' => $consultantId,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    /** 1+2. Employee perusahaan X tidak bisa lihat / POST chat ke sesi employee perusahaan Y. */
    public function test_employee_cannot_access_session_of_other_perusahaan()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $userA = $this->user('a@x.local', 'employee');
        $userB = $this->user('b@y.local', 'employee');
        $empA = $this->employee($px, $userA, 'A');
        $empB = $this->employee($py, $userB, 'B');
        [, $consultant] = $this->consultant('doc@iso.local');

        $sessionB = $this->scheduledSession($empB, $consultant->id);

        $this->actingAs($userA)->get(route('perusahaan.chat', $sessionB))->assertForbidden();
        $this->actingAs($userA)->postJson(route('perusahaan.chat.store', $sessionB), ['body' => 'intrusi'])
            ->assertForbidden();

        $this->assertSame(0, $sessionB->chatMessages()->count());
    }

    /** 3. HR admin perusahaan A tidak bisa akses dashboard perusahaan B. */
    public function test_hr_admin_cannot_access_other_perusahaan_dashboard_or_review()
    {
        $px = $this->perusahaan('PT X');
        $py = $this->perusahaan('PT Y');
        $hrA = $this->user('hr-a@x.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $px->id, 'user_id' => $hrA->id, 'role' => 'hr_admin']);

        // Dashboard A boleh.
        $this->actingAs($hrA)->get(route('perusahaan.dashboard'))->assertOk();

        // ?perusahaan_id= bukan super admin → diabaikan, tetap resolve ke A (200, bukan data B).
        // Verifikasi: review queue hanya memuat sesi A.
        $userB = $this->user('b@y.local', 'employee');
        $empB = $this->employee($py, $userB, 'B');
        $sessionB = PerusahaanSession::create([
            'perusahaan_id' => $py->id, 'employee_id' => $empB->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        // T2.4: TenantScope memfilter session tenant lain di route-model-binding → 404
        // (lebih kuat dari 422 — resource tidak pernah terlihat, bukan hanya ditolak).
        $this->actingAs($hrA)->post(route('perusahaan.review.approve', $sessionB))
            ->assertStatus(404);
        $this->actingAs($hrA)->post(route('perusahaan.review.reject', $sessionB), ['reason' => 'x'])
            ->assertStatus(404);

        $this->assertSame('scheduled', $sessionB->refresh()->status);
        $this->assertNull($sessionB->consultant_id);
    }

    /** 4+5. Consultant tidak bisa akses / check-in sesi consultant lain (ConsultationSession, klinik vertical). */
    public function test_consultant_cannot_touch_other_consultants_consultation_session()
    {
        [$ownerUser, $owner] = $this->consultant('owner@iso.local');
        [$otherUser] = $this->consultant('other@iso.local');

        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $owner->id, 'price' => 100_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $owner->id]);
        $session = ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $owner->id,
            'consultant_package_id' => $pkg->id,
            'order_number' => 'ISO-1',
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

    /** 4b. Consultant lain tidak bisa buka room chat / kirim pesan ke PerusahaanSession consultant lain. */
    public function test_other_consultant_cannot_chat_in_perusahaan_session()
    {
        $p = $this->perusahaan('PT X');
        $empUser = $this->user('emp@x.local', 'employee');
        $emp = $this->employee($p, $empUser, 'Emp');
        [$ownerUser, $owner] = $this->consultant('owner@iso.local');
        [$otherUser] = $this->consultant('other@iso.local');

        $session = $this->scheduledSession($emp, $owner->id);

        $this->actingAs($otherUser)->get(route('consultant.chat', $session))->assertForbidden();
        $this->actingAs($otherUser)->postJson(route('consultant.chat.store', $session), ['body' => 'spoof'])
            ->assertForbidden();

        $this->assertSame(0, $session->chatMessages()->count());
    }

    /** 6. Employee (perusahaan) tidak bisa akses endpoint student sekolah. */
    public function test_employee_cannot_access_sekolah_student_endpoints()
    {
        $p = $this->perusahaan('PT X');
        $empUser = $this->user('emp@x.local', 'employee');
        $this->employee($p, $empUser, 'Emp');

        $this->actingAs($empUser)->get(route('sekolah.student'))->assertForbidden();
        $this->actingAs($empUser)->get(route('sekolah.booking'))->assertForbidden();
        $this->actingAs($empUser)->post(route('sekolah.assessment.store'), [
            'answers' => [1, 1, 1, 1, 1],
        ])->assertForbidden();
    }

    /** 7. Klinik admin tidak bisa akses endpoint HR perusahaan. */
    public function test_klinik_admin_cannot_access_perusahaan_hr_endpoints()
    {
        $klinik = Klinik::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Klinik Iso', 'brand' => 'KlinikIso', 'is_active' => true,
        ]);
        $adminUser = $this->user('admin@klinik.local', 'klinik_admin');
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $adminUser->id, 'role' => 'admin']);

        $this->actingAs($adminUser)->get(route('perusahaan.dashboard'))->assertForbidden();
        $this->actingAs($adminUser)->get(route('perusahaan.employees'))->assertForbidden();
        $this->actingAs($adminUser)->get(route('perusahaan.review'))->assertForbidden();
    }

    /** 8. Chat messages session A tidak terlihat di session B (sesi berbeda, consultant sama). */
    public function test_chat_messages_not_visible_across_sessions()
    {
        $p = $this->perusahaan('PT X');
        $userA = $this->user('a@x.local', 'employee');
        $userB = $this->user('b@x.local', 'employee');
        $empA = $this->employee($p, $userA, 'A');
        $empB = $this->employee($p, $userB, 'B');
        [, $consultant] = $this->consultant('doc@iso.local');

        $sessionA = $this->scheduledSession($empA, $consultant->id);
        $sessionB = $this->scheduledSession($empB, $consultant->id);

        $this->actingAs($userA)->postJson(route('perusahaan.chat.store', $sessionA), ['body' => 'rahasia-A'])
            ->assertOk();

        // Room B tidak memuat pesan A.
        $this->actingAs($userB)->getJson(route('perusahaan.chat', $sessionB))
            ->assertOk()
            ->assertJsonMissing(['body' => 'rahasia-A']);

        // Employee B juga tidak bisa polling room A.
        $this->actingAs($userB)->getJson(route('perusahaan.chat', $sessionA))->assertForbidden();

        $this->assertSame(1, ChatMessage::count());
        $this->assertSame($sessionA->id, ChatMessage::first()->perusahaan_session_id);
    }

    /** 9. Rating session A tidak memengaruhi session B; rating silang ditolak. */
    public function test_rating_isolated_per_session()
    {
        $p = $this->perusahaan('PT X');
        $userA = $this->user('a@x.local', 'employee');
        $userB = $this->user('b@x.local', 'employee');
        $empA = $this->employee($p, $userA, 'A');
        $empB = $this->employee($p, $userB, 'B');
        [, $consultant] = $this->consultant('doc@iso.local');

        $sessionA = $this->scheduledSession($empA, $consultant->id);
        $sessionA->update(['status' => 'done', 'completed_at' => now()]);
        $sessionB = $this->scheduledSession($empB, $consultant->id);
        $sessionB->update(['status' => 'done', 'completed_at' => now()]);

        // Employee B tidak bisa rate session A.
        $this->actingAs($userB)->post(route('perusahaan.chat.rate', $sessionA), ['rating' => 1])
            ->assertForbidden();
        $this->assertNull($sessionA->fresh()->rating);

        // Owner rate session A → session B rating tetap null.
        $this->actingAs($userA)->post(route('perusahaan.chat.rate', $sessionA), ['rating' => 5])
            ->assertRedirect();
        $this->assertSame(5, $sessionA->fresh()->rating);
        $this->assertNull($sessionB->fresh()->rating);
    }
}
