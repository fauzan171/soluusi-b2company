<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    protected function programSlot(string $vertical, int $tenantId, string $type = 'psikolog')
    {
        $consultant = \App\Models\Consultant::factory()->create(['status' => 'accept', 'is_active' => true]);
        $name = ['karir' => 'Konselor Karir', 'nutrisionis' => 'Gizi', 'fisioterapis' => 'Fisioterapi'][$type] ?? 'Psikolog';
        $categoryId = \Illuminate\Support\Facades\DB::table('categories')->insertGetId(['name' => $name, 'code_category' => \Illuminate\Support\Str::random(12), 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\DB::table('consultant_categories')->insert(['consultant_id' => $consultant->id, 'category_id' => $categoryId, 'created_at' => now(), 'updated_at' => now()]);
        \App\Models\ConsultantPool::create([$vertical.'_id' => $tenantId, 'consultant_id' => $consultant->id]);
        return \App\Models\Schedule::factory()->create(['consultant_id' => $consultant->id, $vertical.'_id' => $tenantId, 'klinik_id' => null, 'started_at' => now()->addDays(3), 'ended_at' => now()->addDays(3)->addHour(), 'status' => 'confirmed']);
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        // /dev/null exists on macOS; Laravel treats any existing path as cached config.
        // Per-pid dir + touch .env: phpdotenv warns when the path exists but file doesn't.
        $noEnvDir = sys_get_temp_dir().'/soluusi-tests-no-env-'.getmypid();
        is_dir($noEnvDir) || @mkdir($noEnvDir, 0700, true);
        @touch($noEnvDir.'/.env');
        $app->useEnvironmentPath($noEnvDir);
        $_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = sys_get_temp_dir().'/soluusi-no-config-'.bin2hex(random_bytes(8));
        putenv('APP_CONFIG_CACHE='.$_ENV['APP_CONFIG_CACHE']);

        $app->make(Kernel::class)->bootstrap();

        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || config('database.connections.sqlite.url')) {
            throw new \RuntimeException('Tests require isolated SQLite :memory: with DATABASE_URL blank.');
        }
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
            'mail.default' => 'array',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'midtrans.server_key' => null,
            'services.google_calendar.service_account_json' => null,
            'services.google_calendar.refresh_token' => null,
        ]);
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $calendar = \Mockery::mock(\App\Services\GoogleCalendarService::class);
        $calendar->shouldReceive('createEvent')->andReturn(null);
        $app->instance(\App\Services\GoogleCalendarService::class, $calendar);

        return $app;
    }
}
