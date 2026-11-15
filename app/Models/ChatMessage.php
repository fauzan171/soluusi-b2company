<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanSession;

class ChatMessage extends Model
{
    protected $fillable = [
        'perusahaan_session_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}