<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyWellbeingProgram;

/**
 * wv4: admin "Billing & Kontrak" — daftar kontrak: harga, periode,
 * status notice, badge renewal (ends_at < 30 hari).
 */
class BillingContractController extends Controller
{
    public function index()
    {
        $contracts = CompanyWellbeingProgram::query()
            ->with(['perusahaan:id,brand', 'wellbeingPackage:id,code,name', 'pricingBand:id,band_min,band_max,monthly_price'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $contracts->getCollection()->transform(function ($c) {
            $c->renewal_due = $c->status === 'active'
                && $c->ends_at
                && $c->ends_at->isFuture()
                && $c->ends_at->diffInDays(now()) < 30;

            return $c;
        });

        return view('admin.billing-contracts', ['contracts' => $contracts]);
    }
}
