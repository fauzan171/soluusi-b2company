<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Task D.1: SAP pervorma trigger otomatis saat SekolahSession → done,
// via observer (single-source: end manual, zombie sweep, atau path lain).
class SekolahPervormaTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_done_creates_sap_pervorma_row(): void
    {
        // Stage 1 tercapai dengan 1 sesi done (trigger di-set runtime).
        config()->set('pervorma.sap.stages', [
            1 => ['trigger' => 1, 'amount' => 300_000],
        ]);

        $sekolah = Sekolah::create([
            'name' => 'SMA Pervorma', 'brand' => 'SMA Pervorma',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
            'mou_at' => now(), // stage mou aktif
        ]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Pv',
            'email' => 'siswa@pv.test', 'kelas' => 'X IPA 1',
        ]);
        $docUser = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Doc', 'lastname' => 'Pv',
            'username' => 'docpv', 'email' => 'doc@pv.test',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => DB::table('roles')->where('name', 'consultant')->value('id'),
        ]);
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => now()->subHour(), 'checked_in_at' => now()->subHour(),
        ]);

        // Observer harus menembak saat status berubah ke done (path apa pun).
        $session->update(['status' => 'done', 'completed_at' => now()]);

        // Stage 1 (trigger 1 sesi done) harus ter-record.
        $this->assertDatabaseHas('sekolah_pervormas', [
            'sekolah_id' => $sekolah->id,
            'stage' => 1,
        ]);
    }

    public function test_no_trigger_when_status_not_done(): void
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA Pv2', 'brand' => 'SMA Pv2',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => 'Siswa Pv2',
            'email' => 'siswa@pv2.test', 'kelas' => 'X IPA 1',
        ]);

        SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $this->assertDatabaseMissing('sekolah_pervormas', [
            'sekolah_id' => $sekolah->id,
        ]);
    }
}
