<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sekolah_id',
        'student_id',
        'kelas',
        'answers',
        'score',
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
    ];

    /** Band WHO-5 — sama dengan PerusahaanAssessment::band() (satu formula). */
    public function band(): string
    {
        return $this->score >= 75 ? 'Sehat'
            : ($this->score >= 50 ? 'Perlu Perhatian' : 'Berisiko Tinggi');
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function student()
    {
        return $this->belongsTo(SekolahStudent::class, 'student_id');
    }
}
