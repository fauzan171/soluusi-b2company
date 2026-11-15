<?php

namespace App\Models\Scopes;

use App\Support\KlinikContext;
use App\Support\PerusahaanContext;
use App\Support\SekolahContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * T2.4 — Tenant enforcement di layer data (Opsi A: Global Scope).
 *
 * Scope HANYA aktif bila tenant context resolve ke tenant konkret (request
 * dengan auth user yang terikat perusahaan/sekolah/klinik). Tanpa context
 * (console command, guest, queue worker) scope tidak memfilter apa pun —
 * perilaku lama dipertahankan agar sweep cross-tenant (CancelExpiredSessions)
 * dan fixture test tidak rusak.
 *
 * Bypass eksplisit untuk agregasi superadmin: `->withoutGlobalScope(TenantScope::class)`
 * atau `->withoutGlobalScopes()`.
 *
 * ponytail: sengaja TIDAK di-apply ke Schedule (kolom tenant nullable + dipakai
 * lintas vertical oleh consultant booking + sweep console) dan CheckoutLink
 * (akses sudah token-gated; scoping klinik_id tidak menambah apa-apa di guest
 * flow dan berisiko menyembunyikan link dari admin). Upgrade path: tambah
 * TenantScope('klinik') di CheckoutLink saat admin portal mulai butuh filter
 * default, dan scope Schedule hanya lewat query manual per vertical.
 */
class TenantScope implements Scope
{
    /**
     * @param  string  $vertical  'perusahaan' | 'sekolah' | 'klinik'
     */
    public function __construct(private string $vertical)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->currentTenantId();
        if ($tenantId !== null) {
            $builder->where($model->getTable() . '.' . $this->vertical . '_id', $tenantId);
        }
    }

    private function currentTenantId(): ?int
    {
        // Tanpa auth (console, guest checkout, queue) → scope inactive.
        if (!auth()->hasUser()) {
            return null;
        }

        $tenant = match ($this->vertical) {
            'perusahaan' => PerusahaanContext::current(),
            'sekolah' => SekolahContext::current(),
            'klinik' => KlinikContext::current(),
            default => null,
        };

        return $tenant?->id;
    }
}
