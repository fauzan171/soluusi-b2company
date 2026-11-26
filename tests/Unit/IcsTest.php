<?php

namespace Tests\Unit;

use App\Support\Ics;
use Tests\TestCase;

class IcsTest extends TestCase
{
    public function test_basic_event_structure(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');
        $end = \Illuminate\Support\Carbon::parse('2026-12-01 10:00', 'UTC');

        $ics = Ics::event(42, $start, $end);

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("\r\nEND:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('METHOD:REQUEST', $ics);
        $this->assertStringContainsString("UID:session-42@wellbeing.soluusi.com", $ics);
        $this->assertStringContainsString('SUMMARY:Sesi konsultasi Soluusi', $ics);
        $this->assertStringContainsString('DTSTART:20261201T090000Z', $ics);
        $this->assertStringContainsString('DTEND:20261201T100000Z', $ics);
        $this->assertStringContainsString('SEQUENCE:0', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
        // DTSTAMP UTC, format basic
        $this->assertMatchesRegularExpression('/DTSTAMP:\d{8}T\d{6}Z/', $ics);
    }

    public function test_start_end_converted_to_utc(): void
    {
        // 09:00 WIB (UTC+7) = 02:00 UTC.
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'Asia/Jakarta');
        $end = \Illuminate\Support\Carbon::parse('2026-12-01 10:00', 'Asia/Jakarta');

        $ics = Ics::event(1, $start, $end);

        $this->assertStringContainsString('DTSTART:20261201T020000Z', $ics);
        $this->assertStringContainsString('DTEND:20261201T030000Z', $ics);
    }

    public function test_uid_stable_across_renders(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');
        $end = $start->copy()->addHour();

        $a = Ics::event(42, $start, $end);
        $b = Ics::event(42, $start->copy()->addDay(), $end->copy()->addDay());

        $uidA = $this->line($a, 'UID');
        $uidB = $this->line($b, 'UID');
        $this->assertSame($uidA, $uidB);
    }

    public function test_sequence_bumps_on_reschedule(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');
        $end = $start->copy()->addHour();

        $first = Ics::event(7, $start, $end);
        $second = Ics::event(7, $start->copy()->addDay(), $end->copy()->addDay(), sequence: 1);

        $this->assertStringContainsString('SEQUENCE:0', $first);
        $this->assertStringContainsString('SEQUENCE:1', $second);
        // Same UID on both → calendar updates, not duplicate.
        $this->assertSame($this->line($first, 'UID'), $this->line($second, 'UID'));
    }

    public function test_cancel_method_and_status(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');

        $ics = Ics::cancel(7, $start, $start->copy()->addHour());

        $this->assertStringContainsString('METHOD:CANCEL', $ics);
        $this->assertStringContainsString('STATUS:CANCELLED', $ics);
        $this->assertStringContainsString('SEQUENCE:1', $ics);
    }

    public function test_text_escaping(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');

        $ics = Ics::event(1, $start, $start->copy()->addHour(),
            location: "Ruang 1, Lt. 2; Gedung A\\Jakarta");

        $loc = $this->line($ics, 'LOCATION');
        $this->assertStringContainsString('Ruang 1\, Lt. 2\; Gedung A\\\\Jakarta', $loc);
    }

    public function test_newline_escaping(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');

        $ics = Ics::event(1, $start, $start->copy()->addHour(), description: "baris satu\nbaris dua");

        $this->assertStringContainsString("baris satu\\nbaris dua", $ics);
    }

    public function test_long_value_folds_at_75_octets(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');
        $long = str_repeat('x', 90);
        $ics = Ics::event(1, $start, $start->copy()->addHour(), description: $long);

        // CRLF line endings everywhere (RFC 5545 §3.1) — no bare LF/CR.
        $bare = str_replace("\r\n", '', $ics);
        $this->assertStringNotContainsString("\r", $bare);
        $this->assertStringNotContainsString("\n", $bare);

        // Folding: no logical line longer than 75 octets.
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Line too long: {$line}");
            // Continuation lines start with a space.
        }
        $this->assertStringContainsString("\r\n ", $ics);
    }

    public function test_url_included(): void
    {
        $start = \Illuminate\Support\Carbon::parse('2026-12-01 09:00', 'UTC');

        $ics = Ics::event(1, $start, $start->copy()->addHour(), url: 'https://portal.example/sessions/1');

        $this->assertStringContainsString('URL:https://portal.example/sessions/1', $ics);
    }

    private function line(string $ics, string $key): string
    {
        foreach (explode("\r\n", $ics) as $line) {
            if (str_starts_with($line, $key.':')) {
                return $line;
            }
        }

        $this->fail("No {$key} line in ICS.");
    }
}
