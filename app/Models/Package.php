<?php

namespace App\Models;

use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\Perusahaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Package kuota konsultasi bulanan (mission M1). monthly_quota = sesi/karyawan/bulan.
 */
class Package extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'monthly_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function employeePackages()
    {
        return $this->hasMany(EmployeePackage::class);
    }
}
