<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_category',
        'name',
        'headline',
        'subheadline',
        'problem',
        'problem_answer_1',
        'problem_answer_2',
        'problem_answer_3',
        'problem_answer_4',
        'faq_1',
        'faq_answer_1',
        'faq_2',
        'faq_answer_2',
        'faq_3',
        'faq_answer_3',
        'faq_4',
        'faq_answer_4',
        'faq_5',
        'faq_answer_5',
    ];

    /**
     * Get own consultant
     */
    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    /**
     * get all consultants
     */
    public function consultants()
    {
        return $this->belongsToMany(Consultant::class, ConsultantCategory::class, 'category_id', 'consultant_id');
    }

    /**
     * Get all skills
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, CategorySkill::class, 'category_id', 'skill_id')->withPivot('skill_id', 'category_id', 'consultant_id');
    }

    /**
     * Get all subcategories for this category
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    /**
     * Get all consultants through subcategories
     */
    public function consultantsThroughSubcategories()
    {
        return $this->hasManyThrough(
            Consultant::class,
            ConsultantSubcategory::class,
            'subcategory_id', // Foreign key on consultant_subcategories table
            'id', // Foreign key on consultants table
            'id', // Local key on categories table
            'consultant_id' // Local key on consultant_subcategories table
        )->join('subcategories', 'subcategories.id', '=', 'consultant_subcategories.subcategory_id')
            ->where('subcategories.category_id', $this->id);
    }
}
