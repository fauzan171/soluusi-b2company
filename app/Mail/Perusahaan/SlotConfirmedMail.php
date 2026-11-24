<?php

namespace App\Mail\Perusahaan;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notifikasi ke konsultan: karyawan mengonfirmasi salah satu slot proposal.
 * Hanya waktu sesi + nama karyawan — TIDAK ada data assessment/notes (privacy).
 * Sync mengikuti pola kirim email proyek (MAIL_MAILER=log di dev).
 */
class SlotConfirmedMail extends Mailable
{
    public function __construct(
        public string $employeeName,
        public string $brand,
        public string $startsAt,
        public string $endsAt,
    ) {}

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
}
