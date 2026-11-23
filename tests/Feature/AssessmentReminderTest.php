<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\EngagementCampaign;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use Database\Seeders\AssessmentInstrumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// B5: auto invite/reminder campaign saat assignment dibuat; sender skip
// karyawan yang sudah submit untuk assignment tsb.
class AssessmentReminderTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AssessmentInstrumentSeeder::class);
        $this->company = Perusahaan::create([
            'name' => 'PT B5', 'brand' => 'B5',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
    }

    private function assignment(array $overrides = []): AssessmentAssignment
    {
        return AssessmentAssignment::create(array_merge([
            'assignable_type' => $this->company->getMorphClass(),
            'assignable_id' => $this->company->id,
            'instrument_code' => 'dass21',
            'audience' => 'employee',
            'source' => 'core',
            'opens_at' => today()->addDay(),
            'closes_at' => null,
        ], $overrides));
    }

    private function employee(string $email, ?string $consent = 'consent'): PerusahaanEmployee
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'emp-'.$email, 'created_at' => now(), 'updated_at' => now()]);
        $user = \App\Models\User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'B5',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);

        return PerusahaanEmployee::create([
            'perusahaan_id' => $this->company->id, 'user_id' => $user->id,
            'name' => 'Emp B5', 'email' => $email,
            'consent_at' => $consent === 'consent' ? now() : null,
        ]);
    }

    public function test_perusahaan_assignment_creates_open_campaign()
    {
        $assignment = $this->assignment();

        $campaigns = EngagementCampaign::where('assessment_assignment_id', $assignment->id)->get();
        $this->assertCount(1, $campaigns);
        $c = $campaigns->first();
        $this->assertSame('reminder', $c->kind);
        $this->assertSame($this->company->id, $c->perusahaan_id);
        $this->assertSame('Asesmen DASS-21 (Depresi, Kecemasan, Stres) sudah dibuka', $c->title);
        $this->assertEquals(today()->addDay()->setTime(8, 0), $c->scheduled_at);
        $this->assertNull($c->sent_at);
    }

    public function test_long_window_creates_closing_reminder()
    {
        $assignment = $this->assignment([
            'opens_at' => today()->addDay(),
            'closes_at' => today()->addDays(10),
        ]);

        $campaigns = EngagementCampaign::where('assessment_assignment_id', $assignment->id)
            ->orderBy('scheduled_at')->get();
        $this->assertCount(2, $campaigns);
        $this->assertSame('Asesmen DASS-21 (Depresi, Kecemasan, Stres) sudah dibuka', $campaigns[0]->title);
        $this->assertSame('Asesmen DASS-21 (Depresi, Kecemasan, Stres) sudah dibuka', $campaigns[1]->title);
        $this->assertEquals(today()->addDay()->setTime(8, 0), $campaigns[0]->scheduled_at);
        $this->assertEquals(today()->addDays(7)->setTime(8, 0), $campaigns[1]->scheduled_at);
    }

    public function test_short_window_creates_no_closing_reminder()
    {
        $assignment = $this->assignment([
            'opens_at' => today()->addDay(),
            'closes_at' => today()->addDays(6),
        ]);

        $this->assertCount(1, EngagementCampaign::where('assessment_assignment_id', $assignment->id)->get());
    }

    public function test_no_campaign_without_opens_at()
    {
        // opens_at NOT NULL di schema — jalur guard observer (B5) tak tercapai
        // via create langsung. Verifikasi via model baru tanpa insert DB.
        $assignment = new AssessmentAssignment;
        $assignment->assignable_type = $this->company->getMorphClass();
        $assignment->assignable_id = $this->company->id;
        $assignment->opens_at = null;
        $assignment->id = 999; // tanpa insert; campaign tak boleh dibuat
        (new \App\Observers\AssessmentAssignmentObserver)->created($assignment);

        $this->assertCount(0, EngagementCampaign::where('assessment_assignment_id', 999)->get());
    }

    public function test_sekolah_assignment_creates_no_campaign()
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'op', 'created_at' => now(), 'updated_at' => now()]);
        $user = \App\Models\User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Op', 'lastname' => 'Sek',
            'username' => 'op_sek', 'email' => 'op@sek.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $sekolah = \App\Models\Sekolah\Sekolah::create([
            'name' => 'SMA B5', 'brand' => 'SMAB5', 'is_active' => true,
        ]);
        $assignment = $this->assignment([
            'assignable_type' => $sekolah->getMorphClass(),
            'assignable_id' => $sekolah->id,
            'audience' => 'student',
        ]);

        $this->assertCount(0, EngagementCampaign::where('assessment_assignment_id', $assignment->id)->get());
    }

    public function test_sender_skips_employee_who_submitted()
    {
        // Mail::raw + MAIL_MAILER=array (phpunit.xml) — sama pattern Wv4CampaignTest.
        $assignment = $this->assignment([
            'opens_at' => today()->subDay(),
            'closes_at' => today()->addDay(),
        ]);
        $done = $this->employee('done@b5.local');
        $todo = $this->employee('todo@b5.local');
        $noConsent = $this->employee('noconsent@b5.local', null);

        PerusahaanAssessment::create([
            'perusahaan_id' => $this->company->id, 'employee_id' => $done->id,
            'instrument_code' => 'dass21', 'answers' => [], 'score' => 0, 'taken_at' => now(),
            'assessment_assignment_id' => $assignment->id,
        ]);

        $this->artisan('wellbeing:send-campaigns')->assertSuccessful();

        $sent = $this->sentMessages();
        $this->assertCount(1, $sent, 'Hanya karyawan belum-submit + consent yang menerima');
        $this->assertStringContainsString('todo@b5.local', $sent[0]->toString());
        $this->assertStringNotContainsString('done@b5.local', $sent[0]->toString());
        $this->assertStringNotContainsString('noconsent@b5.local', $sent[0]->toString());
    }

    /** Mail::raw via array mailer — ambil pesan dari transport (Wv4CampaignTest). */
    private function sentMessages(): array
    {
        $transport = app('mailer')->getSymfonyTransport();

        return $transport instanceof \Illuminate\Mail\Transport\ArrayTransport
            ? $transport->messages()->all()
            : [];
    }
}
