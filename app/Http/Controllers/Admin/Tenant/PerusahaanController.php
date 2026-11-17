<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Perusahaan\Perusahaan;
use Illuminate\Http\Request;

/**
 * A1 — Manajemen tenant perusahaan oleh superadmin: list/search, detail,
 * edit field aman (brand + kuota/rate + ai_enabled). Provisioning akun HR
 * tetap di AdminPerusahaanController (form transaksi existing).
 */
class PerusahaanController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $perusahaans = Perusahaan::withCount('employees')->with('admins.user')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tenants.perusahaan.index', ['perusahaans' => $perusahaans, 'q' => $q]);
    }

    public function show(Perusahaan $perusahaan)
    {
        $perusahaan->load('admins.user')->loadCount('employees', 'sessions');

        // B6 — kandidat view-as: HR admins + employees (user non-null).
        $viewAsUsers = collect();
        foreach ($perusahaan->admins as $a) {
            if ($a->user) {
                $viewAsUsers->push([
                    'user_id' => $a->user->id,
                    'label' => trim(($a->user->firstname ?? '') . ' ' . ($a->user->lastname ?? '')) ?: $a->user->email,
                    'role' => 'hr_admin',
                ]);
            }
        }
        foreach ($perusahaan->employees()->with('user')->get() as $e) {
            if ($e->user) {
                $viewAsUsers->push([
                    'user_id' => $e->user->id,
                    'label' => $e->name,
                    'role' => 'employee',
                ]);
            }
        }

        return view('admin.tenants.perusahaan.show', [
            'perusahaan' => $perusahaan,
            'viewAsUsers' => $viewAsUsers,
            'associates' => KlinikController::associateOptions(),
            'assignRoute' => route('admin.tenants.perusahaans.assign-associate', $perusahaan),
        ]);
    }

    public function edit(Perusahaan $perusahaan)
    {
        return view('admin.tenants.perusahaan.edit', ['perusahaan' => $perusahaan]);
    }

    public function update(Request $request, Perusahaan $perusahaan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'pepm_rate' => ['nullable', 'integer', 'min:0'],
            'sessions_per_employee_month' => ['nullable', 'integer', 'min:1', 'max:30'],
            'ai_enabled' => ['nullable', 'boolean'],
        ]);
        $data['pepm_rate'] = $data['pepm_rate'] ?? $perusahaan->pepm_rate;
        $data['sessions_per_employee_month'] = $data['sessions_per_employee_month'] ?? $perusahaan->sessions_per_employee_month;
        $data['ai_enabled'] = (bool) ($data['ai_enabled'] ?? false);

        $perusahaan->fill($data)->save();

        AdminAction::record(auth()->id(), 'perusahaan.update', $perusahaan, $data);

        return redirect()
            ->route('admin.tenants.perusahaans.show', $perusahaan)
            ->with('success', "Perusahaan \"{$perusahaan->brand}\" diperbarui.");
    }
}
