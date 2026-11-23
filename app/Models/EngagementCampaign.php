<?php

namespace App\Models;

use App\Models\Perusahaan\Perusahaan;
use Illuminate\Database\Eloquent\Model;

// wv4: feature D — engagement campaign (email / announcement).
class EngagementCampaign extends Model
{
    protected $guarded = ['id'];

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
}
