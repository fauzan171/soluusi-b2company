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
Schedule::command('wellbeing:send-campaigns')->daily();
// Kontrak ends_at < 30 hari → email HR admin (renewal reminder).
Schedule::command('wellbeing:renewal-notice')->daily();
