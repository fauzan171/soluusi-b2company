<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSessionSummary;
use App\Models\ChatMessage;
use App\Models\Sekolah\SekolahSession;
use App\Services\PervormaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Chat per sesi SAP: siswa <-> konsultan (C.1). Mirror ChatController (CAP):
 * polling 5s, room = sekolah_sessions row, pesan di chat_messages.
 * BK TIDAK pernah punya akses — privacy by design (hanya agregat).
 */
class ChatController extends Controller
{
    /** Siswa/staf: buka room chat sesinya. $prefix memilih route group (sekolah.* vs sekolah.staff.*). */
    public function student(SekolahSession $session, string $prefix = 'sekolah')
    {
        $this->authorize('viewRoom', $session);

        return $this->room(
            $session,
            'Konselor',
            route($prefix.'.chat.store', $session),
            route($prefix === 'sekolah' ? 'sekolah.student' : 'sekolah.staff'),
            true,
            $prefix
        );
    }

    public function studentStore(Request $r, SekolahSession $session, string $prefix = 'sekolah')
    {
        $this->authorize('chat', $session);

        return $this->save($r, $session);
    }

    /** Konsultan: buka room chat sesi sekolah. */
    public function consultant(SekolahSession $session)
    {
        $this->authorize('viewRoom', $session);

        return $this->room($session, 'Siswa', route('consultant.sekolah.chat.store', $session), route('consultant.sessions'));
    }

    public function consultantStore(Request $r, SekolahSession $session)
    {
        $this->authorize('chat', $session);

        return $this->save($r, $session);
    }

    /** Konsultan: mulai sesi (check-in). */
    public function consultantStart(SekolahSession $session)
    {
        $this->authorize('chat', $session);

        abort_if($session->checked_in_at, 422, 'Sesi sudah dimulai.');
        abort_unless($session->status === 'scheduled' && $session->scheduled_at, 422, 'Sesi belum dijadwalkan.');

        $session->update(['checked_in_at' => now()]);

        return redirect()->route('consultant.sekolah.chat', $session)->with('success', 'Sesi dimulai.');
    }

    /** Konsultan: akhiri sesi (mark done). */
    public function consultantEnd(SekolahSession $session)
    {
        $this->authorize('chat', $session);

        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak aktif.');
        abort_unless($session->scheduled_at !== null, 422, 'Sesi belum dijadwalkan.');

        $session->update(['status' => 'done', 'completed_at' => now()]);

        PervormaService::evaluate('sap', $session->sekolah);
        GenerateSessionSummary::dispatch($session->id, 'sekolah');

        return redirect()->route('consultant.sekolah.chat', $session)->with('success', 'Sesi diakhiri.');
    }

    /** Siswa: akhiri sesi (mark done) — mirror employeeEnd CAP. */
    public function studentEnd(SekolahSession $session, string $prefix = 'sekolah')
    {
        $this->authorize('chat', $session);

        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak aktif.');
        abort_unless($session->scheduled_at !== null, 422, 'Sesi belum dijadwalkan.');

        $session->update(['status' => 'done', 'completed_at' => now()]);

        PervormaService::evaluate('sap', $session->sekolah);
        GenerateSessionSummary::dispatch($session->id, 'sekolah');

        // Room done tetap bisa dibuka (read-only) → form rating ada di sana.
        return redirect()->route($prefix.'.chat', $session)
            ->with('success', 'Sesi diakhiri. Silakan beri rating.');
    }

    /** Siswa: beri rating 1-5 setelah sesi done. */
    public function studentRate(Request $r, SekolahSession $session, string $prefix = 'sekolah')
    {
        $this->authorize('rate', $session);

        abort_if($session->rating, 422, 'Sudah dirating.');

        $r->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        $session->update(['rating' => $r->rating]);

        return redirect()->route($prefix === 'sekolah' ? 'sekolah.student' : 'sekolah.staff')
            ->with('success', 'Terima kasih atas ratingnya.');
    }

    private function room(SekolahSession $session, string $peerLabel, string $postUrl, string $backUrl, bool $isStudent = false, string $prefix = 'sekolah')
    {
        if (request()->wantsJson() || request()->has('after')) {
            return response()->json($this->payload($session, (int) request('after', 0), $peerLabel));
        }

        // Summary per-sesi (pasca done): konsultan lihat `summary`, siswa lihat `client_advice`.
        $sessionSummary = null;
        if ($session->status === 'done') {
            $row = \App\Models\SessionSummary::where('sekolah_session_id', $session->id)
                ->where('status', 'completed')
                ->first();
            if ($row) {
                $sessionSummary = $isStudent ? $row->client_advice : $row->summary;
            }
        }

        return view('chat.room', [
            'session' => $session,
            'peerLabel' => $peerLabel,
            'postUrl' => $postUrl,
            'pollUrl' => request()->url(),
            'canSend' => $this->canSend($session),
            'backUrl' => $backUrl,
            'isEmployee' => $isStudent, // view pakai nama var yang sama
            'sessionSummary' => $sessionSummary,
            'endUrl' => $isStudent ? route($prefix.'.chat.end', $session) : route('consultant.sekolah.chat.end', $session),
            'rateUrl' => route($prefix.'.chat.rate', $session),
            'startUrl' => route('consultant.sekolah.chat.start', $session),
        ]);
    }

    private function save(Request $r, SekolahSession $session)
    {
        $data = $r->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        abort_unless($this->canSend($session), 422, 'Chat terkunci sampai sesi terjadwal.');

        $msg = ChatMessage::create([
            'sekolah_session_id' => $session->id,
            'sender_id' => auth()->id(),
            'body' => trim($data['body']),
        ]);

        // ponytail: notif email ke pihak lawan saat pesan pertama sesi. Upgrade: queue + digest.
        if ($session->chatMessages()->count() === 1) {
            $senderIsStudent = $session->student?->user_id === auth()->id();
            $recipient = $senderIsStudent
                ? $session->consultant?->user
                : $session->student?->user;
            if ($recipient?->email) {
                Mail::to($recipient->email)->send(
                    new \App\Mail\ChatNewMessage(
                        $session->konsultan_type,
                        route($senderIsStudent ? 'consultant.sekolah.chat' : 'sekolah.chat', $session)
                    )
                );
            }
        }

        return response()->json(['id' => $msg->id]);
    }

    /** Chat hanya untuk sesi terjadwal (scheduled_at terisi + konsultan ada). */
    private function canSend(SekolahSession $session): bool
    {
        return $session->status === 'scheduled'
            && $session->consultant_id
            && $session->scheduled_at !== null;
    }

    /**
     * Pesan sejak id tertentu, maksimal 200 (poll incremental) + state sesi.
     * Nama siswa tidak dikirim ke konsultan room? Kebalikan — konsultan melihat
     * label 'Siswa' generik; nama siswa TIDAK dikirim (privacy, mirror CAP).
     */
    private function payload(SekolahSession $session, int $after, string $peerLabel)
    {
        $me = auth()->user();
        $myId = $me->id;
        $myName = trim($me->firstname . ' ' . $me->lastname);

        // F7 mirror: `before_id` → halaman 200 pesan sebelum id itu (load-more).
        // reorder() wajib — chatMessages() sudah orderBy('id').
        $beforeId = (int) request('before_id', 0);
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
            // D.5 parity: status baca hanya relevan untuk pesan sendiri.
            'read' => $m->sender_id === $myId && $m->read_at !== null,
        ]);

        // D.5 parity: tandai terbaca HANYA pesan lawan yang benar-benar terkirim
        // di payload ini — sisanya di luar limit 200 belum dilihat user.
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
            'peer_read_upto' => (int) $session->chatMessages()
                ->where('sender_id', $myId)->whereNotNull('read_at')->max('id'),
        ];
    }
}
