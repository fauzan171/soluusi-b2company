<?php

namespace App\Models\Klinik;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Model;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class CheckoutLink extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public function isUsable(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at->isFuture()
            && (bool) $this->klinik?->is_active
            && $this->schedule?->status === Schedule::STATUS_CONFIRMED
            && $this->schedule?->started_at?->isFuture();
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function package()
    {
        return $this->belongsTo(ConsultantPackage::class, 'consultant_package_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
