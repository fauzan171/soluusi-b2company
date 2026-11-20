<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HrReviewTest extends TestCase
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

    /** [$perusahaan, $employeeUser, $employee, $hrUser, $consultant] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $hrUser = $this->user('hr@test.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hrUser->id, 'name' => 'HR']);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $empUser, $emp, $hrUser, $consultant];
    }

    private function pendingSession(Perusahaan $p, PerusahaanEmployee $emp): PerusahaanSession
    {
        return PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);
    }

    public function test_hr_sees_pending_request_list()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $pending = $this->pendingSession($p, $emp);
        // Sesi perusahaan lain tidak ikut.
        $other = Perusahaan::create([
            'name' => 'PT Lain', 'brand' => 'Other', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $other->id, 'employee_id' => null,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);
        // Sesi yang sudah terjadwal tidak masuk antrean review.
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertSeeText('Review Konsultasi')
            ->assertViewHas('requests', fn ($requests) => $requests->count() === 1
                && $requests->first()->id === $pending->id);
    }

    public function test_hr_approve_assigns_consultant()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertRedirect();

        $session->refresh();
        $this->assertSame($consultant->id, $session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at); // konsultan tetap set jadwal (ACC)
    }

    /** F1: approve kedua (model dua HR klik bersamaan) harus 422, bukan menimpa. */
    public function test_double_approve_second_is_rejected()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertRedirect();

        $first = $session->fresh()->consultant_id;

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertStatus(422);

        // Konsultan pertama tidak tertimpa, audit tidak dobel.
        $this->assertSame($first, $session->fresh()->consultant_id);
        $this->assertSame(1, AdminAction::where('action', 'hr_review.approve')
            ->where('target_id', $session->id)->count());
    }

    public function test_hr_approve_with_picked_consultant()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $docUser2 = $this->user('doc2@test.local', 'consultant');
        $consultant2 = Consultant::create([
            'user_id' => $docUser2->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant2->id]);
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session), [
            'consultant_id' => $consultant2->id,
        ])->assertRedirect();

        $this->assertSame($consultant2->id, $session->refresh()->consultant_id);
    }

    public function test_hr_cannot_approve_non_pending_session()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertStatus(422);
    }

    public function test_hr_reject_marks_cancelled_with_reason()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.reject', $session), [
            'reason' => 'Kuota diprioritaskan untuk tim lain.',
        ])->assertRedirect();

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertStringContainsString('Kuota diprioritaskan', $session->cancel_reason);
        $this->assertNotNull($session->cancelled_at);
    }

    public function test_hr_reject_requires_reason()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.reject', $session), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame('scheduled', $session->refresh()->status);
    }

    public function test_employee_cannot_access_hr_review_endpoints()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($empUser)->get(route('perusahaan.review'))->assertForbidden();
        $this->actingAs($empUser)->post(route('perusahaan.review.approve', $session))->assertForbidden();
        $this->actingAs($empUser)->post(route('perusahaan.review.reject', $session), ['reason' => 'x'])->assertForbidden();
    }

    public function test_employee_request_consultation_creates_pending_hr_review()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Stress',
            'body' => 'Saya butuh konsultasi.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = PerusahaanSession::first();
        $this->assertNull($session->consultant_id); // belum di-assign — menunggu HR
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
    }

    public function test_employee_request_persists_request_notes()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();

        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'topic_category' => 'Stress',
            'body' => 'Saya butuh konsultasi soal burnout.', 'mode' => 'online',
        ])->assertRedirect();

        $this->assertSame('Saya butuh konsultasi soal burnout.', PerusahaanSession::first()->request_notes);
    }

    public function test_hr_review_page_never_renders_request_notes()
    {
        // Dibalik dari assertSeeText: isi keluhan hanya boleh dibaca karyawan +
        // konsultan yang ditugaskan. HR cukup jenis konsultan + kategori topik
        // untuk menugaskan. Lihat RequestNotesPrivacyTest untuk invariant penuh.
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);
        $session->update(['request_notes' => 'Saya butuh konsultasi soal kecemasan.']);

        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertDontSeeText('Saya butuh konsultasi soal kecemasan.');
    }

    public function test_hr_approve_keeps_request_notes_untouched()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);
        $session->update(['request_notes' => 'Saya butuh konsultasi.']);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session));

        $this->assertSame('Saya butuh konsultasi.', $session->refresh()->request_notes);
    }

    public function test_employee_can_request_again_after_hr_reject()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.reject', $session), ['reason' => 'Ditolak.']);

        $this->actingAs($empUser)->post(route('perusahaan.request-consultation'), [
            'konsultan_type' => 'psikolog',
            'body' => 'Request ulang setelah ditolak.', 'mode' => 'online',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertSame(2, PerusahaanSession::count());
        $this->assertNull(PerusahaanSession::latest('id')->first()->consultant_id);
    }

    // --- F2.3: HR consultant allocation approval (suggested + override) ---

    private function secondConsultant(Perusahaan $p): Consultant
    {
        $docUser2 = $this->user('doc2@test.local', 'consultant');
        $consultant2 = Consultant::create([
            'user_id' => $docUser2->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant2->id]);

        return $consultant2;
    }

    public function test_hr_review_page_shows_suggested_consultant()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $this->secondConsultant($p);
        $session = $this->pendingSession($p, $emp);

        // Beban: consultant utama punya 1 sesi scheduled → suggested = konsultan ke-2.
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertViewHas('suggested', fn ($suggested) => ($suggested[$session->id] ?? null) !== null
                && $suggested[$session->id]['id'] !== $consultant->id);
    }

    public function test_hr_approve_with_suggested_consultant_audits_suggested_id()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session))
            ->assertRedirect();

        $row = \App\Models\AdminAction::where('action', 'hr_review.approve')->latest('id')->first();
        $this->assertSame($consultant->id, $row->payload_json['consultant_id']);
        $this->assertSame($consultant->id, $row->payload_json['suggested_id']);
        $this->assertArrayNotHasKey('override_reason', $row->payload_json);
    }

    public function test_hr_approve_with_override_audits_reason()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $consultant2 = $this->secondConsultant($p);
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session), [
            'consultant_id' => $consultant2->id,
            'override_reason' => 'Pasien pernah konsultasi dengan konsultan ini.',
        ])->assertRedirect();

        $this->assertSame($consultant2->id, $session->refresh()->consultant_id);

        $row = \App\Models\AdminAction::where('action', 'hr_review.approve')->latest('id')->first();
        $this->assertSame($consultant2->id, $row->payload_json['consultant_id']);
        $this->assertSame($consultant->id, $row->payload_json['suggested_id']);
        $this->assertSame('Pasien pernah konsultasi dengan konsultan ini.', $row->payload_json['override_reason']);
    }

    public function test_hr_approve_override_reason_optional()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $consultant2 = $this->secondConsultant($p);
        $session = $this->pendingSession($p, $emp);

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session), [
            'consultant_id' => $consultant2->id,
        ])->assertRedirect();

        $row = \App\Models\AdminAction::where('action', 'hr_review.approve')->latest('id')->first();
        $this->assertArrayNotHasKey('override_reason', $row->payload_json);
    }
}
