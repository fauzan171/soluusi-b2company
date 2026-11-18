<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\SekolahSession;

class SessionSummary extends Model
{
    protected $fillable = [
        'perusahaan_session_id',
        'sekolah_session_id',
        'summary',
        'client_advice',
        'input_hash',
        'model',
        'status',
        'error_code',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function sekolahSession()
    {
        return $this->belongsTo(SekolahSession::class, 'sekolah_session_id');
    }
}
