<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'perusahaan_id',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan\Perusahaan::class);
    }

    /**
     * ONB.1 — divisi yang boleh dipakai satu perusahaan: miliknya sendiri
     * ditambah row legacy (`perusahaan_id IS NULL`).
     */
    public static function forPerusahaan(int $perusahaanId)
    {
        return static::where(fn ($q) => $q->where('perusahaan_id', $perusahaanId)->orWhereNull('perusahaan_id'))
            ->orderBy('name')
            ->get();
    }
}
