<?php

// Split-payment config (Build-NEW §5.1, Appendix E.1 #1).
// MVP: split dicatat di split_settlements (net→Soluusi, margin→klinik).
// Gateway-level split (Midtrans Connect / Xendit Marketplace for-user) diaktifkan
// via SPLIT_ON + provider credentials saat prod — ledger tetap source of truth.
return [
    'provider' => env('SPLIT_PROVIDER', 'midtrans_connect'), // | xendit_marketplace
    'on' => env('SPLIT_ON', false),
];
