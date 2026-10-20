<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with('consultant.user')
            ->where('klinik_id', $this->klinikId())
            ->orderBy('started_at')
            ->paginate(20);

        return view('klinik.schedules.index', compact('schedules'));
    }

    public function create(Request $request)
    {
        if (!$request->filled('consultant_id')) {
            return redirect()->route('klinik.consultants.index')
                ->with('info', 'Pilih konsultan dulu untuk atur jadwal.');
        }

        $consultant = Consultant::with('user')
            ->where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->findOrFail($request->consultant_id);

        // Slot existing konsultan (untuk calendar — biar admin lihat hari yang sudah terisi).
        $existingSchedules = Schedule::where('consultant_id', $consultant->id)
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now()->subMonth())
            ->orderBy('started_at')
            ->get(['id', 'started_at', 'ended_at', 'mode']);

        return view('klinik.schedules.create', compact('consultant', 'existingSchedules'));
    }

    public function store(Request $request)
    {
        if ($request->hasAny(['date', 'time_start', 'time_end'])) {
            $parts = $request->validate([
                'date' => ['required', 'date_format:Y-m-d'],
                'time_start' => ['required', 'date_format:H:i'],
                'time_end' => ['required', 'date_format:H:i'],
            ]);
            $request->merge([
                'started_at' => $parts['date'].' '.$parts['time_start'],
                'ended_at' => $parts['date'].' '.$parts['time_end'],
            ]);
        }
        $validated = $request->validate([
            'consultant_id' => 'required|exists:consultants,id',
            'started_at' => 'required|date|after:now',
            'ended_at' => 'required|date|after:started_at',
            'mode' => 'required|in:online,offline',
            'meeting_url' => 'nullable|url',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $consultant = Consultant::where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->findOrFail($validated['consultant_id']);

        $overlap = Schedule::where('consultant_id', $consultant->id)
            ->where('status', Schedule::STATUS_CONFIRMED)
            // Interval [start, end): slot yang persis bersebelahan boleh.
            ->where('started_at', '<', $validated['ended_at'])
            ->where('ended_at', '>', $validated['started_at'])
            ->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'Konsultan sudah punya slot lain pada rentang waktu itu.');
        }

        Schedule::create([
            'consultant_id' => $consultant->id,
            'klinik_id' => $this->klinikId(),
            'created_by' => auth()->id(),
            'latest_updated_by' => 'klinik:' . auth()->id(),
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'],
            'mode' => $validated['mode'],
            'meeting_url' => $validated['meeting_url'] ?? null,
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => Schedule::STATUS_CONFIRMED,
        ]);

        return redirect()->route('klinik.consultants.show', $consultant)
            ->with('success', 'Jadwal untuk konsultan berhasil ditambahkan.');
    }

    protected function klinikId(): int
    {
        $klinik = \App\Support\KlinikContext::current();
        abort_unless($klinik, 403, 'Akun tidak terhubung ke klinik manapun.');
        return $klinik->id;
    }
}
