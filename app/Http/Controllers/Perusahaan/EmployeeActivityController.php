<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;

/**
 * wv4: employee "Aktivitasku" — pengumuman + campaign terkirim, dan webinar
 * program (jika model webinar tersedia — dibangun paralel di WV-B).
 */
class EmployeeActivityController extends Controller
{
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();
        $perusahaan = $employee->perusahaan;

        $campaigns = $perusahaan->campaigns()
            ->where('channel', 'announcement')
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->limit(20)
            ->get();

        // Webinar program aktif (model dibangun paralel; guard agar halaman
        // tetap jalan sebelum schema webinar tersedia).
        $webinars = collect();
        if (class_exists(\App\Models\WellbeingWebinar::class)) {
            $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
            if ($program) {
                $webinars = \App\Models\WellbeingWebinar::where('program_id', $program->id)
                    ->whereIn('status', ['planned', 'done'])
                    ->orderBy('scheduled_at')
                    ->limit(20)
                    ->get();
            }
        }

        return view('perusahaan.employee-activities', [
            'employee' => $employee,
            'perusahaan' => $perusahaan,
            'campaigns' => $campaigns,
            'webinars' => $webinars,
        ]);
    }
}
