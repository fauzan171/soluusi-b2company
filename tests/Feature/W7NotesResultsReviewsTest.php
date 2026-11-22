<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\ConsultationNote;
use App\Models\Perusahaan\ConsultationResult;
use App\Models\Perusahaan\ConsultationReview;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// W7 (K14/K16): note konsultan (encrypted+audit), hasil sesi, review employee,
// rating agregat per perusahaan via consultation_reviews.
class W7NotesResultsReviewsTest extends TestCase
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

    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT W7', 'brand' => 'W7Co', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $owner = $this->user('owner@w7.local', 'employee');
        $other = $this->user('other@w7.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $owner->id,
            'name' => 'Owner', 'email' => 'owner@w7.local', 'consent_at' => now(),
        ]);
        $otherEmp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $other->id,
            'name' => 'Other', 'email' => 'other@w7.local', 'consent_at' => now(),
        ]);
        $docUser = $this->user('doc@w7.local', 'consultant');
        $otherDocUser = $this->user('doc2@w7.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $otherConsultant = Consultant::create([
            'user_id' => $otherDocUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        return [$p, $owner, $other, $emp, $otherEmp, $docUser, $otherDocUser, $consultant, $otherConsultant, $session];
    }

    // --- ConsultationNote ---------------------------------------------------

    public function test_consultant_can_store_note_encrypted_at_rest()
    {
        [$p, $owner, , $emp, , $docUser, , $consultant, , $session] = $this->fixture();

        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), [
            'body' => 'klien cemas kerja, teknik relaksasi',
        ])->assertRedirect();

        $this->assertSame(1, ConsultationNote::where('perusahaan_session_id', $session->id)->count());
        $raw = DB::table('consultation_notes')->where('perusahaan_session_id', $session->id)->value('body');
        $this->assertStringNotContainsString('cemas kerja', $raw);
        // Round-trip via model decrypts.
        $this->assertSame('klien cemas kerja, teknik relaksasi', $session->notes()->first()->body);
        // consultant_id disimpan dari sesi.
        $this->assertSame($consultant->id, $session->notes()->first()->consultant_id);
    }

    public function test_consultant_can_update_own_note()
    {
        [, , , , , $docUser, , , , $session] = $this->fixture();
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'v1'])->assertRedirect();

        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'v2 follow-up'])->assertRedirect();

        $this->assertSame(1, ConsultationNote::where('perusahaan_session_id', $session->id)->count());
        $this->assertSame('v2 follow-up', $session->notes()->first()->body);
    }

    public function test_note_audit_rows_written_for_view_and_update()
    {
        [, , , , , $docUser, , , , $session] = $this->fixture();
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'v1'])->assertRedirect();

        // View (full page chat, bukan poll JSON) → note.view.
        $this->actingAs($docUser)->get(route('consultant.chat', $session))->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $docUser->id, 'action' => 'note.view',
            'subject_type' => 'perusahaan_session', 'subject_id' => $session->id,
        ]);

        // Update → note.update.
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'v2'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $docUser->id, 'action' => 'note.update',
            'subject_type' => 'perusahaan_session', 'subject_id' => $session->id,
        ]);
    }

    public function test_note_poll_json_does_not_write_audit_row()
    {
        [, , , , , $docUser, , , , $session] = $this->fixture();
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'v1'])->assertRedirect();
        AuditLog::query()->delete();

        $this->actingAs($docUser)->getJson(route('consultant.chat', $session) . '?after=0')->assertOk();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /** Authz matrix notes: HR / consultant lain / employee lain → 403; owner 200. */
    public function test_notes_authz_matrix()
    {
        [$p, $owner, $other, $emp, $otherEmp, $docUser, $otherDocUser, $consultant, , $session] = $this->fixture();
        $hr = $this->user('hr@w7.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hr->id]);

        ConsultationNote::create([
            'perusahaan_session_id' => $session->id, 'consultant_id' => $consultant->id, 'body' => 'rahasia',
        ]);

        // HR has NO route to notes — chat route itself 403 (not in consultant group).
        $this->actingAs($hr)->get(route('consultant.chat', $session))->assertForbidden();
        $this->actingAs($hr)->postJson(route('consultant.notes.store', $session), ['body' => 'x'])->assertForbidden();

        // Other consultant (not session owner) → 403.
        $this->actingAs($otherDocUser)->postJson(route('consultant.notes.store', $session), ['body' => 'x'])->assertForbidden();
        $this->actingAs($otherDocUser)->get(route('consultant.chat', $session))->assertForbidden();

        // Employee (owner of session, but notes are consultant-only) → 403 on chat route side; notes route is consultant-group → 403.
        $this->actingAs($other)->postJson(route('consultant.notes.store', $session), ['body' => 'x'])->assertForbidden();

        // Owner consultant → 200/redirect.
        $this->actingAs($docUser)->get(route('consultant.chat', $session))->assertOk();
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => 'ok'])->assertRedirect();
    }

    public function test_note_requires_body()
    {
        [, , , , , $docUser, , , , $session] = $this->fixture();
        $this->actingAs($docUser)->postJson(route('consultant.notes.store', $session), ['body' => ''])->assertStatus(422);
    }

    // --- ConsultationResult -------------------------------------------------

    public function test_consultant_can_store_result_employee_sees_it_post_done()
    {
        [$p, $owner, , $emp, , $docUser, , , , $session] = $this->fixture();
        $this->actingAs($docUser)->postJson(route('consultant.results.store', $session), [
            'summary' => 'Sesi membahas manajemen stres',
            'recommendation' => 'Latihan pernapasan 2x/hari',
        ])->assertRedirect();

        $this->assertSame(1, ConsultationResult::where('perusahaan_session_id', $session->id)->count());

        // Owner consultant views chat room → sees result.
        $this->actingAs($docUser)->get(route('consultant.chat', $session))->assertOk()
            ->assertSee('Sesi membahas manajemen stres');

        // Employee of the session, while scheduled → sees "menunggu sesi selesai", NOT the summary.
        $this->actingAs($owner)->get(route('perusahaan.chat', $session))->assertOk()
            ->assertDontSee('Sesi membahas manajemen stres');

        // After done → visible.
        $session->update(['status' => 'done', 'completed_at' => now()]);
        $this->actingAs($owner)->get(route('perusahaan.chat', $session))->assertOk()
            ->assertSee('Sesi membahas manajemen stres');
    }

    public function test_result_authz_matrix()
    {
        [$p, $owner, $other, $emp, $otherEmp, $docUser, $otherDocUser, , , $session] = $this->fixture();
        ConsultationResult::create([
            'perusahaan_session_id' => $session->id,
            'summary' => 'rahasia hasil', 'recommendation' => null,
        ]);
        $hr = $this->user('hr@w7.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hr->id]);
        $session->update(['status' => 'done', 'completed_at' => now()]);

        // Store: only session-owner consultant.
        $this->actingAs($otherDocUser)->postJson(route('consultant.results.store', $session), ['summary' => 'x'])->assertForbidden();
        $this->actingAs($other)->postJson(route('consultant.results.store', $session), ['summary' => 'x'])->assertForbidden();
        $this->actingAs($hr)->postJson(route('consultant.results.store', $session), ['summary' => 'x'])->assertForbidden();
        $this->actingAs($docUser)->postJson(route('consultant.results.store', $session), ['summary' => 'ringkasan baru'])->assertRedirect();

        // View: employee of another session → chat route 403 → cannot see result.
        $this->actingAs($other)->get(route('perusahaan.chat', $session))->assertForbidden();
        // Session employee (owner) sees it post-done.
        $this->actingAs($owner)->get(route('perusahaan.chat', $session))->assertOk()->assertSee('ringkasan baru');
    }

    // --- ConsultationReview -------------------------------------------------

    public function test_employee_can_review_once_after_done()
    {
        [, $owner, , , , , , $consultant, , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        // Before review: prompt visible.
        $this->actingAs($owner)->get(route('perusahaan.chat', $session))->assertOk()->assertSee('Beri ulasan');

        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), [
            'rating' => 5, 'comment' => 'Konsultasi sangat membantu',
        ])->assertRedirect();

        $this->assertSame(1, ConsultationReview::where('perusahaan_session_id', $session->id)->count());
        $this->assertSame(5, $session->reviews()->first()->rating);
        $this->assertSame($session->employee_id, $session->reviews()->first()->perusahaan_employee_id);

        // Second review → 422 (UNIQUE, app-level).
        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), [
            'rating' => 1, 'comment' => 'duplikat',
        ])->assertStatus(422);
        $this->assertSame(5, $session->reviews()->first()->fresh()->rating);
    }

    public function test_review_blocked_before_done()
    {
        [, $owner, , , , , , , , $session] = $this->fixture();
        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), [
            'rating' => 4,
        ])->assertStatus(422);
        $this->assertSame(0, ConsultationReview::count());
    }

    public function test_review_rating_validated_1_to_5()
    {
        [, $owner, , , , , , , , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);

        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 0])->assertStatus(422);
        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 6])->assertStatus(422);
        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), ['rating' => null])->assertStatus(422);
        $this->assertSame(0, ConsultationReview::count());
    }

    public function test_review_authz_matrix()
    {
        [$p, $owner, $other, $emp, $otherEmp, $docUser, , $consultant, , $session] = $this->fixture();
        $hr = $this->user('hr@w7.local', 'hr_admin');
        \App\Models\Perusahaan\PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hr->id]);
        $session->update(['status' => 'done', 'completed_at' => now()]);

        // Other employee → 403.
        $this->actingAs($other)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 5])->assertForbidden();
        // Consultant → 403 (consultant group route; employee group middleware rejects).
        $this->actingAs($docUser)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 5])->assertForbidden();
        // HR → 403.
        $this->actingAs($hr)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 5])->assertForbidden();
        // Session employee → redirect OK.
        $this->actingAs($owner)->postJson(route('perusahaan.reviews.store', $session), ['rating' => 4])->assertRedirect();
    }

    public function test_consultant_does_not_see_individual_review_comment()
    {
        [$p, $owner, , $emp, , $docUser, , $consultant, , $session] = $this->fixture();
        $session->update(['status' => 'done', 'completed_at' => now()]);
        ConsultationReview::create([
            'perusahaan_session_id' => $session->id, 'perusahaan_employee_id' => $emp->id,
            'rating' => 4, 'comment' => 'komentar rahasia karyawan',
        ]);

        // Consultant chat room: aggregate only, comment must NOT appear.
        $this->actingAs($docUser)->get(route('consultant.chat', $session))->assertOk()
            ->assertDontSee('komentar rahasia karyawan');
    }

    // --- Rating aggregate (W3 directory detail) ------------------------------

    public function test_directory_aggregate_rating_from_reviews()
    {
        [$p, $owner, , $emp, , , , $consultant, , ] = $this->fixture();

        $catId = DB::table('categories')->insertGetId([
            'name' => 'Psikolog', 'code_category' => Str::random(10),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consultant_categories')->insert([
            'consultant_id' => $consultant->id, 'category_id' => $catId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([4, 5] as $rating) {
            $s = PerusahaanSession::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
                'status' => 'done', 'scheduled_at' => now()->subDay(), 'completed_at' => now(),
            ]);
            ConsultationReview::create([
                'perusahaan_session_id' => $s->id, 'perusahaan_employee_id' => $emp->id,
                'rating' => $rating, 'comment' => 'ok',
            ]);
        }

        $res = $this->actingAs($owner)->get(route('perusahaan.consultants.show', $consultant))->assertOk();
        $res->assertSee('4.5')->assertSee('2 ulasan');
    }

    public function test_aggregate_is_scoped_per_perusahaan()
    {
        [$p, $owner, , $emp, , , , $consultant, , ] = $this->fixture();

        $p2 = Perusahaan::create([
            'name' => 'PT W7 B', 'brand' => 'W7CoB', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p2->id, 'consultant_id' => $consultant->id]);
        $emp2User = $this->user('emp2@w7.local', 'employee');
        $emp2 = PerusahaanEmployee::create([
            'perusahaan_id' => $p2->id, 'user_id' => $emp2User->id,
            'name' => 'Emp2', 'email' => 'emp2@w7.local', 'consent_at' => now(),
        ]);

        // PT A: 1 review rating 5. PT B: 1 review rating 1.
        foreach ([[$p, $emp, 5], [$p2, $emp2, 1]] as [$tenant, $employee, $rating]) {
            $s = PerusahaanSession::create([
                'perusahaan_id' => $tenant->id, 'employee_id' => $employee->id,
                'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
                'status' => 'done', 'scheduled_at' => now()->subDay(), 'completed_at' => now(),
            ]);
            ConsultationReview::create([
                'perusahaan_session_id' => $s->id, 'perusahaan_employee_id' => $employee->id,
                'rating' => $rating,
            ]);
        }

        $this->actingAs($owner)->get(route('perusahaan.consultants.show', $consultant))
            ->assertOk()->assertSee('5.0 / 5')->assertDontSee('1.0 / 5');
        $this->actingAs($emp2User)->get(route('perusahaan.consultants.show', $consultant))
            ->assertOk()->assertSee('1.0 / 5')->assertDontSee('5.0 / 5');
    }

    // --- AuditLog::record helper ---------------------------------------------

    public function test_audit_log_record_helper()
    {
        [, $owner, , , , , , , , $session] = $this->fixture();

        $log = AuditLog::record($owner, 'note.view', $session, ['via' => 'test']);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $owner->id, 'action' => 'note.view',
            'subject_type' => 'perusahaan_session', 'subject_id' => $session->id,
        ]);
        $this->assertSame(['via' => 'test'], $log->meta);
        $this->assertNotNull($log->created_at);
    }
}
