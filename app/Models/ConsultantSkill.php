<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'skill_id'
    ];
}
