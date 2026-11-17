<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B4 — View-as read-only, deny-by-default.
 *
 * Saat view-as aktif (superadmin sedang "melihat sebagai" user lain):
 * semua request non-GET di luar prefix `admin/` ditolak 403.
 * Admin tetap boleh kelola lewat prefix `admin/*` — termasuk `view-as.stop`.
 *
 * Dipasang di grup `web` setelah auth. Ini healthcare data — JANGAN longgar.
 */
class ViewAsReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! \App\Support\ViewAs::isActive()) {
            return $next($request);
        }

        if ($request->isMethod('get') || $request->isMethod('head') || $request->isMethod('options')) {
            return $next($request);
        }

        // Prefix admin/* tetap jalan (admin boleh kelola sambil view-as aktif;
        // termasuk whitelist view-as.stop yang hidup di /admin/view-as/stop).
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        abort(403, 'View-as read-only: aksi tulis dinonaktifkan.');
    }
}
