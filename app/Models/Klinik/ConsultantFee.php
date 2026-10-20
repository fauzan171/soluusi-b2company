<?php

namespace App\Models\Klinik;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsultantFee extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_at' => 'datetime',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function package()
    {
        return $this->belongsTo(ConsultantPackage::class, 'consultant_package_id');
    }

    /** Fee aktif untuk konsultan (+package opsional) pada waktu tertentu. */
    public static function feeFor(int $consultantId, ?int $packageId, $at = null): ?int
    {
        $at = $at ?? now();

        return static::where('consultant_id', $consultantId)
            ->where(function ($q) use ($packageId) {
                $q->where('consultant_package_id', $packageId)
                    ->orWhereNull('consultant_package_id');
            })
            ->where('effective_at', '<=', $at)
            ->orderByRaw('consultant_package_id IS NULL') // specific package dulu
            ->orderByDesc('effective_at')
            ->value('net_fee');
    }
}
