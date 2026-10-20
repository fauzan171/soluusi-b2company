<?php

namespace App\Support;

use App\Models\ConsultationSession;

/**
 * White-label brand resolver (PRD §15.4 + anti-circumvention §10.8).
 * Satu pintu: semua mail/gcal/page ambil brand dari sini. Customer TIDAK
 * boleh lihat string "Soluusi".
 */
class Brand
{
    public static function for(?ConsultationSession $session): string
    {
        return $session?->klinik?->brand_name ?? config('whitelabel.default_brand');
    }

    public static function fromEmail(?ConsultationSession $session): string
    {
        return $session?->klinik?->from_email ?? config('whitelabel.email');
    }
}
