<?php

namespace App\Services;

use App\Models\Perusahaan\Room;
use Illuminate\Support\Carbon;

/**
 * W5: assign ruang fisik 1-on-1 (M5/K9). Anti-konflik half-open overlap vs
 * sesi scheduled/done lain di ruang yang sama (K5-style check di dalam
 * transaksi BookingService::book).
 */
class RoomService
{
    public const TYPES = ['online_meeting', 'chat', 'one_on_one'];

    /**
     * Cek apakah ruang bebas di window [start, end) — half-open overlap.
     */
    public static function roomConflicts(int $roomId, Carbon $start, Carbon $end, ?int $exceptSessionId = null): bool
    {
        return \App\Models\Perusahaan\PerusahaanSession::query()
            ->where('room_id', $roomId)
            ->whereIn('status', ['scheduled', 'done'])
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->where('scheduled_at', '<', $end)
            ->where(fn ($q) => $q->where('ended_at', '>', $start)
                ->orWhere(fn ($q2) => $q2->whereNull('ended_at')
                    // ponytail: ended_at NULL dianggap durasi 1 jam — konsisten dengan BookingService::blocked.
                    ->where('scheduled_at', '>', $start->copy()->subHour())))
            ->exists();
    }

    /**
     * Auto-pilih room aktif yang bebas di jam itu. Null = tidak ada room bebas
     * (fallback manual HR via route rooms + meeting-link).
     */
    public static function pickFreeRoom(int $perusahaanId, Carbon $start, Carbon $end, ?int $exceptSessionId = null): ?Room
    {
        return Room::where('perusahaan_id', $perusahaanId)
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->first(fn (Room $room) => !self::roomConflicts($room->id, $start, $end, $exceptSessionId));
    }
}
