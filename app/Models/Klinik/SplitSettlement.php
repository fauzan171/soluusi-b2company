<?php

namespace App\Models\Klinik;

use App\Models\ConsultationSession;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SplitSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'klinik_id',
        'net_amount',
        'klinik_margin',
        'status',
        'settled_at',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ConsultationSession::class, 'session_id');
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }
}
