<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Services\WellbeingQuotaService;
use Illuminate\Http\Request;

/**
 * wv4: admin "Karyawan" — browser karyawan lintas perusahaan (read-only).
 * Data kesehatan mental sensitif: hanya metadata roster + status, bukan
 * skor asesmen individual.
 */
class EmployeeBrowseController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->trim()->toString();
        $companyId = $request->integer('perusahaan_id');

        $query = PerusahaanEmployee::query()
            ->with(['perusahaan:id,brand', 'user:id,firstname,lastname'])
            ->when($q !== '', fn ($r) => $r->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->when($companyId > 0, fn ($r) => $r->where('perusahaan_id', $companyId))
            ->orderByDesc('id');

        $employees = $query->paginate(20)->withQueryString();

        // Program aktif per perusahaan (untuk badge "program aktif").
        $activePrograms = $employees->getCollection()
            ->pluck('perusahaan_id')->unique()
            ->mapWithKeys(function ($pid) {
                $perusahaan = \App\Models\Perusahaan\Perusahaan::find($pid);

                return [$pid => $perusahaan ? WellbeingQuotaService::activeProgram($perusahaan) : null];
            });

        return view('admin.employee-browse', [
            'employees' => $employees,
            'activePrograms' => $activePrograms,
            'q' => $q,
            'companyId' => $companyId,
            'companies' => \App\Models\Perusahaan\Perusahaan::orderBy('brand')->get(['id', 'brand']),
        ]);
    }
}
