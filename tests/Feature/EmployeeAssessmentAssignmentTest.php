<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// B1: employee assessment list/form/store assignment-driven (D2/D6/D8) —
// legacy WHO-5 path dihapus, route lama 301 ke index.
class EmployeeAssessmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private PerusahaanEmployee $emp;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AssessmentInstrumentSeeder::class);

        $roleId = \Illuminate\Support\Facades\DB::table('roles')
            ->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT B1', 'brand' => 'B1Co',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
        $this->user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B1',
            'username' => 'emp_b1', 'email' => 'emp@b1.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $this->user->id,
            'name' => 'Emp B1', 'email' => 'emp@b1.local', 'consent_at' => now(),
        ]);
    }

    private function assign(string $code, array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => 'perusahaan',
            'assignable_id' => $this->emp->perusahaan_id,
            'instrument_code' => $code,
            'audience' => 'employee',
            'source' => 'core',
            'opens_at' => today()->subDay(),
            'closes_at' => null,
        ], $overrides));
    }

    private function who5Answers(): array
    {
        return ['W1' => 3, 'W2' => 3, 'W3' => 3, 'W4' => 3, 'W5' => 3];
    }

    // --- index: kartu dari assignment, bukan semua instrumen aktif --------

    public function test_empty_state_without_assignment_and_form_404()
    {
        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.instruments'));
        $res->assertOk()->assertSee('Belum ada asesmen yang dibuka untuk Anda saat ini.');

        // dulu: semua instrumen aktif terlihat tanpa program — bug D2.
        $this->actingAs($this->user)->get(route('perusahaan.assessment.instrument.form', 'who5'))
            ->assertNotFound();
        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => $this->who5Answers(),
        ])->assertNotFound();
    }

    public function test_dashboard_wellbeing_card_uses_latest_who5_not_other_instrument()
    {
        $base = ['perusahaan_id' => $this->emp->perusahaan_id, 'employee_id' => $this->emp->id];
        PerusahaanAssessment::create($base + [
            'instrument_code' => 'who5', 'answers' => $this->who5Answers(), 'score' => 60, 'taken_at' => now()->subDay(),
        ]);
        // Baris non-WHO-5 lebih baru tidak boleh tampil sebagai skor wellbeing.
        PerusahaanAssessment::create($base + [
            'instrument_code' => 'dass21', 'answers' => ['D1' => 3], 'score' => 7, 'taken_at' => now(),
        ]);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'))->assertOk();
        $this->assertSame('who5', $res->viewData('assessment')->instrument_code);
        $this->assertSame(60, (int) $res->viewData('assessment')->score);
    }

    public function test_assigned_addon_visible_unassigned_disc_hidden()
    {
        $this->assign('bigfive', ['source' => 'addon']);
        // disc sengaja TIDAK di-assign — dulu selalu terlihat (bug always-visible).

        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.instruments'));
        $res->assertOk()
            ->assertSee('Big Five (Kepribadian)')
            ->assertSee('Tambahan')
            ->assertDontSee('DISC (Gaya Perilaku Kerja)');
    }

    public function test_ergonomic_visible_when_assigned()
    {
        // dulu tidak pernah terlihat (bug never-visible).
        $this->assign('ergonomic', ['source' => 'addon']);

        $this->actingAs($this->user)->get(route('perusahaan.assessment.instruments'))
            ->assertOk()->assertSee('Ergonomi & Nyeri Kerja');
    }

    public function test_card_shows_round_close_date_and_done_status()
    {
        $a = $this->assign('dass21', [
            'round' => 2, 'closes_at' => today()->addDays(7),
        ]);

        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.instruments'));
        $res->assertOk()
            ->assertSee('Putaran 2')
            ->assertSee('Tutup '.today()->addDays(7)->translatedFormat('d M Y'))
            ->assertSee('Belum dikerjakan');

        // submit → kartu jadi Selesai (tanggal) + muncul di Riwayat.
        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'dass21'), [
            'answers' => $this->dassAnswers(),
        ])->assertRedirect();

        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.instruments'));
        $res->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Riwayat')
            ->assertSee('DASS-21 (Depresi, Kecemasan, Stres)');

        $this->assertDatabaseHas('perusahaan_assessments', [
            'employee_id' => $this->emp->id,
            'instrument_code' => 'dass21',
            'assessment_assignment_id' => $a->id,
        ]);
    }

    // --- satu submission per (assignment, employee) — D6 -------------------

    public function test_second_post_same_assignment_no_new_row_with_flash()
    {
        $a = $this->assign('who5');

        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => $this->who5Answers(),
        ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();

        // double-submit → redirect ke hasil + flash, tanpa row baru.
        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'who5'), [
            'answers' => $this->who5Answers(),
        ])->assertRedirect(route('perusahaan.assessment.result', $row))
            ->assertSessionHas('info', 'Asesmen putaran ini sudah Anda kerjakan.');

        $this->assertSame(1, PerusahaanAssessment::where('employee_id', $this->emp->id)->count());

        // buka form lagi → juga redirect ke hasil (tidak bisa isi ulang di window sama).
        $this->actingAs($this->user)->get(route('perusahaan.assessment.instrument.form', 'who5'))
            ->assertRedirect(route('perusahaan.assessment.result', $row));
    }

    // --- grant klinis bypass assignment (D5) -------------------------------

    public function test_grant_mcmi3_works_without_assignment()
    {
        InstrumentGrant::create([
            'employee_id' => $this->emp->id, 'instrument_code' => 'mcmi3',
            'reason' => 'indikasi klinis (fake data)',
        ]);

        // grant = akses tanpa assignment: form terbuka walau inactive+clinical.
        $this->actingAs($this->user)->get(route('perusahaan.assessment.instrument.form', 'mcmi3'))->assertOk();

        // mcmi3 stub 0 item (skoring menyusul bersama psikolog klinis) — POST
        // hanya memastikan resolve grant lolos (bukan 404), row valid belum bisa.
        $this->actingAs($this->user)->post(route('perusahaan.assessment.instrument.store', 'mcmi3'), [
            'answers' => [],
        ])->assertRedirect();
    }

    // --- legacy WHO-5 path → 301 ke index (D8) ------------------------------

    public function test_legacy_employee_assessment_routes_redirect_301()
    {
        $target = route('perusahaan.assessment.instruments');

        $this->actingAs($this->user)->get(route('perusahaan.assessment.form'))
            ->assertStatus(301)->assertRedirect($target);
        $this->actingAs($this->user)->post(route('perusahaan.assessment.store'), [
            'answers' => $this->who5Answers(),
        ])->assertStatus(301)->assertRedirect($target);

        // tidak ada row NULL instrument_code yang tertulis lewat path lama.
        $this->assertSame(0, PerusahaanAssessment::whereNull('instrument_code')->count());
    }

    private function dassAnswers(): array
    {
        $out = [];
        for ($i = 1; $i <= 21; $i++) {
            $out["D{$i}"] = 1;
        }
        return $out;
    }
}
