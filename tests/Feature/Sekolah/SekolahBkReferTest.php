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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task B.2: BK refer atas nama siswa + auto-provision via StudentProvisioner.
class SekolahBkReferTest extends TestCase
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

    private function bk(Sekolah $sekolah, string $email): User
    {
        $user = $this->user($email, 'bk');
        SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'bk',
        ]);
        return $user;
    }

    private function student(Sekolah $sekolah, ?string $email = 'siswa@test.local'): SekolahStudent
    {
        return SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Test',
            'email' => $email, 'kelas' => 'X IPA 1',
            'parent_name' => 'Ortu', 'parent_consent_at' => now(),
        ]);
    }

    public function test_bk_refer_with_request_notes_creates_pending_session_and_provisions_user(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah, 'bk@test.local');
        $student = $this->student($sekolah);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Siswa menunjukkan tanda cemas.',
        ])->assertRedirect();

        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $student->id)->first();
        $this->assertNotNull($session);
        // Pending = status 'scheduled' + scheduled_at NULL + consultant_id NULL (parity F1.1 perusahaan).
        $this->assertSame('scheduled', $session->status);
        $this->assertSame('bk', $session->requested_for_role);
        $this->assertSame($bk->id, $session->requested_by_user_id);
        $this->assertSame('Siswa menunjukkan tanda cemas.', $session->request_notes);
        $this->assertNull($session->consultant_id);
        $this->assertNull($session->scheduled_at);

        // Auto-provision: user dibuat + linked
        $student->refresh();
        $this->assertNotNull($student->user_id);
        $this->assertDatabaseHas('users', ['id' => $student->user_id, 'email' => 'siswa@test.local']);

        // Audit trail invitation
        $this->assertDatabaseHas('roster_invitations', [
            'type' => 'siswa', 'roster_id' => $student->id, 'email' => 'siswa@test.local',
        ]);
    }

    public function test_bk_refer_existing_user_reuses_user(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah, 'bk@test.local');
        $existing = $this->user('existing@test.local', 'student');
        $student = $this->student($sekolah, 'existing@test.local');
        $student->update(['user_id' => $existing->id]);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Notes',
        ])->assertRedirect();

        $this->assertSame($existing->id, $student->fresh()->user_id);
        $this->assertSame(1, User::where('email', 'existing@test.local')->count());
    }

    public function test_bk_refer_student_without_email_keeps_user_id_null(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah, 'bk@test.local');
        $student = $this->student($sekolah, null);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Notes',
        ])->assertRedirect();

        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $student->id)->first();
        $this->assertNotNull($session);
        $this->assertNull($student->fresh()->user_id);
    }

    public function test_bk_refer_direct_with_schedule_id_still_works_back_compat(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah, 'bk@test.local');
        $student = $this->student($sekolah);

        // Seed consultant + schedule untuk sekolah ini
        $consultUser = User::factory()->create(['firstname' => 'Dr', 'lastname' => 'Psikolog']);
        $consultant = Consultant::factory()->create([
            'user_id' => $consultUser->id, 'status' => 'accept', 'is_active' => true,
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Psikolog', 'code_category' => Str::random(12),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consultant_categories')->insert([
            'consultant_id' => $consultant->id, 'category_id' => $categoryId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        ConsultantPool::create([
            'sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id,
        ]);
        $schedule = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'sekolah_id' => $sekolah->id,
            'klinik_id' => null,
            'started_at' => now()->addDays(3),
            'ended_at' => now()->addDays(3)->addHour(),
            'status' => 'confirmed',
        ]);

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'schedule_id' => $schedule->id,
        ])->assertRedirect();

        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $student->id)->first();
        $this->assertNotNull($session);
        $this->assertSame('scheduled', $session->status);
        $this->assertSame('bk_referral', $session->origin);
        $this->assertNotNull($session->scheduled_at);
        // Auto-provision juga jalan di mode direct
        $this->assertNotNull($student->fresh()->user_id);
    }

    public function test_non_bk_role_cannot_refer(): void
    {
        $sekolah = $this->sekolah();
        $student = $this->student($sekolah);
        $randomUser = $this->user('random@test.local', 'student');

        $this->actingAs($randomUser)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'x',
        ])->assertForbidden();
    }

    public function test_bk_from_other_sekolah_cannot_refer_student(): void
    {
        $sekolahA = $this->sekolah('a');
        $sekolahB = $this->sekolah('b');
        $bkB = $this->bk($sekolahB, 'bk-b@test.local');
        $studentA = $this->student($sekolahA);

        // Konvensi T2.4: TenantScope menyembunyikan siswa tenant lain → 404, bukan 403.
        $this->actingAs($bkB)->post(route('sekolah.students.refer'), [
            'student_id' => $studentA->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'x',
        ])->assertNotFound();

        $this->assertDatabaseMissing('sekolah_sessions', ['student_id' => $studentA->id]);
    }
}
