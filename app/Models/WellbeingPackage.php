<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Katalog paket Wellbeing (DATA, seeder WellbeingPackageSeeder — K3). 4 paket:
 * discovery|foundation|growth|impact. Harga per band di WellbeingPricingBand.
 */
class WellbeingPackage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_free' => 'boolean',
        'assessment_domains' => 'array',
        'quarterly_review' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function pricingBands()
    {
        return $this->hasMany(WellbeingPricingBand::class);
    }

    public function programs()
    {
        return $this->hasMany(CompanyWellbeingProgram::class);
    }
}
