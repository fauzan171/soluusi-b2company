<?php

namespace App\Models;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Audit log minimal (mission M7 / K16) — bukan spatie, minim dependency.
 * Dipakai untuk akses catatan klinis & event privacy-sensitive. created_at
 * saja (immutable log, tanpa updated_at). actor_id tanpa FK: log survive
 * user delete.
 */
class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class);
    }
}
