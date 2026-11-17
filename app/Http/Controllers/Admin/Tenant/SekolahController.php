<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Sekolah\Sekolah;
use Illuminate\Http\Request;

/**
 * A1 — Manajemen tenant sekolah oleh superadmin: satu-satunya UI create
 * sekolah (sebelumnya seeder saja). List/search, create, detail, edit.
 * Aktivasi program tetap lewat route activate existing.
 */
class SekolahController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $sekolahs = Sekolah::withCount('students')->with('admins.user')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tenants.sekolah.index', ['sekolahs' => $sekolahs, 'q' => $q]);
    }

    public function create()
    {
        return view('admin.tenants.sekolah.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'tingkat' => ['nullable', 'string', 'max:20'],
        ]);
        $data['is_active'] = false; // default pending aktivasi; ops jalan via route activate.

        $sekolah = Sekolah::create($data);

        AdminAction::record(auth()->id(), 'sekolah.create', $sekolah, ['name' => $data['name'], 'brand' => $data['brand'], 'tingkat' => $data['tingkat']]);

        return redirect()
            ->route('admin.tenants.sekolahs.show', $sekolah)
            ->with('success', "Sekolah \"{$sekolah->brand}\" dibuat. Aktivasi program untuk mengaktifkan dashboard BK.");
    }

    public function show(Sekolah $sekolah)
    {
        $sekolah->load('admins.user')->loadCount('students', 'sessions');

        // B6 — kandidat view-as: BK (admin sekolah) + siswa.
        $viewAsUsers = $sekolah->admins->filter(fn ($a) => $a->user)->map(fn ($a) => [
            'user_id' => $a->user->id,
            'label' => trim(($a->user->firstname ?? '') . ' ' . ($a->user->lastname ?? '')) ?: $a->user->email,
            'role' => 'bk',
        ])->values();
        foreach ($sekolah->students()->with('user')->get() as $s) {
            if ($s->user) {
                $viewAsUsers->push([
                    'user_id' => $s->user->id,
                    'label' => $s->name ?? trim(($s->user->firstname ?? '') . ' ' . ($s->user->lastname ?? '')) ?: $s->user->email,
                    'role' => 'student',
                ]);
            }
        }

        return view('admin.tenants.sekolah.show', [
            'sekolah' => $sekolah,
            'viewAsUsers' => $viewAsUsers,
            'associates' => KlinikController::associateOptions(),
            'assignRoute' => route('admin.tenants.sekolahs.assign-associate', $sekolah),
        ]);
    }

    public function edit(Sekolah $sekolah)
    {
        return view('admin.tenants.sekolah.edit', ['sekolah' => $sekolah]);
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'tingkat' => ['nullable', 'string', 'max:20'],
            'sessions_per_student_month' => ['nullable', 'integer', 'min:1', 'max:30'],
            'ai_enabled' => ['nullable', 'boolean'],
        ]);
        $data['tingkat'] = $data['tingkat'] ?? $sekolah->tingkat;
        $data['sessions_per_student_month'] = $data['sessions_per_student_month'] ?? $sekolah->sessions_per_student_month;
        $data['ai_enabled'] = (bool) ($data['ai_enabled'] ?? false);

        $sekolah->fill($data)->save();

        AdminAction::record(auth()->id(), 'sekolah.update', $sekolah, $data);

        return redirect()
            ->route('admin.tenants.sekolahs.show', $sekolah)
            ->with('success', "Sekolah \"{$sekolah->brand}\" diperbarui.");
    }
}
