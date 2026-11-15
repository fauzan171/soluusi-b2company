<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionSlotProposal extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'session_id',
        'consultant_id',
        'proposed_at',
        'slots',
        'status',
    ];

    protected $casts = [
        'proposed_at' => 'datetime',
        'slots' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(\App\Models\Perusahaan\PerusahaanSession::class, 'session_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
