<?php

namespace App\Support;

use App\Models\Klinik\Klinik;
use App\Models\User;

/**
 * Resolve klinik aktif untuk halaman portal klinik.
 * - klinik_admin/staff: klinik dari tabel klinik_admins.
 * - associate: klinik pertamanya (milik sendiri).
 * - admin (super admin): pilih klinik via ?klinik_id= — bisa buka semua klinik
 *   ("super admin kayak yang ada di lusi admin" — akses penuh). Default: klinik pertama.
 */
class KlinikContext
{
    public static function current(): ?Klinik
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if (self::canSwitch($user)) {
            // Super admin: ?klinik_id= memilih klinik + simpan di session biar
            // persist antar klik tanpa perlu ubah semua link di view.
            if (request()->filled('klinik_id')) {
                $klinik = Klinik::find(request('klinik_id'));
                if ($klinik) {
                    session(['admin_klinik_id' => $klinik->id]);
                }
                return $klinik;
            }
            return Klinik::find(session('admin_klinik_id'))
                ?: Klinik::where('is_active', true)->orderBy('id')->first();
        }

        return $user->klinik ?: Klinik::where('associate_id', $user->id)->first();
    }

    /** True bila user boleh milih klinik mana saja (super admin only). */
    public static function canSwitch(User $user): bool
    {
        return optional($user->role)->name === 'admin';
    }
}
