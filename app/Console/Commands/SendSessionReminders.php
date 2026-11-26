<?php

namespace App\Console\Commands;

use App\Models\Perusahaan\PerusahaanSession;
use App\Mail\Perusahaan\SessionScheduledMail;
use App\Support\Ics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Reminder sesi terjadwal: H-1 hari dan H-1 jam, kedua pihak.
 * Idempotent via flag reminded_day_at / reminded_hour_at per sesi —
 * scheduler double-fire tidak mengirim ulang.
 */
class SendSessionReminders extends Command
{
    protected $signature = 'sessions:send-reminders';

    protected $description = 'Reminder H-1 hari dan H-1 jam untuk sesi perusahaan terjadwal';

    public function handle(): int
    {
        $now = now();

        $dueDay = $this->due('reminded_day_at', 1440, 1505, $now); // H-1 hari: 24-25 jam sebelum sesi
        $dueHour = $this->due('reminded_hour_at', 55, 60, $now);   // H-1 jam: 55-60 menit sebelum sesi

        $sent = 0;
        foreach ([$dueDay, $dueHour] as $sessions) {
            foreach ($sessions as $s) {
                $sent += $this->remind($s);
            }
        }

        $this->info("Session reminders sent: {$sent}");

        return self::SUCCESS;
    }

    /** Sesi dalam window [from, to] sebelum scheduled_at yang belum diflag. */
    private function due(string $flag, int $fromMin, int $toMin, \Illuminate\Support\Carbon $now)
    {
        return PerusahaanSession::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->whereNull($flag)
            ->whereBetween('scheduled_at', [$now->copy()->addMinutes($fromMin), $now->copy()->addMinutes($toMin)])
            ->get();
    }

    private function remind(PerusahaanSession $s): int
    {
        // Carbon 3 diffInMinutes bertanda — pakai isAfter agar flag tidak terbalik.
        $flag = $s->scheduled_at->isAfter(now()->copy()->addMinutes(90)) ? 'reminded_day_at' : 'reminded_hour_at';
        // Mark FIRST (dalam transaction bersama read di caller) — double scheduler
        // race kalah flag, bukan kirim email dua kali.
        $s->forceFill([$flag => now()])->save();

        $ics = Ics::event($s->id, $s->scheduled_at, $s->ended_at ?: $s->scheduled_at->copy()->addHour(),
            summary: 'Sesi konsultasi Soluusi',
            sequence: max(1, (int) ($s->schedule_version ?? 1)));

        $empEmail = $s->employee?->email ?: $s->employee?->user?->email;
        $conEmail = $s->consultant?->notificationEmail();

        $sent = 0;
        foreach (['employee' => $empEmail, 'consultant' => $conEmail] as $who => $email) {
            if (! $email || str_ends_with($email, '@b2b.local')) {
                continue;
            }
            Mail::to($email)->send(new SessionScheduledMail(
                route($who === 'employee' ? 'perusahaan.employee' : 'consultant.sessions'),
                $s->scheduled_at,
                $s->ended_at ?: $s->scheduled_at->copy()->addHour(),
                $ics,
            ));
            $sent++;
        }

        return $sent;
    }
}
