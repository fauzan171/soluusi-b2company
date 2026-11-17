<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Sekolah\Sekolah;
use Illuminate\Http\Request;

/**
 * A2: tenant lifecycle — suspend/activate dengan alasan wajib.
 * Suspend = blokir login admin tenant + context resolve portal (superadmin tetap lewat).
 */
class LifecycleController extends Controller
{
    public function suspendKlinik(Request $r, Klinik $klinik) { return $this->suspend($r, $klinik, 'klinik'); }
    public function suspendPerusahaan(Request $r, Perusahaan $perusahaan) { return $this->suspend($r, $perusahaan, 'perusahaan'); }
    public function suspendSekolah(Request $r, Sekolah $sekolah) { return $this->suspend($r, $sekolah, 'sekolah'); }

    public function activateKlinik(Request $r, Klinik $klinik) { return $this->activate($r, $klinik, 'klinik'); }
    public function activatePerusahaan(Request $r, Perusahaan $perusahaan) { return $this->activate($r, $perusahaan, 'perusahaan'); }
    public function activateSekolah(Request $r, Sekolah $sekolah) { return $this->activate($r, $sekolah, 'sekolah'); }

    private function suspend(Request $r, object $tenant, string $vertical)
    {
        $r->validate(['reason' => 'required|string|max:500']);
        abort_if($tenant->status === 'suspended', 422, 'Tenant sudah suspended.');
        $tenant->status = 'suspended';
        $tenant->save();

        AdminAction::record(auth()->id(), "{$vertical}.suspend", $tenant, ['reason' => $r->reason]);

        return back()->with('success', ucfirst($vertical) . ' disuspend.');
    }

    private function activate(Request $r, object $tenant, string $vertical)
    {
        abort_if($tenant->status !== 'suspended', 422, 'Tenant tidak sedang suspended.');
        $tenant->status = 'active';
        $tenant->save();

        AdminAction::record(auth()->id(), "{$vertical}.activate", $tenant, ['reason' => $r->input('reason')]);

        return back()->with('success', ucfirst($vertical) . ' diaktifkan kembali.');
    }
}
