<?php

namespace App\Models\Sekolah;

use App\Models\Model;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahStudent extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('sekolah'));
    }

    protected $fillable = [
        'sekolah_id',
        'kelas',
        'user_id',
        'name',
        'email',
        'parent_name',
        'parent_consent_at',
        'consent_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
        'parent_consent_at' => 'datetime',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(SekolahSession::class, 'student_id');
    }

    public function assessments()
    {
        return $this->hasMany(SekolahAssessment::class, 'student_id');
    }
}
