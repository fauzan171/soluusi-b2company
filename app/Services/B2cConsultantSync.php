<?php

namespace App\Services;

use App\Models\Consultant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tarik konsultan REAL dari DB B2C (jerichoad/soluusi, koneksi 'b2c' read-only)
 * ke DB B2B. Kunci: consultants.b2c_consultant_id (unique).
 *
 * Sumber kebenaran data identitas konsultan = B2C. B2B hanya nyimpan copy
 * (id link + user login lokal). Nama/email/kategori di-update saat sync,
 * TIDAK pernah di-edit manual di B2B.
 *
 * Sinkronisasi konsultan B2C yang status='accept' AND is_active=1 saja —
 * konsultan pending/reject/interview di B2C bukan kapasitas B2B.
 */
class B2cConsultantSync
{
    /**
     * @return array{created:int, updated:int, deactivated:int}
     */
    public static function sync(): array
    {
        abort_if(! config('database.connections.b2c.database'), 503,
            'Koneksi B2C belum dikonfigurasi (database.connections.b2c).');

        $count = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
        ];
        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');

        // Snapshot konsultan aktif B2C + kategorinya (pivot di sisi B2C).
        $rows = DB::connection('b2c')->table('consultants as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('consultant_categories as cc', 'cc.consultant_id', '=', 'c.id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'cc.category_id')
            ->where('c.status', 'accept')
            ->where('c.is_active', 1)
            ->whereNull('c.deleted_at')
            ->select('c.id as id', 'c.uuid as uuid', 'c.name as name', 'c.slug as slug',
                'c.email as email', 'c.about as about', 'c.profit_sharing as profit_sharing',
                'c.user_id as user_id', 'c.category as category', 'c.specialty as specialty',
                'cat.name as category_name', 'cat.code_category')
            ->get()
            ->groupBy('id') // pivot multi-kategori → 1 consultant (alias, driver-proof)
            ->map(fn ($g) => tap($g->first(), fn ($r) => $r->categories = $g->pluck('code_category')->filter()->values()->all()));

        foreach ($rows as $b2c) {
            $b2c->id = (int) $b2c->id; // PDO sqlite dapat string — pastikan int.
            $local = Consultant::where('b2c_consultant_id', $b2c->id)->first();

            // User login B2B: satu per konsultan B2C, key b2c_consultant_id di email
            // menghindari duplikat bila re-run. Password B2B di-hash random — login
            // konsultan via SSO dari dashboard B2C, bukan password lokal.
            // Email lokal deterministik dari id B2C: konsultan B2B login via
            // SSO dari dashboard B2C, bukan password lokal (password random).
            $localEmail = 'b2c-'.$b2c->id.'@b2b.local';
            $user = \App\Models\User::where('email', $localEmail)->first();
            if (! $user) {
                $user = \App\Models\User::create([
                    'uuid' => (string) Str::uuid(),
                    'username' => 'b2c-'.$b2c->id,
                    'email' => $localEmail,
                    'password' => Str::random(32), // never used for login (SSO); random per row
                    'firstname' => $b2c->name,
                    'lastname' => '',
                    'role_id' => $consultantRoleId,
                    'is_active' => 1,
                ]);
            } else {
                $user->update(['firstname' => $b2c->name, 'is_active' => 1]);
            }

            // NB: tabel consultants B2B tidak punya kolom name/email —
            // identitas konsultan (nama tampil) diambil dari users.firstname.
            $attrs = [
                'user_id' => $user->id,
                'about' => $b2c->about ?: '',
                'uuid' => $b2c->uuid,
                'profit_sharing' => (int) $b2c->profit_sharing,
                'status' => Consultant::ACCEPT,
                'is_active' => 1,
            ];

            if ($local) {
                $local->update($attrs);
                $count['updated']++;
            } else {
                Consultant::create($attrs + ['b2c_consultant_id' => $b2c->id]);
                $count['created']++;
            }
        }

        // Deactivate: konsultan B2C yang berhenti (bukan accept/active lagi).
        $activeB2cIds = $rows->keys()->map(fn ($k) => (int) $k)->all();
        $count['deactivated'] = Consultant::whereNotNull('b2c_consultant_id')
            ->whereNotIn('b2c_consultant_id', $activeB2cIds)
            ->where('is_active', 1)
            ->get()
            ->each(function (Consultant $c) {
                $c->update(['is_active' => 0]);
                \App\Models\User::where('id', $c->user_id)->update(['is_active' => 0]);
            })
            ->count();

        return $count;
    }
}
