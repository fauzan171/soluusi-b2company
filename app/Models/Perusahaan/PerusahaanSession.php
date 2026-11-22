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
}
