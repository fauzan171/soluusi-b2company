<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\HapPervorma;

class PervormaController extends Controller
{
    /** Tandai pervorma HAP sudah dibayar ke associate. */
    public function pay(HapPervorma $pervorma)
    {
        abort_if($pervorma->status === 'paid', 422, 'Pervorma sudah dibayar.');
        $pervorma->status = 'paid';
        $pervorma->paid_at = now();
        $pervorma->save();

        AdminAction::record(auth()->id(), 'pervorma.pay', $pervorma);

        return back()->with('success', "Pervorma Stage {$pervorma->stage} ditandai sudah dibayar.");
    }
}
