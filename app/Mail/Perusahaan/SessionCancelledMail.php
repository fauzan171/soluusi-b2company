<?php

namespace App\Mail\Perusahaan;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Email pembatalan sesi + ICS METHOD:CANCEL (calendar penerima hapus event).
 * Privacy: waktu + link portal saja.
 */
class SessionCancelledMail extends Mailable
{
    public function __construct(
        public string $viewUrl,
        public Carbon $start,
        public Carbon $end,
        public string $ics,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sesi konsultasi dibatalkan — ' . $this->start->translatedFormat('l, d M Y H.i') . ' WIB',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.perusahaan.session-cancelled');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->ics, 'invite.ics')
                ->withMime('text/calendar; charset=UTF-8; method=CANCEL'),
        ];
    }
}
