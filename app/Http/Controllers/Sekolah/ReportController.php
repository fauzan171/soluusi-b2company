<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\SekolahContext;

class ReportController extends Controller
{
    public function index()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah && $sekolah->is_active, 403);
        return view('sekolah.report', ['sekolah' => $sekolah] + ProgramAggregate::monthly($sekolah, 'sekolah'));
    }
}
