<?php

namespace App\Services;

use App\Mail\Perusahaan\SessionScheduledMail;
use App\Mail\Perusahaan\SessionCancelledMail;
use App\Models\Perusahaan\PerusahaanSession;
use App\Support\Ics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Email notifikasi sesi konsultasi perusahaan (CAP), dipanggil dari
 * SessionStateMachine + BookingService (transisi status) — single hook supaya
 * semua call site (HR review, proposal, booking, sweep) otomatis ter-cover.
 *
 * Privacy: subject + body TIDAK memuat topik/notes/chat (health detail),
 * tidak ada nama karyawan ke konsultan (anonim #id) — hanya waktu, mode,
 * tipe profesional, link portal. Konsultan @b2b.local/null → skip, no throw.
 */
class SessionNotify
{
    /** Sesi baru masuk antrean (scheduled + belum ada konsultan) → info konsultan belum bisa; employee minta request. */
    public static function requested(PerusahaanSession $s): void
    {
        self::send($s, employeeOnly: true);
    }

    /** Konsultan usulkan slot → employee diminta memilih. */
    public static function slotProposed(PerusahaanSession $s): void
    {
        self::send($s, employeeOnly: true);
    }

    /** Sesi terjadwal (booking/schedule/confirm) → kedua pihak + ICS. */
    public static function scheduled(PerusahaanSession $s, ?int $sequence = null): void
    {
        self::send($s, sequence: $sequence);
    }

    /** Reschedule → kedua pihak, UID sama + SEQUENCE naik. */
    public static function rescheduled(PerusahaanSession $s): void
    {
        self::send($s, sequence: max(1, (int) ($s->schedule_version ?? 1)));
    }

    /** Cancel → kedua pihak, METHOD:CANCEL ICS. */
    public static function cancelled(PerusahaanSession $s): void
    {
        // Sesi belum pernah terjadwal (cancel request pending) → tidak ada
        // event kalender untuk dibatalkan; cukup email employee.
        if (! $s->scheduled_at) {
            self::send($s, employeeOnly: true);

            return;
        }

        $sequence = max(1, (int) ($s->schedule_version ?? 1));
        $ics = Ics::cancel($s->id, $s->scheduled_at, self::endAt($s), sequence: $sequence);
        self::eachRecipient($s, fn (string $email, bool $isEmployee) => Mail::to($email)->send(
            new SessionCancelledMail(self::view($s, $isEmployee), $s->scheduled_at, self::endAt($s), $ics)
        ));
    }

    private static function send(PerusahaanSession $s, bool $employeeOnly = false, ?int $sequence = null): void
    {
        $ics = $s->scheduled_at
            ? Ics::event($s->id, $s->scheduled_at, self::endAt($s),
                summary: 'Sesi konsultasi Soluusi',
                location: self::location($s),
                url: self::url($s),
                sequence: $sequence ?? 0)
            : null;

        self::eachRecipient($s, function (string $email, bool $isEmployee) use ($s, $ics, $employeeOnly) {
            if (! $isEmployee && $employeeOnly) {
                return; // konsultan belum ter-assign / bukan pihak notifikasi ini.
            }
            Mail::to($email)->send(new SessionScheduledMail(
                self::view($s, $isEmployee), $s->scheduled_at, self::endAt($s), $ics
            ));
        }, employeeOnly: $employeeOnly);
    }

    /** @param callable(string, bool):void $cb ($email, $isEmployee) */
    private static function eachRecipient(PerusahaanSession $s, callable $cb, bool $employeeOnly = false): void
    {
        // Email employee: kolom email roster (fallback user login).
        $empEmail = $s->employee?->email ?: $s->employee?->user?->email;
        if ($empEmail && ! str_ends_with($empEmail, '@b2b.local')) {
            $cb($empEmail, true);
        }

        if ($employeeOnly) {
            return;
        }

        // Email konsultan: notify_email (B2C) — placeholder/null di-skip.
        $conEmail = $s->consultant?->notificationEmail();
        if ($conEmail && $conEmail !== $empEmail) {
            $cb($conEmail, false);
        }
    }

    private static function endAt(PerusahaanSession $s): Carbon
    {
        if ($s->ended_at) {
            return $s->ended_at;
        }
        // Chat room: durasi konstanta; offline/online default 1 jam.
        $minutes = ($s->consultation_type ?: 'chat') === 'chat'
            ? PerusahaanSession::CHAT_MINUTES
            : 60;

        return ($s->scheduled_at ?? now())->copy()->addMinutes($minutes);
    }

    private static function location(PerusahaanSession $s): ?string
    {
        if (($s->mode ?? 'online') === 'offline') {
            return $s->location ?: ($s->room?->name ?: 'Lihat detail lokasi di portal');
        }

        return $s->meeting_url ?: self::url($s);
    }

    private static function view(PerusahaanSession $s, bool $isEmployee): string
    {
        return route($isEmployee ? 'perusahaan.employee' : 'consultant.sessions');
    }

    private static function url(PerusahaanSession $s): string
    {
        return route($s->employee ? 'perusahaan.chat' : 'consultant.sessions', $s);
    }
}
