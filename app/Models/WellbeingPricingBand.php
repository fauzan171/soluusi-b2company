<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Band harga karyawan per paket (DATA, seeder — K3). Rp/bulan exclude pajak.
 * Band MHCU menang atas deep_support_quota katalog utk kontrak option A (K7).
 */
class WellbeingPricingBand extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function wellbeingPackage()
    {
        return $this->belongsTo(WellbeingPackage::class);
    }

    public function programs()
    {
        return $this->hasMany(CompanyWellbeingProgram::class, 'pricing_band_id');
    }
}
