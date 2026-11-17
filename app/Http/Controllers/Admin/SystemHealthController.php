<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Support\ScheduleCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * A6: system health — failed jobs + queue size + schedule last-run.
 * Retry/failed job: delegate ke queue:retry / queue:forget via failer
 * (driver database-uuids), semuanya ter-audit AdminAction.
 */
class SystemHealthController extends Controller
{
    public function index()
    {
        $failedJobs = collect(app('queue.failer')->all())->map(function ($job) {
            $payload = json_decode($job->payload, true) ?: [];

            return (object) [
                'id' => $job->id,
                'uuid' => $job->id,
                'queue' => $job->queue,
                'displayName' => $payload['displayName'] ?? 'unknown',
                'failed_at' => $job->failed_at,
                'exception' => mb_substr((string) $job->exception, 0, 300),
            ];
        });

        $queueSize = app('queue')->size();

        $schedule = [];
        foreach (ScheduleCommands::tracked() as $command) {
            $ts = Cache::get("schedule:last-run:{$command}");
            $schedule[$command] = $ts ? now()->setTimestamp((int) $ts) : null;
        }

        return view('admin.system-health', [
            'failedJobs' => $failedJobs,
            'queueSize' => $queueSize,
            'schedule' => $schedule,
        ]);
    }

    public function retry(Request $request, string $uuid)
    {
        $job = app('queue.failer')->find($uuid);
        abort_if($job === null, 404, 'Failed job tidak ditemukan.');

        $exit = \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$uuid]]);
        abort_if($exit !== 0, 500, 'queue:retry gagal.');

        AdminAction::record(
            auth()->id(),
            'failed_job.retry',
            $this->auditTarget(),
            ['uuid' => $uuid, 'queue' => $job->queue],
        );

        return back()->with('success', 'Failed job dikembalikan ke antrean.');
    }

    public function delete(Request $request, string $uuid)
    {
        $job = app('queue.failer')->find($uuid);
        abort_if($job === null, 404, 'Failed job tidak ditemukan.');

        app('queue.failer')->forget($uuid);

        AdminAction::record(
            auth()->id(),
            'failed_job.delete',
            $this->auditTarget(),
            ['uuid' => $uuid, 'queue' => $job->queue],
        );

        return back()->with('success', 'Failed job dihapus.');
    }

    /** failed job bukan Eloquent model — target audit minimal. */
    private function auditTarget()
    {
        return new class
        {
            public function getMorphClass()
            {
                return 'failed_job';
            }

            public function getKey()
            {
                return 0;
            }
        };
    }
}
