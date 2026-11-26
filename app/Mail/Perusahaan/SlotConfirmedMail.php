<?php

namespace App\Mail\Perusahaan;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Konfirmasi slot ke karyawan + konsultan, dengan undangan kalender (.ics).
 * Hanya waktu sesi + nama karyawan — TIDAK ada data assessment/notes (privacy).
 * Sync mengikuti pola kirim email proyek (MAIL_MAILER=log di dev).
 */
class SlotConfirmedMail extends Mailable
{
    public string $startsAt;
    public string $endsAt;
    public string $ics;

    public function __construct(
        public string $employeeName,
        public string $brand,
        Carbon $start,
        Carbon $end,
        int $sessionId,
        string $attendee,
    ) {
        $this->startsAt = $start->translatedFormat('l, d M Y H.i');
        $this->endsAt = $end->translatedFormat('H.i');
        $this->ics = self::ics($sessionId, $start, $end, "Sesi konsultasi wellbeing {$brand}", $attendee);
    }

    /**
     * RFC 5545 VEVENT, METHOD:REQUEST → Gmail/Outlook/Apple tampilkan "Add/Accept".
     * UID stabil per sesi: kirim ulang = update event yang sama, bukan duplikat.
     */
    public static function ics(int $sessionId, Carbon $start, Carbon $end, string $summary, string $attendee): string
    {
        $utc = fn (Carbon $t) => $t->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn (string $s) => addcslashes($s, ',;\\');
        $from = config('mail.from.address');
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Soluusi//Wellbeing//ID',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            "UID:perusahaan-session-{$sessionId}@{$host}",
            'DTSTAMP:' . $utc(now()),
            'DTSTART:' . $utc($start),
            'DTEND:' . $utc($end),
            'SUMMARY:' . $esc($summary),
            'DESCRIPTION:' . $esc('Buka portal ' . config('app.url') . ' untuk link & detail sesi.'),
            "ORGANIZER;CN=Soluusi Wellbeing:mailto:{$from}",
            "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=FALSE:mailto:{$attendee}",
            'SEQUENCE:0',
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ]) . "\r\n";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sesi dikonfirmasi — {$this->startsAt} WIB",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.perusahaan.slot-confirmed');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->ics, 'invite.ics')
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }
}
