<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sekolah extends Model
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
        return $this->hasMany(SekolahAdmin::class);
    }

    public function students()
    {
        return $this->hasMany(SekolahStudent::class);
    }

    public function sessions()
    {
        return $this->hasMany(SekolahSession::class);
    }

    public function assessments()
    {
        return $this->hasMany(SekolahAssessment::class);
    }
}
