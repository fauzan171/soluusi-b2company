<?php

namespace App\Policies;

use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;

class SekolahSessionPolicy
{
    /** BK/staff tenant yang sama, siswa pemilik sesi, atau konsultan yang di-assign. */
    public function view(User $user, SekolahSession $session): bool
    {
        return $this->isTenantAdmin($user, $session)
            || $this->isOwnerStudent($user, $session)
            || $this->isAssignedConsultant($user, $session);
    }

    /** BK/staff tenant + konsultan assigned. */
    public function update(User $user, SekolahSession $session): bool
    {
        return $this->isTenantAdmin($user, $session)
            || $this->isAssignedConsultant($user, $session);
    }

    /** Siswa pemilik ATAU BK/staff tenant. */
    public function cancel(User $user, SekolahSession $session): bool
    {
        return $this->isOwnerStudent($user, $session)
            || $this->isTenantAdmin($user, $session);
    }

    /** Siswa pemilik ATAU BK/staff tenant. */
    public function reschedule(User $user, SekolahSession $session): bool
    {
        return $this->cancel($user, $session);
    }

    /** Siswa pemilik atau konsultan assigned, sesi harus scheduled. */
    public function chat(User $user, SekolahSession $session): bool
    {
        if ($session->status !== 'scheduled') {
            return false;
        }

        return $this->isOwnerStudent($user, $session)
            || $this->isAssignedConsultant($user, $session);
    }

    /** Hanya siswa pemilik + sesi sudah done. */
    public function rate(User $user, SekolahSession $session): bool
    {
        return $this->isOwnerStudent($user, $session)
            && $session->status === 'done';
    }

    /** Hanya BK tenant yang sama (untuk approve/reject); superadmin boleh (view-as ops). */
    public function bkReview(User $user, SekolahSession $session): bool
    {
        return $user->role?->name === 'admin' || $this->isTenantBk($user, $session);
    }

    /** Hanya BK tenant yang sama (request atas nama siswa). */
    public function requestFor(User $user, SekolahSession $session): bool
    {
        return $this->isTenantBk($user, $session);
    }

    /** BK atau sekolah_staff tenant yang sama. */
    private function isTenantAdmin(User $user, SekolahSession $session): bool
    {
        return SekolahAdmin::where('user_id', $user->id)
            ->where('sekolah_id', $session->sekolah_id)
            ->whereIn('role', ['bk', 'sekolah_staff'])
            ->exists();
    }

    /** Hanya BK (bukan staff). */
    private function isTenantBk(User $user, SekolahSession $session): bool
    {
        return SekolahAdmin::where('user_id', $user->id)
            ->where('sekolah_id', $session->sekolah_id)
            ->where('role', 'bk')
            ->exists();
    }

    private function isOwnerStudent(User $user, SekolahSession $session): bool
    {
        if (! $session->student_id) {
            return false;
        }

        return SekolahStudent::where('id', $session->student_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function isAssignedConsultant(User $user, SekolahSession $session): bool
    {
        if (! $session->consultant_id) {
            return false;
        }

        $consultant = $user->consultants()->first();

        return $consultant !== null && $session->consultant_id === $consultant->id;
    }
}
