<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\PerusahaanContext;

class ReportController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        return view('perusahaan.report', ['perusahaan' => $perusahaan] + ProgramAggregate::monthly($perusahaan, 'perusahaan'));
    }

    /** Agregat per instrumen (W4): distribusi band + count eskalasi; n≥5 suppression; tanpa identitas individu. */
    public function assessment()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $instruments = \App\Models\AssessmentInstrument::where('is_active', true)->orderBy('id')->get()
            ->map(fn ($instrument) => [
                'instrument' => $instrument,
                'aggregate' => \App\Services\Screening\InstrumentScoring::aggregate($perusahaan, $instrument),
            ]);

        return view('perusahaan.report-assessment', [
            'perusahaan' => $perusahaan,
            'instruments' => $instruments,
        ]);
    }
}
