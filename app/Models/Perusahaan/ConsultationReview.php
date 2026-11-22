<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\Perusahaan\PerusahaanEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Review rating sesi (mission M5 / K14). UNIQUE perusahaan_session_id —
 * hanya setelah status done (enforce app-level, W7).
 */
class ConsultationReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class, 'perusahaan_employee_id');
    }
}
