<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * ONB.4 — gerbang konfirmasi profil login pertama.
 *
 * Dipasang setelah `force.password` supaya urutannya deterministik.
 * Hanya menyalakan untuk role end-user yang dibuat lewat import CSV
 * (`users.must_confirm_profile`), jadi akun lama tidak tersentuh.
 */
class EnsureProfileConfirmed
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->must_confirm_profile) {
            return $next($request);
        }

        if ($request->routeIs('perusahaan.profile*', 'sekolah.profile*', 'logout')) {
            return $next($request);
        }

        $route = match (optional($user->role)->name) {
            'employee' => 'perusahaan.profile',
            'student' => 'sekolah.profile',
            default => null,
        };

        return $route ? redirect()->route($route) : $next($request);
    }
}