<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A1 (D2): assignment instrumen — satu mekanisme utk Perusahaan (level
 * perusahaan / program wellbeing) dan Sekolah. Window opens_at..closes_at +
 * round; precedence program-aktif > legacy perusahaan di AssessmentAccess.
 */
class AssessmentAssignment extends Model
{
    public const SOURCE_CORE = 'core';
    public const SOURCE_ADDON = 'addon';
    public const SOURCE_LEGACY = 'legacy';

    public const AUDIENCE_EMPLOYEE = 'employee';
    public const AUDIENCE_STUDENT = 'student';
    public const AUDIENCE_STAFF = 'staff';

    /** D9: instrumen yang boleh utk siswa sekolah (minor). */
    public const STUDENT_ALLOWED = ['who5', 'pss10', 'bigfive', 'disc'];

    /** D5: klinis — tidak pernah mass-assign, hanya per-person grant. */
    public const CLINICAL = ['mcmi3'];

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'instrument_code',
        'audience',
        'source',
        'addon_price',
        'round',
        'opens_at',
        'closes_at',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'opens_at' => 'date',
        'closes_at' => 'date',
        'addon_price' => 'decimal:2',
    ];

    public function assignable()
    {
        return $this->morphTo();
    }

    public function instrument()
    {
        return $this->belongsTo(AssessmentInstrument::class, 'instrument_code', 'code');
    }

    public function assigner()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    /** Assignment terbuka pada $date: active + dalam window (null closes = open-ended). */
    public function scopeOpen(Builder $q, $date = null): Builder
    {
        $date ??= today();

        return $q->where('status', 'active')
            ->whereDate('opens_at', '<=', $date)
            ->where(fn (Builder $w) => $w->whereNull('closes_at')->orWhereDate('closes_at', '>=', $date));
    }

    public function isOpen($date = null): bool
    {
        $date ??= today();

        return $this->status === 'active'
            && $this->opens_at->lessThanOrEqualTo($date)
            && ($this->closes_at === null || $this->closes_at->greaterThanOrEqualTo($date));
    }
}
