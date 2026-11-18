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

    /** Siswa terdaftar — dipakai semua agregat (kuota, kelas, registered). */
    public function students()
    {
        return $this->hasMany(SekolahStudent::class)->where('type', SekolahStudent::TYPE_STUDENT);
    }

    /** Staf sekolah (guru/karyawan) yang ikut program sebagai end-user. */
    public function staff()
    {
        return $this->hasMany(SekolahStudent::class)->where('type', SekolahStudent::TYPE_STAFF);
    }

    /**
     * Sesi program yang dihitung di agregat BK: hanya sesi milik siswa.
     * Sesi staf (self-service, E.1) tidak masuk dashboard/kuota sekolah —
     * person row-nya type='staff' dan tidak ada di Sekolah::students().
     */
    public function sessions()
    {
        return $this->hasMany(SekolahSession::class)
            ->whereDoesntHave('student', fn ($q) => $q->where('type', SekolahStudent::TYPE_STAFF));
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
