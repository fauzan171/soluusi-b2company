<?php

namespace Tests\Feature;

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
            'body' => 'Saya butuh konsultasi.',
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
            'body' => 'Saya butuh konsultasi soal burnout.',
        ])->assertRedirect();

        $this->assertSame('Saya butuh konsultasi soal burnout.', PerusahaanSession::first()->request_notes);
    }

    public function test_hr_review_page_renders_request_notes()
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        $session = $this->pendingSession($p, $emp);
        $session->update(['request_notes' => 'Saya butuh konsultasi soal kecemasan.']);

        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertSeeText('Saya butuh konsultasi soal kecemasan.');
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
            'body' => 'Request ulang setelah ditolak.',
        ])->assertRedirect(route('perusahaan.employee'));

        $this->assertSame(2, PerusahaanSession::count());
        $this->assertNull(PerusahaanSession::latest('id')->first()->consultant_id);
    }
}
