<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\PerusahaanSession;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Antrean review HR (F1.1): request konsultasi employee masuk status
 * 'scheduled' + scheduled_at NULL + consultant_id NULL = menunggu review HR.
 * HR approve → consultant di-assign (konsultan tetap ACC jadwal via schedulePerusahaan,
 * pattern lama). HR reject → 'cancelled' + cancel_reason (pakai nilai enum existing;
 * CHECK constraint tidak mengizinkan 'rejected' — lihat migrasi 2026_10_21).
 */
class HrReviewController extends Controller
{
    /** Sesi menunggu review HR milik tenant aktif. */
    private function pendingQuery()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403);

        return PerusahaanSession::with(['employee', 'consultant.user'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('status', 'scheduled')
            ->whereNull('scheduled_at')
            ->whereNull('consultant_id');
    }

    private function resolvePending(PerusahaanSession $session): PerusahaanSession
    {
        // F1: lock row dalam transaction — dua HR approve bersamaan, yang kedua
        // baca state terbaru (udah ke-assign) → ditolak 422, bukan numpuk assignment.
        return DB::transaction(fn () => $this->pendingQuery()
            ->lockForUpdate()
            ->where('id', $session->id)
            ->first())
            ?? abort(422, 'Request tidak menunggu review HR.');
    }

    public function index(Request $r)
    {
        $requests = $this->pendingQuery()
            ->when($r->string('type')->trim()->toString(), fn ($q, $t) => $q->where('konsultan_type', $t))
            ->orderBy('id')
            ->get();

        $perusahaan = PerusahaanContext::current();

        $pools = $requests->groupBy('konsultan_type')
            ->map(fn ($g) => ConsultantPool::poolFor('perusahaan', $perusahaan->id)
                ->where('type', $g->first()->konsultan_type)->values())
            ->filter(fn ($p) => $p->isNotEmpty());

        // F2.3: suggested consultant = pool beban terendah per request (kalkulasi sekali per tipe).
        $suggested = $requests->mapWithKeys(function ($s) use ($pools) {
            $pool = $pools->get($s->konsultan_type) ?? collect();
            $pick = $pool->sortBy(fn ($c) => PerusahaanSession::where('consultant_id', $c['id'])
                ->where('status', 'scheduled')->count())->first();

            return [$s->id => $pick];
        });

        return view('perusahaan.hr-review', [
            'perusahaan' => $perusahaan,
            'requests' => $requests,
            'pools' => $pools,
            'suggested' => $suggested,
            'activeFilter' => $r->string('type')->trim()->toString(),
        ]);
    }

    /** Approve: assign consultant (dipilih HR, atau pool terdekat beban). */
    public function approve(Request $r, PerusahaanSession $session)
    {
        $pending = $this->resolvePending($session);
        $r->validate([
            'consultant_id' => ['nullable', 'integer'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $pool = ConsultantPool::poolFor('perusahaan', $pending->perusahaan_id)
            ->where('type', $pending->konsultan_type)->values();
        abort_if($pool->isEmpty(), 422, 'Tidak ada konsultan tipe ini di program kamu.');

        // Suggested = beban terendah (selalu dikalkulasi, untuk audit).
        $suggested = $pool->sortBy(fn ($c) => PerusahaanSession::where('consultant_id', $c['id'])
            ->where('status', 'scheduled')->count())->first();

        if ($r->filled('consultant_id')) {
            abort_unless(ConsultantPool::validChoice('perusahaan', $pending->perusahaan_id,
                $r->integer('consultant_id'), $pending->konsultan_type), 422, 'Konsultan tidak ada di pool program.');
            $assigned = $pool->firstWhere('id', $r->integer('consultant_id'));
        } else {
            $assigned = $suggested;
        }

        \App\Support\SessionStateMachine::assign($pending, $assigned['id']);

        // Audit payload: suggested_id selalu ada; override_reason hanya bila beda dari suggested.
        $payload = [
            'consultant_id' => $assigned['id'],
            'suggested_id' => $suggested['id'],
        ];
        if ($assigned['id'] !== $suggested['id'] && $r->filled('override_reason')) {
            $payload['override_reason'] = $r->string('override_reason')->trim()->toString();
        }
        AdminAction::record($r->user()->id, 'hr_review.approve', $pending, $payload);

        return back()->with('success', 'Request disetujui — ' . $assigned['name'] . ' ditugaskan. Konsultan tinggal set jadwal.');
    }

    /** Reject: cancelled + alasan (terlihat ke employee di riwayat). */
    public function reject(Request $r, PerusahaanSession $session)
    {
        $pending = $this->resolvePending($session);
        $r->validate(['reason' => ['required', 'string', 'max:1000']]);

        // FIX (review wb REV#2): cancel path ini tidak me-release ledger padahal
        // requestConsultation sudah reserve saat request dibuat — kuota kontrak
        // WB bocor permanen (legacy self-heal via reset bulanan, WB tidak).
        \App\Support\SessionStateMachine::cancel($pending, '[HR] ' . $r->reason);
        if (\App\Services\WellbeingQuotaService::reservedLedgerFor($pending)) {
            \App\Services\WellbeingQuotaService::release($pending, 'hr reject');
        } else {
            \App\Services\QuotaService::release($pending, 'hr reject');
        }

        // Reason = free-text HR, sudah terlihat ke employee di cancel_reason —
        // dicatat apa adanya (tidak menambah eksposur PII baru).
        AdminAction::record($r->user()->id, 'hr_review.reject', $pending, [
            'reason' => $r->reason,
        ]);

        return back()->with('success', 'Request ditolak. Karyawan bisa mengirim request baru.');
    }
}
