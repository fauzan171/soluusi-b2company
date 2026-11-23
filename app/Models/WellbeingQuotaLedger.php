<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ledger kuota Wellbeing append-only (mirror pola M2). 2 bucket terpisah
 * expert_chat|deep_support (K6), TANPA period_ym — periode = umur kontrak.
 */
class WellbeingQuotaLedger extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'wellbeing_quota_ledger';

    public function program()
    {
        return $this->belongsTo(CompanyWellbeingProgram::class, 'program_id');
    }

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function employee()
    {
        return $this->belongsTo(PerusahaanEmployee::class, 'perusahaan_employee_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
