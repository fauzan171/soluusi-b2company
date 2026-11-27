<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// A3: wizard step-by-step di form assessment — satu pertanyaan per langkah,
// progress bar + terjawab counter, likert button, optional discussion note.
// Input names tetap sama (answers[...]) — store validation tidak berubah.
class AssessmentWizardTest extends TestCase
{
    use RefreshDatabase;

    private PerusahaanEmployee $emp;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);

        $roleId = Role::firstOrCreate(['name' => 'employee'])->id;
        $p = Perusahaan::create([
            'name' => 'PT A3', 'brand' => 'A3Co',
            'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $this->user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A3',
            'username' => 'emp_a3', 'email' => 'emp@a3.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $this->user->id,
            'name' => 'Emp A3', 'email' => 'emp@a3.local', 'consent_at' => now(),
        ]);
    }

    private function assign(string $code): AssessmentAssignment
    {
        return AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $this->emp->perusahaan_id,
            'instrument_code' => $code, 'audience' => 'employee', 'source' => 'core',
            'opens_at' => today()->subDay(),
        ]);
    }

    public function test_form_renders_wizard_shell()
    {
        $this->assign('who5');

        $res = $this->actingAs($this->user)->get(route('perusahaan.assessment.instrument.form', 'who5'));
        $res->assertOk()
            // step shell: satu pertanyaan per card, progress bar, counter terjawab
            ->assertSee('data-assessment-wizard', false)
            ->assertSee('data-progress', false)
            ->assertSee('terjawab')
            ->assertSee('Apa yang ingin kamu diskusikan dengan konsultan?', false)
            // nav buttons + submit (progressive enhancement, semua input tetap dirender)
            ->assertSee('Sebelumnya', false)
            ->assertSee('Lanjut', false);
    }

    public function test_store_accepts_optional_discussion_note()
    {
        $this->assign('who5');

        $answers = ['W1' => 3, 'W2' => 3, 'W3' => 3, 'W4' => 3, 'W5' => 3];
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'who5'), [
                'answers' => $answers,
                'discussion_note' => 'Ingin bicara soal pola tidur (fake data)',
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertSame('Ingin bicara soal pola tidur (fake data)', $row->notes);
    }

    public function test_store_discussion_note_optional_and_bounded()
    {
        $this->assign('who5');
        $answers = ['W1' => 3, 'W2' => 3, 'W3' => 3, 'W4' => 3, 'W5' => 3];

        // tanpa note → tetap sukses
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'who5'), ['answers' => $answers])
            ->assertRedirect();
        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->assertNull($row->notes);

        // >1000 char → validation error (baru di-assign round 2 utk cek terpisah).
        $this->assign('dass21');
        $dass = [];
        for ($i = 1; $i <= 21; $i++) {
            $dass["D{$i}"] = 1;
        }
        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'dass21'), [
                'answers' => $dass,
                'discussion_note' => str_repeat('x', 1001),
            ])->assertSessionHasErrors('discussion_note');
    }

    public function test_discussion_note_visible_on_result_page()
    {
        $this->assign('who5');
        $answers = ['W1' => 3, 'W2' => 3, 'W3' => 3, 'W4' => 3, 'W5' => 3];

        $this->actingAs($this->user)
            ->post(route('perusahaan.assessment.instrument.store', 'who5'), [
                'answers' => $answers,
                'discussion_note' => 'topik: manajemen stres (fake data)',
            ])->assertRedirect();

        $row = PerusahaanAssessment::where('employee_id', $this->emp->id)->firstOrFail();
        $this->actingAs($this->user)->get(route('perusahaan.assessment.result', $row))
            ->assertOk()
            ->assertSee('topik: manajemen stres (fake data)');
    }
}
