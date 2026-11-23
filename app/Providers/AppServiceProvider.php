<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Date;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\App\Contracts\AIService::class, function () {
            $provider = (string) config('ai.provider', 'null');
            $map = (array) config('ai.providers', []);
            $class = $map[$provider] ?? \App\Services\AI\NullAIService::class;
            return new $class();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // A1: morph alias pendek utk assessment_assignments.assignable (D2).
        // TANPA enforce — AdminAction::record target lama tetap simpan FQCN.
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'program' => \App\Models\CompanyWellbeingProgram::class,
            'perusahaan' => \App\Models\Perusahaan\Perusahaan::class,
            'sekolah' => \App\Models\Sekolah\Sekolah::class,
        ]);

        // Locale tanggal Indonesia global (F5): hari/bulan via translatedFormat jadi id.
        Date::setLocale('id');

        // A6: catat last-run task scheduler untuk halaman system health.
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Console\Events\ScheduledTaskFinished::class,
            \App\Listeners\RecordScheduleLastRun::class,
        );

        // White-label $brand untuk semua email klinik (PRD §15.4). Resolve dari
        // session kalau ada (per-klinik), fallback internal/default config.
        \Illuminate\Support\Facades\View::composer('emails.klinik.*', function ($view) {
            $session = $view->offsetExists('session') ? $view->offsetGet('session')
                : ($view->offsetExists('consultationSession') ? $view->offsetGet('consultationSession') : null);
            $brand = $session
                ? \App\Support\Brand::for($session)
                : config('whitelabel.internal_brand', config('whitelabel.default_brand'));
            $view->with('brand', $brand);
        });
    }
}
