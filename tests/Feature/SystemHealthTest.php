<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A6: system health — failed_jobs list + retry/delete ter-audit,
 * queue size, schedule last-run dari cache timestamp.
 */
class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    /** Insert failed job langsung via failer (driver database-uuids). */
    private function failJob(string $queue = 'default'): string
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        app('queue.failer')->log(
            'database',
            $queue,
            json_encode(['uuid' => $uuid, 'displayName' => 'App\\Jobs\\Dummy', 'maxTries' => null, 'attempts' => 2]),
            new \RuntimeException('boom: connection refused'),
        );
        return $uuid;
    }

    public function test_halaman_menampilkan_failed_job_dan_queue_size()
    {
        $this->failJob();
        $this->failJob('high');

        // queue size (driver database → hitung tabel jobs)
        \DB::table('jobs')->insert([
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
            'reserved_at' => null, 'available_at' => time(), 'created_at' => time(),
        ]);

        $res = $this->actingAs($this->admin())->get('/admin/system-health');

        $res->assertOk();
        $res->assertSee('App\\Jobs\\Dummy');
        $res->assertSee('boom: connection refused');
        $res->assertSeeText('1'); // queue size: 1 job pending
    }

    public function test_halaman_menampilkan_schedule_last_run()
    {
        Cache::put('schedule:last-run:sessions:cancel-expired', now()->subMinutes(10)->timestamp);

        $res = $this->actingAs($this->admin())->get('/admin/system-health');

        $res->assertOk();
        $res->assertSee('sessions:cancel-expired');
    }

    public function test_schedule_last_run_tanpa_timestamp_menampilkan_never()
    {
        $res = $this->actingAs($this->admin())->get('/admin/system-health');

        $res->assertOk();
        $res->assertSee('Belum pernah');
    }

    public function test_retry_failed_job_requeue_dan_teraudit()
    {
        $uuid = $this->failJob();

        $before = \DB::table('jobs')->count();
        $res = $this->actingAs($this->admin())
            ->post("/admin/system-health/failed-jobs/{$uuid}/retry");

        $res->assertRedirect();
        $this->assertSame(0, app('queue.failer')->count()); // hilang dari failed_jobs
        $this->assertSame($before + 1, \DB::table('jobs')->count()); // balik ke antrean
        // payload menyimpan uuid
        $log = AdminAction::where('action', 'failed_job.retry')->first();
        $this->assertNotNull($log);
        $this->assertSame($uuid, $log->payload_json['uuid']);
    }

    public function test_delete_failed_job_teraudit()
    {
        $uuid = $this->failJob();

        $res = $this->actingAs($this->admin())
            ->post("/admin/system-health/failed-jobs/{$uuid}/delete");

        $res->assertRedirect();
        $this->assertSame(0, app('queue.failer')->count());
        $log = AdminAction::where('action', 'failed_job.delete')->first();
        $this->assertNotNull($log);
        $this->assertSame($uuid, $log->payload_json['uuid']);
    }

    public function test_retry_job_tidak_ditemukan_404()
    {
        $res = $this->actingAs($this->admin())
            ->post('/admin/system-health/failed-jobs/' . \Illuminate\Support\Str::uuid() . '/retry');

        $res->assertNotFound();
    }

    public function test_non_admin_ditolak()
    {
        $role = Role::firstOrCreate(['name' => 'consultant']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]))
            ->get('/admin/system-health')
            ->assertForbidden();
    }
}
