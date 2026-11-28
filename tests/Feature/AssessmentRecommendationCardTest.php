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

// A4: kartu rekomendasi di dashboard employee — derived dari hasil asesmen
// terbaru (band → saran jumlah sesi + tipe konsultasi + mode chat/online/
// offline → booking prefilled). Wording deskriptif, bukan diagnosis.
class AssessmentRecommendationCardTest extends TestCase
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
            'name' => 'PT A4', 'brand' => 'A4Co',
            'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $this->user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'A4',
            'username' => 'emp_a4', 'email' => 'emp@a4.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $this->emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $this->user->id,
            'name' => 'Emp A4', 'email' => 'emp@a4.local', 'consent_at' => now(),
        ]);
    }

    private function submitWho5(int $w1, string $code = 'who5'): PerusahaanAssessment
    {
        AssessmentAssignment::create([
            'assignable_type' => 'perusahaan', 'assignable_id' => $this->emp->perusahaan_id,
            'instrument_code' => $code, 'audience' => 'employee', 'source' => 'core',
            'opens_at' => today()->subDay(),
        ]);
        $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();
        $answers = [];
        foreach ($instrument->questions()->orderBy('ord')->get() as $item) {
            // who5: likert 0..5 positif — semua jawaban diset nilai sama utk
            // menghasilkan band rendah (w1=0) atau tinggi (w1=5).
            $answers[$item->code] = $w1;
        }
        $result = \App\Services\Screening\InstrumentScoring::score($instrument, $answers);

        return PerusahaanAssessment::create([
            'perusahaan_id' => $this->emp->perusahaan_id,
            'employee_id' => $this->emp->id,
            'instrument_code' => $code,
            'answers' => $answers,
            'score' => $result['score'],
            'taken_at' => now(),
        ]);
    }

    public function test_dashboard_renders_recommendation_card_from_latest_result()
    {
        // who5 rendah (semua 0) → band Berisiko Tinggi → saran 4+ sesi.
        $this->submitWho5(0);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()
            ->assertSee('Rekomendasi untuk Kamu', false)
            ->assertSee('4+', false)
            ->assertSee('bukan diagnosis', false)
            // mode options → booking link prefilled
            ->assertSee('chat', false)
            ->assertSee('online', false)
            ->assertSee('offline', false)
            ->assertSee(route('perusahaan.booking', ['consultation_type' => 'chat']));
    }

    public function test_recommendation_sessions_follow_band()
    {
        // band Sehat (skor tinggi) → tanpa saran sesi tambahan (cukup 1 rutin / none).
        $this->submitWho5(5);

        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertSee('Rekomendasi untuk Kamu', false);

        // band label Sehat → constant sesi rendah (1) dirender.
        $res->assertSee('1 sesi', false);
    }

    public function test_no_card_without_results()
    {
        $res = $this->actingAs($this->user)->get(route('perusahaan.employee'));
        $res->assertOk()->assertDontSee('Rekomendasi untuk Kamu', false);
    }

    public function test_booking_form_prefills_consultation_type_from_query()
    {
        $this->actingAs($this->user)
            ->get(route('perusahaan.booking', ['consultation_type' => 'one_on_one']))
            ->assertOk()
            // view menerima prefill dan default radio jadi one_on_one.
            ->assertViewHas('prefillConsultationType', 'one_on_one');
    }

    public function test_booking_form_ignores_invalid_prefill()
    {
        $this->actingAs($this->user)
            ->get(route('perusahaan.booking', ['consultation_type' => 'invalid_mode']))
            ->assertOk()
            ->assertViewHas('prefillConsultationType', null);
    }
}
