<?php

namespace App\Observers;

use App\Models\Sekolah\SekolahSession;
use App\Services\PervormaService;

/**
 * D.1: single-source trigger pervorma SAP — saat SekolahSession berubah
 * status ke done (end manual, zombie sweep, path lain) evaluate sekolah.
 * Idempotent di layer PervormaService (firstOrCreate per stage), jadi
 * multiple-fire aman.
 */
class SekolahSessionObserver
{
    public function updated(SekolahSession $session): void
    {
        if ($session->status === 'done'
            && $session->getOriginal('status') !== 'done'
            && $session->wasChanged('status')) {
            PervormaService::evaluate('sap', $session->sekolah);
        }
    }
}
