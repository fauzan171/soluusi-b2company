<?php

namespace App\Models\Sekolah;

use App\Models\ChatMessage;
use App\Models\Consultant;
use App\Models\Model;
use App\Models\Scopes\TenantScope;
use App\Models\SessionSlotProposal;
use App\Models\SessionSummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahSession extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('sekolah'));
        // D.1: SAP pervorma trigger single-source (observer). Idempotent di PervormaService.
        static::observe(\App\Observers\SekolahSessionObserver::class);
    }

    protected $fillable = [
        'sekolah_id',
        'student_id',
        'kelas',
        'consultant_id',
        'konsultan_type',
        'topic_category',
        'mode',
        'origin',
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
        'transcript_purged_at',
        'delivery_status',
        'no_show_party',
        'request_notes',
        'schedule_version',
        'requested_by_user_id',
        'requested_for_role',
        'parent_notified_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
        'transcript_purged_at' => 'datetime',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function student()
    {
        return $this->belongsTo(SekolahStudent::class, 'student_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sekolah_session_id')->orderBy('id');
    }

    public function sessionSummaries()
    {
        return $this->hasMany(SessionSummary::class, 'sekolah_session_id');
    }

    public function slotProposals()
    {
        return $this->hasMany(SessionSlotProposal::class, 'sekolah_session_id');
    }
}
