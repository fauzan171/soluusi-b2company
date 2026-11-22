<?php

namespace App\Models\Perusahaan;

use App\Models\Consultant;
use App\Models\Departement;
use App\Models\Model;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerusahaanSession extends Model
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
        'consultant_id',
        'konsultan_type',
        'topic_category',
        'status',
        'rating',
        'completed_at',
        'scheduled_at',
        'schedule_id',
        'ended_at',
        'quota_consumed',
        'cancel_reason',
        'cancelled_at',
        'checked_in_at',
        'meeting_url',
        'location',
        'delivery_status',
        'no_show_party',
        'request_notes',
        'schedule_version',
        'transcript_purged_at',
        'mode',
        'consultation_type',
        'room_id',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
        'transcript_purged_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(\App\Models\Schedule::class);
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

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(\App\Models\ChatMessage::class, 'perusahaan_session_id')->orderBy('id');
    }

    public function room()
    {
        return $this->belongsTo(\App\Models\Perusahaan\Room::class);
    }

    // W7 (K14): note/result/review — 1:1 per sesi (UNIQUE perusahaan_session_id).
    public function note()
    {
        return $this->hasOne(ConsultationNote::class, 'perusahaan_session_id');
    }

    public function result()
    {
        return $this->hasOne(ConsultationResult::class, 'perusahaan_session_id');
    }

    public function review()
    {
        return $this->hasOne(ConsultationReview::class, 'perusahaan_session_id');
    }

    /** Catatan konsultan (nama relations plural: dipakai test W7). */
    public function notes()
    {
        return $this->hasOne(ConsultationNote::class, 'perusahaan_session_id');
    }

    public function reviews()
    {
        return $this->hasOne(ConsultationReview::class, 'perusahaan_session_id');
    }

    /**
     * W7: rating agregat konsultan untuk perusahaan ini (privacy-safe —
     * komentar individu tidak pernah ikut, hanya avg + count).
     */
    public static function ratingAggregate(int $consultantId, int $perusahaanId): array
    {
        $row = static::query()
            ->join('consultation_reviews', 'consultation_reviews.perusahaan_session_id', '=', 'perusahaan_sessions.id')
            ->where('perusahaan_sessions.consultant_id', $consultantId)
            ->where('perusahaan_sessions.perusahaan_id', $perusahaanId)
            ->selectRaw('avg(consultation_reviews.rating) as avg_rating, count(*) as total')
            ->first();

        return ['avg' => $row && $row->total ? round((float) $row->avg_rating, 1) : null, 'count' => (int) ($row->total ?? 0)];

    }
}
