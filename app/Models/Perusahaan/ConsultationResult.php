<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ringkasan hasil konsultasi (mission M5 / K14). Boleh dilihat employee
 * (pemilik sesi) + konsultan — bukan HR.
 */
class ConsultationResult extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }
}
