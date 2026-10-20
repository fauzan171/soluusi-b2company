<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use Illuminate\Support\Facades\DB;

/**
 * Associate (HAP) dashboard — see own clinics + commission tracking.
 * Confidentiality: HAP sees aggregate per clinic, NOT individual customer data.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Clinics owned by this associate
        $kliniks = Klinik::where('associate_id', $user->id)
            ->withCount(['checkoutLinks', 'splitSettlements'])
            ->orderByDesc('created_at')
            ->get();

        // Per-klinik stats — window 90 hari sejak MoU (PDF Doc 3). Stage 1 dari
        // sesi dalam window; Stage 2/3 dari semua sesi sejak MoU (tidak hangus).
        $klinikStats = [];

        foreach ($kliniks as $klinik) {
            $mouAt = $klinik->mou_at ? \Carbon\Carbon::parse($klinik->mou_at) : $klinik->created_at;
            $windowEnd = $mouAt->copy()->addDays(config('klinik.hap.window_days', 90));

            $base = ConsultationSession::where('klinik_id', $klinik->id)
                ->whereNotNull('completion_verified_at')
                ->where('completion_verified_at', '>=', $mouAt);
            $completedSinceMou = (clone $base)->count();
            $completedInWindow = (clone $base)
                ->where('completion_verified_at', '<=', $windowEnd)->count();

            // Pervorma stages for this clinic
            $pervormas = $klinik->hapPervormas()->orderBy('stage')->get();
            $totalDue = $pervormas->where('status', 'due')->sum('amount');
            $totalPaid = $pervormas->where('status', 'paid')->sum('amount');

            // Stage progress (5/25/40)
            $stage1Reached = $completedInWindow >= 5;
            $stage2Reached = $completedSinceMou >= 25;
            $stage3Reached = $completedSinceMou >= 40;

            $klinikStats[$klinik->id] = [
                'completed' => $completedSinceMou,
                'stage1' => $stage1Reached,
                'stage2' => $stage2Reached,
                'stage3' => $stage3Reached,
                'due' => $totalDue,
                'paid' => $totalPaid,
                'pervormas' => $pervormas,
            ];
        }

        // Summary across all clinics
        $totalDue = collect($klinikStats)->sum('due');
        $totalPaid = collect($klinikStats)->sum('paid');
        $totalRequested = HapPervorma::where('associate_id', $user->id)
            ->where('status', 'due')
            ->whereNotNull('requested_at')
            ->sum('amount');
        $totalClinics = $kliniks->count();
        $activeClinics = $kliniks->where('is_active', true)->count();

        $payoutItems = HapPervorma::where('associate_id', $user->id)
            ->with('klinik')
            ->orderByRaw("case when status = 'due' and requested_at is null then 0 when status = 'due' then 1 else 2 end")
            ->orderBy('due_at')
            ->get();

        // Aktivitas harian agregat. HPA tidak menerima baris sesi individual
        // maupun identitas customer/konsultan (Spec §16/§17.2).
        $recentSessions = ConsultationSession::whereIn('klinik_id', $kliniks->pluck('id'))
            ->where('status', '!=', ConsultationSession::STATUS_NO_SHOW)
            ->selectRaw('DATE(order_at) as activity_date, klinik_id, status, COUNT(*) as session_count')
            ->with('klinik')
            ->groupByRaw('DATE(order_at), klinik_id, status')
            ->orderByDesc('activity_date')
            ->limit(15)
            ->get();

        // Chart: sessions per clinic
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

        return view('associate.dashboard', compact(
            'kliniks',
            'klinikStats',
            'totalDue',
            'totalPaid',
            'totalRequested',
            'totalClinics',
            'activeClinics',
            'payoutItems',
            'recentSessions',
            'chartKlinik'
        ));
    }

    public function requestPayout(HapPervorma $pervorma)
    {
        $user = auth()->user();

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
