<?php

namespace App\Services;

use App\Models\Consultant;
use Illuminate\Support\Facades\DB;

/**
 * Isi consultants.notify_email dari B2C (koneksi read-only 'b2c') untuk
 * konsultan hasil sync. Email user B2B konsultan sync = placeholder
 * b2c-{id}@b2b.local — mail ke sana tidak pernah sampai; email real ada di
 * consultants.email B2C.
 *
 * Dijadwalkan routes/console.php setelah consultants:sync-b2c.
 */
class B2cConsultantContact
{
    public static function refresh(): int
    {
        abort_if(! config('database.connections.b2c.database'), 503,
            'Koneksi B2C belum dikonfigurasi (database.connections.b2c).');

        // Ambil email B2C untuk semua konsultan B2B yang punya b2c link.
        $links = Consultant::whereNotNull('b2c_consultant_id')
            ->pluck('b2c_consultant_id');

        if ($links->isEmpty()) {
            return 0;
        }

        $emails = DB::connection('b2c')->table('consultants')
            ->whereIn('id', $links)
            ->whereNull('deleted_at')
            ->pluck('email', 'id');

        $updated = 0;
        foreach ($emails as $b2cId => $email) {
            if ($email && str_ends_with($email, '@b2b.local')) {
                continue; // B2C juga placeholder — skip.
            }
            $updated += Consultant::where('b2c_consultant_id', (int) $b2cId)
                ->where(fn ($q) => $q->whereNull('notify_email')->orWhere('notify_email', '!=', $email))
                ->update(['notify_email' => $email]);
        }

        return $updated;
    }
}
