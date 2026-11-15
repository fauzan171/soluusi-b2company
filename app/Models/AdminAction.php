<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail aksi super admin (bayar pervorma, approve mitra, aktivasi program,
 * settle margin, verify sesi, preview data karyawan). Append-only: tidak ada
 * updated_at.
 */
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Catat satu aksi admin. Dipanggil dari controller admin. */
    public static function record(int $adminId, string $action, $target, ?array $payload = null): self
    {
        return static::create([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'payload_json' => $payload,
        ]);
    }
}
