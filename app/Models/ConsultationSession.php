<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationSession extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_WAITING = 'waiting';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_DONE = 'done';
    const STATUS_CANCELED = 'canceled';
    const STATUS_NO_SHOW = 'no_show';

    // Payment status constants
    const PAYMENT_STATUS_WAITING = 'waiting';
    const PAYMENT_STATUS_VERIFIED = 'verified';
    const PAYMENT_STATUS_CANCELED = 'canceled';
    const PAYMENT_STATUS_FAILED = 'failed';

    protected $guarded = ['id'];

    /**
     * Get the schedule of the consultation session.
     */

    protected $casts = [
        'order_at' => 'datetime',
        'payment_at' => 'datetime',
        'konsultan_done_at' => 'datetime',
        'completion_verified_at' => 'datetime',
        'konsultan_checked_in_at' => 'datetime',
        'customer_checked_in_at' => 'datetime',
        'no_show_at' => 'datetime',
        'customer_dob' => 'date',
        'needs_ops_review' => 'boolean',
    ];
    
    public function scopeBlocking($query)
    {
        return $query->where(function ($query) {
            $query->where('payment_status', self::PAYMENT_STATUS_VERIFIED)
                ->orWhere(function ($query) {
                    $query->where('payment_status', self::PAYMENT_STATUS_WAITING)
                        ->where(function ($query) {
                            $query->whereNull('expired_at')->orWhere('expired_at', '>', now());
                        });
                });
        });
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Get the user of the consultation session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the consultant of the consultation session.
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * Get the consultant package of the consultation session.
     */
    public function consultantPackage()
    {
        return $this->belongsTo(ConsultantPackage::class)->withTrashed();
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /** B2B: klinik pemilik sesi (white-label brand source). */
    public function klinik()
    {
        return $this->belongsTo(\App\Models\Klinik\Klinik::class);
    }

    /** B2B: link asal booking (admin-generate-link flow). */
    public function checkoutLink()
    {
        return $this->belongsTo(\App\Models\Klinik\CheckoutLink::class);
    }

    /** B2B: split ledger sesi ini (net→Soluusi, margin→klinik). */
    public function splitSettlement()
    {
        return $this->hasOne(\App\Models\Klinik\SplitSettlement::class, 'session_id');
    }

    /** B2B: kota customer guest (Model C — bukan users.regency). */
    public function customerRegency()
    {
        return $this->belongsTo(Regency::class, 'customer_regency_id');
    }

    /** B2B: email customer — guest dulu, fallback user B2C-style. */
    public function getCustomerEmailAttribute($value)
    {
        return $this->attributes['customer_email'] ?? $this->user?->email;
    }

    /** B2B: nama customer — guest dulu, fallback user. */
    public function getCustomerDisplayNameAttribute(): string
    {
        if (!empty($this->attributes['customer_name'])) {
            return $this->attributes['customer_name'];
        }
        return trim(($this->user?->firstname ?? '') . ' ' . ($this->user?->lastname ?? ''));
    }

    /**
     * Calculate voucher discount amount
     */
    public function getVoucherDiscountAttribute()
    {
        if ($this->voucher_amount > 0) {
            return $this->voucher_amount;
        }

        if (!$this->voucher || !$this->consultantPackage) {
            return 0;
        }

        $voucher = $this->voucher;
        $packagePrice = $this->consultantPackage->price;

        if ($voucher->type == 'diskon') {
            $discount = $packagePrice * $voucher->discount_percentage / 100;
            return $discount > $voucher->max_discount ? $voucher->max_discount : $discount;
        } elseif ($voucher->type == 'potongan') {
            return $voucher->nominal;
        }

        return 0;
    }
}
