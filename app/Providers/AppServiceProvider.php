<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
