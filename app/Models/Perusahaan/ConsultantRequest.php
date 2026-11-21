<?php

namespace App\Models\Perusahaan;

use Illuminate\Database\Eloquent\Model;

class ConsultantRequest extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'perusahaan_id',
        'consultant_id',
        'konsultan_type',
        'note',
        'status',
        'requested_by',
        'decided_at',
    ];
}
