<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use App\Support\ViewAs;
use Illuminate\Http\Request;

/**
 * F2.2 / B1 — Start/stop view-as. Superadmin only (route guard `role:admin`).
 * Start + stop dicatat ke admin_actions. Target semua role KECUALI admin (422).
 */
class ViewAsController extends Controller
{
    public function start(Request $r)
    {
        $data = $r->validate([
            'user_id' => ['required_without:employee_id', 'integer', 'exists:users,id'],
            'employee_id' => ['required_without:user_id', 'integer', 'exists:perusahaan_employees,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Legacy path: employee_id → resolve user.
        $target = isset($data['user_id'])
            ? User::findOrFail($data['user_id'])
            : PerusahaanEmployee::findOrFail($data['employee_id'])->user;

        ViewAs::start($target);
        AdminAction::record($r->user()->id, 'view_as.start', $target, [
            'target_role' => $target->role?->name,
            'tenant_id' => ViewAs::current()['tenant_id'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);

        $redirectRoute = match (ViewAs::current()['vertical'] ?? null) {
            'perusahaan' => 'perusahaan.employee',
            'sekolah' => 'sekolah.dashboard',
            'klinik' => 'klinik.dashboard',
            default => 'admin.dashboard',
        };

        return redirect()->route($redirectRoute)
            ->with('success', 'Mode view-as aktif: ' . (ViewAs::current()['name'] ?? $target->email));
    }

    public function stop(Request $r)
    {
        $current = ViewAs::current();
        ViewAs::stop();

        if ($current && ($target = User::find($current['target_user_id'] ?? null))) {
            AdminAction::record($r->user()->id, 'view_as.stop', $target, [
                'target_role' => $current['target_role'] ?? null,
                'tenant_id' => $current['tenant_id'] ?? null,
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Mode view-as dinonaktifkan.');
    }
}
