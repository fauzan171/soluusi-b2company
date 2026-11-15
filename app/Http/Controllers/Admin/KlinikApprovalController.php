<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\Klinik;

class KlinikApprovalController extends Controller
{
    /** Approve mitra klinik baru (dari registrasi /daftar-mitra). */
    public function store(Klinik $klinik)
    {
        $klinik->is_active = true;
        $klinik->approved_at = now();
        $klinik->save();

        AdminAction::record(auth()->id(), 'klinik.approve', $klinik);

        return back()->with('success', "Mitra {$klinik->name} di-approve. Dashboard mitra sekarang aktif.");
    }
}
