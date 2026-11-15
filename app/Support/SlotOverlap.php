<?php

namespace App\Support;

use App\Models\ConsultationSession;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Support\Carbon;

/**
 * Overlap check untuk sesi konsultan: sesi perusahaan (scheduled/done) dan sesi B2C blocking.
 * Dipakai oleh SlotProposalController (propose) dan EmployeeController (confirm)
 * untuk mencegah double-book race.
 */
class SlotOverlap
{
    /**
     * ponytail: service container ceremony tidak diperlukan — static helper cukup.
     * $ignoreScheduleId untuk reschedule (abaikan slot existing yang diganti).
     */
    public static function exists(int $consultantId, Carbon $start, Carbon $end, ?int $ignoreScheduleId = null): bool
    {
        $perusahaanHit = PerusahaanSession::where('consultant_id', $consultantId)
            ->whereIn('status', ['scheduled', 'done'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $end)
            ->when($ignoreScheduleId, fn ($q) => $q->where('schedule_id', '!=', $ignoreScheduleId))
            ->where(fn ($q) => $q->where('ended_at', '>', $start)
                // ponytail: ended_at NULL dianggap durasi 1 jam (pattern BookingService::blocked)
                ->orWhere(fn ($q2) => $q2->whereNull('ended_at')
                    ->where('scheduled_at', '>', $start->copy()->subHour())))
            ->exists();

        if ($perusahaanHit) {
            return true;
        }

        return ConsultationSession::where('consultant_id', $consultantId)
            ->blocking()
            ->whereHas('schedule', fn ($q) => $q
                ->when($ignoreScheduleId, fn ($q2) => $q2->where('id', '!=', $ignoreScheduleId))
                ->where('started_at', '<', $end)->where('ended_at', '>', $start))
            ->exists();
    }
}
