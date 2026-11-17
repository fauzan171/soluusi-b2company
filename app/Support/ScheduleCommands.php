<?php

namespace App\Support;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;

/**
 * A6: mapping task scheduler → nama command untuk cache last-run.
 * Komando event: `php artisan sessions:cancel-expired` (setelah
 * normalizeCommand). Callback event: pakai sha1 mutex (command string
 * tidak tersedia).
 */
class ScheduleCommands
{
    /** Daftar command yang di-track di system health. */
    public static function tracked(): array
    {
        return ['sessions:cancel-expired', 'chat:purge-ended'];
    }

    /**
     * Nama command untuk sebuah scheduled event, null bila tidak
     * representable (callback closure).
     */
    public static function commandFor(Event $event): ?string
    {
        if ($event instanceof CallbackEvent) {
            return 'callback:' . sha1($event->mutexName());
        }

        $command = Event::normalizeCommand($event->command ?? '');

        if (preg_match("/^'php'\s+'artisan'\s+(.+)$/u", $command, $m)) {
            return preg_replace("#['\"]#", '', $m[1]);
        }

        return null;
    }
}
