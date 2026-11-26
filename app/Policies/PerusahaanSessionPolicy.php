<?php

namespace App\Policies;

use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;

class PerusahaanSessionPolicy
{
    /** Employee pemilik sesi ATAU konsultan yang ditugaskan. */
    public function view(User $user, PerusahaanSession $session): bool
    {
        return $this->isOwnerEmployee($user, $session) || $this->isAssignedConsultant($user, $session);
    }

    /** Sama dengan view + sesi aktif (scheduled, konsultan assigned, sudah di-ACC). */
    public function send(User $user, PerusahaanSession $session): bool
    {
        return $this->view($user, $session)
            && $session->status === 'scheduled'
            && $session->consultant_id
            && $session->scheduled_at !== null
            && ! $session->chatExpired();
    }

    /** Employee atau konsultan pemilik sesi. */
    public function end(User $user, PerusahaanSession $session): bool
    {
        return $this->view($user, $session);
    }

    /** Hanya employee pemilik + sesi sudah done. */
    public function rate(User $user, PerusahaanSession $session): bool
    {
        return $this->isOwnerEmployee($user, $session) && $session->status === 'done';
    }

    private function isOwnerEmployee(User $user, PerusahaanSession $session): bool
    {
        $employee = PerusahaanEmployee::where('user_id', $user->id)->first();

        return $employee !== null && $session->employee_id === $employee->id;
    }

    private function isAssignedConsultant(User $user, PerusahaanSession $session): bool
    {
        $consultant = $user->consultants()->first();

        return $consultant !== null && $session->consultant_id === $consultant->id;
    }
}
