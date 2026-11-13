# ADR 001: MAX_SCHEDULES_PER_SESSION Semantics

## Status
Decided

## Context
North Star menyebut "Maksimal 3 jadwal per sesi lewat config MAX_SCHEDULES_PER_SESSION". Ambigu: apakah "3 jadwal" berarti:
- **A**: Total 3 slot terkait sesi (1 awal + 2 reschedule)
- **B**: 3 kali reschedule (1 slot awal + 3 reschedule = 4 total)

## Decision
**Opsi A: Total 3 jadwal per sesi.**

`MAX_SCHEDULES_PER_SESSION = 3` berarti:
- Sesi bisa punya maksimal 3 `scheduled_at` yang berbeda sepanjang hidupnya
- Jadwal awal + 2 reschedule = 3 total
- Reschedule ke-3 (yang membuat total jadi 4) ditolak dengan 422

## Rationale
- Lebih ketat = lebih aman untuk konsultan (mencegah abuse reschedule berulang)
- "3 jadwal" lebih natural dibaca sebagai total, bukan "3 kali ubah"
- Konsultan punya kepastian: maksimal 3 kali lihat slot berbeda

## Implementation
- Track `schedule_history` JSON column di `*_sessions` table: array of `{scheduled_at, changed_at, reason}`
- Atau simpler: `schedule_version` integer, increment tiap reschedule
- Enforcement di `SessionStateMachine::reschedule`: `abort_if($session->schedule_version >= config('session.max_schedules_per_session'), 422, ...)`

## Consequences
- User yang reschedule 2x sudah habis kuota jadwal
- Admin/ops bisa reset manual jika perlu (via tinker/admin UI)
