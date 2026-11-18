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

        // Tanpa row roster tidak ada yang bisa dikonfirmasi — dan halaman
        // konfirmasi sendiri 403 untuk akun tanpa row. Gate diam di sini,
        // jangan mengunci user di redirect yang tidak pernah selesai.
        if ($route && $this->hasRosterRow($user, $route === 'sekolah.profile')) {
            return redirect()->route($route);
        }

        return $next($request);
    }

    private function hasRosterRow($user, bool $sekolah): bool
    {
        $model = $sekolah
            ? \App\Models\Sekolah\SekolahStudent::class
            : \App\Models\Perusahaan\PerusahaanEmployee::class;

        return $model::where('user_id', $user->id)->exists();
    }
}