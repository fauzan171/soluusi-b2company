<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorySkill extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'skill_id', 'consultant_id'];

    /**
     * Get category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get skill
     */

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Get consultant
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
