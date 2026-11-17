<?php

namespace App\Http\Middleware;

use App\Models\AdminAction;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * F2.2 — Share view-as context ke view (banner) saat superadmin view-as aktif.
 * Guard role ditangani route (`role:admin`); middleware ini expose context +
 * enforce expiry 30 menit (B5): expired → auto-stop + flash + audit.
 */
class ViewAs
{
    public function handle(Request $request, Closure $next)
    {
        $raw = session(\App\Support\ViewAs::SESSION_KEY);
        if ($raw && !empty($raw['expires_at'])
            && now()->greaterThan(\Illuminate\Support\Carbon::parse($raw['expires_at']))) {
            // B5: view-as kedaluwarsa → auto-stop + audit + flash.
            \App\Support\ViewAs::stop();
            if ($request->user() && ($target = User::find($raw['target_user_id'] ?? null))) {
                AdminAction::record($request->user()->id, 'view_as.stop', $target, [
                    'target_role' => $raw['target_role'] ?? null,
                    'tenant_id' => $raw['tenant_id'] ?? null,
                    'reason' => 'expired',
                ]);
            }
            session()->flash('info', 'Mode view-as expired (30 menit) dan telah dinonaktifkan.');
        } elseif (\App\Support\ViewAs::isActive()) {
            view()->share('viewAs', \App\Support\ViewAs::current());
        }

        return $next($request);
    }
}
