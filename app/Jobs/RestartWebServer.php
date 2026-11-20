<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ponytail: one-shot ops job — restart the soluusi-b2b systemd user unit.
 * Runs under hermes' queue worker, which CAN talk to its own systemd user bus
 * (unlike the t3 session). Dispatched manually by inserting into the jobs table.
 * Delete after use.
 */
class RestartWebServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $cmds = [
            'systemctl --user restart soluusi-b2b.service',
            'systemctl --user reset-failed soluusi-b2b.service',
            'XDG_RUNTIME_DIR=/run/user/$(id -u) systemctl --user restart soluusi-b2b.service',
        ];
        foreach ($cmds as $c) {
            exec($c.' 2>&1', $out, $rc);
            file_put_contents('/tmp/soluusi-restart.log', "$c => rc=$rc\n".implode("\n", $out)."\n", FILE_APPEND);
            if ($rc === 0) {
                break;
            }
        }
    }
}
