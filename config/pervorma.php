<?php

// Pervorma (commission associate) stage rates per vertikal. Defaults persis
// playbook (task.md C2). Per-tenant overrides tetap di tabel tenant
// (mis. kliniks.total_rate); window 90 hari stage-1 HAP tetap dievaluasi di
// service, konstanta di sini hanya sumber kebenaran nominal.
return [
    // HAP — klinik. Trigger = sesi completed-verified.
    'hap' => [
        'window_days' => (int) env('HAP_WINDOW_DAYS', 90),
        'stages' => [
            1 => ['trigger' => (int) env('HAP_STAGE1_TRIGGER', 5), 'amount' => (int) env('HAP_STAGE1_AMOUNT', 250_000)],
            2 => ['trigger' => (int) env('HAP_STAGE2_TRIGGER', 25), 'amount' => (int) env('HAP_STAGE2_AMOUNT', 250_000)],
            3 => ['trigger' => (int) env('HAP_STAGE3_TRIGGER', 40), 'amount' => (int) env('HAP_STAGE3_AMOUNT', 500_000)],
        ],
    ],

    // SAP — sekolah. Stage 1 trigger = 5 sesi completed.
    'sap' => [
        'stages' => [
            1 => ['trigger' => (int) env('SAP_STAGE1_TRIGGER', 5), 'amount' => (int) env('SAP_STAGE1_AMOUNT', 300_000)],
            2 => ['trigger' => (int) env('SAP_STAGE2_TRIGGER', 25), 'amount' => (int) env('SAP_STAGE2_AMOUNT', 400_000)],
            3 => ['trigger' => (int) env('SAP_STAGE3_TRIGGER', 40), 'amount' => (int) env('SAP_STAGE3_AMOUNT', 800_000)],
        ],
    ],

    // CAP — perusahaan. Stage 1 trigger = tenant activated (kolom mou_at
    // terisi), bukan hitungan sesi.
    'cap' => [
        'stages' => [
            1 => ['trigger' => 'mou_at', 'amount' => (int) env('CAP_STAGE1_AMOUNT', 600_000)],
            2 => ['trigger' => (int) env('CAP_STAGE2_TRIGGER', 50), 'amount' => (int) env('CAP_STAGE2_AMOUNT', 800_000)],
            3 => ['trigger' => (int) env('CAP_STAGE3_TRIGGER', 150), 'amount' => (int) env('CAP_STAGE3_AMOUNT', 1_600_000)],
        ],
    ],
];
