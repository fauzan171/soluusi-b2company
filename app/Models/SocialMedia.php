<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
    ];

    /**
     * Get all of the consultants for the SocialMedia
     */
    public function consultantSocialMedia()
    {
        return $this->hasMany(ConsultantSocialMedia::class);
    }
}
