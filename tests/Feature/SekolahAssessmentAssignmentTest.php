<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use App\Services\AssessmentAccess;
use App\Services\Screening\InstrumentScoring;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// B3: siswa/staf sekolah mengerjakan instrumen via engine generic, tampil
// hanya yang di-assign admin (D2), minor-safety D9, satu submission per
// assignment (D6), legacy who5 tetap jalan (D8).
class SekolahAssessmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);
    }

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

    private function sekolah(): Sekolah
    {
        return Sekolah::create([
            'name' => 'SMA B3', 'brand' => 'SMA B3', 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function student(Sekolah $s, string $email = 'siswa@b3.local'): array
    {
        $user = $this->user($email, 'student');
        $person = SekolahStudent::create([
            'sekolah_id' => $s->id, 'user_id' => $user->id,
            'name' => 'Siswa B3', 'email' => $email, 'kelas' => 'X IPA 1',
            'parent_name' => 'Ortu B3', 'parent_consent_at' => now(),
        ]);

        return [$user, $person];
    }

    private function staff(Sekolah $s, string $email = 'staff@b3.local'): array
    {
        $user = $this->user($email, 'sekolah_staff');
        SekolahAdmin::create(['sekolah_id' => $s->id, 'user_id' => $user->id, 'role' => 'sekolah_staff']);
        $person = SekolahStudent::create([
            'sekolah_id' => $s->id, 'user_id' => $user->id,
            'name' => 'Staf B3', 'email' => $email, 'type' => 'staff',
            'kelas' => 'Guru', 'consent_at' => now(),
        ]);

        return [$user, $person];
    }

    private function adminUser(string $role = 'admin'): User
    {
        return $this->user($role . '@b3.local', $role);
    }

    private function assign(Sekolah $s, string $code, string $audience, array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => 'sekolah',
            'assignable_id' => $s->id,
            'instrument_code' => $code,
            'audience' => $audience,
            'source' => 'core',
            'round' => 1,
            'opens_at' => today()->subDay(),
            'closes_at' => null,
            'status' => 'active',
        ], $overrides));
    }

    // --- WHO-5 parity (D8) ---------------------------------------------------

    public function test_who5_engine_parity_with_legacy_formula()
    {
        $legacyAnswers = [1, 2, 3, 2, 2]; // sum 10 → 40
        $instrument = AssessmentInstrument::where('code', 'who5')->firstOrFail();
        $answers = [];
        foreach (InstrumentScoring::items($instrument) as $i => $item) {
            $answers[$item->code] = $legacyAnswers[$i];
        }

        $result = InstrumentScoring::score($instrument, $answers);

        $this->assertSame(array_sum($legacyAnswers) * 4, $result['score']);
        $this->assertSame('Berisiko Tinggi', $result['band']['label']);
    }

    public function test_legacy_who5_assignment_keeps_student_flow_working()
    {
        $sekolah = $this->sekolah();
        [$user, $student] = $this->student($sekolah);
        // D8: assignment legacy hasil backfill — form indexed 0-4 tetap jalan.
        $this->assign($sekolah, 'who5', 'student', ['source' => 'legacy', 'opens_at' => '1970-01-01']);

        $this->actingAs($user)->post(route('sekolah.assessment.store'), [
            'answers' => [1, 2, 3, 2, 2], // legacy indexed shape
        ])->assertRedirect(route('sekolah.student'));

        $row = $student->assessments()->first();
        $this->assertSame(40, (int) $row->score);
        $this->assertSame('who5', $row->instrument_code);
    }

    // --- D9: minor safety ----------------------------------------------------

    public function test_student_sees_only_assigned_allowed_instruments()
    {
        $sekolah = $this->sekolah();
        [$user] = $this->student($sekolah);
        $this->assign($sekolah, 'who5', 'student', ['source' => 'legacy', 'opens_at' => '1970-01-01']);
        $this->assign($sekolah, 'pss10', 'student');

        $res = $this->actingAs($user)->get(route('sekolah.assessment.list'))->assertOk();

        $res->assertSee('PSS-10');
        $res->assertDontSee('DASS-21');
    }

    public function test_student_cannot_take_unassigned_or_disallowed_instrument()
    {
        $sekolah = $this->sekolah();
        [$user] = $this->student($sekolah);
        $this->assign($sekolah, 'who5', 'student', ['source' => 'legacy', 'opens_at' => '1970-01-01']);
        // dass21 di-assign ke staf (bukan siswa) — siswa tidak boleh akses.
        $this->assign($sekolah, 'dass21', 'staff');

        $this->actingAs($user)->get(route('sekolah.assessment.form', 'dass21'))->assertForbidden();
        $this->actingAs($user)->post(route('sekolah.assessment.store', 'dass21'), [
            'answers' => array_fill_keys(['D1', 'D2', 'D3'], 1),
        ])->assertForbidden();
    }

    // --- Admin assignment (D9 + D5 gates) ------------------------------------

    public function test_admin_assign_dass21_to_student_rejected_422()
    {
        $sekolah = $this->sekolah();
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)->post(route('admin.tenants.sekolahs.assignments.store', $sekolah), [
            'instrument_code' => 'dass21', 'audience' => 'student',
        ]);

        // abort(422) → HTML error page (bukan ValidationException JSON); cukup status + no row.
        $res->assertStatus(422);
        $this->assertDatabaseMissing('assessment_assignments', [
            'assignable_id' => $sekolah->id, 'instrument_code' => 'dass21', 'audience' => 'student',
        ]);
    }

    public function test_admin_assign_mcmi3_always_rejected_422()
    {
        $sekolah = $this->sekolah();
        $admin = $this->adminUser();

        foreach (['student', 'staff'] as $audience) {
            $res = $this->actingAs($admin)->post(route('admin.tenants.sekolahs.assignments.store', $sekolah), [
                'instrument_code' => 'mcmi3', 'audience' => $audience,
            ]);
            $res->assertStatus(422);
        }

        $this->assertDatabaseMissing('assessment_assignments', [
            'assignable_id' => $sekolah->id, 'instrument_code' => 'mcmi3',
        ]);
    }

    public function test_admin_assign_pss10_to_student_succeeds_and_records_audit()
    {
        $sekolah = $this->sekolah();
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)->post(route('admin.tenants.sekolahs.assignments.store', $sekolah), [
            'instrument_code' => 'pss10', 'audience' => 'student',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('assessment_assignments', [
            'assignable_id' => $sekolah->id, 'instrument_code' => 'pss10',
            'audience' => 'student', 'status' => 'active',
        ]);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id, 'action' => 'sekolah.assignment.create',
        ]);
    }

    public function test_admin_routes_reject_bk_role()
    {
        $sekolah = $this->sekolah();
        $bk = $this->user('bk@b3.local', 'bk');
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $bk->id, 'role' => 'bk']);

        $this->actingAs($bk)
            ->post(route('admin.tenants.sekolahs.assignments.store', $sekolah), [
                'instrument_code' => 'pss10', 'audience' => 'student',
            ])->assertForbidden();
    }

    public function test_operations_role_can_assign()
    {
        $sekolah = $this->sekolah();
        $ops = $this->adminUser('operations');

        $this->actingAs($ops)->post(route('admin.tenants.sekolahs.assignments.store', $sekolah), [
            'instrument_code' => 'pss10', 'audience' => 'student',
        ])->assertRedirect();
    }

    public function test_admin_can_close_assignment()
    {
        $sekolah = $this->sekolah();
        $admin = $this->adminUser();
        $assignment = $this->assign($sekolah, 'pss10', 'student');

        $this->actingAs($admin)
            ->post(route('admin.tenants.sekolahs.assignments.close', [$sekolah, $assignment]))
            ->assertRedirect();

        $this->assertSame('closed', $assignment->fresh()->status);
    }

    // --- Staff flow + escalation + single submission (D6) --------------------

    public function test_staff_takes_dass21_with_escalated_and_answer_rows_stored()
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        $assignment = $this->assign($sekolah, 'dass21', 'staff');

        // Semua item DASS-21 dijawab 0 kecuali P9-style escalation item (DASS: pakai
        // phq9 utk escalate; dass21 tidak punya escalate_items). Pakai phq9 utk
        // tes escalation terpisah; dass21 verifikasi skor + dimensions rows.
        $answers = [];
        foreach (InstrumentScoring::items(AssessmentInstrument::where('code', 'dass21')->firstOrFail()) as $item) {
            $answers[$item->code] = 0;
        }
        $answers['D1'] = 3; // skor total 'sum' = 3 (multiplier x2 hanya di dimensi, bukan total)

        $this->actingAs($user)->post(route('sekolah.staff.assessment.store', 'dass21'), [
            'answers' => $answers,
        ])->assertRedirect();

        $row = SekolahAssessment::where('student_id', $person->id)
            ->where('instrument_code', 'dass21')->sole();
        $this->assertSame($assignment->id, $row->assessment_assignment_id);
        $this->assertSame(3, (int) $row->score); // engine: sum mentah (D1=3)
        $this->assertFalse((bool) $row->escalated);
        $this->assertSame(21, DB::table('screening_answers')
            ->where('assessable_id', $row->id)->where('assessable_type', SekolahAssessment::class)->count());
    }

    public function test_staff_phq9_escalation_flag_stored()
    {
        $sekolah = $this->sekolah();
        [$user, $person] = $this->staff($sekolah);
        $this->assign($sekolah, 'phq9', 'staff');

        $answers = [];
        foreach (InstrumentScoring::items(AssessmentInstrument::where('code', 'phq9')->firstOrFail()) as $item) {
            $answers[$item->code] = 0;
        }
        $answers['P9'] = 1; // escalate_items PHQ-9

        $this->actingAs($user)->post(route('sekolah.staff.assessment.store', 'phq9'), [
            'answers' => $answers,
        ])->assertRedirect();

        $row = SekolahAssessment::where('student_id', $person->id)
            ->where('instrument_code', 'phq9')->sole();
        $this->assertTrue((bool) $row->escalated);
    }

    public function test_one_submission_per_assignment_per_student()
    {
        $sekolah = $this->sekolah();
        [$user] = $this->student($sekolah);
        $assignment = $this->assign($sekolah, 'pss10', 'student');

        $answers = [];
        foreach (InstrumentScoring::items(AssessmentInstrument::where('code', 'pss10')->firstOrFail()) as $item) {
            $answers[$item->code] = 2;
        }

        $this->actingAs($user)->post(route('sekolah.assessment.store', 'pss10'), [
            'answers' => $answers,
        ])->assertRedirect();

        $second = $this->actingAs($user)->post(route('sekolah.assessment.store', 'pss10'), [
            'answers' => $answers,
        ]);
        $second->assertStatus(422);

        $this->assertSame(1, SekolahAssessment::where('assessment_assignment_id', $assignment->id)->count());
    }

    public function test_has_submission_reflects_assignment()
    {
        $sekolah = $this->sekolah();
        [$user, $student] = $this->student($sekolah);
        $assignment = $this->assign($sekolah, 'pss10', 'student');

        $this->assertFalse(AssessmentAccess::hasSubmitted($assignment, $student->id, 'sekolah'));

        $answers = [];
        foreach (InstrumentScoring::items(AssessmentInstrument::where('code', 'pss10')->firstOrFail()) as $item) {
            $answers[$item->code] = 2;
        }
        $this->actingAs($user)->post(route('sekolah.assessment.store', 'pss10'), [
            'answers' => $answers,
        ])->assertRedirect();

        $this->assertTrue(AssessmentAccess::hasSubmitted($assignment, $student->id, 'sekolah'));
    }
}
