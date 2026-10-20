<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantSocialMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'social_media_id',
        'url'
    ];
}
