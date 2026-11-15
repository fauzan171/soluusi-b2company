<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * F2.2 — Share view-as context ke view (banner) saat superadmin view-as aktif.
 * Guard role ditangani route (`role:admin`); middleware ini hanya expose context.
 */
class ViewAs
{
    public function handle(Request $request, Closure $next)
    {
        if (\App\Support\ViewAs::isActive()) {
            view()->share('viewAs', \App\Support\ViewAs::current());
        }

        return $next($request);
    }
}
