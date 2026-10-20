<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Get all categories
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, CategorySkill::class, 'skill_id', 'category_id');
    }

    /**
     * Get all consultants
     */
    public function consultants()
    {
        return $this->belongsToMany(Consultant::class, CategorySkill::class, 'skill_id', 'consultant_id');
    }
}
