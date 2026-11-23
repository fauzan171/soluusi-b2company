<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// wv4: feature B — action plan tracker per area prioritas.
class ActionPlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function program()
    {
        return $this->belongsTo(CompanyWellbeingProgram::class, 'company_wellbeing_program_id');
    }
}
