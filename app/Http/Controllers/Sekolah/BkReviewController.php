<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ConsultantPool;
use App\Models\Sekolah\SekolahSession;
use App\Support\SekolahContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Antrean review BK (D.2): mirror HrReviewController (F1.1).
 * Request konseling (self-request siswa B.1 / refer BK B.2) masuk status
 * 'scheduled' + scheduled_at NULL + consultant_id NULL = menunggu review BK.
 * BK approve → konselor di-assign. BK reject → cancelled + alasan.
 *
 * BK TIDAK melihat konten sesi — request_notes terlihat untuk keputusan
 * review saja (internal BK, bukan konten chat).
 */
class BkReviewController extends Controller
{
    /** Sesi menunggu review BK milik tenant aktif. */
    private function pendingQuery()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);

        return SekolahSession::with(['student', 'consultant.user', 'requestedBy'])
            ->where('sekolah_id', $sekolah->id)
            ->where('status', 'scheduled')
            ->whereNull('scheduled_at')
            ->whereNull('consultant_id');
    }

    private function resolvePending(SekolahSession $session): SekolahSession
    {
        // F1 mirror: lock row dalam transaction — guard double-approve BK.
        return DB::transaction(fn () => $this->pendingQuery()
            ->lockForUpdate()
            ->where('id', $session->id)
            ->first())
            ?? abort(422, 'Request tidak menunggu review BK.');
    }

    public function index(Request $r)
    {
        $requests = $this->pendingQuery()
            ->when($r->string('type')->trim()->toString(), fn ($q, $t) => $q->where('konsultan_type', $t))
            ->orderBy('id')
            ->get();

        $sekolah = SekolahContext::current();

        $pools = $requests->groupBy('konsultan_type')
            ->map(fn ($g) => ConsultantPool::poolFor('sekolah', $sekolah->id)
                ->where('type', $g->first()->konsultan_type)->values())
            ->filter(fn ($p) => $p->isNotEmpty());

        // Suggested konselor = pool beban terendah per request (mirror F2.3).
        $suggested = $requests->mapWithKeys(function ($s) use ($pools) {
            $pool = $pools->get($s->konsultan_type) ?? collect();
            $pick = $pool->sortBy(fn ($c) => SekolahSession::where('consultant_id', $c['id'])
                ->where('status', 'scheduled')->count())->first();

            return [$s->id => $pick];
        });

        return view('sekolah.bk-review', [
            'sekolah' => $sekolah,
            'requests' => $requests,
            'pools' => $pools,
            'suggested' => $suggested,
            'activeFilter' => $r->string('type')->trim()->toString(),
        ]);
    }

    /** Approve: assign konselor (dipilih BK, atau pool terdekat beban). */
    public function approve(Request $r, SekolahSession $session)
    {
        $pending = $this->resolvePending($session);
        $this->authorize('bkReview', $pending);
        $r->validate([
            'consultant_id' => ['nullable', 'integer'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $pool = ConsultantPool::poolFor('sekolah', $pending->sekolah_id)
            ->where('type', $pending->konsultan_type)->values();
        abort_if($pool->isEmpty(), 422, 'Tidak ada konselor tipe ini di program sekolah.');

        $suggested = $pool->sortBy(fn ($c) => SekolahSession::where('consultant_id', $c['id'])
            ->where('status', 'scheduled')->count())->first();

        if ($r->filled('consultant_id')) {
            abort_unless(ConsultantPool::validChoice('sekolah', $pending->sekolah_id,
                $r->integer('consultant_id'), $pending->konsultan_type), 422, 'Konselor tidak ada di pool program.');
            $assigned = $pool->firstWhere('id', $r->integer('consultant_id'));
        } else {
            $assigned = $suggested;
        }

        \App\Support\SessionStateMachine::assign($pending, $assigned['id']);

        $payload = [
            'consultant_id' => $assigned['id'],
            'suggested_id' => $suggested['id'],
        ];
        if ($assigned['id'] !== $suggested['id'] && $r->filled('override_reason')) {
            $payload['override_reason'] = $r->string('override_reason')->trim()->toString();
        }
        AdminAction::record($r->user()->id, 'bk_review.approve', $pending, $payload);

        return back()->with('success', 'Request disetujui — ' . $assigned['name'] . ' ditugaskan. Konselor tinggal set jadwal.');
    }

    /** Reject: cancelled + alasan (terlihat ke siswa di riwayat). */
    public function reject(Request $r, SekolahSession $session)
    {
        $pending = $this->resolvePending($session);
        $this->authorize('bkReview', $pending);
        $r->validate(['reason' => ['required', 'string', 'max:1000']]);

        \App\Support\SessionStateMachine::cancel($pending, '[BK] ' . $r->reason);

        AdminAction::record($r->user()->id, 'bk_review.reject', $pending, [
            'reason' => $r->reason,
        ]);

        return back()->with('success', 'Request ditolak. Siswa bisa mengirim request baru.');
    }
}
