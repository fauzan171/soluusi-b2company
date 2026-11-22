<?php

namespace App\Models\Perusahaan;

use App\Models\Package;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Assignment package ke karyawan (mission M1). Aktif 1 per employee dijaga
 * app-level (W2 QuotaService) — skema tanpa partial unique (sqlite-safe).
 */
class EmployeePackage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class, 'perusahaan_employee_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(ConsultationQuotaLedger::class);
    }
}
