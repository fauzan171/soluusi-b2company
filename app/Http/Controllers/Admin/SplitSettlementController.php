<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\SplitSettlement;

class SplitSettlementController extends Controller
{
    /** Settle margin klinik (finance: margin sudah ditransfer ke klinik). */
    public function settle(SplitSettlement $split)
    {
        abort_if($split->status === 'settled', 422, 'Sudah settled.');
        $split->status = 'settled';
        $split->settled_at = now();
        $split->save();

        AdminAction::record(auth()->id(), 'split.settle', $split);

        return back()->with('success', 'Margin Rp'.number_format($split->klinik_margin, 0, ',', '.')." untuk {$split->klinik->brand_name} ditandai settled.");
    }
}
