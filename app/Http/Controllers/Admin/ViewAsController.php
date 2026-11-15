<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Support\ViewAs;
use Illuminate\Http\Request;

/**
 * F2.2 — Start/stop view-as. Superadmin only (route guard `role:admin`).
 * Start + stop dicatat ke admin_actions.
 */
class ViewAsController extends Controller
{
    public function start(Request $r)
    {
        $data = $r->validate(['employee_id' => ['required', 'integer', 'exists:perusahaan_employees,id']]);
        $target = PerusahaanEmployee::findOrFail($data['employee_id']);

        ViewAs::start($target);
        AdminAction::record($r->user()->id, 'view_as.start', $target, [
            'perusahaan_id' => $target->perusahaan_id,
        ]);

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Mode view-as aktif: ' . $target->name);
    }

    public function stop(Request $r)
    {
        $current = ViewAs::current();
        ViewAs::stop();

        if ($current && ($target = PerusahaanEmployee::find($current['employee_id']))) {
            AdminAction::record($r->user()->id, 'view_as.stop', $target, [
                'perusahaan_id' => $target->perusahaan_id,
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Mode view-as dinonaktifkan.');
    }
}
