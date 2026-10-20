<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Klinik belum di-approve ops → redirect ke dashboard (pending view). */
class KlinikActive
{
    public function handle(Request $request, Closure $next)
    {
        $klinik = \App\Support\KlinikContext::current();
        if (!$klinik || !$klinik->is_active) {
            return redirect()->route('klinik.dashboard');
        }
        return $next($request);
    }
}
