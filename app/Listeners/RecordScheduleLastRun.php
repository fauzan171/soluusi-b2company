<?php

namespace App\Listeners;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Support\Facades\Cache;

/**
 * A6: catat last-run tiap task scheduler ke cache
 * (key `schedule:last-run:{command}`) — dibaca halaman system health.
 *
 * ponytail: key command di-derive saat schedule definition (lihat
 * SystemHealthListener di AppServiceProvider) karena framework tidak
 * mengekspos mapping event→command. Upgrade: pakai $task->name() bila
 * semua event scheduler diberi ->name().
 */
class RecordScheduleLastRun
{
    public function handle(ScheduledTaskFinished $event): void
    {
        $command = \App\Support\ScheduleCommands::commandFor($event->task);

        if ($command !== null) {
            Cache::put(
                "schedule:last-run:{$command}",
                now()->timestamp,
                now()->addDays(7),
            );
        }
    }
}
