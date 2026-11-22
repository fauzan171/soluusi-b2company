<?php

namespace App\Models\Perusahaan;

use App\Models\Departement;
use App\Models\Model;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanAssessment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('perusahaan'));
    }

    protected $fillable = [
        'perusahaan_id',
        'employee_id',
        'departement_id',
        'instrument_code',
        'answers',
        'score',
        'escalated',
        'taken_at',
        'section',
        'status',
        'consultant_id',
        'notes',
        'conclusion',
        'concluded_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'taken_at' => 'datetime',
        'escalated' => 'bool',
    ];

    /** Band WHO-5: ≥75 sehat, 50-74 perlu perhatian, <50 berisiko (low-threshold 28 masuk berisiko). */
    public function band(): string
    {
        return $this->score >= 75 ? 'Sehat'
            : ($this->score >= 50 ? 'Perlu Perhatian' : 'Berisiko Tinggi');
    }

    public function instrument()
    {
        return $this->belongsTo(\App\Models\AssessmentInstrument::class, 'instrument_code', 'code');
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
