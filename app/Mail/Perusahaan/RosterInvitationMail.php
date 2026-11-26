<?php

namespace App\Mail\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Undangan program untuk karyawan (roster CAP). Pengganti Mail::raw yang
 * tidak terekam Mail::fake — audit UX butuh assert sulit di test.
 * Sync (bukan queue) mengikuti pola kirim email lama proyek (MAIL_MAILER=log di dev).
 */
class RosterInvitationMail extends Mailable
{
    public function __construct(
        public Perusahaan $perusahaan,
        public PerusahaanEmployee $employee,
        public string $registerUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->employee->email,
            subject: "Undangan program wellbeing {$this->perusahaan->brand}",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.perusahaan.roster-invitation');
    }
}
