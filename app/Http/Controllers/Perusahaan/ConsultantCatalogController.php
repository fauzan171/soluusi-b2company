<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\Perusahaan\ConsultantRequest;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;

/**
 * Katalog + detail konsultan untuk HR (view-only) dan request penambahan
 * konsultan ke pool (disetujui/ditolak Soluusi/superadmin).
 */
class ConsultantCatalogController extends Controller
{
    public function index(Request $request)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $q = $request->string('q')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $query = Consultant::query()
            ->where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->whereHas('user', fn ($u) => $u->where('is_active', true))
            ->with(['user', 'categories', 'skills', 'educations', 'certificates'])
            ->withCount('ratings');

        if ($q !== '') {
            $query->whereHas('user', fn ($u) => $u->where('firstname', 'like', "%{$q}%")->orWhere('lastname', 'like', "%{$q}%"));
        }
        if ($category !== '') {
            $query->whereHas('categories', fn ($c) => $c->where('name', $category));
        }

        $consultants = $query->orderBy('id')->paginate(12)->withQueryString();
        $categories = Consultant::query()->where('status', Consultant::ACCEPT)
            ->with('categories')->get()->flatMap(fn ($c) => $c->categories->pluck('name'))->unique()->sort()->values();

        return view('perusahaan.consultant-catalog', [
            'perusahaan' => $perusahaan,
            'consultants' => $consultants,
            'categories' => $categories,
            'q' => $q,
            'activeCategory' => $category,
            'requests' => $perusahaan->consultantRequests()->latest()->take(10)->get(),
        ]);
    }

    public function show(Consultant $consultant)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        abort_unless($consultant->status === Consultant::ACCEPT && $consultant->is_active, 404);
        $consultant->load(['user', 'categories', 'skills', 'educations', 'certificates'])->loadCount('ratings');

        // Statistik sesi konsultan ini di tenant HR (agregat, tanpa identitas karyawan).
        $sessions = $perusahaan->sessions()->where('consultant_id', $consultant->id)->get();
        $stats = [
            'done' => $sessions->where('status', 'done')->count(),
            'scheduled' => $sessions->where('status', 'scheduled')->count(),
            'avgRating' => $sessions->where('status', 'done')->whereNotNull('rating')->avg('rating'),
            'rated' => $sessions->whereNotNull('rating')->count(),
        ];

        return view('perusahaan.consultant-detail', [
            'perusahaan' => $perusahaan,
            'consultant' => $consultant,
            'stats' => $stats,
        ]);
    }

    public function requestStore(Request $request, Consultant $consultant)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        ConsultantRequest::create([
            'perusahaan_id' => $perusahaan->id,
            'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog',
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        return back()->with('success', 'Request konsultan dikirim ke Soluusi untuk peninjauan.');
    }
}
