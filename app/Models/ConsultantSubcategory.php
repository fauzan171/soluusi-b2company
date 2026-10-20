<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'subcategory_id'
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}
