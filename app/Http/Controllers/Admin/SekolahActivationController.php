<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Sekolah\Sekolah;

class SekolahActivationController extends Controller
{
    /** Aktivasi program sekolah. */
    public function store(Sekolah $sekolah)
    {
        $sekolah->is_active = true;
        $sekolah->save();

        AdminAction::record(auth()->id(), 'sekolah.activate', $sekolah);

        return back()->with('success', "Program {$sekolah->brand} diaktifkan. Dashboard BK sekarang aktif.");
    }
}
