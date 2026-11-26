<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\SessionSlotProposal;
use App\Support\SlotOverlap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * F1.2: konsultan propose 2-3 opsi slot untuk request yang sudah di-ACC HR
 * (status scheduled + consultant assigned + scheduled_at NULL).
 * Employee/HR pilih salah satu → sesi di-book (di luar scope F1.2).
 */
class SlotProposalController extends Controller
{
    /** Form propose slots. */
    public function create(PerusahaanSession $session)
    {
        $this->resolve($session);

        return view('consultant.slot-proposals.create', ['session' => $session]);
    }

    /** Simpan proposal. */
    public function store(Request $r, PerusahaanSession $session)
    {
        $consultant = $this->resolve($session);

        $data = $r->validate([
            'slots' => ['required', 'array', 'min:2', 'max:3'],
            'slots.*.start' => ['required', 'date', 'after:now'],
            'slots.*.end' => ['required', 'date', 'after:slots.*.start'],
        ]);

        $slots = collect($data['slots'])->map(fn ($s) => [
            'start' => Carbon::parse($s['start']),
            'end' => Carbon::parse($s['end']),
        ]);

        // Tolak bila ada slot yang overlap dengan booking existing konsultan.
        foreach ($slots as $slot) {
            if (SlotOverlap::exists($consultant->id, $slot['start'], $slot['end'])) {
                throw ValidationException::withMessages([
                    'slots' => 'Slot ' . $slot['start']->format('d M Y H:i') . ' bentrok dengan jadwal kamu yang lain.',
                ]);
            }
        }

        SessionSlotProposal::create([
            'session_id' => $session->id,
            'consultant_id' => $consultant->id,
            'proposed_at' => now(),
            'slots' => $slots->map(fn ($s) => [
                'start' => $s['start']->toDateTimeString(),
                'end' => $s['end']->toDateTimeString(),
            ])->all(),
            'status' => SessionSlotProposal::STATUS_PENDING,
        ]);
        // B2B-notify: slot diusulkan → email karyawan (pilih jadwal, no health detail).
        \App\Services\SessionNotify::slotProposed($session->withoutRelations());

        return redirect()->route('consultant.sessions')
            ->with('success', 'Opsi jadwal dikirim. Menunggu karyawan/HR memilih salah satu.');
    }

    /** Guard: milik konsultan ini + menunggu penjadwalan. */
    private function resolve(PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant, 403, 'Bukan konsultan.');
        abort_unless($session->consultant_id === $consultant->id, 403);
        abort_unless($session->status === 'scheduled' && $session->scheduled_at === null,
            422, 'Sesi sudah dijadwalkan atau tidak menunggu penjadwalan.');

        return $consultant;
    }

}
