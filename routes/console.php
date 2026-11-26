<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// wv4: scheduler entries (feature D + renewal reminder).
// Email campaign terjadwal yang due → kirim + tandai sent_at.
// QA7-F6: withoutOverlapping — dua invoker (schedule:work daemon + timer)
// tidak boleh double-fire event yang sama.
Schedule::command('wellbeing:send-campaigns')->daily()->withoutOverlapping();
// Kontrak ends_at < 30 hari → email HR admin (renewal reminder).
Schedule::command('wellbeing:renewal-notice')->daily()->withoutOverlapping();
// G2: tarik konsultan B2C (read-only) tiap malam — sumber identitas konsultan.
Schedule::command('consultants:sync-b2c')->dailyAt('02:00')->withoutOverlapping();
// Email notifikasi real konsultan (B2C) — refresh right after nightly sync.
Schedule::command('consultants:refresh-notify-emails')->dailyAt('02:10')->withoutOverlapping();
// B2B-notify: reminder sesi H-1 hari & H-1 jam (idempotent per flag sesi).
Schedule::command('sessions:send-reminders')->hourly()->withoutOverlapping();
