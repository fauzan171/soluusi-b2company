<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'started_at',
        'ended_at',
        'location',
        'position',
        'consultant_id'
    ];

    /**
     * get consultant with this Company
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * get all consultant of the company
     */
    public function consultants()
    {
        return $this->belongsToMany(Consultant::class, ConsultantCompany::class, 'company_id', 'consultant_id');
    }
}
