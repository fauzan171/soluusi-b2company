<?php

namespace Tests\Feature;

use App\Models\EngagementCampaign;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// wv4: feature D — engagement campaign (announcement + email scheduled)
// + scheduler commands.
class Wv4CampaignTest extends TestCase
{
    use RefreshDatabase;

    private function makeHrCompany(): array
    {
        $hrRoleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Camp', 'brand' => 'CampCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 40, 'is_active' => true,
        ]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Camp',
            'username' => 'hr_camp', 'email' => 'hr_camp@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$p, $hr];
    }

    private function makeEmployee(Perusahaan $p, int $i, bool $consent = true): User
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $u = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => "C{$i}",
            'username' => "emp_c{$i}", 'email' => "emp_c{$i}@test.local",
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        DB::table('perusahaan_employees')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $u->id, 'name' => "Emp Camp {$i}",
            'email' => "emp_c{$i}@test.local", 'consent_at' => $consent ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u;
    }

    public function test_hr_creates_announcement_visible_in_employee_activities(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $emp = $this->makeEmployee($p, 1);

        $this->actingAs($hr)->post(route('perusahaan.campaigns.store'), [
            'title' => 'Webinar Mindfulness', 'body' => 'Jangan lupa ikut webinar minggu depan.',
            'channel' => 'announcement',
        ])->assertRedirect(route('perusahaan.campaigns.index'));

        $campaign = EngagementCampaign::where('perusahaan_id', $p->id)->first();
        $this->assertNotNull($campaign->sent_at, 'Announcement harus langsung sent');

        // Employee sees it in Aktivitasku.
        $this->actingAs($emp)->get(route('perusahaan.employee.activities'))
            ->assertOk()
            ->assertSee('Webinar Mindfulness')
            ->assertSee('Jangan lupa ikut webinar');
    }

    public function test_store_persists_kind(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $this->actingAs($hr)->post(route('perusahaan.campaigns.store'), [
            'title' => 'Pengingat asesmen', 'body' => 'Isi asesmenmu ya.',
            'channel' => 'announcement', 'kind' => 'reminder',
        ])->assertRedirect(route('perusahaan.campaigns.index'));

        $campaign = EngagementCampaign::where('perusahaan_id', $p->id)->first();
        $this->assertSame('reminder', $campaign->kind);
    }

    public function test_invalid_kind_rejected(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $this->actingAs($hr)->post(route('perusahaan.campaigns.store'), [
            'title' => 'X', 'body' => 'Y', 'channel' => 'email', 'kind' => 'ngasal',
        ])->assertSessionHasErrors('kind');

        $this->assertSame(0, EngagementCampaign::where('perusahaan_id', $p->id)->count());
    }

    public function test_announcement_auto_sent_for_any_kind(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        foreach (['edukasi', 'pengumuman', 'reminder'] as $kind) {
            $this->actingAs($hr)->post(route('perusahaan.campaigns.store'), [
                'title' => "T {$kind}", 'body' => 'B', 'channel' => 'announcement', 'kind' => $kind,
            ])->assertRedirect(route('perusahaan.campaigns.index'));
        }

        $this->assertSame(3, EngagementCampaign::where('perusahaan_id', $p->id)->whereNotNull('sent_at')->count());
    }

    public function test_email_edukasi_kind_still_due_for_sender(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $p->campaigns()->create([
            'title' => 'Tips tidur', 'body' => 'Tidur 7-8 jam.',
            'channel' => 'email', 'kind' => 'edukasi',
            'scheduled_at' => now()->subMinute(), 'sent_at' => null, 'created_by' => $hr->id,
        ]);

        $this->artisan('wellbeing:send-campaigns')->assertExitCode(0);
        $this->assertNotNull($p->campaigns()->first()->refresh()->sent_at, 'Email edukasi due harus terkirim');
    }

    public function test_scheduled_email_campaign_sends_via_command_to_consented_only(): void
    {
        [$p, $hr] = $this->makeHrCompany();
        $consented = $this->makeEmployee($p, 2, true);
        $this->makeEmployee($p, 3, false); // no consent — must NOT receive

        $p->campaigns()->create([
            'title' => 'Nudge Asesmen', 'body' => 'Isi asesmen bulan ini ya.',
            'channel' => 'email', 'scheduled_at' => now()->subMinute(), 'sent_at' => null,
            'created_by' => $hr->id,
        ]);

        $this->artisan('wellbeing:send-campaigns')->assertExitCode(0);

        $this->assertNotNull($p->campaigns()->first()->refresh()->sent_at, 'Campaign due harus terkirim');

        // Mail::raw (array mailer di test) — hitung pesan via log mailer.
        $sent = $this->sentMessages();
        $this->assertCount(1, $sent, 'Hanya karyawan consent yang menerima email');
        $this->assertStringContainsString($consented->email, $sent[0]->toString());
    }

    public function test_future_email_campaign_not_sent_early(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $p->campaigns()->create([
            'title' => 'Nanti', 'body' => 'Belum due.', 'channel' => 'email',
            'scheduled_at' => now()->addDays(2), 'sent_at' => null, 'created_by' => $hr->id,
        ]);

        $this->artisan('wellbeing:send-campaigns')->assertExitCode(0);
        $this->assertNull($p->campaigns()->first()->refresh()->sent_at);
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_renewal_notice_emails_hr_admin_when_expiring(): void
    {
        [$p, $hr] = $this->makeHrCompany();

        $pkg = \App\Models\WellbeingPackage::create([
            'code' => 'growth-rn', 'name' => 'Growth', 'duration_days' => 180,
            'assessment_count' => 2, 'assessment_domains' => ['mh'],
            'expert_chat_experts' => 1, 'webinar_count' => 3, 'quarterly_review' => false,
        ]);
        \App\Models\CompanyWellbeingProgram::create([
            'perusahaan_id' => $p->id, 'wellbeing_package_id' => $pkg->id,
            'employee_band_size' => 100, 'deep_support_mode' => 'mhcu',
            'starts_at' => today()->subDays(160)->toDateString(),
            'ends_at' => today()->addDays(20)->toDateString(),
            'expert_chat_quota' => 30, 'deep_support_quota' => 15, 'webinar_quota' => 3,
            'assessment_count' => 2, 'monthly_price' => 9999999, 'status' => 'active',
        ]);

        $this->artisan('wellbeing:renewal-notice')->assertExitCode(0);

        $sent = $this->sentMessages();
        $this->assertCount(1, $sent, 'HR admin harus menerima renewal notice');
        $this->assertStringContainsString($hr->email, $sent[0]->toString());
    }

    /**
     * Mail::raw dengan MAIL_MAILER=array — ambil pesan dari mailer transport.
     */
    private function sentMessages(): array
    {
        $transport = app('mailer')->getSymfonyTransport();

        return $transport instanceof \Illuminate\Mail\Transport\ArrayTransport
            ? $transport->messages()->all()
            : [];
    }

    public function test_scheduler_registers_wv4_commands(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '')->implode(' ');

        $this->assertStringContainsString('wellbeing:send-campaigns', $events);
        $this->assertStringContainsString('wellbeing:renewal-notice', $events);
        // ExpireWellbeingPrograms (Kernel) tetap terjadwal.
        $this->assertStringContainsString('wellbeing:expire-programs', $events);
    }
}
