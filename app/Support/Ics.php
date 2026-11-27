<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * RFC 5545 VEVENT minimal — dipakai undangan kalender sesi konsultasi.
 * Pure PHP, tanpa dependency. METHOD:REQUEST → Gmail/Outlook/Apple tampilkan
 * "Add/Accept"; METHOD:CANCEL → hapus event di calendar penerima.
 *
 * UID stabil per sesi: kirim ulang = update event yang sama, bukan duplikat.
 * SEQUENCE dinaikkan saat jadwal berubah (reschedule) / cancel.
 */
class Ics
{
    /** Prefix line folding = 75 oktet termasuk CRLF (RFC 5545 §3.1). */
    private const LINE_OCTETS = 75;

    public static function event(
        int $sessionId,
        Carbon $start,
        Carbon $end,
        ?string $summary = null,
        ?string $location = null,
        ?string $description = null,
        ?string $url = null,
        int $sequence = 0,
    ): string {
        return self::calendar('REQUEST', [
            'UID' => self::uid($sessionId),
            'DTSTAMP' => self::utc(now()),
            'DTSTART' => self::utc($start),
            'DTEND' => self::utc($end),
            'SUMMARY' => self::esc($summary ?? 'Sesi konsultasi Soluusi'),
            'DESCRIPTION' => self::esc($description ?? 'Buka portal Soluusi untuk detail sesi.'),
            'LOCATION' => self::esc($location),
            'URL' => $url,
            'SEQUENCE' => (string) $sequence,
            'STATUS' => 'CONFIRMED',
        ]);
    }

    /** Cancel: METHOD:CANCEL + STATUS:CANCELLED, SEQUENCE dinaikkan. */
    public static function cancel(
        int $sessionId,
        Carbon $start,
        Carbon $end,
        ?string $summary = null,
        int $sequence = 1,
    ): string {
        return self::calendar('CANCEL', [
            'UID' => self::uid($sessionId),
            'DTSTAMP' => self::utc(now()),
            'DTSTART' => self::utc($start),
            'DTEND' => self::utc($end),
            'SUMMARY' => self::esc($summary ?? 'Sesi konsultasi Soluusi'),
            'SEQUENCE' => (string) $sequence,
            'STATUS' => 'CANCELLED',
        ]);
    }

    /** @param array<string, ?string> $props */
    private static function calendar(string $method, array $props): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Soluusi//Wellbeing//ID',
            "METHOD:{$method}",
            'BEGIN:VEVENT',
        ];
        foreach ($props as $key => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = "{$key}:{$value}";
            }
        }
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines)) . "\r\n";
    }

    private static function uid(int $sessionId): string
    {
        return "session-{$sessionId}@wellbeing.soluusi.com";
    }

    private static function utc(Carbon $t): string
    {
        return $t->copy()->utc()->format('Ymd\THis\Z');
    }

    /** RFC 5545 §3.3.11: escape backslash, semicolon, comma, newline. */
    private static function esc(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }

        return str_replace(["\\", "\r\n", "\n", "\r", ';', ','], ['\\\\', '\n', '\n', '\n', '\;', '\,'], $s);
    }

    /** Line folding ≤75 oktet; continuation diawali spasi. */
    private static function fold(string $line): string
    {
        if (strlen($line) <= self::LINE_OCTETS) {
            return $line;
        }

        $out = substr($line, 0, self::LINE_OCTETS);
        $rest = substr($line, self::LINE_OCTETS);
        while ($rest !== '') {
            $chunk = substr($rest, 0, self::LINE_OCTETS - 1); // 74 + leading space = 75
            $out .= "\r\n " . $chunk;
            $rest = substr($rest, self::LINE_OCTETS - 1);
        }

        return $out;
    }
}
