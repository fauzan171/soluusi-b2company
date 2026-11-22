<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Models\ConsultantAvailabilityException;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\Perusahaan;
use Illuminate\Http\Request;

/**
 * Superadmin: assignment konsultan↔perusahaan (contract CRUD, K6) +
 * workhour template CRUD (K7) + availability exception CRUD.
 * Route group role:admin — HR hanya lihat (tidak punya akses route ini).
 */
class AdminAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $perusahaans = Perusahaan::orderBy('name')->get();
        $selected = Perusahaan::find($request->input('perusahaan_id')) ?: $perusahaans->first();

        $assignments = collect();
        $exceptions = collect();
        $consultants = collect();
        if ($selected) {
            $assignments = ConsultantPool::where('perusahaan_id', $selected->id)
                ->with(['consultant.user', 'workHours'])
                ->get();
            $consultantIds = $assignments->pluck('consultant_id');
            $exceptions = ConsultantAvailabilityException::whereIn('consultant_id', $consultantIds)
                ->with('consultant.user')->orderBy('date')->get();
        }
        $consultants = Consultant::where('status', Consultant::ACCEPT)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->with('user')->orderBy('id')->get();

        return view('admin.assignments', compact('perusahaans', 'selected', 'assignments', 'exceptions', 'consultants'));
    }

    /** Assign konsultan ke perusahaan dengan kontrak (K6). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'perusahaan_id' => ['required', 'integer', 'exists:perusahaans,id'],
            'consultant_id' => ['required', 'integer', 'exists:consultants,id'],
            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
        ]);

        abort_if(ConsultantPool::where('perusahaan_id', $data['perusahaan_id'])
            ->where('consultant_id', $data['consultant_id'])->exists(), 422, 'Konsultan sudah di-assign.');

        ConsultantPool::create([
            'perusahaan_id' => $data['perusahaan_id'],
            'consultant_id' => $data['consultant_id'],
            'contract_start' => $data['contract_start'] ?? null,
            'contract_end' => $data['contract_end'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Konsultan di-assign ke perusahaan.');
    }

    /** Akhiri assignment (contract status ended). */
    public function end(ConsultantPool $assignment)
    {
        abort_if($assignment->status === 'ended', 422, 'Assignment sudah berakhir.');
        $assignment->update(['status' => 'ended']);
        return back()->with('success', 'Assignment diakhiri.');
    }

    /** Aktifkan ulang assignment yang ended. */
    public function activate(ConsultantPool $assignment)
    {
        $assignment->update(['status' => 'active']);
        return back()->with('success', 'Assignment diaktifkan.');
    }

    /** Template jam kerja mingguan per assignment (K7). */
    public function storeWorkHour(Request $request)
    {
        $data = $request->validate([
            'consultant_pool_id' => ['required', 'integer', 'exists:consultant_pools,id'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
        ]);

        ConsultantWorkHour::create([
            'consultant_pool_id' => $data['consultant_pool_id'],
            'weekday' => $data['weekday'],
            'start_time' => $data['start_time'].':00',
            'end_time' => $data['end_time'].':00',
            'slot_minutes' => $data['slot_minutes'] ?? 60,
        ]);

        return back()->with('success', 'Jam kerja ditambahkan.');
    }

    public function destroyWorkHour(ConsultantWorkHour $workHour)
    {
        $workHour->delete();
        return back()->with('success', 'Jam kerja dihapus.');
    }

    /** Exception ketersediaan global per konsultan (K7) — NULL time = full-day. */
    public function storeException(Request $request)
    {
        $data = $request->validate([
            'consultant_id' => ['required', 'integer', 'exists:consultants,id'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        ConsultantAvailabilityException::create([
            'consultant_id' => $data['consultant_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'] ? $data['start_time'].':00' : null,
            'end_time' => $data['end_time'] ? $data['end_time'].':00' : null,
        ]);

        return back()->with('success', 'Exception ketersediaan ditambahkan.');
    }

    public function destroyException(ConsultantAvailabilityException $exception)
    {
        $exception->delete();
        return back()->with('success', 'Exception dihapus.');
    }

    /** Toggle feature flag lock request per perusahaan (K8). */
    public function toggleRequest(Perusahaan $perusahaan)
    {
        $perusahaan->update(['consultant_request_enabled' => !$perusahaan->consultant_request_enabled]);
        return back()->with('success', $perusahaan->fresh()->consultant_request_enabled
            ? 'Permintaan konsultan DIBUKA.'
            : 'Permintaan konsultan DIKUNCI.');
    }
}
