<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Perusahaan\PerusahaanEmployee;
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
        $user = auth()->user();
        $employee = PerusahaanEmployee::where('user_id', $user->id)->first();
        abort_unless($employee && $session->employee_id === $employee->id, 403);

        return $this->room($session, "Konsultan", route('perusahaan.chat.store', $session), route('perusahaan.employee'));
    }

    public function employeeStore(Request $r, PerusahaanSession $session)
    {
        $user = auth()->user();
        $employee = PerusahaanEmployee::where('user_id', $user->id)->first();
        abort_unless($employee && $session->employee_id === $employee->id, 403);

        return $this->save($r, $session);
    }

    /** Konsultan: pastikan sesi miliknya. */
    public function consultant(PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        return $this->room($session, "Karyawan", route('consultant.chat.store', $session), route('consultant.sessions'));
    }

    public function consultantStore(Request $r, PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        return $this->save($r, $session);
    }

    private function room(PerusahaanSession $session, string $peerLabel, string $postUrl, string $backUrl)
    {
        if (request()->wantsJson() || request()->has('after')) {
            return response()->json($this->payload($session, (int) request('after', 0), $peerLabel));
        }

        return view('chat.room', [
            'session' => $session,
            'peerLabel' => $peerLabel,
            'postUrl' => $postUrl,
            'pollUrl' => request()->url(),
            'canSend' => $this->canSend($session),
            'backUrl' => $backUrl,
        ]);
    }

    private function save(Request $r, PerusahaanSession $session)
    {
        abort_unless($this->canSend($session), 422, 'Chat hanya aktif untuk sesi terjadwal.');

        $data = $r->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = ChatMessage::create([
            'perusahaan_session_id' => $session->id,
            'sender_id' => auth()->id(),
            'body' => trim($data['body']),
        ]);

        return response()->json(['id' => $msg->id]);
    }

    /** Chat hanya untuk sesi terjadwal yang sudah punya konsultan. */
    private function canSend(PerusahaanSession $session): bool
    {
        return $session->status === 'scheduled'
            && $session->consultant_id;
    }

    /** Pesan sejak id tertentu, maksimal 200 (poll incremental). Nama peserta tidak dikirim: karyawan anonim. */
    private function payload(PerusahaanSession $session, int $after, string $peerLabel)
    {
        return $session->chatMessages()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === auth()->id(),
                'name' => $peerLabel,
                'body' => $m->body,
                'at' => $m->created_at->format('H:i'),
            ]);
    }
}