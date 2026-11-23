<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Support\AggregatePrivacy;
use App\Support\PerusahaanContext;

/**
 * wv4: HR "Keterlibatan Karyawan" — agregat partisipasi bulanan
 * (suppressed <10 via ProgramAggregate), tanpa data individual.
 */
class EngagementController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $monthly = \App\Services\ProgramAggregate::monthly($perusahaan, 'perusahaan');
        [$month] = AggregatePrivacy::month();
        $campaigns = $perusahaan->campaigns()->latest('id')->limit(10)->get();

        return view('perusahaan.engagement', [
            'perusahaan' => $perusahaan,
            'month' => $month,
            'registered' => $monthly['registered'] ?? 0,
            'participation' => $monthly['participation'],
            'doneMonth' => $monthly['done'],
            'noShow' => $monthly['noShow'],
            'avgRating' => $monthly['avgRating'],
            'suppressed' => $monthly['suppressed'],
            'campaigns' => $campaigns,
        ]);
    }
}
