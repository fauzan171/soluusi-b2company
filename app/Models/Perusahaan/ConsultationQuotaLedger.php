<?php

namespace App\Models\Perusahaan;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ledger kuota append-only (mission M2 / MASTER-PLAN K2). Periode calendar month
 * period_ym "YYYY-MM" tz Asia/Jakarta. UNIQUE(session, event) = idempotent.
 * Events: reserved|used|forfeited|refunded|released — konstanta di bawah.
 */
class ConsultationQuotaLedger extends Model
{
    use HasFactory;

    protected $table = 'consultation_quota_ledger'; // bentukan nama tabel (bukan bentuk jamak Eloquent)

    public const EVENT_RESERVED = 'reserved';
    public const EVENT_USED = 'used';
    public const EVENT_FORFEITED = 'forfeited';
    public const EVENT_REFUNDED = 'refunded';
    public const EVENT_RELEASED = 'released';

    protected $guarded = ['id'];

    protected $casts = [
        'period_ym' => 'string',
    ];

    public function employeePackage()
    {
        return $this->belongsTo(EmployeePackage::class);
    }

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class);
    }
}
