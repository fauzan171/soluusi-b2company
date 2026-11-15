<?php

namespace App\Models;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Sekolah\Sekolah;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Konsultan yang Soluusi assign ke tenant (pool). Sekolah/perusahaan nullable:
 * row khusus satu vertikal saja.
 */
class ConsultantPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'sekolah_id',
        'perusahaan_id',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    /** konsultan_type dari kategori konsultan (map nama kategori → tipe sesi). */
    public static function typeFromCategory(?string $categoryName): string
    {
        return match (true) {
            $categoryName && str_contains($categoryName, 'Gizi') => 'nutrisionis',
            $categoryName && str_contains($categoryName, 'Fisioterapi') => 'fisioterapis',
            $categoryName && str_contains($categoryName, 'Karir') => 'karir',
            default => 'psikolog',
        };
    }

    /**
     * Pool konsultan untuk tenant: [{id, name, category, type}].
     * Nama asli di users.firstname/lastname, kategori via pivot (B2B-spec §4.1/§5.2).
     */
    public static function poolFor(string $tenant, int $tenantId): \Illuminate\Support\Collection
    {
        return static::query()
            ->where("{$tenant}_id", $tenantId)
            ->whereHas('consultant', fn ($q) => $q->where('status', 'accept')->whereHas('user', fn ($u) => $u->where('is_active', true)))
            ->with('consultant.user', 'consultant.categories')
            ->get()
            ->map(fn (self $p) => [
                'id' => $p->consultant_id,
                'name' => $p->consultant->user?->fullname ?? 'Konsultan #' . $p->consultant_id,
                'category' => $p->consultant->categories->pluck('name')->implode(', '),
                'type' => self::typeFromCategory($p->consultant->categories->first()?->name),
            ])
            ->sortBy('name')->values();
    }

    /** Validasi pilihan user: konsultan ada di pool tenant DAN tipenya cocok. */
    public static function validChoice(string $tenant, int $tenantId, int $consultantId, string $type): bool
    {
        return static::poolFor($tenant, $tenantId)
            ->contains(fn ($c) => $c['id'] === $consultantId && $c['type'] === $type);
    }
}
