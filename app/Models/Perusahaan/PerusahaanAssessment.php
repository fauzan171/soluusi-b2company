<?php

namespace App\Models\Perusahaan;

use App\Models\Departement;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanAssessment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'answers' => 'array',
        'taken_at' => 'datetime',
    ];

    /** Band WHO-5: ≥75 sehat, 50-74 perlu perhatian, <50 berisiko (low-threshold 28 masuk berisiko). */
    public function band(): string
    {
        return $this->score >= 75 ? 'Sehat'
            : ($this->score >= 50 ? 'Perlu Perhatian' : 'Berisiko Tinggi');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class, 'employee_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
}
