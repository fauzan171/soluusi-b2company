<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Illuminate\Http\Request;

/**
 * Katalog paket Wellbeing superadmin (wb-T3): index + edit satu paket per
 * halaman + harga per band inline. NO create/delete — katalog = 4 paket
 * seeder (K3, YAGNI). Edit katalog hanya ubah master: snapshot kontrak
 * (company_wellbeing_programs) tidak tersentuh (K4).
 */
class WellbeingPackageController extends Controller
{
    public function index()
    {
        return view('admin.wellbeing-packages.index', [
            'packages' => WellbeingPackage::with('pricingBands')->orderBy('duration_days')->get(),
        ]);
    }

    public function edit(WellbeingPackage $package)
    {
        return view('admin.wellbeing-packages.edit', [
            'package' => $package->load(['pricingBands' => fn ($q) => $q->orderBy('band_min')]),
        ]);
    }

    public function update(Request $request, WellbeingPackage $package)
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:500',
            'expert_chat_experts' => 'required|integer|min:0|max:99',
            'deep_support_quota' => 'required|integer|min:0|max:999',
            'webinar_count' => 'required|integer|min:0|max:99',
            'is_active' => 'required|boolean',
        ]);

        $package->update($data + ['is_active' => (bool) $data['is_active']]);

        AdminAction::record(auth()->id(), 'wellbeing_package.update', $package, $data);

        return redirect()->route('admin.wellbeing-packages.edit', $package)
            ->with('success', "Paket {$package->name} diperbarui.");
    }

    public function updateBand(Request $request, WellbeingPackage $package, WellbeingPricingBand $band)
    {
        if ($band->wellbeing_package_id !== $package->id) {
            abort(404);
        }
        if ($package->is_free) {
            abort(422, 'Paket gratis tidak memiliki harga band.');
        }

        $data = $request->validate([
            'band_min' => 'required|integer|min:1|max:100000',
            'band_max' => 'required|integer|min:1|max:100000|gt:band_min',
            'monthly_price' => 'required|integer|min:0|max:999999999',
            'mhcu_quota' => 'nullable|integer|min:0|max:9999',
        ]);

        // Overlap dengan band lain di paket yang sama (band ini dikecualikan).
        $overlap = $package->pricingBands()
            ->whereKeyNot($band->getKey())
            ->where('band_min', '<=', $data['band_max'])
            ->where('band_max', '>=', $data['band_min'])
            ->exists();
        if ($overlap) {
            return back()->withErrors(['band_min' => 'Rentang band tumpang tindih dengan band lain di paket ini.'])
                ->withInput();
        }

        $band->update($data);

        AdminAction::record(auth()->id(), 'wellbeing_band.update', $band, [
            'band' => "{$data['band_min']}–{$data['band_max']}",
            'monthly_price' => $data['monthly_price'],
            'mhcu_quota' => $data['mhcu_quota'],
        ]);

        return back()->with('success', 'Harga band diperbarui.');
    }
}
