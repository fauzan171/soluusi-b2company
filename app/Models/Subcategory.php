<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = [
        'name',
        'code_subcategory',
        'category_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all consultants for this subcategory
     */
    public function consultants()
    {
        return $this->belongsToMany(Consultant::class, 'consultant_subcategories', 'subcategory_id', 'consultant_id');
    }
}
