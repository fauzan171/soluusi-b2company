<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\MarkupRule;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Schedule;
use Illuminate\Http\Request;

/**
 * Pilih konsultan dari pool (Build: consultant picker). Filter: kategori
 * keahlian, daerah (provinsi/kabupaten — default = daerah klinik), nama.
 * Konsultan di sini = pool Soluusi; profil Soluusi TIDAK bocor ke customer
 * (anti-circumvention — hanya admin klinik yang lihat halaman ini).
 */
class ConsultantController extends Controller
{
    public function index(Request $request)
    {
        $klinik = \App\Support\KlinikContext::current();

        // Default: auto-filter ke daerah klinik (misal klinik Bandung → konsultan
        // Bandung). ?semua_daerah=1 untuk lihat seluruh pool.
        $defaultRegency = null;
        if ($klinik?->regency_id && !$request->boolean('semua_daerah') && !$request->hasAny(['regency_id', 'province_id', 'category_id', 'q'])) {
            $defaultRegency = $klinik->regency_id;
            $request->merge(['regency_id' => $defaultRegency]);
        }

        $availableScheduleScope = function ($query) use ($klinik) {
            $query
                ->where('klinik_id', $klinik?->id)
                ->where('status', Schedule::STATUS_CONFIRMED)
                ->where('started_at', '>', now())
                ->whereDoesntHave('consultationSession', fn ($session) => $session
                    ->blocking())
                ->whereDoesntHave('checkoutLinks', fn ($link) => $link
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now()));
        };

        $consultants = Consultant::query()
            ->where('status', 'accept')
            ->where('is_active', true)
            ->with([
                'user.regency.province',
                'categories',
                'packages' => fn ($q) => $q->orderBy('price')->orderBy('duration'),
            ])
            ->withCount([
                'schedules' => fn ($q) => $q
                    ->tap($availableScheduleScope)
                    ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)),
                'schedules as online_schedules_count' => fn ($q) => $q
                    ->tap($availableScheduleScope)
                    ->where('mode', Schedule::MODE_ONLINE),
                'schedules as offline_schedules_count' => fn ($q) => $q
                    ->tap($availableScheduleScope)
                    ->where('mode', Schedule::MODE_OFFLINE),
                'packages',
            ])
            ->whereHas('schedules', fn ($q) => $q
                ->tap($availableScheduleScope)
                ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)))
            // Filter: daerah (default = regency klinik)
            ->when($request->filled('regency_id'), fn ($q) =>
                $q->whereHas('user', fn ($u) => $u->where('regency_id', $request->regency_id)))
            ->when($request->filled('province_id') && !$request->filled('regency_id'), fn ($q) =>
                $q->whereHas('user.regency', fn ($r) => $r->where('province_id', $request->province_id)))
            // Filter: kategori keahlian
            ->when($request->filled('category_id'), fn ($q) =>
                $q->whereHas('categories', fn ($c) => $c->where('categories.id', $request->category_id)))
            // Filter: kebutuhan, kategori, deskripsi, atau nama.
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = '%' . $request->q . '%';

                $q->where(function ($match) use ($keyword) {
                    $match
                        ->where('about', 'like', $keyword)
                        ->orWhereHas('categories', fn ($categories) => $categories->where('name', 'like', $keyword))
                        ->orWhereHas('skills', fn ($skills) => $skills->where('name', 'like', $keyword))
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('firstname', 'like', $keyword)
                            ->orWhere('lastname', 'like', $keyword));
                });
            })
            ->orderByDesc('schedules_count')
            ->paginate(12)
            ->withQueryString();

        $packageIds = $consultants->getCollection()
            ->flatMap(fn ($consultant) => $consultant->packages->pluck('id'))
            ->filter();

        $markups = MarkupRule::where('klinik_id', $klinik?->id)
            ->whereIn('consultant_package_id', $packageIds)
            ->pluck('markup_amount', 'consultant_package_id');

        return view('klinik.consultants.index', [
            'consultants' => $consultants,
            'klinik' => $klinik,
            'markups' => $markups,
            'defaultRegency' => $defaultRegency,
            'categories' => Category::whereIn('vertical', ['klinik', 'both'])->orderBy('name')->get(),
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
            'regencies' => $request->filled('province_id')
                ? Regency::where('province_id', $request->province_id)->orderBy('name')->get(['id', 'name'])
                : Regency::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Consultant $consultant)
    {
        $klinik = \App\Support\KlinikContext::current();

        abort_unless(
            $consultant->status === Consultant::ACCEPT && (bool) $consultant->is_active,
            404
        );

        $consultant->load([
            'user.regency.province',
            'categories',
            'subcategories',
            'skills',
            'consultantCompanies',
            'consultantEducations',
            'certificates',
        ]);

        $packages = ConsultantPackage::where('consultant_id', $consultant->id)
            ->orderBy('mode')
            ->orderBy('duration')
            ->get();

        $upcomingSchedules = Schedule::where('consultant_id', $consultant->id)
            ->where('klinik_id', $klinik?->id)
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())
            ->whereDoesntHave('consultationSession', function ($query) {
                $query->blocking();
            })
            ->orderBy('started_at')
            ->get();

        $markups = \App\Models\Klinik\MarkupRule::where('klinik_id', $klinik?->id)
            ->whereIn('consultant_package_id', $packages->pluck('id'))
            ->pluck('markup_amount', 'consultant_package_id');

        return view('klinik.consultants.show', [
            'consultant' => $consultant,
            'klinik' => $klinik,
            'packages' => $packages,
            'upcomingSchedules' => $upcomingSchedules,
            'markups' => $markups,
        ]);
    }
}
