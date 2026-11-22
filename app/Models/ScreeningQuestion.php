<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bank pertanyaan screening + item instrumen assessment (kolom M6: instrument_code/version/weight).
 * Baris instrumen: vertical 'perusahaan', code = kode item instrumen (W1..W5, P1..P9, dst).
 */
class ScreeningQuestion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'reverse' => 'bool',
    ];
}
