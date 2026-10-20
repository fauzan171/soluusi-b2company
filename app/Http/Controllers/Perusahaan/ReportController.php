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
}
