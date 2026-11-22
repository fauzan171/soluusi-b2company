<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Perusahaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'logo',
        'associate_id',
        'mou_at',
        'pepm_rate',
        'sessions_per_employee_month',
        'employee_count',
        'nps_score',
        'is_active',
        'status',
        'ai_enabled',
        'consultant_request_enabled',
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

    public function consultantRequests()
    {
        return $this->hasMany(ConsultantRequest::class);
    }

    public function admins()
    {
        return $this->hasMany(PerusahaanAdmin::class);
    }

    public function employees()
    {
        return $this->hasMany(PerusahaanEmployee::class);
    }

    /** Pool assignment konsultan (mission M3 / K6). */
    public function pools()
    {
        return $this->hasMany(\App\Models\ConsultantPool::class);
    }

    public function sessions()
    {
        return $this->hasMany(PerusahaanSession::class);
    }

    public function pervormas()
    {
        return $this->hasMany(PerusahaanPervorma::class);
    }

    public function assessments()
    {
        return $this->hasMany(PerusahaanAssessment::class);
    }
}
