<?php

namespace App\Models;

use App\Models\Perusahaan\Perusahaan;
use Illuminate\Database\Eloquent\Model;

// wv4: feature D — engagement campaign (email / announcement).
class EngagementCampaign extends Model
{
    // QA8-F3: whitelist eksplisit — semua create/update call-site sudah pakai
    // field-field ini (CampaignController::store).
    protected $fillable = [
        'perusahaan_id',
        'title',
        'body',
        'channel',
        'kind',
        'scheduled_at',
        'sent_at',
        'created_by',
        'assessment_assignment_id', // B5: link reminder ke assignment (sender exclude submitter)
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** B5: assignment yang jadi sumber reminder ini (null = manual). */
    public function assessmentAssignment()
    {
        return $this->belongsTo(AssessmentAssignment::class);
    }
}
