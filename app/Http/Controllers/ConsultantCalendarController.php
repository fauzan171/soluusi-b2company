<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan\Perusahaan;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

/**
 * W3: kalender konsultan portal — jadwal sendiri lintas perusahaan,
 * filter per perusahaan. Route role:consultant,admin (superadmin preview).
 */
class ConsultantCalendarController extends Controller
{
    public function index(Request $request)
    {
        $consultant = auth()->user()->consultants()->first();
        // W3: superadmin tanpa row konsultan boleh preview (recon-R2 fix).
        if (!$consultant && optional(auth()->user()->role)->name !== 'admin') {
            abort(403, 'Bukan konsultan.');
        }

        // Pool perusahaan aktif konsultan ini (lintas perusahaan).
        $perusahaans = Perusahaan::where('is_active', true)
            ->whereHas('pools', fn ($q) => $q->where('consultant_id', $consultant->id))
            ->orderBy('brand')->get();

        $filterId = $request->integer('perusahaan_id');
        $selected = $perusahaans->firstWhere('id', $filterId);

        $weeks = collect();
        $perusahaans->when($selected, fn ($c) => $c->where('id', $selected->id))
            ->each(function (Perusahaan $p) use ($consultant, &$weeks) {
                AvailabilityService::slotsFor($consultant, $p, now(), now()->addDays(14))
                    ->each(function ($slot) use (&$weeks, $p) {
                        $date = $slot->start->timezone(AvailabilityService::TZ)->toDateString();
                        $weeks->push([
                            'date' => $date,
                            'start' => $slot->start,
                            'end' => $slot->end,
                            'perusahaan' => $p->brand,
                        ]);
                    });
            });

        // Sesi terjadwal konsultan (lintas perusahaan) untuk kalender.
        $sessions = $consultant->pools()->pluck('perusahaan_id')->unique();
        $booked = \App\Models\Perusahaan\PerusahaanSession::where('consultant_id', $consultant->id)
            ->whereIn('status', ['scheduled', 'done'])
            ->whereNotNull('scheduled_at')
            ->with('perusahaan')
            ->orderBy('scheduled_at')->limit(100)->get();

        $slots = $weeks->sortBy(fn ($s) => $s['start']->getTimestamp())->values();

        return view('consultant.calendar', [
            'consultant' => $consultant,
            'perusahaans' => $perusahaans,
            'selected' => $selected,
            'slots' => $slots,
            'booked' => $booked,
        ]);
    }
}
