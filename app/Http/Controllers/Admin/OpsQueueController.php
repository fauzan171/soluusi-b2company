<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * A7 — Ops work queue terpadu: satu halaman read-only gabungan 4 antrean ops
 * (sesi needs_ops_review, klinik pending approval, pervorma payout due,
 * aktivasi perusahaan/sekolah pending). Filter ?type=; aksi link ke route
 * existing (verify/approve/pay/activate) — tidak ada write path baru.
 */
class OpsQueueController extends Controller
{
    public const TYPES = ['sessions', 'kliniks', 'pervormas', 'perusahaan', 'sekolah'];

    public function index(Request $request)
    {
        $type = $request->query('type');

        $sessions = ConsultationSession::where('needs_ops_review', true)
            ->whereNull('completion_verified_at')
            ->with(['consultant.user', 'klinik', 'schedule'])
            ->orderByDesc('konsultan_done_at')
            ->limit(50)->get();

        $kliniks = Klinik::where('is_active', false)
            ->orderByDesc('created_at')->limit(50)->get();

        // Payout due: status due (belum dibayar) di 3 vertikal. Tiap item
        // ditandai vertical agar form action + label tenant benar di view.
        $pervormas = collect();
        foreach ([
            'hap' => [HapPervorma::class, 'klinik'],
            'cap' => [PerusahaanPervorma::class, 'perusahaan'],
            'sap' => [SekolahPervorma::class, 'sekolah'],
        ] as $slug => [$model, $tenantRelation]) {
            $items = $model::where('status', 'due')
                ->with([$tenantRelation, 'associate'])
                ->orderBy('due_at')->limit(50)->get();
            foreach ($items as $item) {
                $item->vertical = $slug;
                $pervormas->push($item);
            }
        }
        $pervormas = $pervormas->sortBy(fn ($p) => $p->due_at?->timestamp ?? PHP_INT_MAX)->values();

        $perusahaans = Perusahaan::where('is_active', false)
            ->orderByDesc('created_at')->limit(50)->get();

        $sekolahs = Sekolah::where('is_active', false)
            ->orderByDesc('created_at')->limit(50)->get();

        $counts = [
            'sessions' => $sessions->count(),
            'kliniks' => $kliniks->count(),
            'pervormas' => $pervormas->count(),
            'perusahaan' => $perusahaans->count(),
            'sekolah' => $sekolahs->count(),
        ];

        // ?type= → hanya section itu; tanpa filter → semua.
        $active = in_array($type, self::TYPES, true) ? $type : null;

        return view('admin.ops-queue', [
            'sessions' => $active === null || $active === 'sessions' ? $sessions : collect(),
            'kliniks' => $active === null || $active === 'kliniks' ? $kliniks : collect(),
            'pervormas' => $active === null || $active === 'pervormas' ? $pervormas : collect(),
            'perusahaans' => $active === null || $active === 'perusahaan' ? $perusahaans : collect(),
            'sekolahs' => $active === null || $active === 'sekolah' ? $sekolahs : collect(),
            'counts' => $counts,
            'total' => array_sum($counts),
            'active' => $active,
        ]);
    }
}
