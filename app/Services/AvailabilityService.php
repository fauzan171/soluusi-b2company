<?php

namespace App\Services;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Schedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Availability compute-on-read (mission M3 / K4, research-B Cal.com pattern).
 * Slot = template jam kerja mingguan per assignment (consultant_work_hours)
 * minus exception ketersediaan global per konsultan
 * minus booked (perusahaan_sessions + schedules yang sudah diklaim).
 * TANPA materialisasi slot rows.
 *
 * Semua waktu dalam timezone aplikasi Asia/Jakarta (konvensi repo, WIB =
 * UTC+7 fixed tanpa DST — research-B Q3), wall-clock arithmetic stabil.
 */
class AvailabilityService
{
    public const TZ = 'Asia/Jakarta';

    /** Slot yang tersedia untuk konsultan di perusahaan, range [from, to). */
    public static function slotsFor(Consultant $consultant, Perusahaan $perusahaan, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $slots = self::templateSlots($consultant, $perusahaan, $from, $to);

        $exceptions = $consultant->availabilityExceptions()
            ->whereBetween('date', [
                $from->copy()->timezone(self::TZ)->toDateString(),
                $to->copy()->timezone(self::TZ)->toDateString(),
            ])->get();

        $booked = self::bookedRanges($consultant, $from, $to);

        return $slots
            // Window: slot mulai di [from, to) dan tidak di masa lalu.
            ->filter(fn ($s) => $s->start >= $from && $s->start < $to && $s->start->isFuture())
            // Exception: full-day (NULL times) atau partial overlap (half-open).
            ->reject(fn ($s) => $exceptions->contains(fn ($e) => self::exceptionBlocks($e, $s)))
            // Booked: overlap half-open vs session terjadwal/done atau slot schedule terklaim.
            ->reject(fn ($s) => $booked->contains(fn ($r) => self::overlaps($r['start'], $r['end'], $s->start, $s->end)))
            ->values();
    }

    /**
     * Overlap half-open [start, end): adjacent 10-11 vs 11-12 tidak overlap.
     * Dipakai juga oleh booking path (W5/W6) di dalam DB::transaction (K5).
     */
    public static function overlaps(CarbonInterface $aStart, CarbonInterface $aEnd, CarbonInterface $bStart, CarbonInterface $bEnd): bool
    {
        return $aStart < $bEnd && $aEnd > $bStart;
    }

    /** Ekspansi template mingguan → slot instants (app tz). */
    private static function templateSlots(Consultant $consultant, Perusahaan $perusahaan, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $pools = ConsultantPool::where('consultant_id', $consultant->id)
            ->where('perusahaan_id', $perusahaan->id)
            ->where('status', 'active')
            ->with('workHours')
            ->get();

        $templates = $pools->flatMap(fn ($p) => $p->workHours);
        if ($templates->isEmpty()) {
            return collect();
        }

        $slots = collect();
        $fromLocal = $from->copy()->timezone(self::TZ)->startOfDay();
        $toLocal = $to->copy()->timezone(self::TZ)->startOfDay();

        for ($day = $fromLocal->copy(); $day <= $toLocal; $day->addDay()) {
            foreach ($templates as $tpl) {
                if ((int) $tpl->weekday !== (int) $day->dayOfWeek) {
                    continue;
                }
                $minutes = max(15, (int) ($tpl->slot_minutes ?: 60));
                $start = $day->copy()->setTimeFromTimeString($tpl->start_time);
                $end = $day->copy()->setTimeFromTimeString($tpl->end_time);
                for ($t = $start->copy(); $t->copy()->addMinutes($minutes) <= $end; $t->addMinutes($minutes)) {
                    $slots->push((object) [
                        'start' => $t->copy(),
                        'end' => $t->copy()->addMinutes($minutes),
                        'minutes' => $minutes,
                        'consultation_types' => $tpl->consultation_types,
                    ]);
                }
            }
        }

        return $slots->sortBy(fn ($s) => $s->start->getTimestamp())->values();
    }

    /** Range terpakai konsultan: sesi perusahaan (scheduled/done) + slot schedule terklaim. */
    private static function bookedRanges(Consultant $consultant, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $pad = 36 * 3600; // window guard untuk session tanpa ended_at
        $lo = $from->copy()->subSeconds($pad);
        $hi = $to->copy()->addSeconds($pad);

        $sessions = PerusahaanSession::where('consultant_id', $consultant->id)
            ->whereIn('status', ['scheduled', 'done'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', $lo)
            ->where('scheduled_at', '<', $hi)
            ->get();

        $booked = $sessions->map(fn ($s) => [
            'start' => Carbon::parse($s->scheduled_at),
            // ponytail: ended_at NULL dianggap durasi 1 jam (pola BookingService); upgrade: kolom durasi eksplisit.
            'end' => $s->ended_at
                ? Carbon::parse($s->ended_at)
                : Carbon::parse($s->scheduled_at)->addHour(),
        ]);

        // Slot schedule existing yang sudah diklaim (booking_claims / checkout live).
        $scheduleIds = Schedule::where('consultant_id', $consultant->id)
            ->whereIn('status', [Schedule::STATUS_CONFIRMED])
            ->whereBetween('started_at', [$lo, $hi])
            ->pluck('id');
        $claimed = \Illuminate\Support\Facades\DB::table('booking_claims')
            ->whereIn('schedule_id', $scheduleIds)
            ->pluck('schedule_id');
        Schedule::whereIn('id', $claimed)->get()
            ->each(fn ($s) => $booked->push(['start' => Carbon::parse($s->started_at), 'end' => Carbon::parse($s->ended_at)]));

        return $booked;
    }

    /** Exception memblok slot: full-day (NULL) atau partial overlap half-open dalam wall-clock WIB. */
    private static function exceptionBlocks($exception, object $slot): bool
    {
        $date = Carbon::parse($exception->date)->timezone(self::TZ)->toDateString();
        $slotDate = $slot->start->copy()->timezone(self::TZ)->toDateString();
        if ($date !== $slotDate) {
            return false;
        }
        if ($exception->start_time === null || $exception->end_time === null) {
            return true; // full-day block
        }
        $exStart = Carbon::parse($date.' '.$exception->start_time, self::TZ);
        $exEnd = Carbon::parse($date.' '.$exception->end_time, self::TZ);

        return self::overlaps($exStart, $exEnd, $slot->start, $slot->end);
    }
}
