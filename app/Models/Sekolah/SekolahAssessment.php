<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahAssessment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('sekolah'));
    }

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

    /** E.1: agregat hanya menghitung asesmen siswa, bukan staf. */
    public function scopeStudentRoster($q)
    {
        return $q->whereIn('student_id', SekolahStudent::select('id')
            ->where('type', SekolahStudent::TYPE_STUDENT));
    }

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
