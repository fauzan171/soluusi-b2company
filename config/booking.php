<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-cancel Hours (B2B session sweep, F1.4)
    |--------------------------------------------------------------------------
    |
    | Scheduled B2B sessions (perusahaan/sekolah) older than this many hours
    | without check-in are auto-cancelled by `sessions:cancel-expired` and
    | their quota is restored (quota_consumed = false).
    |
    */

    'auto_cancel_hours' => (int) env('SESSION_AUTO_CANCEL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Pending Request Cancel Hours (stale HR review sweep, F1.4)
    |--------------------------------------------------------------------------
    |
    | Pending consultation requests (status scheduled, no slot, no consultant)
    | stuck awaiting HR review longer than this are auto-cancelled by
    | `sessions:cancel-expired`, quota restored, proposals rejected.
    |
    */

    'pending_cancel_hours' => (int) env('SESSION_PENDING_CANCEL_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Max Schedules Per Session (ADR 001, opsi A)
    |--------------------------------------------------------------------------
    |
    | Total jadwal berbeda yang boleh dimiliki satu sesi sepanjang hidupnya:
    | 1 jadwal awal + (max - 1) reschedule. Reschedule yang membuat total
    | melewati batas ditolak 422. Tracked via schedule_version.
    |
    */

    'max_schedules_per_session' => (int) env('MAX_SCHEDULES_PER_SESSION', 3),

];
