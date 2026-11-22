<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban item assessment, polymorphic ke baris assessment (perusahaan/sekolah).
 * Raw value tidak pernah di-reverse — reversal dihitung di engine.
 */
class ScreeningAnswer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'answered_at' => 'datetime',
    ];
}
