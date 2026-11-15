<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NotProduction
{
    /**
     * Block route in production (demo-only endpoints, e.g. dummy payment).
     */
    public function handle(Request $request, Closure $next)
    {
        abort_if(app()->environment('production'), 404);

        return $next($request);
    }
}
