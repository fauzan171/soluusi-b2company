<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Link "Tambah ke Google Calendar" (template link resmi Google).
 * ponytail: tanpa OAuth — user klik → event terbuka di Google Calendar mereka.
 * Sinkronisasi otomatis 2-arah (calendar guru/HR) butuh Google OAuth API:
 * buat Google Client credentials + simpan token per user, upgrade di sini.
 */
class GCalLink
{
    public static function addLink(string $title, $start, ?string $details = null, ?string $location = null): string
    {
        $start = Carbon::parse($start);
        $end = $start->copy()->addHour(); // ponytail: sesi default 1 jam, kolom end belum ada.
        $url = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . urlencode($title)
            . '&dates=' . $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis');
        if ($details) {
            $url .= '&details=' . urlencode($details);
        }
        if ($location) {
            $url .= '&location=' . urlencode($location);
        }
        return $url;
    }
}
