<?php

namespace App\Support;

use App\Models\Model;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * T4.3: Legal transitions PerusahaanSession (vertical perusahaan), satu pintu.
 *
 * Precondition dilock di sini supaya tiap call site tidak menyalin guard-nya
 * sendiri-sendiri. Illegal transition → HttpException 422 (konsisten dengan
 * abort(422) yang dipakai controller).
 *
 * ponytail: call site yang BELUM dimigrasi (sengaja, di luar scope T4.3):
 * - BookingService::cancel / ::reschedule (dipakai lintas vertical, risky)
 * - ChatController start/end (in_progress/done jika sesi exists)
 * - Klinik\SessionController::schedulePerusahaan (set schedule_id + scheduled_at)
 * Migrasikan saat menyentuh flow tersebut; tambahkan method start()/complete()/
 * noShow() di sini begitu ada call site pertamanya.
 */
class SessionStateMachine
{
    /**
     * pending review (scheduled + scheduled_at NULL) → consultant assigned.
     * D.2: type-hint diperlebar ke Model — dipakai PerusahaanSession + SekolahSession.
     */
    public static function assign(Model $s, int $consultantId): Model
    {
        self::require(
            $s->status === 'scheduled' && $s->scheduled_at === null,
            'Sesi tidak menunggu assignment konsultan.'
        );

        $s->update(['consultant_id' => $consultantId]);

        return $s;
    }

    /** scheduled (assigned, tanpa jadwal) → scheduled dengan jadwal (booking). */
    public static function schedule(Model $s, Carbon $start, Carbon $end): Model
    {
        self::require(
            $s->status === 'scheduled' && $s->scheduled_at === null && $s->consultant_id !== null,
            'Sesi tidak bisa dijadwalkan dari status saat ini.'
        );

        $s->update([
            'scheduled_at' => $start,
            'ended_at' => $end,
            'schedule_version' => 1,
        ]);
        if ($s instanceof PerusahaanSession) {
            \App\Services\SessionNotify::scheduled($s->withoutRelations());
        }

        return $s;
    }

    /**
     * ADR 001 (opsi A): reschedule = pindah ke slot baru, max
     * config('booking.max_schedules_per_session') jadwal total per sesi.
     * Jadwal awal = version 1 → reschedule ke-2 ditolak saat max=3.
     */
    public static function reschedule(PerusahaanSession $s, Carbon $start, Carbon $end): PerusahaanSession
    {
        self::require(
            $s->status === 'scheduled' && $s->scheduled_at !== null,
            'Hanya sesi terjadwal yang bisa diubah jadwalnya.'
        );

        $max = (int) config('booking.max_schedules_per_session', 3);
        self::require(
            $s->schedule_version < $max,
            "Maksimal {$max} jadwal per sesi tercapai. Batalkan sesi dan buat yang baru."
        );

        $s->update([
            'scheduled_at' => $start,
            'ended_at' => $end,
            'schedule_version' => $s->schedule_version + 1,
        ]);
        \App\Services\SessionNotify::rescheduled($s->withoutRelations());

        return $s;
    }

    /**
     * scheduled → cancelled. Forbidden dari done/no_show/cancelled.
     * $restoreQuota=false dipakai untuk late-cancel (kuota tetap terpakai).
     * Typed Model (bukan PerusahaanSession): sweep expiry memakai class ini untuk
     * SekolahSession juga — shape kolomnya sama.
     */
    public static function cancel(Model $s, string $reason, bool $restoreQuota = true): Model
    {
        self::require($s->status === 'scheduled', 'Hanya sesi terjadwal yang bisa dibatalkan.');

        $s->update([
            'status' => 'cancelled',
            'quota_consumed' => !$restoreQuota,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);
        if ($s instanceof PerusahaanSession) {
            \App\Services\SessionNotify::cancelled($s->withoutRelations());
        }

        return $s;
    }

    private static function require(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new HttpException(422, $message);
        }
    }
}
