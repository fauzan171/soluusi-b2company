<?php

namespace App\Http\Controllers;

use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Support\PerusahaanContext;
use App\Support\SekolahContext;
use Illuminate\Http\Request;

/**
 * ONB.5 — HR (perusahaan) / BK (sekolah) menyalakan & mematikan konsultan yang
 * SUDAH di-assign Soluusi ke tenant-nya.
 *
 * Batas kontrak: kelas ini tidak punya jalur menambah konsultan baru. Himpunan
 * konsultan ditetapkan superadmin di Admin\ConsultantAssignmentController;
 * yang boleh diubah tenant hanya `is_active` pada row miliknya sendiri.
 */
abstract class TenantConsultantPoolController extends Controller
{
    /** 'perusahaan' | 'sekolah' */
    protected const VERTICAL = '';

    public function index()
    {
        $tenant = $this->tenant();

        return view('tenant.consultant-pool', [
            'vertical' => static::VERTICAL,
            'tenant' => $tenant,
            'rows' => $this->rows($tenant),
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'consultant_id' => ['required', 'integer', 'min:1'],
        ]);

        $tenant = $this->tenant();
        $other = static::VERTICAL === 'perusahaan' ? 'sekolah_id' : 'perusahaan_id';

        // Guard kepemilikan: row harus ada di pool tenant ini. Row tenant lain
        // (atau konsultan yang belum pernah di-assign) → 404, bukan 403, supaya
        // keberadaan pool tenant lain tidak bocor.
        //
        // Row yang punya KEDUA kolom tenant (skema membolehkan, dan assign()
        // firstOrCreate bisa mendarat di sana) sengaja dikecualikan: satu bit
        // is_active dipakai bersama, jadi HR A mematikan konsultan di sekolah S.
        // Serahkan row ambigu itu ke superadmin.
        $pool = ConsultantPool::where('consultant_id', $data['consultant_id'])
            ->where(static::VERTICAL . '_id', $tenant->id)
            ->whereNull($other)
            ->firstOrFail();

        $pool->update(['is_active' => !$pool->is_active]);

        AdminAction::record(auth()->id(), 'consultant_pool.toggle', $tenant, [
            'consultant_id' => (int) $pool->consultant_id,
            'is_active' => (bool) $pool->is_active,
            'actor_role' => optional(auth()->user()->role)->name,
        ]);

        return back()->with('success', $pool->is_active
            ? 'Konsultan diaktifkan untuk organisasi ini.'
            : 'Konsultan dinonaktifkan untuk organisasi ini.');
    }

    /** Semua konsultan di pool tenant, termasuk yang non-aktif (agar bisa dinyalakan lagi). */
    private function rows($tenant)
    {
        return ConsultantPool::where(static::VERTICAL . '_id', $tenant->id)
            // Sama dengan toggle(): row yang juga terhubung ke vertikal lain
            // hanya boleh diubah superadmin.
            ->whereNull(static::VERTICAL === 'perusahaan' ? 'sekolah_id' : 'perusahaan_id')
            ->whereHas('consultant', fn ($q) => $q
                ->where('status', Consultant::ACCEPT)
                ->whereHas('user', fn ($u) => $u->where('is_active', true)))
            ->with('consultant.user', 'consultant.categories')
            ->get()
            ->map(fn (ConsultantPool $p) => [
                'id' => $p->consultant_id,
                'name' => $p->consultant->user?->fullname ?? 'Konsultan #' . $p->consultant_id,
                'category' => $p->consultant->categories->pluck('name')->implode(', '),
                'is_active' => (bool) $p->is_active,
                // T2: profil kaya (photo/headline/JSON) untuk display pool HR.
                'photo_url' => $p->consultant->photo_url,
                'headline' => $p->consultant->headline,
                'profile' => $p->consultant->profile,
            ])
            ->sortBy('name')
            ->values();
    }

    /** @return \App\Models\Perusahaan\Perusahaan|\App\Models\Sekolah\Sekolah */
    private function tenant()
    {
        $tenant = static::VERTICAL === 'perusahaan'
            ? PerusahaanContext::current()
            : SekolahContext::current();

        abort_unless($tenant, 403, 'Akun tidak terhubung ke organisasi manapun.');
        abort_unless($tenant->is_active, 403, 'Program belum aktif. Hubungi ops.');

        return $tenant;
    }
}