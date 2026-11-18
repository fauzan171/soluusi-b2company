<?php

namespace Tests\Feature\Sekolah;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Staff sekolah self-booking: alur siswa (booking slot / assessment / request BK)
// untuk person row ber-type 'staff'. Staff TIDAK boleh ikut terhitung sebagai
// siswa terdaftar di metrik agregat dashboard.
class SekolahStaffFlowTest extends TestCase
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

    private function staff(Sekolah $sekolah, string $email = 'staff@test.local', string $name = 'Staf Test'): array
    {
        $user = $this->user($email, 'sekolah_staff');
        SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'sekolah_staff',
        ]);
        $person = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => $name, 'email' => $email, 'type' => 'staff',
            'kelas' => 'Guru', 'consent_at' => now(),
        ]);

        return [$user, $person];
    }

    private function student(Sekolah $sekolah, string $email, string $name = 'Siswa Test'): SekolahStudent
    {
        return SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'name' => $name, 'email' => $email,
            'kelas' => 'X IPA 1', 'parent_consent_at' => now(),
        ]);
    }

    /** Konsultan di pool + slot confirmed di masa depan. */
    private function slot(Sekolah $sekolah, string $firstname = 'DocAlpha'): array
    {
        $docUser = $this->user(Str::slug($firstname).'@test.local', 'consultant');
        $docUser->update(['firstname' => $firstname, 'lastname' => 'Test']);
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['sekolah_id' => $sekolah->id, 'consultant_id' => $consultant->id]);
        $slot = Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'sekolah_id' => $sekolah->id,
            'klinik_id' => null,
            'status' => Schedule::STATUS_CONFIRMED,
            'started_at' => now()->addDays(3),
            'ended_at' => now()->addDays(3)->addHour(),
        ]);

        return [$consultant, $slot];
    }

    public function test_staff_can_self_book_a_slot(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        [, $slot] = $this->slot($sekolah);

        $this->actingAs($user)->post(route('sekolah.staff.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('sekolah.staff'));

        $session = SekolahSession::where('student_id', $person->id)->sole();
        $this->assertSame($person->id, $session->student_id);
        $this->assertSame('self_booking', $session->origin);
        $this->assertSame('scheduled', $session->status);
        $this->assertSame($slot->started_at->toDateTimeString(), $session->scheduled_at->toDateTimeString());
    }

    public function test_staff_booking_form_lists_available_slots(): void
    {
        $sekolah = $this->sekolah();
        [$user] = $this->staff($sekolah);
        $this->slot($sekolah);

        $response = $this->actingAs($user)->get(route('sekolah.staff.booking'));

        $response->assertOk();
        $response->assertSeeText('DocAlpha');
        $response->assertSee('name="schedule_id"', false);
    }

    public function test_staff_can_submit_assessment(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        $answers = [1, 2, 3, 4, 5];

        $this->actingAs($user)->post(route('sekolah.staff.assessment.store'), [
            'answers' => $answers,
        ])->assertRedirect();

        $assessment = SekolahAssessment::where('student_id', $person->id)->sole();
        $this->assertSame($person->id, $assessment->student_id);
        $this->assertSame(array_sum($answers) * 4, (int) $assessment->score);
    }

    public function test_staff_can_request_consultation_into_bk_queue(): void
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);

        $this->actingAs($user)->post(route('sekolah.staff.request'), [
            'konsultan_type' => 'psikolog',
            'request_notes' => 'Butuh bantuan mengelola stres kerja.',
        ])->assertRedirect();

        $session = SekolahSession::where('student_id', $person->id)->sole();
        $this->assertSame('scheduled', $session->status);
        $this->assertNull($session->scheduled_at);
        $this->assertNull($session->consultant_id);
        $this->assertSame('self', $session->requested_for_role);
    }

    public function test_staff_row_is_not_counted_as_registered_student(): void
    {
        $sekolah = $this->sekolah();
        for ($i = 1; $i <= 6; $i++) {
            $this->student($sekolah, "siswa{$i}@test.local", "Siswa {$i}");
        }
        $this->staff($sekolah);

        $metrics = DashboardMetrics::build($sekolah, 'sekolah');

        $this->assertSame(6, $metrics['registered']);
    }

    public function test_staff_assessment_does_not_enter_bk_wellbeing_aggregate(): void
    {
        $sekolah = $this->sekolah();
        for ($i = 1; $i <= 4; $i++) {
            $this->student($sekolah, "siswa{$i}@test.local", "Siswa {$i}");
        }
        $this->staff($sekolah);

        // Staff menilai dirinya sangat baik — kalau ikut terhitung, npsScore naik dari 0.
        $staffPerson = SekolahStudent::where('sekolah_id', $sekolah->id)->where('type', 'staff')->sole();
        SekolahAssessment::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $staffPerson->id,
            'answers' => [5, 5, 5, 5, 5], 'score' => 100, 'taken_at' => now(),
        ]);

        $metrics = DashboardMetrics::build($sekolah, 'sekolah');

        $this->assertSame(0.0, $metrics['npsScore'], 'Skor staf tidak boleh masuk agregat wellbeing BK.');
    }

    public function test_other_staff_cannot_cancel_someone_elses_session(): void
    {
        $sekolah = $this->sekolah();
        [, $personA] = $this->staff($sekolah, 'staff-a@test.local', 'Staf A');
        [$userB] = $this->staff($sekolah, 'staff-b@test.local', 'Staf B');
        $session = SekolahSession::create([
            'sekolah_id' => $sekolah->id, 'student_id' => $personA->id,
            'kelas' => 'Guru', 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(3),
        ]);

        $this->actingAs($userB)->post(route('sekolah.staff.booking.cancel', $session), [
            'reason' => 'Bukan sesi saya.',
        ])->assertForbidden();

        $this->assertSame('scheduled', $session->refresh()->status);
    }
}