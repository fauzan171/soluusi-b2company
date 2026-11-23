<?php

namespace App\Policies;

use App\Models\ActionPlan;
use App\Models\Perusahaan\Perusahaan;
use App\Models\User;

class ActionPlanPolicy
{
    /** HR perusahaan pemilik program plan (tenant scoping). */
    public function manage(User $user, ActionPlan $plan, Perusahaan $perusahaan): bool
    {
        return $plan->program?->perusahaan_id === $perusahaan->id;
    }
}
