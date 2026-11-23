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
            $failed = 0;
            foreach ($recipients as $employee) {
                // REV MED-3: try/catch per penerima — mail gagal di tengah tidak
                // menandai campaign terkirim lalu re-send ke yang sudah dapat.
                // ponytail: partial retry belum ada ledger per-penerima; upgrade
                // ke tabel campaign_recipients bila resend duplikat jadi masalah.
                try {
                    Mail::raw(
                        "Halo {$employee->name},\n\n{$campaign->title}\n\n{$campaign->body}\n\nSalam,\n{$perusahaan->brand}",
                        fn ($m) => $m->to($employee->email)->subject($campaign->title)
                    );
                } catch (\Throwable $e) {
                    $failed++;
                    report($e);
                }
            }
            // Tandai terkirim hanya bila semua penerima berhasil (atau tidak ada
            // penerima sama sekali). Gagal total → tetap due, dicoba lagi besok.
            if ($failed === 0) {
                $campaign->update(['sent_at' => now()]);
                $sent++;
            } else {
                $this->warn("Campaign {$campaign->id}: {$failed}/{$recipients->count()} penerima gagal — diulang besok.");
            }
        }

        $this->info("Campaigns sent: {$sent}.");

        return self::SUCCESS;
    }
}
