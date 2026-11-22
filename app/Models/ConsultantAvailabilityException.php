<?php

namespace App\Models;

use App\Models\Consultant;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Exception ketersediaan konsultan (mission M3 / K7) — global semua perusahaan.
 * start_time/end_time NULL = full-day block.
 */
class ConsultantAvailabilityException extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
