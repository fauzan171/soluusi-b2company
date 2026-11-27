<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\InstrumentGrant;
use App\Models\Perusahaan\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * A2: alokasi instrumen assessment langsung ke perusahaan (morph 'perusahaan',
 * audience employee) oleh superadmin — independen dari program wellbeing.
 * MCMI-III (clinical) selalu ditolak: hanya per-employee via InstrumentGrant
 * (D5). Audit mengikuti pola AssessmentAssignmentController.
 */
class PerusahaanAssessmentAllocationController extends Controller
{
    private const ADDON_CODES = ['bigfive', 'disc', 'ergonomic', 'phq9', 'gad7', 'cbi', 'bfitrader', 'disc24'];

    public function index(Perusahaan $perusahaan)
    {
        // filter klinis di PHP: NOT IN men-drop baris domain NULL (bigfive/ds dll).
        $instruments = AssessmentInstrument::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->reject(fn (AssessmentInstrument $i) => $i->domain === 'clinical');

        $allocations = AssessmentAssignment::where('assignable_type', $perusahaan->getMorphClass())
            ->where('assignable_id', $perusahaan->id)
            ->where('audience', AssessmentAssignment::AUDIENCE_EMPLOYEE)
            ->whereIn('status', ['active'])
            ->get()
            ->keyBy('instrument_code');

        // MCMI grants utk karyawan perusahaan ini (form grant per-employee D5).
        $employees = $perusahaan->employees()->orderBy('name')->limit(100)->get();
        $grants = InstrumentGrant::whereIn('employee_id', $perusahaan->employees()->pluck('id'))
            ->with('employee')
            ->orderByDesc('id')
            ->get();

        return view('admin.tenants.perusahaan.assessments', [
            'perusahaan' => $perusahaan,
            'instruments' => $instruments,
            'allocations' => $allocations,
            'employees' => $employees,
            'grants' => $grants,
            'addonCodes' => self::ADDON_CODES,
        ]);
    }

    public function store(Request $r, Perusahaan $perusahaan)
    {
        $r->merge(['instrument_code' => strtolower(trim((string) $r->input('instrument_code')))]);
        $data = $r->validate([
            'instrument_code' => [
                'required', 'string', 'max:32',
                Rule::exists('assessment_instruments', 'code'),
                function ($attribute, $value, $fail) {
                    if (in_array($value, AssessmentAssignment::CLINICAL, true)
                        || AssessmentInstrument::where('code', $value)->value('domain') === 'clinical') {
                        $fail('Instrumen klinis (MCMI-III) hanya bisa diberikan per karyawan lewat grant dengan indikasi klinis, bukan massal.');
                    }
                    if (! AssessmentInstrument::where('code', $value)->value('is_active')) {
                        $fail('Instrumen ini tidak aktif.');
                    }
                },
            ],
        ]);

        $exists = AssessmentAssignment::where('assignable_type', $perusahaan->getMorphClass())
            ->where('assignable_id', $perusahaan->id)
            ->where('instrument_code', $data['instrument_code'])
            ->where('audience', AssessmentAssignment::AUDIENCE_EMPLOYEE)
            ->whereIn('status', ['active'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'instrument_code' => "Instrumen {$data['instrument_code']} sudah dialokasikan untuk perusahaan ini.",
            ]);
        }

        $assignment = AssessmentAssignment::create([
            'assignable_type' => $perusahaan->getMorphClass(),
            'assignable_id' => $perusahaan->id,
            'instrument_code' => $data['instrument_code'],
            'audience' => AssessmentAssignment::AUDIENCE_EMPLOYEE,
            'source' => in_array($data['instrument_code'], self::ADDON_CODES, true)
                ? AssessmentAssignment::SOURCE_ADDON
                : AssessmentAssignment::SOURCE_CORE,
            'round' => 1,
            'opens_at' => today(),
            'closes_at' => null,
            'status' => 'active',
            'assigned_by' => auth()->id(),
        ]);

        AdminAction::record(auth()->id(), 'perusahaan.assessment_allocation.created', $assignment, [
            'perusahaan_id' => $perusahaan->id,
            'instrument_code' => $assignment->instrument_code,
            'source' => $assignment->source,
        ]);

        return redirect()->route('admin.tenants.perusahaans.assessments', $perusahaan)
            ->with('success', "Instrumen {$assignment->instrument_code} dialokasikan ke {$perusahaan->brand}.");
    }

    public function close(Request $r, Perusahaan $perusahaan, AssessmentAssignment $assignment)
    {
        abort_unless($assignment->assignable_type === $perusahaan->getMorphClass()
            && (int) $assignment->assignable_id === $perusahaan->id, 404);

        $assignment->update(['status' => 'closed']);
        AdminAction::record(auth()->id(), 'perusahaan.assessment_allocation.closed', $assignment, [
            'perusahaan_id' => $perusahaan->id,
            'instrument_code' => $assignment->instrument_code,
        ]);

        return redirect()->route('admin.tenants.perusahaans.assessments', $perusahaan)
            ->with('success', "Alokasi {$assignment->instrument_code} ditutup.");
    }
}
