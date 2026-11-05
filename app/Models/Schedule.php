<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_at',
        'ended_at',
        'consultant_id',
        'latest_updated_by',
        'klinik_id',
        'perusahaan_id',
        'sekolah_id',
        'created_by',
        'mode',
        'status',
        'location',
        'meeting_url',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public const MODE_ONLINE = 'online';
    public const MODE_OFFLINE = 'offline';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * get consultant with this Schedule
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function klinik()
    {
        return $this->belongsTo(\App\Models\Klinik\Klinik::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the consultation session for the schedule.
     */
    public function consultationSession()
    {
        return $this->hasOne(ConsultationSession::class);
    }

    public function checkoutLinks()
    {
        return $this->hasMany(\App\Models\Klinik\CheckoutLink::class);
    }

    public function perusahaanSessions()
    {
        return $this->hasMany(\App\Models\Perusahaan\PerusahaanSession::class, 'schedule_id');
    }
}
