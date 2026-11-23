# wb-T4 — Assign Program wizard + aktivasi + 30-day notice termination

Status: READY
Depends: wb-T3. PARALLEL GROUP dengan wb-T5 (file disjoint — lihat CONSTRAINTS).

## GOAL
Wizard superadmin "Assign Program" (pilih perusahaan → paket → band auto + option A/B → konfirmasi checkbox → aktifkan) yang membuat `company_wellbeing_programs` dengan snapshot lengkap, plus aksi terminasi 30-day notice + daily command expire.

## CONTEXT
- UX EKSAK: `research-ux.md` §3c (wizard 3 step: Perusahaan & Paket → Detail (band auto-highlight dari `perusahaans.employee_count`, harga terkunci readonly, opsi A/B conditional growth/impact, tanggal mulai default besok, durasi readonly) → Konfirmasi (ringkasan + checkbox konfirmasi + tombol "Aktifkan Program")) + §3d (tabel "Program Berjalan" bawah halaman assign: Perusahaan|Paket|Band|Opsi DS|Periode|Status chip).
- Biz rules: `research-bizrules.md` R2 (mode wajib utk growth/impact, `in:mhcu,one_on_one`), R4 (discovery `selected_employee_ids` — v1: input optional list employee id / biarkan null = semua; gate sudah di T2), R6 (30-day notice: request terminasi → validasi tanggal efektif ≥ today+30 else 422; program tetap aktif; daily command flip `active→ended` saat tanggal efektif tiba; kolom `notice_requested_at`+`terminated_at`), R9 (snapshot scope: quota/band/option/harga/assessment_domains locked), §3 (band dari `employee_count`, validasi ≥50 kecuali `is_free`; employee di luar semua band (mis. 1200) → error jelas), §4 (guard 1 program aktif per perusahaan → assign kedua 422; setelah ended → sukses).
- Snapshot saat create: `expert_chat_quota`, `deep_support_quota` (band MHCU jika option A; default katalog jika B/discovery), `webinar_quota`, `assessment_count`, `assessment_max_participants`, `assessment_domains`, `monthly_price` (band; null discovery), `pricing_band_id`, `employee_band_size`, `deep_support_mode`, `starts_at`, `ends_at = starts_at + duration_days − 1`, `created_by` = admin id. Semua dalam `DB::transaction` + `AdminAction::record('wellbeing_program.activated')`.
- Pola: `Admin\PerusahaanActivationController` (flip + record + flash) + `ProvisioningController` (multi-step 1 transaksi). Daily command + scheduler: pola `Console/Kernel.php` (command + `$schedule`, pola `updateExpiredPayments`).
- Wizard state = form POST step-by-step (input old()) BUKAN session state machine — paling sederhana yang memenuhi UX; step 3 POST final create. (Kalau implementer temukan lebih sederhana: 1 form panjang dengan conditional render section juga DITERIMA asal 3 visual step + konfirmasi checkbox terpenuhi.)

## CONSTRAINTS
- Files: `app/Http/Controllers/Admin/WellbeingProgramController.php` (create form, store, requestTermination), `resources/views/admin/wellbeing-programs/create.blade.php` (+ partial per step bila perlu), `app/Console/Commands/ExpireWellbeingPrograms.php` (`wellbeing:expire-programs`), entry `$schedule` di `app/Console/Kernel.php`, route BARU section admin group (`admin.wellbeing-programs.*`: GET create, POST store, POST {program}/request-termination), nav entry admin "Assign Program" icon 'heart' di `layouts/app.blade.php` — BARIS BARU SAJA di section yang sudah dibuat T3; no reformat baris existing (T5 merge serial kemudian).
- No Midtrans, no invoice entity, no add-on, no pro-rate (v1 manual).
- No touch file T5 (`Perusahaan/ProgramSubscriptionController.php`, `perusahaan/program-subscription.blade.php`, `AggregatePrivacy.php`, nav hr_admin section).
- File baru `chgrp hermes` + `g+r`. Conventional commits English.

## ACCEPTANCE
1. `WellbeingProgramAssignmentTest` baru (RefreshDatabase):
   - (C1) assign growth band 401–600 option A → program row + snapshot eksak (`monthly_price=20.499.999`, `deep_support_quota=15`, `ends_at` benar, `created_by`); redirect + flash + AdminAction row.
   - (R2) assign growth tanpa mode → 422; nilai di luar enum → 422.
   - (R6) terminasi efektif +20 hari → 422; +30 hari → accepted, program masih `active`; jalankan `artisan wellbeing:expire-programs` dengan tanggal efektif tercapai → `ended`, AdminAction row.
   - (biz §4) assign kontrak kedua saat masih aktif → 422 "masih ada program aktif"; setelah `ended` → sukses.
   - (biz §3) `employee_count` di luar band (1200) → error jelas; `employee_count` 30 + paket berbayar → 422; discovery (is_free) employee_count 30 → sukses.
   - (R9) ubah harga band master setelah assign → snapshot kontrak tidak berubah.
   - (A3) hr_admin POST store → 403.
2. View test: GET create admin 200, band auto-highlight sesuai employee_count, opsi A/B hanya muncul growth/impact, harga readonly + "+ pajak".
3. Full suite `/usr/local/bin/php artisan test` hijau, 0 edit test existing.

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil / deviations / blockers
