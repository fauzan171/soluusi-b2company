<?php

namespace App\Models\Perusahaan;

use App\Models\Departement;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'perusahaan_id',
        'departement_id',
        'user_id',
        'name',
        'email',
        'consent_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(PerusahaanSession::class, 'employee_id');
    }

    public function assessments()
    {
        return $this->hasMany(PerusahaanAssessment::class, 'employee_id');
    }
}
