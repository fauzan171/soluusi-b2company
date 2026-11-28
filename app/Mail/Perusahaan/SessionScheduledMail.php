<?php

namespace App\Mail\Perusahaan;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Email jadwal sesi konsultasi (request/proposal/scheduled/reschedule/reminder
 * — template sama). Privacy: hanya waktu + mode + link portal; TIDAK ada
 * topik/notes/chat/nama karyawan. ICS dilampirkan bila sesi sudah terjadwal.
 */
class SessionScheduledMail extends Mailable
{
    public function __construct(
        public string $viewUrl,
        public ?Carbon $start,
        public ?Carbon $end,
        public ?string $ics,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->start ? 'Sesi konsultasi — ' . $this->start->translatedFormat('l, d M Y H.i') . ' WIB' : 'Sesi konsultasi Soluusi',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.perusahaan.session-scheduled');
    }

    public function attachments(): array
    {
        return $this->ics ? [
            Attachment::fromData(fn () => $this->ics, 'invite.ics')
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ] : [];
    }
}
