<?php

namespace App\Models;

use App\Models\ConsultantPool;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Template jam kerja mingguan per assignment pool (mission M3 / K7).
 * weekday 0=Minggu..6=Sabtu; slot dihitung on-read (K4, tanpa materialisasi).
 */
class ConsultantWorkHour extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'consultation_types' => 'array',
        'slot_minutes' => 'integer',
    ];

    public function consultantPool()
    {
        return $this->belongsTo(ConsultantPool::class);
    }
}
