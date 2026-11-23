<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Webinar program Wellbeing (wv2 research2-infra §2). Mark-done admin →
 * 1 ledger row bucket 'webinar' event 'used' (no attendance table v1).
 */
class WellbeingWebinar extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function program()
    {
        return $this->belongsTo(CompanyWellbeingProgram::class, 'program_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
