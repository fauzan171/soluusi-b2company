<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Get own consultant
     */
    public function consultanEducation()
    {
        return $this->belongsTo(ConsultantEducation::class);
    }

    /**
     * get all consultants
     * 
     */
    public function consultants()
    {
        return $this->belongsToMany(Consultant::class, ConsultantEducation::class, 'education_id', 'consultant_id');
    }
}
