<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;

/**
 * wv4: employee "Hasilku" — riwayat hasil asesmen pribadi.
 */
class EmployeeResultController extends Controller
{
    public function index()
    {
        $employee = app(EmployeeController::class)->resolveEmployeePublic();

        $assessments = $employee->assessments()
            ->orderByDesc('taken_at')
            ->limit(50)
            ->get()
            // QA-LOW: instrument di-resolve 1 query (map kode→model), bukan
            // N+1 query per baris asesmen.
            ->map(function ($a) use (&$instrumentCache) {
                $instrument = $instrumentCache[$a->instrument_code]
                    ??= \App\Models\AssessmentInstrument::where('code', $a->instrument_code)->first();

                return [
                    'id' => $a->id,
                    'instrument' => $instrument?->name ?? $a->instrument_code,
                    'score' => $a->score,
                    'band' => $instrument ? $instrument->bandLabelFor((int) $a->score) : $a->band(),
                    'taken_at' => $a->taken_at,
                ];
            });

        return view('perusahaan.employee-results', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'results' => $assessments,
        ]);
    }
}
