<?php

namespace App\Models\Klinik;

use App\Models\ConsultantPackage;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarkupRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'klinik_id',
        'consultant_package_id',
        'markup_amount',
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }

    public function package()
    {
        return $this->belongsTo(ConsultantPackage::class, 'consultant_package_id');
    }
}
