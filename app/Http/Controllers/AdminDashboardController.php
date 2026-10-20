<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $flagged = ConsultationSession::where('needs_ops_review', true)
            ->whereNull('completion_verified_at')
            ->with(['consultant.user', 'klinik', 'schedule'])
            ->orderByDesc('konsultan_done_at')
            ->paginate(20);

        $pendingKliniks = Klinik::where('is_active', false)
            ->orderByDesc('created_at')->get();

        // Chart: sesi per hari (30 hari) platform-wide.
        $daily = ConsultationSession::where('order_at', '>=', now()->subDays(30)->startOfDay())
            ->get(['order_at'])
            ->groupBy(fn ($s) => $s->order_at->format('d M'))
            ->map->count();
        $dayLabels = collect(range(29, 0))->map(fn ($i) => now()->subDays($i));
        $chartDaily = [
            'labels' => $dayLabels->map->format('d M')->values(),
            'data' => $dayLabels->map(fn ($d) => $daily->get($d->format('d M'), 0))->values(),
        ];

        // Chart: sesi per klinik (top 8 by completed).
        $perKlinik = ConsultationSession::whereNotNull('klinik_id')
            ->selectRaw('klinik_id, COUNT(*) as c')
            ->groupBy('klinik_id')->orderByDesc('c')->limit(8)->get();
        $klinikNames = Klinik::whereIn('id', $perKlinik->pluck('klinik_id'))->pluck('brand', 'id');
        $chartKlinik = [
            'labels' => $perKlinik->map(fn ($r) => $klinikNames[$r->klinik_id] ?? '—')->values(),
            'data' => $perKlinik->pluck('c')->values(),
        ];

        // Stats + activity.
        $activeKliniks = Klinik::where('is_active', true)->count();
        $totalSessions = ConsultationSession::count();
        $verifiedRevenue = ConsultationSession::where('payment_status', 'verified')
            ->selectRaw('COALESCE(SUM(CAST(payment AS INTEGER)), 0) as rev')->value('rev');

        $recentSessions = ConsultationSession::with(['consultant.user', 'klinik', 'schedule'])
            ->orderByDesc('order_at')->limit(10)->get();

        $pervormasDue = HapPervorma::where('status', 'due')
            ->with(['klinik', 'associate'])->orderBy('due_at')->limit(10)->get();

        $splitsPending = \App\Models\Klinik\SplitSettlement::where('status', 'pending')
            ->with(['klinik', 'session'])
            ->orderBy('created_at')->limit(10)->get();

        $recentKliniks = Klinik::orderByDesc('created_at')->limit(5)->get();

        return view('admin.dashboard', compact(
            'flagged', 'pendingKliniks', 'chartDaily', 'chartKlinik',
            'activeKliniks', 'totalSessions', 'verifiedRevenue',
            'recentSessions', 'pervormasDue', 'recentKliniks', 'splitsPending'
        ));
    }

    /**
     * Overview referral/afiliasi HAP → klinik (business-owner view). Tujuan:
     * lihat klinik siapa yang dibawa HAP mana, dan apakah kliniknya benar-benar
     * ramai order konsultan — payout pervorma harus sebanding dengan aktivitas.
     * Klinik 0 sesi selesai 90 hari = flag "cek bodong".
     */
    public function referralOverview()
    {
        $kliniks = Klinik::with('associate')->orderBy('brand')->get();

        // Aktivitas per klinik (90 hari terakhir + lifetime), satu query group-by.
        $stats = ConsultationSession::whereNotNull('klinik_id')
            ->selectRaw("
                klinik_id,
                COUNT(*) as total_sessions,
                SUM(CASE WHEN completion_verified_at IS NOT NULL THEN 1 ELSE 0 END) as completed_total,
                SUM(CASE WHEN completion_verified_at IS NOT NULL AND completion_verified_at >= ? THEN 1 ELSE 0 END) as completed_90,
                SUM(CASE WHEN payment_status = 'verified' THEN CAST(payment AS INTEGER) ELSE 0 END) as revenue_verified,
                MAX(order_at) as last_order_at
            ", [now()->subDays(90)])
            ->groupBy('klinik_id')
            ->get()
            ->keyBy('klinik_id');

        // Pervorma terakumulasi per klinik: berapa yang sudah dibayar vs jatuh tempo.
        $pervorma = HapPervorma::selectRaw("
                klinik_id,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_total,
                SUM(CASE WHEN status = 'due' THEN amount ELSE 0 END) as due_total
            ")->groupBy('klinik_id')->get()->keyBy('klinik_id');

        return view('admin.referral-overview', compact('kliniks', 'stats', 'pervorma'));
    }

    /**
     * Tracking konsultan per mitra klinik (brief: "berapa konsultasi per mitra
     * klinik"). Baris = (klinik × konsultan), kolom = total/completed/no-show,
     * revenue, last session. Superadmin only.
     */
    public function consultantPerKlinik()
    {
        $rows = ConsultationSession::whereNotNull('klinik_id')
            ->selectRaw("
                klinik_id,
                consultant_id,
                COUNT(*) as total_sessions,
                SUM(CASE WHEN completion_verified_at IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_sessions,
                SUM(CASE WHEN payment_status = 'verified' THEN CAST(payment AS INTEGER) ELSE 0 END) as verified_revenue,
                MAX(order_at) as last_session_at
            ")
            ->groupBy('klinik_id', 'consultant_id')
            ->orderByDesc('completed_sessions')
            ->get();

        $kliniks = Klinik::whereIn('id', $rows->pluck('klinik_id')->unique())->get()->keyBy('id');
        $consultants = Consultant::with('user')->whereIn('id', $rows->pluck('consultant_id')->unique())->get()->keyBy('id');

        return view('admin.consultant-per-klinik', compact('rows', 'kliniks', 'consultants'));
    }

    /**
     * Sesi-sesi yang berkontribusi ke pervorma HAP per stage (brief: "tandai
     * karena dia dapat cost dari sana"). Filter: ?klinik_id= & ?stage=.
     */
    public function pervormaSessions()
    {
        $sessions = ConsultationSession::whereNotNull('hap_pervorma_stage')
            ->with(['klinik', 'consultant.user', 'schedule'])
            ->when(request('klinik_id'), fn ($q) => $q->where('klinik_id', request('klinik_id')))
            ->when(request('stage'), fn ($q) => $q->where('hap_pervorma_stage', request('stage')))
            ->orderByDesc('completion_verified_at')
            ->paginate(50)
            ->withQueryString();

        $kliniks = Klinik::orderBy('brand')->get(['id', 'brand']);

        return view('admin.pervorma-sessions', compact('sessions', 'kliniks'));
    }
}
