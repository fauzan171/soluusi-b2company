<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Http\Request;

/**
 * Chat per sesi CAP: karyawan <-> konsultan. Room = perusahaan_sessions row.
 * Polling 5s (tanpa websocket — PHP-FPM worker mahal).
 */
class ChatController extends Controller
{
    /** Karyawan: pastikan sesi miliknya. */
    public function employee(PerusahaanSession $session)
    {
        $this->authorize('view', $session);

        return $this->room($session, "Konsultan", route('perusahaan.chat.store', $session), route('perusahaan.employee'), true);
    }

    public function employeeStore(Request $r, PerusahaanSession $session)
    {
        $this->authorize('send', $session);

        return $this->save($r, $session);
    }

    /** Konsultan: pastikan sesi miliknya. */
    public function consultant(PerusahaanSession $session)
    {
        $this->authorize('view', $session);

        // W7 (K16): akses halaman chat (non-poll) = potensi akses catatan klinis → audit.
        if (! request()->wantsJson() && ! request()->has('after') && $session->note) {
            \App\Models\AuditLog::record(auth()->user(), 'note.view', $session);
        }

        return $this->room($session, "Karyawan", route('consultant.chat.store', $session), route('consultant.sessions'));
    }

    public function consultantStore(Request $r, PerusahaanSession $session)
    {
        $this->authorize('send', $session);

        return $this->save($r, $session);
    }

    /** Konsultan: mulai sesi (check-in). */
    public function consultantStart(PerusahaanSession $session)
    {
        $this->authorize('view', $session);

        abort_if($session->checked_in_at, 422, 'Sesi sudah dimulai.');
        abort_unless($session->status === 'scheduled' && $session->scheduled_at, 422, 'Sesi belum dijadwalkan.');

        $session->update(['checked_in_at' => now()]);

        return redirect()->route('consultant.chat', $session)->with('success', 'Sesi dimulai.');
    }

    /** Konsultan: akhiri sesi (mark done). */
    public function consultantEnd(PerusahaanSession $session)
    {
        $this->authorize('end', $session);

        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak aktif.');
        abort_unless($session->scheduled_at !== null, 422, 'Sesi belum dijadwalkan.');

        $session->update(['status' => 'done', 'completed_at' => now()]);
        // W2: sesi selesai → ledger event used (reserved jadi terminal).
        // WB: routing — sesi wellbeing → ledger wellbeing; else legacy.
        if (\App\Services\WellbeingQuotaService::reservedLedgerFor($session)) {
            \App\Services\WellbeingQuotaService::consume($session, \App\Services\WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        } else {
            \App\Services\QuotaService::consume($session);
        }

        \App\Services\PervormaService::evaluate('cap', $session->perusahaan);
        \App\Jobs\GenerateSessionSummary::dispatch($session->id);

        return redirect()->route('consultant.chat', $session)->with('success', 'Sesi diakhiri.');
    }

    /** Employee: akhiri sesi (mark done). */
    public function employeeEnd(PerusahaanSession $session)
    {
        $this->authorize('end', $session);

        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak aktif.');
        abort_unless($session->scheduled_at !== null, 422, 'Sesi belum dijadwalkan.');

        $session->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);
        // W2: sesi selesai → ledger event used (reserved jadi terminal).
        // WB: routing — sesi wellbeing → ledger wellbeing; else legacy.
        if (\App\Services\WellbeingQuotaService::reservedLedgerFor($session)) {
            \App\Services\WellbeingQuotaService::consume($session, \App\Services\WellbeingQuotaService::BUCKET_EXPERT_CHAT);
        } else {
            \App\Services\QuotaService::consume($session);
        }

        \App\Services\PervormaService::evaluate('cap', $session->perusahaan);
        \App\Jobs\GenerateSessionSummary::dispatch($session->id);

        return redirect()->route('perusahaan.chat', $session)
            ->with('success', 'Sesi diakhiri. Silakan beri rating.');
    }

    /** Employee: beri rating 1-5 setelah sesi done. */
    public function employeeRate(Request $r, PerusahaanSession $session)
    {
        $this->authorize('rate', $session);

        abort_if($session->rating, 422, 'Sudah dirating.');

        $r->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        $session->update(['rating' => $r->rating]);

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Terima kasih atas ratingnya.');
    }

    /**
     * W5 (K10): konsultan/HR set link meeting per sesi (override manual).
     * Validasi: https + host meet.google.com / *.zoom.us (gmeet/zoom only).
     */
    public function meetingLink(Request $r, PerusahaanSession $session)
    {
        $user = auth()->user();
        $role = optional($user->role)->name;

        if ($role === 'consultant') {
            $consultant = $user->consultants()->first();
            abort_unless($consultant && $session->consultant_id === $consultant->id, 403);
        } elseif ($role === 'hr_admin' || $role === 'admin') {
            $tenant = \App\Support\PerusahaanContext::current();
            abort_unless($tenant && $session->perusahaan_id === $tenant->id, 403);
        } else {
            abort(403);
        }

        $r->validate([
            'meeting_url' => [
                'required', 'url', 'starts_with:https://',
                'regex:#^https://([\w-]+\.)*((meet\.google\.com)|(zoom\.us))(/|$)#i',
            ],
        ]);

        $session->update(['meeting_url' => $r->meeting_url]);

        return back()->with('success', 'Link meeting tersimpan.');
    }

    private function room(PerusahaanSession $session, string $peerLabel, string $postUrl, string $backUrl, bool $isEmployee = false)
    {
        if (request()->wantsJson() || request()->has('after')) {
            return response()->json($this->payload($session, (int) request('after', 0), $peerLabel));
        }

        // Summary per-sesi (pasca done): konsultan lihat `summary`, karyawan lihat `client_advice`.
        // HR tidak pernah melihat ini — hanya agregat via AiInsightService.
        $sessionSummary = null;
        if ($session->status === 'done') {
            $row = \App\Models\SessionSummary::where('perusahaan_session_id', $session->id)
                ->where('status', 'completed')
                ->first();
            if ($row) {
                $sessionSummary = $isEmployee ? $row->client_advice : $row->summary;
            }
        }

        return view('chat.room', [
            'session' => $session,
            'peerLabel' => $peerLabel,
            'postUrl' => $postUrl,
            'pollUrl' => request()->url(),
            'canSend' => $this->canSend($session),
            'backUrl' => $backUrl,
            'isEmployee' => $isEmployee,
            'sessionSummary' => $sessionSummary,
            // D.7: pembeda "transkrip dipurge" vs "belum pernah chat" — harus penanda
// yang ditulis job purge, bukan doesntExist() (keduanya 0 pesan).
            'transcriptPurged' => $session->transcript_purged_at !== null,
        ]);
    }

    private function save(Request $r, PerusahaanSession $session)
    {
        $data = $r->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        // W5: sender_role — bedakan sumber pesan tanpa join ke tabel identitas.
        $senderIsEmployee = $session->employee?->user_id === auth()->id();
        $msg = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => auth()->id(),
            'sender_role' => $senderIsEmployee ? 'employee' : 'consultant',
            'body' => trim($data['body']),
        ]);

        // ponytail: notif email ke pihak lawan saat pesan pertama sesi. Upgrade: queue + digest.
        if ($session->chatMessages()->count() === 1) {
            $recipient = $senderIsEmployee
                ? $session->consultant?->user
                : $session->employee?->user;
            if ($recipient?->email) {
                \Illuminate\Support\Facades\Mail::to($recipient->email)->send(
                    new \App\Mail\ChatNewMessage(
                        $session->konsultan_type,
                        route($senderIsEmployee ? 'consultant.chat' : 'perusahaan.chat', $session)
                    )
                );
            }
        }

        return response()->json(['id' => $msg->id]);
    }

    /** Chat hanya untuk sesi terjadwal yang sudah di-ACC konsultan (scheduled_at terisi). */
    private function canSend(PerusahaanSession $session): bool
    {
        return $session->status === 'scheduled'
            && $session->consultant_id
            && $session->scheduled_at !== null;
    }

    /**
     * Pesan sejak id tertentu, maksimal 200 (poll incremental) + state sesi
     * supaya UI yang kebuka ikut nutup begitu pihak lain mengakhiri.
     * Nama peserta tidak dikirim: karyawan anonim.
     * F7: `before_id` → halaman 200 pesan sebelum id itu (load-more riwayat lama).
     */
    private function payload(PerusahaanSession $session, int $after, string $peerLabel)
    {
        $me = auth()->user();
        $myId = $me->id;
        $myName = trim($me->firstname.' '.$me->lastname);

        $beforeId = (int) request('before_id', 0);
        // F7: relation chatMessages() sudah membawa orderBy('id') — reorder WAJIB.
        // Tanpa itu SQL jadi `id asc, id desc` dan LIMIT mengambil 200 pesan
        // TERLAMA (pesan baru tak pernah tampil, has_more selalu false).
        $q = $session->chatMessages()
            ->when($beforeId > 0, fn ($qq) => $qq->where('id', '<', $beforeId),
                fn ($qq) => $qq->where('id', '>', $after))
            ->reorder('id', 'desc')
            ->limit(200)
            ->get()
            ->reverse()->values(); // desc→asc: UI append kronologis

        $hasMore = $q->isNotEmpty()
            && $session->chatMessages()->where('id', '<', $q->min('id'))->exists();

        $messages = $q->map(fn (ChatMessage $m) => [
            'id' => $m->id,
            'mine' => $m->sender_id === $myId,
            'name' => $m->sender_id === $myId ? $myName : $peerLabel,
            'body' => $m->body,
            'at' => $m->created_at->format('H:i'),
            'read' => $m->sender_id === $myId && $m->read_at !== null,
        ]);

        // D.5: tandai terbaca HANYA pesan lawan yang benar-benar terkirim di payload
// ini — pesan di luar limit 200 belum dilihat user, jangan ditandai.
        $delivered = $messages->where('mine', false)->pluck('id');
        if ($delivered->isNotEmpty()) {
            $session->chatMessages()
                ->whereIn('id', $delivered)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return [
            'messages' => $messages->values(),
            // F7: ada riwayat lebih lama → UI render tombol "Muat pesan lama".
            'has_more' => $hasMore,
            'status' => $session->status,
            'can_send' => $this->canSend($session),
            // D.5: id pesan sendiri terakhir yang sudah dibaca lawan → tick live.
            'peer_read_upto' => (int) $session->chatMessages()
                ->where('sender_id', $myId)->whereNotNull('read_at')->max('id'),
        ];
    }
}