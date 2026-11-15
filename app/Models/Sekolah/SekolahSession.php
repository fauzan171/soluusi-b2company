<?php

namespace App\Models\Sekolah;

use App\Models\Consultant;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahSession extends Model
{
    use HasFactory;

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
        'delivery_status',
        'no_show_party',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
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
}
