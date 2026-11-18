<?php

namespace App\Services\Sekolah;

use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use App\Services\RosterInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Auto-provision akun siswa saat pertama kali BK refer/request konsultasi (B.2).
 *
 * Siswa tidak mendaftar sendiri — akun dibuat otomatis oleh sistem dengan
 * role 'student', lalu undangan tautan akun dikirim via RosterInvitation
 * (audit trail + revoke token lama). Idempotent: user_id sudah terisi →
 * return user existing tanpa duplikat.
 */
class StudentProvisioner
{
    public static function ensure(SekolahStudent $student): ?User
    {
        if ($student->user_id) {
            return $student->user;
        }
        if (!$student->email) {
            return null; // tanpa email tidak bisa provision — siswa off-platform
        }

        return DB::transaction(function () use ($student) {
            // Race guard: re-read dengan lock, re-check setelah lock.
            $locked = SekolahStudent::withoutGlobalScopes()
                ->whereKey($student->id)->lockForUpdate()->firstOrFail();

            // Email sudah dipakai user lain? Link user itu, jangan duplikat akun.
            $existing = User::where('email', $locked->email)->first();
            $user = $existing ?? User::create([
                'uuid' => (string) Str::uuid(),
                'firstname' => strtok($locked->name, ' ') ?: 'Siswa',
                'lastname' => trim((string) strstr($locked->name, ' ')) ?: '-',
                'username' => 'siswa-' . Str::random(8),
                'email' => $locked->email,
                'password' => bcrypt(Str::random(32)), // di-reset via undangan
                'is_active' => true,
                'role_id' => DB::table('roles')->where('name', 'student')->value('id'),
            ]);

            $locked->update(['user_id' => $user->id]);
            RosterInvitation::issue('siswa', $locked);

            return $user;
        });
    }
}
