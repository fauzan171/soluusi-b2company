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

        return $this->room($session, "Konsultan", route('perusahaan.chat.store', $session), route('perusahaan.employee'), true);
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

    /** Konsultan: mulai sesi (check-in). */
    public function consultantStart(PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        abort_if($session->checked_in_at, 422, 'Sesi sudah dimulai.');
        abort_unless($session->status === 'scheduled' && $session->scheduled_at, 422, 'Sesi belum dijadwalkan.');

        $session->update(['checked_in_at' => now()]);

        return redirect()->route('consultant.chat', $session)->with('success', 'Sesi dimulai.');
    }

    /** Konsultan: akhiri sesi (mark done). */
    public function consultantEnd(PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        abort_unless($session->status === 'scheduled', 422, 'Sesi tidak aktif.');

        $session->update(['status' => 'done', 'completed_at' => now()]);

        return redirect()->route('consultant.chat', $session)->with('success', 'Sesi diakhiri.');
    }

    /** Employee: akhiri sesi (mark done). */
    public function employeeEnd(PerusahaanSession $session)
    {
        $employee = PerusahaanEmployee::where('user_id', auth()->id())->first();
        abort_unless($employee && $session->employee_id === $employee->id, 403);

        abort_if($session->status === 'done', 422, 'Sesi sudah selesai.');

        $session->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return redirect()->route('perusahaan.chat', $session)
            ->with('success', 'Sesi diakhiri. Silakan beri rating.');
    }

    /** Employee: beri rating 1-5 setelah sesi done. */
    public function employeeRate(Request $r, PerusahaanSession $session)
    {
        $employee = PerusahaanEmployee::where('user_id', auth()->id())->first();
        abort_unless($employee && $session->employee_id === $employee->id, 403);

        abort_unless($session->status === 'done', 422, 'Hanya sesi selesai yang bisa dirating.');
        abort_if($session->rating, 422, 'Sudah dirating.');

        $r->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        $session->update(['rating' => $r->rating]);

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Terima kasih atas ratingnya.');
    }

    private function room(PerusahaanSession $session, string $peerLabel, string $postUrl, string $backUrl, bool $isEmployee = false)
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
            'isEmployee' => $isEmployee,
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

        // ponytail: notif email ke pihak lawan saat pesan pertama sesi. Upgrade: queue + digest.
        if ($session->chatMessages()->count() === 1) {
            $senderIsEmployee = $session->employee?->user_id === auth()->id();
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
                'name' => $m->sender_id === auth()->id() ? trim(auth()->user()->firstname.' '.auth()->user()->lastname) : $peerLabel,
                'body' => $m->body,
                'at' => $m->created_at->format('H:i'),
            ]);
    }
}