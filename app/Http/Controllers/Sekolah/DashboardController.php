<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\SekolahContext;

class DashboardController extends Controller
{
    public function index()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);
        if (!$sekolah->is_active) {
            return view('sekolah.pending', ['sekolah' => $sekolah, 'isSuperAdmin' => SekolahContext::canSwitch(auth()->user())]);
        }
        return view('sekolah.dashboard', ['sekolah' => $sekolah] + ProgramAggregate::monthly($sekolah, 'sekolah'));
    }
}
