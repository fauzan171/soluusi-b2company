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

/**
 * G.4 (repo-wide): string mentah 'online'/'offline' tidak boleh tampil ke user
 * di portal sekolah — labelnya "Online" / "Tatap muka".
 */
class SekolahModeLabelTest extends TestCase
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

    private function sekolah(): Sekolah
    {
        return Sekolah::create([
            'name' => 'SMA Label', 'brand' => 'SMA Label',
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function studentSession(Sekolah $sekolah, SekolahStudent $student): SekolahSession
    {
        return SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $student->id,
            'kelas' => $student->kelas, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(3), 'mode' => 'offline',
        ]);
    }

    public function test_student_page_shows_label_not_raw_mode(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa@label.local', 'student');
        $student = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Label', 'email' => 'siswa@label.local',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);
        $this->studentSession($sekolah, $student);

        $this->actingAs($user)->get(route('sekolah.student'))
            ->assertOk()
            ->assertSee('Tatap muka')
            ->assertDontSee('offline');
    }

    public function test_staff_page_shows_label_not_raw_mode(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('staff@label.local', 'sekolah_staff');
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'sekolah_staff']);
        $person = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Staf Label', 'email' => 'staff@label.local',
            'type' => 'staff', 'kelas' => 'Guru', 'consent_at' => now(),
        ]);
        $this->studentSession($sekolah, $person);

        $this->actingAs($user)->get(route('sekolah.staff'))
            ->assertOk()
            ->assertSee('Tatap muka')
            ->assertDontSee('offline');
    }

    public function test_booking_form_shows_label_not_raw_mode(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa2@label.local', 'student');
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Dua', 'email' => 'siswa2@label.local',
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);

        $docUser = $this->user('doc@label.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id]);
        Schedule::factory()->create([
            'consultant_id' => $consultant->id, 'sekolah_id' => $sekolah->id, 'klinik_id' => null,
            'status' => Schedule::STATUS_CONFIRMED, 'mode' => 'offline',
            'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(),
        ]);

        $this->actingAs($user)->get(route('sekolah.booking'))
            ->assertOk()
            ->assertSee('Tatap muka')
            ->assertDontSee('offline');
    }
}