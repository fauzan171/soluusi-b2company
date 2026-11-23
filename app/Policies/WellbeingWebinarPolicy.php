<?php

namespace App\Policies;

use App\Models\CompanyWellbeingProgram;
use App\Models\User;
use App\Models\WellbeingWebinar;

class WellbeingWebinarPolicy
{
    /** Admin; webinar harus milik program rute (mismatch → 404 semantics via caller). */
    public function manage(User $user, WellbeingWebinar $webinar, CompanyWellbeingProgram $program): bool
    {
        return $user->role?->name === 'admin'
            && $webinar->program_id === $program->id;
    }
}
