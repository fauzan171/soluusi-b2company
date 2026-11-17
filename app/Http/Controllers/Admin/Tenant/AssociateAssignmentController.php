<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * C4 — Assign associate ke tenant 3 vertikal. User harus ber-role associate;
 * unassign via associate_id kosong. Setiap perubahan ter-audit.
 */
class AssociateAssignmentController extends Controller
{
    public function assignKlinik(Request $r, Klinik $klinik) { return $this->assign($r, $klinik, 'klinik'); }
    public function assignPerusahaan(Request $r, Perusahaan $perusahaan) { return $this->assign($r, $perusahaan, 'perusahaan'); }
    public function assignSekolah(Request $r, Sekolah $sekolah) { return $this->assign($r, $sekolah, 'sekolah'); }

    private function assign(Request $r, object $tenant, string $vertical)
    {
        $data = $r->validate(['associate_id' => 'nullable|integer|exists:users,id']);

        $associate = null;
        if (!empty($data['associate_id'])) {
            $associate = User::with('role')->findOrFail($data['associate_id']);
            abort_unless($associate->role?->name === 'associate', 422, 'User bukan associate.');
        }

        $previousId = $tenant->associate_id;
        $tenant->associate_id = $associate?->id;
        $tenant->save();

        AdminAction::record(auth()->id(), "{$vertical}.assign-associate", $tenant, [
            'associate_id' => $associate?->id,
            'previous_associate_id' => $previousId,
        ]);

        return back()->with('success', $associate
            ? 'Associate di-assign ke ' . ucfirst($vertical) . '.'
            : 'Associate dilepas dari ' . ucfirst($vertical) . '.');
    }
}
