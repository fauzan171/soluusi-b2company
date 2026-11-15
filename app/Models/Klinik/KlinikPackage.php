<?php

namespace App\Models\Klinik;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KlinikPackage extends Model
{
    use HasFactory;

    protected $table = 'klinik_packages';

    protected $fillable = [
        'klinik_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'mode',
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }
}
