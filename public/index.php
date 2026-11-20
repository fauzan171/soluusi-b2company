<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// ponytail: php -S workers on cpanel VPS keep a pre-`composer dump-autoload`
// classmap in memory — after any dump, stale workers 500 with empty body until
// restarted. Self-heal: a worker whose process started before the classmap's
// mtime exits; systemd user unit (Restart=on-failure) respawns it fresh.
// Remove when the app moves to php-fpm.
$classmapMtime = @filemtime(__DIR__.'/../vendor/composer/autoload_classmap.php');
if ($classmapMtime !== false && PHP_SAPI === 'cli-server') {
    $stat = explode(' ', trim((string) file_get_contents('/proc/self/stat')));
    $startedAt = (int) $stat[21] / 100; // starttime field (ticks → seconds since boot)
    $bootTime = (int) trim((string) file_get_contents('/proc/uptime'));
    $processBornUnix = time() - ($bootTime - $startedAt);
    if ($processBornUnix > 0 && $processBornUnix < $classmapMtime) {
        @file_put_contents('/tmp/soluusi-guard-debug.log', sprintf("worker born=%d mtime=%d — exiting\n", $processBornUnix, $classmapMtime), FILE_APPEND);
        exit(1); // trigger systemd respawn
    }
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
