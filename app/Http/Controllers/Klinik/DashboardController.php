<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Klinik\SplitSettlement;
use Illuminate\Support\Facades\DB;

/**
 * Klinik dashboard (Build-NEW #6, M8). AGGREGATE ONLY — confidentiality inti:
 * Partner Admin/Staff TIDAK lihat konten sesi/PII individual (PRD §4.2).
 */
class DashboardController extends Controller
{
    public function index()
    {
        $klinik = $this->klinik();

        // Klinik belum di-approve ops → halaman menunggu, bukan dashboard.
        if (!$klinik->is_active) {
            return view('klinik.pending', [
                'klinik' => $klinik,
                'isSuperAdmin' => \App\Support\KlinikContext::canSwitch(auth()->user()),
            ]);
        }

        $mouAt = $klinik->mou_at ?? $klinik->created_at;

        $sessions = ConsultationSession::where('klinik_id', $klinik->id);
        $completedCount = (clone $sessions)->whereNotNull('completion_verified_at')
            ->where('completion_verified_at', '>=', $mouAt)->count();
        $grossSales = (clone $sessions)
            ->where('payment_status', ConsultationSession::PAYMENT_STATUS_VERIFIED)
            ->selectRaw('COALESCE(SUM(CAST(amount AS INTEGER)), 0) as total')
            ->value('total');

        $margin = SplitSettlement::where('klinik_id', $klinik->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'settled' THEN klinik_margin END), 0) as settled")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN klinik_margin END), 0) as pending")
            ->first();

        // Topik kategori anonim (aggregate, no individual) — note/solution TIDAK pernah dikirim ke view.
        $topics = (clone $sessions)->whereNotNull('completion_verified_at')
            ->whereNotNull('note')
            ->selectRaw("COUNT(*) as c")
            ->count(); // ponytail: topik_kategori belum ada kolomnya — Phase 2 MBC. Count saja dulu.

        // Chart: sesi completed per minggu (12 minggu terakhir).
        $weekly = (clone $sessions)->whereNotNull('completion_verified_at')
            ->where('completion_verified_at', '>=', now()->subWeeks(12)->startOfWeek())
            ->get(['completion_verified_at'])
            ->groupBy(fn ($s) => $s->completion_verified_at->copy()->startOfWeek()->format('d M'))
            ->map->count();
        // Isi minggu kosong biar chart rata.
        $weekLabels = collect(range(11, 0))->map(fn ($i) => now()->subWeeks($i)->startOfWeek());
        $chartWeekly = [
            'labels' => $weekLabels->map->format('d M')->values(),
            'data' => $weekLabels->map(fn ($w) => $weekly->get($w->format('d M'), 0))->values(),
        ];

        // Chart: breakdown status sesi.
        $statusCounts = (clone $sessions)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $statusLabels = [
            'waiting' => 'Menunggu pembayaran',
            'scheduled' => 'Terjadwal',
            'done' => 'Selesai',
            'canceled' => 'Dibatalkan',
            'failed' => 'Gagal',
        ];
        $chartStatus = [
            'labels' => $statusCounts->except('no_show')->keys()->map(fn ($s) => $statusLabels[$s] ?? ucfirst(str_replace('_', ' ', $s)))->values(),
            'data' => $statusCounts->except('no_show')->values(),
        ];

        // Activity list: sesi terbaru (AGREGAT — tanpa PII customer, confidentiality §4.2).
        $recentSessions = (clone $sessions)
            ->where('status', '!=', 'no_show')
            ->with(['consultant.user', 'schedule', 'consultantPackage', 'splitSettlement'])
            ->orderByDesc('order_at')
            ->limit(10)
            ->get();

        // Konsultan sedaerah klinik (preview — full list di /klinik/consultants).
        $nearbyConsultants = \App\Models\Consultant::query()
            ->where('status', 'accept')->where('is_active', true)
            ->with(['user.regency', 'categories'])
            ->withCount([
                'schedules' => fn ($q) => $q->where('started_at', '>', now()),
                'packages',
            ])
            ->when($klinik->regency_id, fn ($q) =>
                $q->whereHas('user', fn ($u) => $u->where('regency_id', $klinik->regency_id)))
            ->orderByDesc('schedules_count')
            ->limit(6)->get();

        // Mode ops: super admin masuk dashboard mitra butuh info internal — markup
        // rules, pervorma HPA, paket. Semua Soluusi-side (white-label: klinik
        // tidak pernah lihat markup/pervorma).
        $isSuperAdmin = \App\Support\KlinikContext::canSwitch(auth()->user());

        return view('klinik.dashboard', [
            'klinik' => $klinik,
            'completedCount' => $completedCount,
            'stageTarget' => config('klinik.hap.stage3_trigger', 40),
            'scheduledCount' => (clone $sessions)->where('status', 'scheduled')->count(),
            'grossSales' => (int) $grossSales,
            'marginSettled' => (int) $margin->settled,
            'marginPending' => (int) $margin->pending,
            'isSuperAdmin' => $isSuperAdmin,
            'pervormas' => $klinik->hapPervormas()->orderBy('stage')->get(),
            'markupRules' => $isSuperAdmin ? $klinik->markupRules()->with('package')->orderBy('consultant_package_id')->get() : collect(),
            // Health funnel ops: berapa link checkout aktif/pakai/kadaluarsa.
            'linkStats' => $isSuperAdmin ? [
                'aktif' => $klinik->checkoutLinks()->whereNull('used_at')->where('expires_at', '>', now())->count(),
                'terpakai' => $klinik->checkoutLinks()->whereNotNull('used_at')->count(),
                'kadaluarsa' => $klinik->checkoutLinks()->whereNull('used_at')->where('expires_at', '<=', now())->count(),
            ] : null,
            'chartWeekly' => $chartWeekly,
            'chartStatus' => $chartStatus,
            'recentSessions' => $recentSessions,
            'nearbyConsultants' => $nearbyConsultants,
            // Paket disusun Soluusi bersama mitra (read-only untuk klinik).
            'klinikPackages' => $klinik->klinikPackages()->orderBy('name')->get(),
        ]);
    }

    protected function klinik()
    {
        $klinik = \App\Support\KlinikContext::current();
        abort_unless($klinik, 403, 'Akun tidak terhubung ke klinik manapun.');
        return $klinik;
    }
}
