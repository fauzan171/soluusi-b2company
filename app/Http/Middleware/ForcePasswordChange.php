<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * ONB.3 — gerbang wajib ganti password.
 *
 * Dipasang manual per-grup portal (bukan di grup `web` global, bukan di
 * grup `admin`): superadmin dan halaman publik harus tetap jalan.
 *
 * Guest dilewatkan — redirect login adalah tugas middleware `auth`.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('password.change*', 'logout')) {
            return $next($request);
        }

        return redirect()->route('password.change');
    }
}