<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Klinik\HapPervorma;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Sekolah\SekolahPervorma;

/**
 * C6 — Admin pervorma pay 3 vertikal. Route POST /admin/pervormas/{vertical}/{pervorma}/pay
 * menyamakan pola associate requestPayout (C5). Idempotent via guard status === 'paid'.
 */
class PervormaController extends Controller
{
    /** Map vertical slug → model pervorma. Sumber kebenaran yang sama dengan C5. */
    private const VERTICALS = [
        'hap' => HapPervorma::class,
        'sap' => SekolahPervorma::class,
        'cap' => PerusahaanPervorma::class,
    ];

    /** Tandai pervorma (3 vertikal) sudah dibayar ke associate. */
    public function pay(string $vertical, int $pervormaId)
    {
        abort_unless(isset(self::VERTICALS[$vertical]), 404);

        $model = self::VERTICALS[$vertical];
        $pervorma = $model::findOrFail($pervormaId);

        abort_if($pervorma->status === 'paid', 422, 'Pervorma sudah dibayar.');
        $pervorma->status = 'paid';
        $pervorma->paid_at = now();
        $pervorma->save();

        AdminAction::record(auth()->id(), 'pervorma.pay', $pervorma, ['vertical' => $vertical]);

        return back()->with('success', "Pervorma Stage {$pervorma->stage} ({$vertical}) ditandai sudah dibayar.");
    }
}
