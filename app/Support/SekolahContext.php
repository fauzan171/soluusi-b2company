<?php

namespace App\Support;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\User;

/**
 * Resolve sekolah aktif untuk portal Sekolah. Mirror PerusahaanContext:
 * - sekolah_admin/bk: sekolah dari tabel sekolah_admins.
 * - admin (super admin): pilih via ?sekolah_id= — akses penuh semua sekolah.
 */
class SekolahContext
{
    public static function current(): ?Sekolah
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // B2: view-as superadmin — scope ke tenant target, abaikan admin_sekolah_id.
        if ($viewAs = ViewAs::current()) {
            return $viewAs['vertical'] === 'sekolah' ? Sekolah::find($viewAs['tenant_id']) : null;
        }

        if (self::canSwitch($user)) {
            if (request()->filled('sekolah_id')) {
                $s = Sekolah::find(request('sekolah_id'));
                if ($s) {
                    session(['admin_sekolah_id' => $s->id]);
                }
                return $s;
            }
            return Sekolah::find(session('admin_sekolah_id'))
                ?: Sekolah::where('is_active', true)->orderBy('id')->first();
        }

        $memberships = SekolahAdmin::where('user_id', $user->id)->get();
        abort_if($memberships->count() > 1, 403, 'Membership ambigu. Hubungi ops.');
        $tenant = $memberships->first()?->sekolah;
        abort_if($tenant && !$tenant->is_active && !request()->routeIs('sekolah.dashboard'), 403, 'Program belum aktif. Hubungi ops.');
        abort_if($tenant && $tenant->status === 'suspended', 403, 'Tenant disuspend. Hubungi ops Soluusi.');
        return $tenant;
    }

    /** True bila user boleh milih sekolah mana saja (super admin only). */
    public static function canSwitch(User $user): bool
    {
        return optional($user->role)->name === 'admin';
    }
}
