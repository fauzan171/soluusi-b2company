<?php

namespace Tests\Feature;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use App\Support\InsightEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// ponytail: satu check per logika non-trivial — WHO-5 siswa, kuota, consent gate,
// cross-student 403, register, InsightEngine rules.
class SekolahWellbeingTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $quota = 2, bool $consent = true): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'student', 'created_at' => now(), 'updated_at' => now()]);
        $bkRoleId = DB::table('roles')->insertGetId(['name' => 'bk', 'created_at' => now(), 'updated_at' => now()]);

        $sekolah = Sekolah::create([
            'name' => 'SMA Test', 'brand' => 'TestHigh',
            'sessions_per_student_month' => $quota, 'student_count' => 5, 'is_active' => true,
        ]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Test',
            'email' => 'siswa@test.local', 'kelas' => 'X IPA 1',
            'parent_name' => 'Ortu Test', 'parent_consent_at' => $consent ? now() : null,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Siswa', 'lastname' => 'Test',
            'username' => 'siswa_test', 'email' => 'siswa@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $student->user_id = $user->id;
        $student->save();

        $bkUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'BK', 'lastname' => 'Test',
            'username' => 'bk_test', 'email' => 'bk@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $bkRoleId,
        ]);
        DB::table('sekolah_admins')->insert([
            'sekolah_id' => $sekolah->id, 'user_id' => $bkUser->id, 'role' => 'bk',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$sekolah, $student, $user, $bkUser];
    }

    public function test_student_assessment_uses_who5_formula()
    {
        [, $student, $user] = $this->makeStudent();

        $this->actingAs($user)->post(route('sekolah.assessment.store'), [
            'answers' => [1, 2, 3, 2, 2], // sum 10 × 4 = 40
        ])->assertRedirect(route('sekolah.student'));

        $row = $student->assessments()->first();
        $this->assertEquals(40, $row->score);
        $this->assertEquals('Berisiko Tinggi', $row->band());
    }

    public function test_booking_blocked_without_parent_consent()
    {
        [, , $user] = $this->makeStudent(consent: false);

        $this->actingAs($user)->post(route('sekolah.booking.store'), [
            'konsultan_type' => 'psikolog', 'mode' => 'online',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertForbidden();
    }

    public function test_booking_rejected_when_quota_full()
    {
        [$sekolah, $student, $user] = $this->makeStudent(1);

        \App\Models\Sekolah\SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id, 'kelas' => $student->kelas,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $slot = $this->programSlot('sekolah', $sekolah->id);
        $this->actingAs($user)->post(route('sekolah.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog', 'mode' => 'online',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertForbidden();
    }

    public function test_booking_creates_session_within_quota()
    {
        [$sekolah, $student, $user] = $this->makeStudent();

        $slot = $this->programSlot('sekolah', $sekolah->id, 'karir');
        $this->actingAs($user)->post(route('sekolah.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'karir', 'mode' => 'offline',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('sekolah.student'));

        $this->assertDatabaseHas('sekolah_sessions', [
            'student_id' => $student->id, 'konsultan_type' => 'karir',
            'origin' => 'self_booking', 'status' => 'scheduled',
        ]);
    }

    public function test_booking_records_consultant_and_rejects_outside_pool()
    {
        [$sekolah, $student, $user] = $this->makeStudent();

        // Konsultan di pool sekolah ini (kategori cocok) + satu luar pool.
        $consultant = \App\Models\Consultant::create(['user_id' => $user->id, 'status' => 'accept', 'about' => 'Test', 'profit_sharing' => 95]);
        $catId = DB::table('categories')->insertGetId([
            'code_category' => 'anak_remaja_test', 'name' => 'Psikolog Anak & Remaja',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consultant_categories')->insert([
            'consultant_id' => $consultant->id, 'category_id' => $catId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consultant_pools')->insert([
            'consultant_id' => $consultant->id, 'sekolah_id' => $sekolah->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $consultant->update(['is_active' => true]);
        $slot = \App\Models\Schedule::factory()->create(['consultant_id' => $consultant->id, 'sekolah_id' => $sekolah->id, 'klinik_id' => null, 'status' => 'confirmed']);
        // Pilihan valid → consultant_id terekam.
        $this->actingAs($user)->post(route('sekolah.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog', 'consultant_id' => $consultant->id, 'mode' => 'online',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('sekolah.student'));
        $this->assertEquals($consultant->id, $student->sessions()->latest('id')->first()->consultant_id);

        // Konsultan luar pool → ditolak, tidak ada sesi baru.
        $before = $student->sessions()->count();
        $this->actingAs($user)->post(route('sekolah.booking.store'), [
            'konsultan_type' => 'karir', 'consultant_id' => $consultant->id, 'mode' => 'online',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($this->app->session->has('errors'));
        $this->assertEquals($before, $student->sessions()->count());
    }

    public function test_student_cannot_cancel_others_session()
    {
        [$sekolah, , , $user] = $this->makeStudent();
        $other = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Lain',
            'kelas' => 'X IPA 1', 'parent_name' => 'Ortu', 'parent_consent_at' => now(),
        ]);
        $session = \App\Models\Sekolah\SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $other->id, 'kelas' => $other->kelas,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('sekolah.booking.cancel', $session))->assertForbidden();
        $this->assertEquals('scheduled', $session->fresh()->status);
    }

    public function test_bk_dashboard_renders_with_insights()
    {
        [$sekolah, $student, , $bkUser] = $this->makeStudent();

        // Sesi done dengan topik dominan Bullying (threshold rendah → selalu insight).
        \App\Models\Sekolah\SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id, 'kelas' => $student->kelas,
            'konsultan_type' => 'psikolog', 'topic_category' => 'Bullying', 'status' => 'done',
            'completed_at' => now(), 'scheduled_at' => now()->subDay(),
        ]);

        $res = $this->actingAs($bkUser)->get(route('sekolah.dashboard'));
        $res->assertOk();
        $res->assertSee('Tindakan');
        $res->assertDontSee('Bullying');
        $this->assertTrue($res->viewData('suppressed'));
        $this->assertNull($res->viewData('done'));
    }

    public function test_self_register_links_roster_row()
    {
        [, $student, $user] = $this->makeStudent();
        // Lepas akun lama supaya row bebas didaftarkan lagi (hapus user → email bebas).
        $user->delete();
        $student->user_id = null;
        $student->save();

        $url = \App\Services\RosterInvitation::issue('siswa', $student);
        $this->get($url)->assertOk();
        $this->assertNull(DB::table('roster_invitations')->first()->consumed_at);
        $this->post($url, ['name' => 'Siswa Test', 'password' => 'password123'])->assertRedirect(route('sekolah.student'));
        $this->assertEquals($student->fresh()->user_id, auth()->id());
        $this->post($url, ['name' => 'Siswa Test', 'password' => 'changed123'])->assertForbidden();
    }

    public function test_self_register_rejects_unknown_email_and_name_mismatch()
    {
        $this->post(route('selfregister.store', 'siswa'), [
            'name' => 'Orang Lain', 'email' => 'tidak-ada@test.local',
            'password' => 'password123', 'parent_consent' => '1',
        ])->assertForbidden();

        $res = $this->post(route('selfregister.store', 'siswa'), [
            'name' => 'Nama Beda', 'email' => 'siswa@test.local',
            'password' => 'password123', 'parent_consent' => '1',
        ]);
        $res->assertForbidden();
    }

    /** Invitation valid + nama salah → ditolak DAN undangan tidak terpakai (bisa dipakai ulang oleh pemilik roster). */
    public function test_self_register_name_mismatch_keeps_invitation_usable(): void
    {
        [, $student, $user] = $this->makeStudent();
        $user->delete();
        $student->user_id = null;
        $student->save();

        $url = \App\Services\RosterInvitation::issue('siswa', $student);

        $this->post($url, ['name' => 'Nama Orang Lain', 'password' => 'password123'])
            ->assertForbidden();

        // Rollback transaksi menjaga undangan tetap belum dikonsumsi.
        $this->assertNull(DB::table('roster_invitations')->first()->consumed_at);
        $this->assertNull($student->fresh()->user_id);

        // Nama yang benar bisa langsung lanjut pakai undangan yang sama.
        $this->post($url, ['name' => 'Siswa Test', 'password' => 'password123'])
            ->assertRedirect(route('sekolah.student'));
        $this->assertNotNull($student->fresh()->user_id);
    }

    public function test_invitation_tamper_expiry_revocation_and_existing_owner(): void
    {
        [, $student, $user] = $this->makeStudent();
        $first = \App\Services\RosterInvitation::issue('siswa', $student);
        $second = \App\Services\RosterInvitation::issue('siswa', $student);
        $this->get($first)->assertForbidden();
        $this->get($second.'&roster_id=999')->assertForbidden();
        $this->post($second)->assertForbidden();
        $password = $user->password;
        $this->actingAs($user)->post($second, ['name' => 'Siswa Test'])->assertRedirect(route('sekolah.student'));
        $this->assertSame($password, $user->fresh()->password);
        $third = \App\Services\RosterInvitation::issue('siswa', $student);
        $this->travel(8)->days();
        $this->get($third)->assertForbidden();
        $this->travelBack();
    }

    public function test_roster_does_not_expose_individual_counseling_usage(): void
    {
        [, , , $bk] = $this->makeStudent();
        $response = $this->actingAs($bk)->get(route('sekolah.students'))->assertOk();
        $response->assertDontSee('Kuota bulan ini');
        foreach ($response->viewData('students') as $student) {
            $this->assertArrayNotHasKey('used_month', $student->getAttributes());
        }
    }

    public function test_bk_external_guardian_consent_keeps_history_and_blocks_future_evidence(): void
    {
        [$school, $student, $user, $bk] = $this->makeStudent();
        $url = route('sekolah.students.consent', $student);
        $data = ['granted' => '1', 'evidence' => 'Signed guardian form #123', 'agreed_at' => now()->subHour()->format('Y-m-d H:i:s')];
        $this->actingAs($bk)->post($url, $data)->assertRedirect();
        $this->assertNotNull($student->fresh()->parent_consent_at);
        $this->post($url, array_merge($data, ['granted' => '0']))->assertRedirect();
        $this->assertNull($student->fresh()->parent_consent_at);
        $this->assertDatabaseCount('program_consent_history', 2);
        $this->post($url, array_merge($data, ['agreed_at' => now()->addDay()->format('Y-m-d H:i:s')]))->assertSessionHasErrors('agreed_at');
        $this->actingAs($user)->post(route('sekolah.assessment.store'), ['answers' => [1,1,1,1,1]])->assertForbidden();
    }

    public function test_insight_engine_flags_bullying_first()
    {
        $cards = InsightEngine::forAudience(
            ['Bullying' => 1, 'Stress' => 2], 10, 'school', ['risk_high_pct' => 12],
        );

        // Risk flag selalu di depan, lalu red (Bullying).
        $this->assertEquals('Kesejahteraan psikologis', $cards[0]['topic']);
        $this->assertEquals('Bullying', $cards[1]['topic']);
        $this->assertEquals('red', $cards[1]['tone']);
        // Tindakan memakai bahasa sekolah, bukan HR.
        $this->assertStringContainsString('BK', $cards[1]['action']);
    }
}
