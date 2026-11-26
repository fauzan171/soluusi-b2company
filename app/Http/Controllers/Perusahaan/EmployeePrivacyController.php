<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;

/**
 * wv4: employee "Privasi & Consent" — status consent + penjelasan apa yang
 * HR bisa/lihat tidak. Consent di-set HR (ProgramConsent, ada provenance);
 * model saat ini tidak menyediakan self-toggle karyawan.
 */
class EmployeePrivacyController extends Controller
{
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();

        $consents = \DB::table('program_consent_history')
            ->where('type', 'karyawan')
            ->where('roster_id', $employee->id)
            ->orderByDesc('recorded_at')
            ->limit(10)
            ->get();

        return view('perusahaan.employee-privacy', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'consents' => $consents,
        ]);
    }
}
