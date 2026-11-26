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
            // Window belum buka (opens_at digeser maju) → jangan klaim; dicoba lagi run berikutnya.
            // Sudah tutup/dihapus → klaim tetap jalan lalu di-skip (reminder tidak relevan).
            if ($campaign->assessment_assignment_id) {
                $assignment = \App\Models\AssessmentAssignment::find($campaign->assessment_assignment_id);
                if ($assignment && $assignment->status === 'active' && $assignment->opens_at->isAfter(today())) {
                    continue;
                }
            }
            // QA7-F1: claim atomic — conditional update sent_at WHERE NULL;
            // invoker kedua (timer + daemon) tidak bisa double-send.
            $claimed = EngagementCampaign::whereKey($campaign->id)
                ->whereNull('sent_at')
                ->update(['sent_at' => now()]);
            if (! $claimed) {
                continue; // sudah dikirim invoker lain
            }
            $perusahaan = $campaign->perusahaan;
            if (! $perusahaan) {
                continue;
            }
            // Hanya karyawan dengan consent + email (privacy: opt-in).
            // B5: campaign dari assessment assignment — skip yang sudah submit.
            $recipients = $perusahaan->employees()
                ->whereNotNull('consent_at')
                ->whereNotNull('email');
            if ($campaign->assessment_assignment_id) {
                // assignment ditutup/dihapus → reminder tidak relevan lagi.
                $assignment = \App\Models\AssessmentAssignment::find($campaign->assessment_assignment_id);
                if (! $assignment || ! $assignment->isOpen()) {
                    continue;
                }
                $recipients->whereDoesntHave('assessments', function ($q) use ($campaign) {
                    $q->where('assessment_assignment_id', $campaign->assessment_assignment_id);
                });
            }
            $recipients = $recipients->get();
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
                    // Exception SMTP bisa memuat email penerima — log generik tanpa PII.
                    report(new \RuntimeException("Campaign {$campaign->id}: gagal kirim ke penerima #{$employee->id}"));
                }
            }
            // Tandai terkirim hanya bila semua penerima berhasil (atau tidak ada
            // penerima sama sekali). Gagal total → tetap due, dicoba lagi besok.
            if ($failed === 0) {
                $sent++;
            } else {
                // Gagal sebagian → lepas claim, dicoba lagi besok (SEMANTIK LAMA:
                // sent_at hanya final saat semua penerima berhasil).
                EngagementCampaign::whereKey($campaign->id)->update(['sent_at' => null]);
                $this->warn("Campaign {$campaign->id}: {$failed}/{$recipients->count()} penerima gagal — diulang besok.");
            }
        }

        $this->info("Campaigns sent: {$sent}.");

        return self::SUCCESS;
    }
}
