<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantEducation extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'education_id',
        'start_date',
        'end_date',
        'degree',
        'departement_id',
    ];

    /**
     * Get own consultant
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * Get own education
     */
    public function education()
    {
        return $this->belongsTo(Education::class);
    }

    /**
     * Get own departement
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
