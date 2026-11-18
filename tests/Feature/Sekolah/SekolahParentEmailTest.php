<?php

namespace Tests\Feature\Sekolah;

use App\Mail\Sekolah\ParentConsultationRequested;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task B.3: orangtua/wali di-email saat BK refer siswa — TANPA akun user
// dibuat, TANPA request_notes bocor ke body email (konten sesi rahasia).
class SekolahParentEmailTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email) . '-' . Str::random(4),
            'email' => $email, 'password' => bcrypt('password'),
            'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function fixture(): array
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Parent', 'brand' => 'SMA Parent',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $bk = $this->user('bk@parent.test', 'bk');
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $bk->id, 'role' => 'bk']);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Parent',
            'email' => 'siswa@parent.test', 'kelas' => 'X IPA 1',
            'parent_name' => 'Wali Parent', 'parent_consent_at' => now(),
        ]);

        return [$sekolah, $bk, $student];
    }

    public function test_parent_email_sent_without_user_created(): void
    {
        Mail::fake();
        [$sekolah, $bk, $student] = $this->fixture();

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'RUJUKAN-RAHASIA-MARKER',
            'parent_email' => 'wali@example.test',
        ])->assertRedirect();

        Mail::assertSent(ParentConsultationRequested::class, fn ($m) => $m->hasTo('wali@example.test'));

        // TIDAK ada akun untuk parent.
        $this->assertDatabaseMissing('users', ['email' => 'wali@example.test']);

        // Audit: parent_notified_at terisi.
        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $student->id)->first();
        $this->assertNotNull($session->parent_notified_at);
    }

    public function test_request_notes_never_leaks_into_parent_email(): void
    {
        Mail::fake();
        [$sekolah, $bk, $student] = $this->fixture();
        $secret = 'RAHASIA-SISWA-XYZ-123';

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => $secret,
            'parent_email' => 'wali@example.test',
        ])->assertRedirect();

        $mail = Mail::sent(ParentConsultationRequested::class)->first();
        $rendered = $mail->render();

        $this->assertStringNotContainsString($secret, $rendered);
        $this->assertStringNotContainsString('request_notes', $rendered);
    }

    public function test_no_parent_email_no_mail_no_timestamp(): void
    {
        Mail::fake();
        [$sekolah, $bk, $student] = $this->fixture();

        $this->actingAs($bk)->post(route('sekolah.students.refer'), [
            'student_id' => $student->id,
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Catatan internal',
        ])->assertRedirect();

        Mail::assertNothingSent();
        $session = SekolahSession::withoutGlobalScopes()->where('student_id', $student->id)->first();
        $this->assertNull($session->parent_notified_at);
    }
}
