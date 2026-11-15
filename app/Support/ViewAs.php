<?php

namespace App\Support;

use App\Models\Perusahaan\PerusahaanEmployee;

/**
 * F2.2 — Context view-as untuk superadmin. Disimpan di session, satu key.
 * View-as = "lihat portal seolah-olah saya employee X"; read-only.
 */
class ViewAs
{
    private const KEY = 'view_as';

    /** Mulai view-as sebagai employee target. */
    public static function start(PerusahaanEmployee $target): void
    {
        session([self::KEY => [
            'employee_id' => $target->id,
            'perusahaan_id' => $target->perusahaan_id,
            'name' => $target->name,
            'started_at' => now()->toIso8601String(),
        ]]);
    }

    public static function stop(): void
    {
        session()->forget(self::KEY);
    }

    /** @return array{employee_id:int,perusahaan_id:int,name:string,started_at:string}|null */
    public static function current(): ?array
    {
        return session(self::KEY);
    }

    public static function isActive(): bool
    {
        return session()->has(self::KEY);
    }
}
