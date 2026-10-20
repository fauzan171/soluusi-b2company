<?php

namespace App\Models\Klinik;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Klinik extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'mou_at' => 'datetime',
        'approved_at' => 'datetime',
        'terms_agreed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($klinik) {
            if (empty($klinik->uuid)) {
                $klinik->uuid = (string) Str::uuid();
            }
        });
    }

    /** White-label brand — fallback config default (PRD §15.4). */
    public function getBrandNameAttribute(): string
    {
        return $this->brand ?: config('whitelabel.default_brand');
    }

    public function associate()
    {
        return $this->belongsTo(User::class, 'associate_id');
    }

    public function admins()
    {
        return $this->hasMany(KlinikAdmin::class);
    }

    public function checkoutLinks()
    {
        return $this->hasMany(CheckoutLink::class);
    }

    public function splitSettlements()
    {
        return $this->hasMany(SplitSettlement::class);
    }

    public function hapPervormas()
    {
        return $this->hasMany(HapPervorma::class);
    }

    public function markupRules()
    {
        return $this->hasMany(MarkupRule::class);
    }

    public function klinikPackages()
    {
        return $this->hasMany(KlinikPackage::class);
    }
}
