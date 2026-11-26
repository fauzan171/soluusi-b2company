<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanSession;
use App\Support\Ics;
use Illuminate\Support\Carbon;

/**
 * Download .ics sesi (employee/konsultan pemilik sesi saja — 403 selain itu).
 * METHOD:REQUEST + SEQUENCE = schedule_version → kalender update event yang
 * sama saat reschedule, bukan duplikat.
 */
class SessionCalendarController extends Controller
{
    public function show(PerusahaanSession $session)
    {
        abort_unless($session->scheduled_at, 422, 'Sesi belum terjadwal.');

        if (auth()->user()?->role?->name === 'consultant') {
            $consultant = auth()->user()->consultants()->first();
            abort_unless($consultant && $session->consultant_id === $consultant->id, 403);
        } else {
            // Employee: pemilik sesi (tenant scope sudah memfilter lintas tenant,
            // owner check ini menutup sesame employee satu tenant).
            $rows = \App\Models\Perusahaan\PerusahaanEmployee::where('user_id', auth()->id())->pluck('id');
            abort_unless($rows->contains($session->employee_id), 403);
        }

        $end = $session->ended_at
            ?? $session->scheduled_at->copy()->addMinutes(
                ($session->consultation_type ?: 'chat') === 'chat' ? PerusahaanSession::CHAT_MINUTES : 60
            );

        $location = ($session->mode ?? 'online') === 'offline'
            ? ($session->location ?: $session->room?->name)
            : ($session->meeting_url ?: route('perusahaan.employee'));

        $ics = Ics::event(
            $session->id,
            $session->scheduled_at,
            $end,
            summary: 'Sesi konsultasi Soluusi',
            location: $location,
            url: route($session->employee ? 'perusahaan.chat' : 'consultant.chat', $session),
            sequence: max(0, (int) ($session->schedule_version ?? 0)),
        );

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="soluusi-session-'.$session->id.'.ics"',
        ]);
    }
}
