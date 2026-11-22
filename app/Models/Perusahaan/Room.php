<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\Perusahaan\Perusahaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ruang fisik konsultasi 1-on-1 (mission M5). Kolom status TEXT default
 * 'active' (bukan is_active boolean — test expect status 'active').
 */
class Room extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function sessions()
    {
        return $this->hasMany(PerusahaanSession::class);
    }
}
