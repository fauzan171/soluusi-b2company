<?php

// Klinik vertical config (Build-NEW §5.2). Defaults neutral; per-klinik values
// live in kliniks table. HAP commission rate parameterized (§15.7 — finance
// ubah tanpa code).
return [
    // HAP 3-stage commission (Spec §3). Total Rate per mitra set at MoU.
    'hap' => [
        'total_rate' => (int) env('HAP_TOTAL_RATE', 1_000_000),
        'stage1_flat' => (int) env('HAP_STAGE1_FLAT', 250_000),
        'stage1_trigger' => (int) env('HAP_STAGE1_TRIGGER', 5),   // sesi completed
        'stage2_trigger' => (int) env('HAP_STAGE2_TRIGGER', 25),
        'stage3_trigger' => (int) env('HAP_STAGE3_TRIGGER', 40),
        'window_days' => (int) env('HAP_WINDOW_DAYS', 90),       // rolling window
    ],

    // Markup cap per price range (Spec §2.2). margin = (final - net) / final.
    // ponytail: range termurah mulai dari 0 supaya paket < Rp250k tetap dapat cap,
    // bukan fallback 0 yang menolak semua margin (link gagal dibuat).
    'markup_caps' => [
        ['min' => 0, 'max' => 299_999, 'cap' => 0.10],
        ['min' => 300_000, 'max' => 349_999, 'cap' => 0.15],
        ['min' => 350_000, 'max' => 399_999, 'cap' => 0.20],
        ['min' => 400_000, 'max' => PHP_INT_MAX, 'cap' => 0.25],
    ],

    // Dual-confirm (Spec §9): auto-flag ops review bila tidak confirm dalam X jam.
    'confirm_window_hours' => (int) env('DUAL_CONFIRM_WINDOW_HOURS', 24),
];
