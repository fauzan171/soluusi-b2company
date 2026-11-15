<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Perusahaan\Perusahaan;

class PerusahaanActivationController extends Controller
{
    /** Aktivasi program perusahaan (CAP). */
    public function store(Perusahaan $perusahaan)
    {
        $perusahaan->is_active = true;
        $perusahaan->save();

        AdminAction::record(auth()->id(), 'perusahaan.activate', $perusahaan);

        return back()->with('success', "Program {$perusahaan->brand} diaktifkan. Dashboard HR sekarang aktif.");
    }
}
