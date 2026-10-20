<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Output extends Model
{
    use HasFactory;

    protected $fillable = [
        'output',
        'consultant_package_id'
    ];

    /**
     * Get own consultant_package
     */
    public function consultant_package()
    {
        return $this->belongsTo(ConsultantPackage::class);
    }
}
