<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RosterInvitation
{
    public static function issue(string $type, $row): string
    {
        $token = Str::random(64);
        $expires = now()->addDays(7);
        DB::transaction(function () use ($type, $row, $token, $expires) {
            $row->newQuery()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            DB::table('roster_invitations')->where('type', $type)->where('roster_id', $row->id)
                ->whereNull('consumed_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            DB::table('roster_invitations')->insert([
                'type' => $type,
                'tenant_id' => $type === 'karyawan' ? $row->perusahaan_id : $row->sekolah_id,
                'roster_id' => $row->id,
                'email' => $row->email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expires,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return URL::temporarySignedRoute('selfregister.form', $expires, ['type' => $type, 'token' => $token]);
    }
}
