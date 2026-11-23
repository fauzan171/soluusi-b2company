<?php

namespace App\Console\Commands;

use App\Models\EngagementCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * wv4 (feature D) — daily sender campaign email terjadwal yang due
 * (scheduled_at <= now, belum sent). Announcement sudah inline saat create.
 */
class SendEngagementCampaigns extends Command
{
    protected $signature = 'wellbeing:send-campaigns';
    protected $description = 'Kirim engagement campaign email yang sudah due';

    public function handle(): int
    {
        $due = EngagementCampaign::query()
            ->where('channel', 'email')
            ->whereNull('sent_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('perusahaan')
            ->get();

        $sent = 0;
        foreach ($due as $campaign) {
            $perusahaan = $campaign->perusahaan;
            if (! $perusahaan) {
                continue;
            }
            // Hanya karyawan dengan consent + email (privacy: opt-in).
            $recipients = $perusahaan->employees()
                ->whereNotNull('consent_at')
                ->whereNotNull('email')
                ->get();
            foreach ($recipients as $employee) {
                Mail::raw(
                    "Halo {$employee->name},\n\n{$campaign->title}\n\n{$campaign->body}\n\nSalam,\n{$perusahaan->brand}",
                    fn ($m) => $m->to($employee->email)->subject($campaign->title)
                );
            }
            $campaign->update(['sent_at' => now()]);
            $sent++;
        }

        $this->info("Campaigns sent: {$sent}.");

        return self::SUCCESS;
    }
}
