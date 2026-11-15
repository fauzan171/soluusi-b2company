<?php

namespace App\Support\Checkout;

use App\Models\Category;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\MarkupRule;
use App\Models\Regency;
use App\Models\Schedule;
use Illuminate\Http\Request;

/**
 * T4.2: query builder untuk halaman browse klinik (daftar konsultan + jadwal
 * tersedia). Dipisah dari controller supaya CheckoutController < 200 baris.
 * Murni query — tidak ada side effect.
 */
class KlinikBrowse
{
    /** View-data lengkap untuk halaman browse klinik (checkout.klinik). */
    public static function viewData(Klinik $klinik, Request $request): array
    {
        $consultants = self::availableConsultantsQuery($klinik, $request)
            ->paginate(9)
            ->withQueryString();

        $selectedConsultant = null;
        $schedules = collect();
        $packages = collect();
        $markups = collect();

        if ($request->filled('consultant_id')) {
            $selectedConsultant = self::availableConsultantsQuery($klinik, $request)
                ->where('consultants.id', $request->integer('consultant_id'))
                ->first();

            if ($selectedConsultant) {
                $schedules = self::availableSchedulesQuery($klinik, $selectedConsultant->id)
                    ->orderBy('started_at')
                    ->limit(40)
                    ->get();

                $packages = ConsultantPackage::where('consultant_id', $selectedConsultant->id)
                    ->when($request->filled('mode'), fn ($query) => $query->where('mode', $request->mode))
                    ->orderBy('mode')
                    ->orderBy('duration')
                    ->get();

                $markups = MarkupRule::where('klinik_id', $klinik->id)
                    ->whereIn('consultant_package_id', $packages->pluck('id'))
                    ->get()
                    ->keyBy('consultant_package_id');
            }
        }

        return [
            'klinik' => $klinik,
            'brand' => $klinik->brand_name,
            'consultants' => $consultants,
            'selectedConsultant' => $selectedConsultant,
            'schedules' => $schedules,
            'packages' => $packages,
            'markups' => $markups,
            'regencies' => Regency::orderBy('name')->get(['id', 'name']),
            'categories' => Category::whereIn('vertical', ['klinik', 'both'])->orderBy('name')->get(),
        ];
    }

    public static function availableConsultantsQuery(Klinik $klinik, Request $request)
    {
        return Consultant::query()
            ->where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->with(['user.regency.province', 'categories'])
            ->withCount([
                'schedules' => fn ($query) => self::availableSchedulesQuery($klinik, null, $query)
                    ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)),
                'packages',
            ])
            ->whereHas('schedules', fn ($query) => self::availableSchedulesQuery($klinik, null, $query)
                ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)))
            ->whereHas('packages', fn ($query) => $query
                ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)))
            ->when($request->filled('category_id'), fn ($query) =>
                $query->whereHas('categories', fn ($category) => $category->where('categories.id', $request->category_id)))
            ->when($request->filled('q'), fn ($query) =>
                $query->whereHas('user', fn ($user) =>
                    $user->where('firstname', 'like', '%' . $request->q . '%')
                        ->orWhere('lastname', 'like', '%' . $request->q . '%')))
            ->orderByDesc('schedules_count')
            ->orderBy('id');
    }

    public static function availableSchedulesQuery(Klinik $klinik, ?int $consultantId = null, $query = null)
    {
        $query = $query ?: Schedule::query();

        return $query
            ->where('klinik_id', $klinik->id)
            ->when($consultantId, fn ($q) => $q->where('consultant_id', $consultantId))
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())
            ->whereDoesntHave('consultationSession', function ($session) {
                $session->blocking();
            })
            ->whereDoesntHave('checkoutLinks', function ($link) {
                $link->whereNull('used_at')->where('expires_at', '>', now());
            });
    }
}
