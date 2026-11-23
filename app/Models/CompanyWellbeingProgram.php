<?php

namespace App\Models;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Models\WellbeingProgramReview;
use App\Models\WellbeingWebinar;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Kontrak Wellbeing: paket di-assign superadmin ke perusahaan (K4 snapshot
 * kuota+band+harga saat assign; K8 1 program aktif per perusahaan guard
 * app-level). Kuota kontrak-level total, unused hangus saat ends_at.
 */
class CompanyWellbeingProgram extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'assessment_domains' => 'array',
        'selected_employee_ids' => 'array',
        'notice_requested_at' => 'datetime',
        'terminated_at' => 'date',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function wellbeingPackage()
    {
        return $this->belongsTo(WellbeingPackage::class);
    }

    public function pricingBand()
    {
        return $this->belongsTo(WellbeingPricingBand::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotaLedgers()
    {
        return $this->hasMany(WellbeingQuotaLedger::class, 'program_id');
    }

    /** wv2: webinar per kontrak (§2 research2-infra). */
    public function webinars()
    {
        return $this->hasMany(WellbeingWebinar::class, 'program_id');
    }

    /** wv2: review kuartalan per kontrak — hanya Impact (§3 research2-infra). */
    public function reviews()
    {
        return $this->hasMany(WellbeingProgramReview::class, 'program_id')->orderBy('period_no');
    }

    // wv4: feature B — action plans per area prioritas (Growth/Impact only).
    public function actionPlans()
    {
        return $this->hasMany(\App\Models\ActionPlan::class);
    }
}
