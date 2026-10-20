<?php

namespace App\Services\Klinik;

use App\Models\ConsultationSession;
use App\Models\Klinik\SplitSettlement;

/**
 * Split-payment ledger (Build-NEW #1, §15.10). Dipanggil SEKALI saat payment
 * verified (webhook). Net → Soluusi, margin → klinik.
 *
 * Gateway-level split (Midtrans Connect / Xendit Marketplace for-user) aktif
 * via config split.on saat prod; ledger ini tetap source of truth + idempotent
 * (unique session_id) sehingga webhook retry aman.
 */
class SplitSettlementService
{
    public static function record(ConsultationSession $session): ?SplitSettlement
    {
        // Idempotent — webhook Midtrans bisa kirim status berulang.
        $existing = SplitSettlement::where('session_id', $session->id)->first();
        if ($existing) {
            return $existing;
        }

        $link = $session->checkoutLink;
        if (!$session->klinik_id) {
            return null; // sesi non-klinik (tidak ada split)
        }

        // Amount dari markup snapshot link (locked); fallback: tanpa margin.
        $net = (int) ($link?->net_price ?? $session->package_price) - (int) $session->voucher_amount;
        $margin = (int) ($link?->markup_amount ?? 0);
        if ($net < 0 || $net + $margin !== (int) $session->amount) {
            throw new \RuntimeException('Split ledger does not balance received amount; ops reconciliation required.');
        }

        return SplitSettlement::create([
            'session_id' => $session->id,
            'klinik_id' => $session->klinik_id,
            'net_amount' => $net,
            'klinik_margin' => $margin,
            'status' => 'pending',
        ]);
    }
}
