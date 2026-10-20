<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikPackage;

/**
 * Paket klinik B2B — hanya super admin yang buat/ubah (akses + pembuatan cuma
 * di superadmin). B2C: paket dibuat konsultan; B2B: Soluusi susun bersama
 * mitra, harga sesuai budget klinik (termasuk paket 1-on-1 & mental health
 * check-up). Klinik mitra lihat read-only di dashboardnya.
 */
class KlinikPackageController extends Controller
{
    public function index(Klinik $klinik)
    {
        return view('admin.klinik-packages', [
            'klinik' => $klinik,
            'packages' => $klinik->klinikPackages()->orderBy('name')->get(),
        ]);
    }

    public function store(Klinik $klinik)
    {
        $data = $this->validateData();
        $klinik->klinikPackages()->create($data);

        return back()->with('success', "Paket \"{$data['name']}\" untuk {$klinik->brand} dibuat.");
    }

    public function update(KlinikPackage $package)
    {
        $data = $this->validateData();
        $package->update($data);

        return back()->with('success', "Paket \"{$data['name']}\" diperbarui.");
    }

    public function destroy(KlinikPackage $package)
    {
        $package->delete();

        return back()->with('success', 'Paket dihapus.');
    }

    protected function validateData(): array
    {
        return request()->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:10|max:480',
            'mode' => 'required|in:online,offline',
        ]);
    }
}
