<?php

namespace App\Models\Perusahaan;

use App\Models\Consultant;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Catatan klinis konsultan per sesi (mission M5 / K14, K16). Konsultan-only;
 * body encrypted at rest (encrypted cast) — PDP sensitive.
 */
class ConsultationNote extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'body' => 'encrypted',
    ];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
