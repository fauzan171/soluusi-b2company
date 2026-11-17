<?php

namespace App\Support;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\User;

/**
 * Resolve perusahaan aktif untuk portal Perusahaan (CAP). Mirror KlinikContext:
 * - hr_admin: perusahaan dari tabel perusahaan_admins.
 * - admin (super admin): pilih via ?perusahaan_id= — akses penuh semua perusahaan.
 */
class PerusahaanContext
{
    public static function current(): ?Perusahaan
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // F2.2: view-as superadmin — scope ke tenant target, abaikan admin_perusahaan_id.
        if ($viewAs = ViewAs::current()) {
            return Perusahaan::find($viewAs['perusahaan_id']);
        }

        if (self::canSwitch($user)) {
            if (request()->filled('perusahaan_id')) {
                $p = Perusahaan::find(request('perusahaan_id'));
                if ($p) {
                    session(['admin_perusahaan_id' => $p->id]);
                }
                return $p;
            }
            return Perusahaan::find(session('admin_perusahaan_id'))
                ?: Perusahaan::where('is_active', true)->orderBy('id')->first();
        }

        $memberships = PerusahaanAdmin::where('user_id', $user->id)->get();
        abort_if($memberships->count() > 1, 403, 'Membership ambigu. Hubungi ops.');
        $tenant = $memberships->first()?->perusahaan;
        abort_if($tenant && !$tenant->is_active && !request()->routeIs('perusahaan.dashboard'), 403, 'Program belum aktif. Hubungi ops.');
        abort_if($tenant && $tenant->status === 'suspended', 403, 'Tenant disuspend. Hubungi ops Soluusi.');
        return $tenant;
    }

    /** True bila user boleh milih perusahaan mana saja (super admin only). */
    public static function canSwitch(User $user): bool
    {
        return optional($user->role)->name === 'admin';
    }
}
