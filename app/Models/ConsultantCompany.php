<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'company_id',
        'start_at',
        'end_at',
        'position',
        'description',
    ];

    /**
     * Get the company of the consultant company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the consultant of the consultant company.
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
