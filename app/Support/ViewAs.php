<?php

namespace App\Support;

use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\Klinik\KlinikAdmin;
use App\Models\User;

/**
 * F2.2 / B1 — Context view-as untuk superadmin. Disimpan di session, satu key.
 * View-as = "lihat portal seolah-olah saya user X"; read-only.
 *
 * Struct session: {target_user_id, target_role, vertical, tenant_id, started_at, expires_at}
 * (+ legacy keys employee_id/perusahaan_id/name untuk banner & PerusahaanContext).
 */
class ViewAs
{
    public const SESSION_KEY = 'view_as';
    private const KEY = self::SESSION_KEY;

    /**
     * TTL view-as session (menit). Setelah lewat, current() anggap expired.
     * ponytail: naikkan ke config saat ada tenant-level SLA.
     */
    public const TTL_MINUTES = 30;

    /** Mulai view-as sebagai user target. Blok target role=admin. */
    public static function start(User $target): void
    {
        $role = $target->role?->name ?? '';
        abort_if($role === 'admin', 422, 'Tidak bisa view-as sesama admin.');

        [$vertical, $tenantId, $employee] = self::resolveIdentity($target, $role);

        $now = now();
        session([self::KEY => [
            'target_user_id' => $target->id,
            'target_role' => $role,
            'vertical' => $vertical,
            'tenant_id' => $tenantId,
            'started_at' => $now->toIso8601String(),
            'expires_at' => $now->copy()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            // legacy keys — dipakai banner + PerusahaanContext + stop audit
            'employee_id' => $employee?->id,
            'perusahaan_id' => $vertical === 'perusahaan' ? $tenantId : null,
            'name' => $employee?->name ?? trim(($target->firstname ?? '') . ' ' . ($target->lastname ?? '')) ?: $target->email,
        ]]);
    }

    public static function stop(): void
    {
        session()->forget(self::KEY);
    }

    /** @return array<string,mixed>|null */
    public static function current(): ?array
    {
        $data = session(self::KEY);
        if (!$data) {
            return null;
        }
        // B1 scaffold: expiry check — session stale di-drop.
        if (!empty($data['expires_at']) && now()->greaterThan(\Illuminate\Support\Carbon::parse($data['expires_at']))) {
            self::stop();
            return null;
        }
        return $data;
    }

    public static function isActive(): bool
    {
        return self::current() !== null;
    }

    /**
     * Resolve (vertical, tenant_id, employee?) dari identitas target user.
     *
     * @return array{0:?string,1:?int,2:?PerusahaanEmployee}
     */
    private static function resolveIdentity(User $target, string $role): array
    {
        switch ($role) {
            case 'employee':
                $emp = PerusahaanEmployee::where('user_id', $target->id)->first();
                return ['perusahaan', $emp?->perusahaan_id, $emp];
            case 'hr_admin':
                $pa = PerusahaanAdmin::where('user_id', $target->id)->first();
                return ['perusahaan', $pa?->perusahaan_id, null];
            case 'student':
                $st = SekolahStudent::withoutGlobalScopes()->where('user_id', $target->id)->first();
                return ['sekolah', $st?->sekolah_id, null];
            case 'bk':
            case 'sekolah_staff':
                $sa = SekolahAdmin::where('user_id', $target->id)->first();
                return ['sekolah', $sa?->sekolah_id, null];
            case 'klinik_admin':
                $ka = KlinikAdmin::where('user_id', $target->id)->first();
                return ['klinik', $ka?->klinik_id, null];
            default:
                // consultant / associate / customer / lainnya — tidak ada tenant.
                return [null, null, null];
        }
    }
}
