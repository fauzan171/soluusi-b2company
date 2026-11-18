<?php

namespace App\Services\Perusahaan;

use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use App\Services\RosterInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Auto-provision akun karyawan saat HR menambah roster (parity D.1).
 *
 * Mirror Sekolah\StudentProvisioner: akun dibuat dengan role 'employee' lalu
 * undangan tautan akun diterbitkan (audit trail + revoke token lama).
 * Idempotent: user_id sudah terisi → return user existing tanpa duplikat.
 */
class EmployeeProvisioner
{
    public static function ensure(PerusahaanEmployee $employee): ?User
    {
        if ($employee->user_id) {
            return $employee->user;
        }
        if (!$employee->email) {
            return null; // tanpa email tidak bisa provision
        }

        return DB::transaction(function () use ($employee) {
            // Race guard: re-read dengan lock, re-check setelah lock.
            $locked = PerusahaanEmployee::withoutGlobalScopes()
                ->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if ($locked->user_id) {
                return $locked->user;
            }

            // Email sudah dipakai user lain? Link user itu, jangan duplikat akun —
            // kecuali user itu bukan karyawan (HR/konsultan/admin) atau sudah
            // jadi karyawan tenant lain (membership ambigu).
            $existing = User::where('email', $locked->email)->first();
            if ($existing && ($existing->role?->name !== 'employee'
                || PerusahaanEmployee::withoutGlobalScopes()
                    ->where('user_id', $existing->id)
                    ->where('perusahaan_id', '!=', $locked->perusahaan_id)->exists())) {
                return null;
            }
            $user = $existing ?? User::create([
                'uuid' => (string) Str::uuid(),
                'firstname' => strtok($locked->name, ' ') ?: 'Karyawan',
                'lastname' => trim((string) strstr($locked->name, ' ')) ?: '-',
                'username' => 'karyawan-' . Str::random(8),
                'email' => $locked->email,
                'password' => bcrypt(Str::random(32)), // di-reset via undangan
                'is_active' => true,
                'role_id' => DB::table('roles')->where('name', 'employee')->value('id'),
            ]);

            $locked->update(['user_id' => $user->id]);
            RosterInvitation::issue('karyawan', $locked);

            return $user;
        });
    }
}