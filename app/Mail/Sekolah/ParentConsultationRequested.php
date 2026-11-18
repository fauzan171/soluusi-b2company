<?php

namespace App\Mail\Sekolah;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use Illuminate\Mail\Mailable;

/**
 * Pemberitahuan orangtua/wali bahwa konsultasi diminta untuk anaknya (B.3).
 *
 * Email-only: TIDAK ada akun dibuat, TIDAK ada link login, TIDAK ada
 * request_notes/alasan rujukan — konten sesi rahasia antara siswa
 * dan konselor. Orangtua hanya tahu bahwa sesi akan berlangsung.
 */
class ParentConsultationRequested extends Mailable
{
    public function __construct(
        public readonly Sekolah $sekolah,
        public readonly SekolahStudent $student,
        public readonly SekolahSession $session,
    ) {}

    public function build(): static
    {
        return $this->subject("Pemberitahuan program konseling {$this->sekolah->brand}")
            ->text('emails.sekolah.parent-consultation-requested');
    }
}
