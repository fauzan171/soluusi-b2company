<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use Illuminate\Support\Facades\DB;

/**
 * Associate (HAP) dashboard — see own tenants (klinik/perusahaan/sekolah) +
 * commission tracking across 3 verticals.
 * Confidentiality: HAP sees aggregate per tenant, NOT individual customer data.
 */
class DashboardController extends Controller
{
    /**
     * C5: map vertical slug → [tenant model, pervorma model, tenant FK].
     * Sumber kebenaran stage config: config/pervorma.php (C2).
     */
    private const VERTICALS = [
        'hap' => ['label' => 'Klinik', 'tenant' => Klinik::class, 'pervorma' => HapPervorma::class, 'fk' => 'klinik_id', 'relation' => 'hapPervormas'],
        'cap' => ['label' => 'Perusahaan', 'tenant' => Perusahaan::class, 'pervorma' => PerusahaanPervorma::class, 'fk' => 'perusahaan_id', 'relation' => 'pervormas'],
        'sap' => ['label' => 'Sekolah', 'tenant' => Sekolah::class, 'pervorma' => SekolahPervorma::class, 'fk' => 'sekolah_id', 'relation' => 'pervormas'],
    ];

    public function index()
    {
        // B3: saat superadmin view-as associate, scope data ke associate target.
        // Admin tanpa view-as ditolak (portal ini khusus associate).
        $viewAs = \App\Support\ViewAs::current();
        $user = auth()->user();
        if (($viewAs['target_role'] ?? null) === 'associate') {
            $user = \App\Models\User::findOrFail($viewAs['target_user_id']);
        } elseif ($user?->role?->name === 'admin') {
            abort(403, 'Aktifkan view-as associate untuk masuk portal ini.');
        }

        // Tenants per vertical — satu query per tabel, digabung dalam satu struktur.
        $verticals = [];
        foreach (self::VERTICALS as $slug => $def) {
            $tenants = $def['tenant']::where('associate_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            $tenantStats = [];
            foreach ($tenants as $tenant) {
                $stages = config("pervorma.{$slug}.stages");
                $pervormas = $tenant->{$def['relation']}()->orderBy('stage')->get();

                // Stage progress: hap pakai engine existing (90-hari window);
                // sap/cap pakai hitungan sesi done / mou_at sesuai trigger type.
                if ($slug === 'hap') {
                    $counts = \App\Services\Klinik\HapPervormaService::completedCounts($tenant);
                    $progress = [
                        1 => $counts['inWindow'] >= ($stages[1]['trigger'] ?? PHP_INT_MAX),
                        2 => $counts['sinceMou'] >= ($stages[2]['trigger'] ?? PHP_INT_MAX),
                        3 => $counts['sinceMou'] >= ($stages[3]['trigger'] ?? PHP_INT_MAX),
                    ];
                    $completed = $counts['sinceMou'];
                } else {
                    // SAP: komisi menghitung SEMUA sesi selesai termasuk staf; Sekolah::sessions()
                    // mengecualikan staf demi agregat wellbeing BK, jadi jangan dipakai di sini.
                    $doneCount = $slug === 'sap'
                        ? \App\Models\Sekolah\SekolahSession::where('sekolah_id', $tenant->id)->whereNotNull('completed_at')->count()
                        : $tenant->sessions()->whereNotNull('completed_at')->count();
                    $activated = $tenant->mou_at !== null;
                    $progress = [];
                    foreach ($stages as $stage => $defStage) {
                        $progress[$stage] = $defStage['trigger'] === 'mou_at'
                            ? $activated
                            : $doneCount >= (int) $defStage['trigger'];
                    }
                    $completed = $doneCount;
                }

                $tenantStats[$tenant->id] = [
                    'completed' => $completed,
                    'progress' => $progress,
                    'due' => $pervormas->where('status', 'due')->sum('amount'),
                    'paid' => $pervormas->where('status', 'paid')->sum('amount'),
                    'pervormas' => $pervormas,
                ];
            }

            $verticals[$slug] = [
                'label' => $def['label'],
                'tenants' => $tenants,
                'stats' => $tenantStats,
                'stages' => config("pervorma.{$slug}.stages"),
            ];
        }

        // Summary across all verticals
        $totalDue = collect($verticals)->flatMap(fn ($v) => $v['stats'])->sum('due');
        $totalPaid = collect($verticals)->flatMap(fn ($v) => $v['stats'])->sum('paid');

        $totalRequested = 0;
        foreach (self::VERTICALS as $def) {
            $totalRequested += $def['pervorma']::where('associate_id', $user->id)
                ->where('status', 'due')
                ->whereNotNull('requested_at')
                ->sum('amount');
        }

        $totalTenants = collect($verticals)->sum(fn ($v) => $v['tenants']->count());
        $activeTenants = collect($verticals)->sum(fn ($v) => $v['tenants']->where('is_active', true)->count());

        // Payout items gabungan 3 vertikal — tiap item diberi penanda vertical
        // untuk form action + label.
        $payoutItems = collect();
        foreach (self::VERTICALS as $slug => $def) {
            $items = $def['pervorma']::where('associate_id', $user->id)
                ->with(strtolower(class_basename($def['tenant'])))
                ->get();
            foreach ($items as $item) {
                $item->vertical = $slug;
                $payoutItems->push($item);
            }
        }
        $payoutItems = $payoutItems->sortBy([
            fn ($a, $b) => (($a->status === 'due' && !$a->requested_at) ? 0 : (($a->status === 'due') ? 1 : 2)) <=> (($b->status === 'due' && !$b->requested_at) ? 0 : (($b->status === 'due') ? 1 : 2)),
            fn ($a, $b) => ($a->due_at?->timestamp ?? PHP_INT_MAX) <=> ($b->due_at?->timestamp ?? PHP_INT_MAX),
        ])->values();

        // Aktivitas harian agregat (klinik saja — spec §16/§17.2 membatasi HPA
        // dari baris sesi individual; perusahaan/sekolah belum ada aktivitas feed).
        $kliniks = $verticals['hap']['tenants'];
        $recentSessions = ConsultationSession::whereIn('klinik_id', $kliniks->pluck('id'))
            ->where('status', '!=', ConsultationSession::STATUS_NO_SHOW)
            ->selectRaw('DATE(order_at) as activity_date, klinik_id, status, COUNT(*) as session_count')
            ->with('klinik')
            ->groupByRaw('DATE(order_at), klinik_id, status')
            ->orderByDesc('activity_date')
            ->limit(15)
            ->get();

        // Chart: sessions per klinik (legacy HAP view tetap ditampilkan)
        $perKlinik = ConsultationSession::whereIn('klinik_id', $kliniks->pluck('id'))
            ->selectRaw('klinik_id, COUNT(*) as c')
            ->groupBy('klinik_id')
            ->orderByDesc('c')
            ->get();
        $klinikNames = $kliniks->pluck('brand_name', 'id');
        $chartKlinik = [
            'labels' => $perKlinik->map(fn ($r) => $klinikNames[$r->klinik_id] ?? '—')->values(),
            'data' => $perKlinik->pluck('c')->values(),
        ];

        // Back-compat alias untuk view yang sudah ada (kliniks/klinikStats/
        // totalClinics/activeClinics dipakai oleh partial lama).
        $klinikStats = $verticals['hap']['stats'];
        $totalClinics = $verticals['hap']['tenants']->count();
        $activeClinics = $verticals['hap']['tenants']->where('is_active', true)->count();

        return view('associate.dashboard', compact(
            'verticals',
            'kliniks',
            'klinikStats',
            'totalDue',
            'totalPaid',
            'totalRequested',
            'totalClinics',
            'activeClinics',
            'totalTenants',
            'activeTenants',
            'payoutItems',
            'recentSessions',
            'chartKlinik'
        ));
    }

    public function requestPayout(string $vertical, int $pervormaId)
    {
        // F2: resolve view-as sama seperti index() — superadmin yang view-as
        // associate harus bisa trigger payout milik associate target (fitur
        // audit view-as patah di sini karena user masih admin → 403).
        $viewAs = \App\Support\ViewAs::current();
        $user = auth()->user();
        if (($viewAs['target_role'] ?? null) === 'associate') {
            $user = \App\Models\User::findOrFail($viewAs['target_user_id']);
        } elseif ($user?->role?->name === 'admin') {
            abort(403, 'Aktifkan view-as associate untuk aksi ini.');
        }

        abort_unless(isset(self::VERTICALS[$vertical]), 404);
        $model = self::VERTICALS[$vertical]['pervorma'];
        $pervorma = $model::findOrFail($pervormaId);

        abort_unless((int) $pervorma->associate_id === (int) $user->id, 403);

        if ($pervorma->status === 'paid') {
            return back()->with('info', 'Komisi ini sudah cair.');
        }

        if (!$pervorma->requested_at) {
            $pervorma->requested_at = now();
            $pervorma->requested_by = $user->id;
            $pervorma->due_at = $pervorma->due_at ?: now()->addWeekdays(7);
            $pervorma->save();
        }

        return back()->with('success', 'Permintaan pencairan dikirim. Finance memproses sesuai estimasi tanggal cair.');
    }
}
