<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\MarkupRule;
use App\Services\Klinik\MarkupService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MarkupRuleController extends Controller
{
    public function index(Request $request, Klinik $klinik)
    {
        $packages = ConsultantPackage::query()
            ->whereHas('consultant', fn ($query) => $query
                ->where('status', Consultant::ACCEPT)
                ->where('is_active', true))
            ->with('consultant.user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->string('q')->toString();
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', "%{$term}%")
                        ->orWhereHas('consultant.user', fn ($user) => $user
                            ->where('firstname', 'like', "%{$term}%")
                            ->orWhere('lastname', 'like', "%{$term}%"));
                });
            })
            ->orderBy('consultant_id')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $rules = MarkupRule::where('klinik_id', $klinik->id)
            ->whereIn('consultant_package_id', $packages->pluck('id'))
            ->get()
            ->keyBy('consultant_package_id');

        return view('admin.markup-rules', compact('klinik', 'packages', 'rules'));
    }

    public function store(Request $request, Klinik $klinik)
    {
        $data = $request->validate([
            'consultant_package_id' => 'required|exists:consultant_packages,id',
            'markup_amount' => 'required|integer|min:0',
        ]);

        $package = ConsultantPackage::findOrFail($data['consultant_package_id']);
        $net = (int) $package->price;
        $final = $net + (int) $data['markup_amount'];

        if (!MarkupService::isValid($net, $final)) {
            throw ValidationException::withMessages([
                'markup_amount' => 'Markup melewati cap. Harga final maksimum untuk paket ini Rp'
                    . number_format(MarkupService::maxFinalFor($net), 0, ',', '.') . '.',
            ]);
        }

        MarkupRule::updateOrCreate(
            ['klinik_id' => $klinik->id, 'consultant_package_id' => $package->id],
            ['markup_amount' => (int) $data['markup_amount']]
        );

        return back()->with('success', 'Markup paket berhasil disimpan.');
    }
}
