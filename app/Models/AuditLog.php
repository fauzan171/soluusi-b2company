<?php

namespace App\Models;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

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

    /**
     * Helper W7 (K16): satu pintu penulisan audit — format konsisten dengan
     * refund W2 (actor_id, action, subject_type, subject_id, meta).
     * subject = model Eloquent (class name + key) atau null.
     */
    public static function record($actor, string $action, $subject = null, array $meta = []): self
    {
        return static::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            // W7: snake_case class name — konsisten dengan W2 ('consultation_quota_ledger').
            'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
