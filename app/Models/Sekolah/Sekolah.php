<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sekolah extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'logo',
        'tingkat',
        'student_count',
        'sessions_per_student_month',
        'associate_id',
        'mou_at',
        'is_active',
        'status',
        'ai_enabled',
    ];

    protected $casts = [
        'mou_at' => 'datetime',
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
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

    public function pervormas()
    {
        return $this->hasMany(SekolahPervorma::class);
    }

    public function assessments()
    {
        return $this->hasMany(SekolahAssessment::class);
    }
}
