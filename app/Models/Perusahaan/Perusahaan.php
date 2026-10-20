<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Perusahaan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'mou_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function associate()
    {
        return $this->belongsTo(User::class, 'associate_id');
    }

    public function admins()
    {
        return $this->hasMany(PerusahaanAdmin::class);
    }

    public function employees()
    {
        return $this->hasMany(PerusahaanEmployee::class);
    }

    public function sessions()
    {
        return $this->hasMany(PerusahaanSession::class);
    }

    public function assessments()
    {
        return $this->hasMany(PerusahaanAssessment::class);
    }
}
