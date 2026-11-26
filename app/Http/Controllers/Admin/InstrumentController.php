<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\PerusahaanEmployee;
use Illuminate\Http\Request;

/**
 * wv1: admin instrumen assessment — list + toggle aktif + grant/revoke MCMI
 * per employee (clinical indication-based, NO mass-test). Admin portal saja.
 */
class InstrumentController extends Controller
{
    public function index(Request $r)
    {
        $instruments = AssessmentInstrument::orderBy('id')->get()
            ->map(function ($instrument) {
                $instrument->item_count = \App\Models\ScreeningQuestion::where('instrument_code', $instrument->code)->count();
                return $instrument;
            });

        $q = $r->input('q');
        $employees = PerusahaanEmployee::query()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('id')->limit(20)->get();

        $grants = InstrumentGrant::with('employee')->orderByDesc('id')->limit(50)->get();

        return view('admin.instruments.index', [
            'instruments' => $instruments,
            'employees' => $employees,
            'grants' => $grants,
            'q' => $q,
        ]);
    }

    public function toggle(AssessmentInstrument $instrument)
    {
        // SEC MED-1 (audit 2026-11-23): instrumen klinis (MCMI-III) tidak boleh
        // diaktifkan massal via toggle — akses tetap grant-only per karyawan
        // (indication-based). Menonaktifkan boleh.
        abort_if($instrument->domain === 'clinical' && ! $instrument->is_active, 403,
            'Instrumen klinis hanya bisa diakses lewat grant per karyawan (indication-based), tidak bisa diaktifkan massal.');

        $instrument->update(['is_active' => ! $instrument->is_active]);
        AdminAction::record(auth()->id(), 'instrument.toggled', $instrument, [
            'is_active' => $instrument->is_active,
        ]);
        return redirect()->route('admin.instruments.index')
            ->with('success', 'Instrumen '.$instrument->name.' '.($instrument->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    public function storeGrant(Request $r)
    {
        $validated = $r->validate([
            'employee_id' => [
                'required', 'integer',
                'exists:perusahaan_employees,id',
                function ($attribute, $value, $fail) {
                    if (InstrumentGrant::where('employee_id', $value)
                        ->where('instrument_code', request('instrument_code'))->exists()) {
                        $fail('Grant untuk karyawan dan instrumen ini sudah ada.');
                    }
                },
            ],
            'instrument_code' => ['required', 'string', 'exists:assessment_instruments,code'],
            // D5: grant klinis wajib indikasi klinis (max 500).
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $grant = InstrumentGrant::create([
            'employee_id' => $validated['employee_id'],
            'instrument_code' => $validated['instrument_code'],
            'reason' => $validated['reason'],
            'granted_by' => auth()->id(),
        ]);
        AdminAction::record(auth()->id(), 'instrument.granted', $grant, [
            'employee_id' => $grant->employee_id,
            'instrument_code' => $grant->instrument_code,
        ]);

        return redirect()->route('admin.instruments.index')
            ->with('success', 'Grant akses instrumen diberikan.');
    }

    public function destroyGrant(InstrumentGrant $grant)
    {
        AdminAction::record(auth()->id(), 'instrument.grant_revoked', $grant, [
            'employee_id' => $grant->employee_id,
            'instrument_code' => $grant->instrument_code,
        ]);
        $grant->delete();
        return redirect()->route('admin.instruments.index')
            ->with('success', 'Grant akses instrumen dicabut.');
    }
}
