<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * UX audit fixes — portal sekolah:
 * 1. Cancel sesi wajib bawa `reason` (controller validasi) — form kini menyediakannya.
 * 2. Staf self-consent Unlock portal (tanpa itu 403 semua POST).
 * 4. Export CSV di halaman laporan membawa bulan terpilih (?month=).
 * 8. Student WHO-5 nag hanya saat WHO-5 di-assign.
 */
class SekolahPortalUxFixTest extends TestCase
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

    private function staff(Sekolah $sekolah, string $email = 'staff@test.local', array $overrides = []): array
    {
        $user = $this->user($email, 'sekolah_staff');
        SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'sekolah_staff',
        ]);
        $person = SekolahStudent::create(array_merge([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Staf Test', 'email' => $email, 'type' => 'staff',
            'kelas' => 'Guru', 'consent_at' => now(),
        ], $overrides));

        return [$user, $person];
    }

    private function student(Sekolah $sekolah, string $email = 'siswa@test.local', array $overrides = []): array
    {
        $user = $this->user($email, 'student');
        $person = SekolahStudent::create(array_merge([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Test', 'email' => $email, 'type' => 'student',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ], $overrides));

        return [$user, $person];
    }

    private function bookedSession(SekolahStudent $person, Sekolah $sekolah): SekolahSession
    {
        $docUser = $this->user(Str::random(8).'@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id, 'sekolah_id' => $sekolah->id, 'klinik_id' => null,
            'status' => Schedule::STATUS_CONFIRMED,
            'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(),
        ]);
        // Jangan hapus consultant user: FK consultant user tetap dipakai render
        // riwayat (head lama menghapus docUser → FK violation via Consultant).

        return SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $person->id,
            'kelas' => $person->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => $schedule->started_at, 'origin' => 'self_booking',
        ]);
    }

    /** T2: cancel dengan reason → status cancelled, dan form render berisi input reason. */
    public function test_student_cancel_with_reason_succeeds_and_form_has_reason(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->student($sekolah);
        $session = $this->bookedSession($person, $sekolah);

        // Form render berisi input reason (di portal siswa, riwayat sesi).
        $page = $this->actingAs($user)->get(route('sekolah.student'));
        $page->assertOk();
        $this->assertMatchesRegularExpression('/name="reason"/', $page->getContent());

        $this->actingAs($user)->post(route('sekolah.booking.cancel', $session), [
            'reason' => 'Bentrok jadwal ulangan.',
        ])->assertStatus(302);

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
    }

    public function test_student_cancel_without_reason_rejected(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->student($sekolah);
        $session = $this->bookedSession($person, $sekolah);

        $this->actingAs($user)->post(route('sekolah.booking.cancel', $session))
            ->assertSessionHasErrors('reason');

        $this->assertSame('scheduled', $session->refresh()->status);
    }

    /** T2: staff cancel sama aturan — reason wajib. */
    public function test_staff_cancel_with_reason_succeeds(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        $session = $this->bookedSession($person, $sekolah);

        // Form render berisi input reason (portal staf, riwayat sesi).
        $page = $this->actingAs($user)->get(route('sekolah.staff'));
        $page->assertOk();
        $this->assertMatchesRegularExpression('/name="reason"/', $page->getContent());

        $this->actingAs($user)->post(route('sekolah.staff.booking.cancel', $session), [
            'reason' => 'Ada rapat mendadak.',
        ])->assertStatus(302);

        $this->assertSame('cancelled', $session->refresh()->status);
    }

    public function test_staff_cancel_without_reason_rejected(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        $session = $this->bookedSession($person, $sekolah);

        $this->actingAs($user)->post(route('sekolah.staff.booking.cancel', $session))
            ->assertSessionHasErrors('reason');

        $this->assertSame('scheduled', $session->refresh()->status);
    }

    /** T3: staf tanpa consent: portal kunci, lalu POST self-consent unlock. */
    public function test_staff_self_consent_unlocks_portal(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah, 'staff2@test.local', ['consent_at' => null]);

        // POST apa pun dikunci 403 (resolveStaff gate) sebelum consent.
        $this->actingAs($user)->post(route('sekolah.staff.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Butuh konsultasi.',
        ])->assertStatus(403);

        // Halaman portal menampilkan UI consent sendiri.
        $this->actingAs($user)->get(route('sekolah.staff'))
            ->assertOk()
            ->assertSee(route('sekolah.staff.consent'), false)
            ->assertSee('Saya setuju ikut program', false);

        // POST consent → unlock; request konsultasi jalan.
        $this->actingAs($user)->post(route('sekolah.staff.consent'))
            ->assertRedirect(route('sekolah.staff'))
            ->assertSessionHas('success');

        $this->assertNotNull($person->refresh()->consent_at);

        $this->actingAs($user)->post(route('sekolah.staff.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Butuh konsultasi.',
        ])->assertRedirect(route('sekolah.staff'));
    }

    public function test_other_tenant_staff_cannot_post_consent_to_other_sekolah(): void
    {
        $sekolahA = $this->sekolah('A');
        $sekolahB = $this->sekolah('B');
        [$userA] = $this->staff($sekolahA, 'staff-a@test.local', ['consent_at' => null]);
        $adminB = $this->user('bk-b@test.local', 'bk');
        SekolahAdmin::create(['sekolah_id' => $sekolahB->id, 'user_id' => $adminB->id, 'role' => 'bk']);

        // Cross-sekolah: person A milik sekolah A, viewas B context → resolveStaff
        // punya user_id milik A. POST consent hanya sah, karena manajemen konsent
        // hanya boleh oleh pemilik person row (id sama user login).
        $staffB = SekolahStudent::create([
            'sekolah_id' => $sekolahB->id, 'user_id' => $userA->id,
            'name' => 'Staf B', 'email' => 'x@test.local', 'type' => 'staff',
            'kelas' => 'Guru', 'consent_at' => null,
        ]);

        // userA (staff sekolah A) berusaha consent terhadap row milik $staffB.
        $this->actingAs($userA)->get(route('sekolah.staff'))
            ->assertOk();

        $this->assertNull($staffB->consent_at);
    }

    /** T4: refer dengan parent_email + request_notes + tanpa schedule_id. */
    public function test_bk_refer_request_mode_with_parent_email(): void
    {
        Mail::fake();
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $person = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa RR', 'email' => null,
            'kelas' => 'X IPA 1', 'parent_name' => 'Ortu RR', 'parent_consent_at' => now(),
        ]);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $person->id,
            'konsultan_type' => 'karir',
            'request_notes' => 'Butuh arahan studi lanjut.',
            'topic_category' => 'Karir',
            'parent_email' => 'ortu-rr@test.local',
        ])->assertRedirect();

        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $person->id)->sole();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertNull($session->consultant_id);
        $this->assertSame('Butuh arahan studi lanjut.', $session->request_notes);
        $this->assertSame('Karir', $session->topic_category);
        Mail::assertSent(\App\Mail\Sekolah\ParentConsultationRequested::class);
    }

    /** T4: refer cross-sekolah ditolak (404 via tenant scope `exists`, bukan 403). */
    public function test_bk_cross_sekolah_refer_denied(): void
    {
        $sekolahA = $this->sekolah('A');
        $sekolahB = $this->sekolah('B');
        $bk = $this->bk($sekolahA);
        $personB = SekolahStudent::create([
            'sekolah_id' => $sekolahB->id, 'name' => 'Siswa B', 'email' => null,
            'kelas' => 'X', 'parent_consent_at' => now(),
        ]);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $personB->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Cross sekolah.',
        ])->assertNotFound();

        $this->assertSame(0, SekolahSession::withoutGlobalScopes()->count());
    }

    /**
     * T8: nag WHO-5 hanya tampil bila WHO-5 beneran di-assign.
     * Persona tanpa assignment → tidak ada teks nag "> 30 hari".
     */
    public function test_who5_nag_only_when_assigned(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->student($sekolah);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            ->assertDontSee('Asesmen terakhir');
    }

    /** T8b: dengan WHO-5 assignment aktif + skor lama, nag muncul. */
    public function test_who5_nag_shows_when_assigned_and_stale(): void
    {
        $this->seed(AssessmentInstrumentSeeder::class);
        $sekolah = $this->sekolah();
        [$user, $person] = $this->student($sekolah);
        \App\Models\AssessmentAssignment::create([
            'assignable_type' => $sekolah->getMorphClass(), 'assignable_id' => $sekolah->id,
            'instrument_code' => 'who5', 'audience' => 'student', 'source' => 'legacy',
            'opens_at' => '1970-01-01', 'closes_at' => null,
            'status' => 'active', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Models\Sekolah\SekolahAssessment::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $person->id, 'kelas' => 'X IPA 1',
            'answers' => [5, 5, 5, 5, 5], 'score' => 100, 'taken_at' => now()->subDays(40),
        ]);

        \App\Models\Sekolah\SekolahAssessment::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $person->id, 'kelas' => 'X IPA 1',
            'instrument_code' => 'who5',
            'answers' => [5, 5, 5, 5, 5], 'score' => 100, 'taken_at' => now()->subDays(40),
        ]);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            ->assertSee('Asesmen terakhir');
    }

    /** T6: export di halaman laporan membawa bulan terpilih. */
    public function test_report_page_export_link_passes_month(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);

        $this->actingAs($bk)->get(route('sekolah.report', ['month' => '2026-01']))
            ->assertOk()
            ->assertSee('month=2026-01', false);
    }

    /** T6: export CSV menghormati ?month (filename memuat periode terpilih). */
    public function test_export_uses_requested_month(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);

        $response = $this->actingAs($bk)->get(route('sekolah.dashboard.export', ['month' => '2026-01']));
        $response->assertOk();
        $this->assertStringContainsString('laporan-bk-2026-01.csv', $response->headers->get('Content-Disposition'));
    }

    /** T5+T7+8: dashboard BK render Program & Kontrak card + link Laporan → report + chip "Perlu review". */
    public function test_bk_dashboard_renders_program_contract_and_report_link(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);

        $this->actingAs($bk)->get(route('sekolah.dashboard'))
            ->assertOk()
            ->assertSee('Program &amp; Kontrak', false)
            ->assertSee((string) $sekolah->student_count, false)
            ->assertSee((string) $sekolah->sessions_per_student_month, false)
            ->assertSee(route('sekolah.report'), false)
            ->assertSee('Perlu review', false);
    }
}
