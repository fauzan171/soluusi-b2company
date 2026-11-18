<?php

namespace Tests\Feature\Sekolah;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task D.2: BK review queue — mirror HrReviewController (F1.1) untuk vertikal sekolah.
class SekolahBkReviewTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email).'-'.Str::random(4), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function sekolah(string $slug = 'a'): Sekolah
    {
        return Sekolah::create([
            'name' => "SMA {$slug}", 'brand' => "SMA {$slug}",
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function bk(Sekolah $sekolah, string $email = 'bk@test.local'): User
    {
        $user = $this->user($email, 'bk');
        SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'bk',
        ]);

        return $user;
    }

    private function student(Sekolah $sekolah, string $email = 'siswa@test.local'): SekolahStudent
    {
        return SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Test',
            'email' => $email, 'kelas' => 'X IPA 1',
            'parent_name' => 'Ortu', 'parent_consent_at' => now(),
        ]);
    }

    private function consultantInPool(Sekolah $sekolah, string $email = 'doc@test.local'): Consultant
    {
        $docUser = $this->user($email, 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id]);

        return $consultant;
    }

    private function pending(Sekolah $sekolah, SekolahStudent $student): SekolahSession
    {
        return SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
            'requested_for_role' => 'self',
        ]);
    }

    public function test_bk_sees_only_own_tenant_pending_requests(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $mine = $this->pending($sekolah, $student);

        // Tenant lain — tidak boleh muncul.
        $other = $this->sekolah('b');
        $this->pending($other, $this->student($other, 'siswa-b@test.local'));

        // Sudah terjadwal / sudah ada konselor — bukan antrean review.
        SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($bk)->get(route('sekolah.review'))
            ->assertOk()
            ->assertSeeText('Review Konseling')
            ->assertViewHas('requests', fn ($r) => $r->count() === 1 && $r->first()->id === $mine->id);
    }

    public function test_bk_review_page_renders_request_notes(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $session = $this->pending($sekolah, $student);
        $session->update(['request_notes' => 'Sering cemas sebelum ujian.']);

        $this->actingAs($bk)->get(route('sekolah.review'))
            ->assertOk()
            ->assertSeeText('Sering cemas sebelum ujian.');
    }

    public function test_bk_approve_assigns_suggested_consultant(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $consultant = $this->consultantInPool($sekolah);
        $session = $this->pending($sekolah, $student);

        $this->actingAs($bk)->post(route('sekolah.review.approve', $session))
            ->assertRedirect();

        $session->refresh();
        $this->assertSame($consultant->id, $session->consultant_id);
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at); // konselor yang set jadwal

        $this->assertDatabaseHas('admin_actions', [
            'action' => 'bk_review.approve',
            'target_id' => $session->id,
        ]);
    }

    public function test_bk_approve_with_picked_consultant_records_override_reason(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $docA = $this->consultantInPool($sekolah, 'a@test.local');
        $docB = $this->consultantInPool($sekolah, 'b@test.local');

        // Beban docA lebih tinggi → saran = docB; pilih docA = override.
        $session = $this->pending($sekolah, $student);
        SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'consultant_id' => $docA->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($bk)->post(route('sekolah.review.approve', $session), [
            'consultant_id' => $docA->id,
            'override_reason' => 'Sudah kenal siswa.',
        ])->assertRedirect();

        $this->assertSame($docA->id, $session->refresh()->consultant_id);

        $action = AdminAction::where('action', 'bk_review.approve')
            ->where('target_id', $session->id)->first();
        $this->assertSame($docA->id, $action->payload_json['consultant_id']);
        $this->assertSame($docB->id, $action->payload_json['suggested_id']);
        $this->assertSame('Sudah kenal siswa.', $action->payload_json['override_reason']);
    }

    public function test_bk_cannot_approve_consultant_outside_pool(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $this->consultantInPool($sekolah);
        $outsider = $this->consultantInPool($this->sekolah('z'), 'z@test.local');
        $session = $this->pending($sekolah, $student);

        $this->actingAs($bk)->post(route('sekolah.review.approve', $session), [
            'consultant_id' => $outsider->id,
        ])->assertStatus(422);

        $this->assertNull($session->refresh()->consultant_id);
    }

    public function test_bk_cannot_approve_non_pending_session(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $consultant = $this->consultantInPool($sekolah);
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($bk)->post(route('sekolah.review.approve', $session))
            ->assertStatus(422);
    }

    public function test_bk_reject_cancels_with_reason_and_audits(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $session = $this->pending($sekolah, $student);

        $this->actingAs($bk)->post(route('sekolah.review.reject', $session), [
            'reason' => 'Dirujuk ke layanan luar sekolah.',
        ])->assertRedirect();

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertStringContainsString('Dirujuk ke layanan luar sekolah.', $session->cancel_reason);
        $this->assertNotNull($session->cancelled_at);

        $this->assertDatabaseHas('admin_actions', [
            'action' => 'bk_review.reject',
            'target_id' => $session->id,
        ]);
    }

    public function test_bk_reject_requires_reason(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $student = $this->student($sekolah);
        $session = $this->pending($sekolah, $student);

        $this->actingAs($bk)->post(route('sekolah.review.reject', $session), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('scheduled', $session->refresh()->status);
    }

    public function test_student_and_consultant_cannot_access_bk_review(): void
    {
        $sekolah = $this->sekolah();
        $studentUser = $this->user('siswa@test.local', 'student');
        $student = $this->student($sekolah);
        $student->update(['user_id' => $studentUser->id]);
        $session = $this->pending($sekolah, $student);

        $this->actingAs($studentUser)->get(route('sekolah.review'))->assertForbidden();
        $this->actingAs($studentUser)->post(route('sekolah.review.approve', $session))->assertForbidden();

        $consultant = $this->consultantInPool($sekolah);
        $this->actingAs($consultant->user)->get(route('sekolah.review'))->assertForbidden();
    }

    public function test_bk_from_other_tenant_cannot_review(): void
    {
        $sekolahA = $this->sekolah('a');
        $sekolahB = $this->sekolah('b');
        $bkA = $this->bk($sekolahA, 'bk-a@test.local');
        $studentB = $this->student($sekolahB, 'siswa-b@test.local');
        $sessionB = $this->pending($sekolahB, $studentB);
        $this->consultantInPool($sekolahB, 'doc-b@test.local');

        // TenantScope menyembunyikan sesi tenant lain → route model binding 404 (fail-closed).
        $this->actingAs($bkA)->post(route('sekolah.review.approve', $sessionB))
            ->assertNotFound();

        $this->assertNull($sessionB->refresh()->consultant_id);
    }
}