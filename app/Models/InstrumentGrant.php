<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;

/**
 * wv1: grant akses instrumen klinis (MCMI-III) per employee — clinical
 * indication-based, diberikan superadmin. Tanpa grant → instrumen inactive 404.
 */
class InstrumentGrant extends Model
{
    protected $fillable = ['employee_id', 'instrument_code', 'granted_by', 'reason'];

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class);
    }

    public function grantor()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
