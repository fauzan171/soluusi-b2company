<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Expiry sweep (§17.7): payment waiting + expired → canceled.
        $schedule->call(function () {
            (new \App\Http\Controllers\MidtransController)->updateExpiredPayments();
        })->everyFiveMinutes();

        // F1.4: auto-cancel scheduled B2B sessions tanpa check-in, restore kuota.
        $schedule->command('sessions:cancel-expired')->hourly();

        // Retensi privasi: hapus transkrip chat untuk sesi done ≥24 jam.
        $schedule->command('chat:purge-ended')->daily();

        // ONB.2: file CSV import berisi password awal plaintext. Jalur commit
        // menghapus sendiri; ini jaring pengaman untuk pratinjau yang ditinggal.
        $schedule->command('imports:prune')->hourly();

        // Dual-confirm fallback (Spec §9.1): konsultan done >24h tanpa customer
        // confirm → flag ops review (anti fake sesi). Tidak auto-complete.
        $schedule->call(function () {
            \App\Models\ConsultationSession::whereNotNull('konsultan_done_at')
                ->whereNull('completion_verified_at')
                ->where('konsultan_done_at', '<', now()->subHours(config('klinik.confirm_window_hours', 24)))
                ->update(['needs_ops_review' => true]);
        })->hourly();

        // W2 (MASTER-PLAN K2): sesi scheduled lewat jadwal >24 jam → no_show +
        // ledger forfeited. PROD: `schedule:run` must be enabled (systemd timer
        // / cron — aktivasi didokumentasikan untuk W8 deploy doc).
        $schedule->command('sessions:sweep-no-show')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
