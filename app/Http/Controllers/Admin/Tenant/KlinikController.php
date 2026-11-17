<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use Illuminate\Http\Request;

/**
 * A1 — Manajemen tenant klinik oleh superadmin: list/search, detail, edit
 * field aman (identitas brand + rate + ai_enabled). Suspend/lifecycle = A2.
 */
class KlinikController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $kliniks = Klinik::with('admins.user', 'associate')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tenants.klinik.index', ['kliniks' => $kliniks, 'q' => $q]);
    }

    public function show(Klinik $klinik)
    {
        $klinik->load('admins.user', 'associate')
            ->loadCount('checkoutLinks', 'splitSettlements', 'klinikPackages');

        // B6 — kandidat view-as: admin klinik.
        $viewAsUsers = $klinik->admins->filter(fn ($a) => $a->user)->map(fn ($a) => [
            'user_id' => $a->user->id,
            'label' => trim(($a->user->firstname ?? '') . ' ' . ($a->user->lastname ?? '')) ?: $a->user->email,
            'role' => 'klinik_admin',
        ])->values();

        return view('admin.tenants.klinik.show', [
            'klinik' => $klinik,
            'viewAsUsers' => $viewAsUsers,
            'associates' => self::associateOptions(),
            'assignRoute' => route('admin.tenants.kliniks.assign-associate', $klinik),
        ]);
    }

    /** C4 — daftar kandidat associate untuk form assign (dipakai 3 show). */
    public static function associateOptions()
    {
        return \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'associate'))
            ->orderBy('firstname')->get(['id', 'firstname', 'lastname', 'email']);
    }

    public function edit(Klinik $klinik)
    {
        return view('admin.tenants.klinik.edit', ['klinik' => $klinik]);
    }

    public function update(Request $request, Klinik $klinik)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'total_rate' => ['nullable', 'integer', 'min:0'],
            'ai_enabled' => ['nullable', 'boolean'],
        ]);
        $data['total_rate'] = $data['total_rate'] ?? $klinik->total_rate;
        $data['ai_enabled'] = (bool) ($data['ai_enabled'] ?? false);

        $klinik->fill($data)->save();

        AdminAction::record(auth()->id(), 'klinik.update', $klinik, $data);

        return redirect()
            ->route('admin.tenants.kliniks.show', $klinik)
            ->with('success', "Klinik \"{$klinik->brand}\" diperbarui.");
    }
}
