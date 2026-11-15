<?php

namespace App\Policies;

use App\Models\ConsultationSession;
use App\Models\User;

class ConsultationSessionPolicy
{
    /** Konsultan pemilik sesi. */
    public function checkIn(User $user, ConsultationSession $session): bool
    {
        return $this->ownsSession($user, $session);
    }

    /** Konsultan pemilik sesi. */
    public function markDone(User $user, ConsultationSession $session): bool
    {
        return $this->ownsSession($user, $session);
    }

    /** Admin role. */
    public function verify(User $user, ConsultationSession $session): bool
    {
        return $user->role?->name === 'admin';
    }

    private function ownsSession(User $user, ConsultationSession $session): bool
    {
        $consultant = $user->consultants()->first();

        return $consultant !== null && $session->consultant_id === $consultant->id;
    }
}
