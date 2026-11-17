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
        // C3: aktivasi = trigger CAP stage-1 (config pervorma.cap stage 1
        // trigger 'mou_at') — isi mou_at saat aktivasi bila belum ada.
        $perusahaan->mou_at ??= now();
        $perusahaan->save();

        AdminAction::record(auth()->id(), 'perusahaan.activate', $perusahaan);
        \App\Services\PervormaService::evaluate('cap', $perusahaan);

        return back()->with('success', "Program {$perusahaan->brand} diaktifkan. Dashboard HR sekarang aktif.");
    }
}
