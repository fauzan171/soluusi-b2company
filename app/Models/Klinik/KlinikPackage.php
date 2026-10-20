<?php

namespace App\Models\Klinik;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KlinikPackage extends Model
{
    use HasFactory;

    protected $table = 'klinik_packages';

    protected $guarded = ['id'];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }
}
