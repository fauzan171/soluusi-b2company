# wb-T1 — Migrasi + model + seeder katalog Wellbeing

Status: READY
Depends: — (task pertama; blocking T2–T6)

## GOAL
Buat 4 tabel baru (katalog 4 paket + 15 band harga + kontrak + ledger) via 2 migrasi aditif, 4 model Eloquent, dan seeder katalog idempotent — tanpa menyentuh tabel/kolom existing.

## CONTEXT
- Worktree `/home/hermes/workspace/projects/soluusi-b2b-wellbeing`, branch `feat/wellbeing-product` @ `5dce21f`, baseline 712 test hijau.
- Skema EKSAK di `docs/plan/research-arch.md` §3.1–3.6 (salin definisi kolom dari sana — sudah disepakati, jangan redesain).
- Preseden pola: migration M1 `2026_11_20_000001_create_mission_m1_packages_tables.php` (2 tabel per file, TEXT enum, `Schema::hasColumn` guard), seeder `database/seeders/MissionPackageSeeder.php` (`firstOrCreate` per code), model `app/Models/AssessmentInstrument.php` (definitions-as-data).
- Data katalog & harga: `PRODUCT-DOC-SUMMARY.md` §1 (matrix 4 paket) + §2 (tabel harga 5 band: 50–150, 151–400, 401–600, 601–800, 801–1000; Foundation 7.999.999/14.999.999/20.999.999/25.999.999/30.999.999; Growth 7.499.999/14.499.999/20.499.999/25.499.999/30.499.999; Impact 6.999.999/13.999.999/19.999.999/24.999.999/29.999.999; MHCU Growth 5/10/15/20/25; MHCU Impact 10/20/30/40/50). Discovery: is_free, 14 hari (duration_days=14), assessment_count=1, max_participants=40, deep_support_quota=8, mode=null, webinar=0.

## CONSTRAINTS
- Migrasi:
  1. `database/migrations/2026_11_23_000001_create_wellbeing_catalog_tables.php` — `wellbeing_packages` + `wellbeing_pricing_bands` (arch §3.1+3.2, termasuk UNIQUE(`wellbeing_package_id`,`band_min`)).
  2. `database/migrations/2026_11_23_000002_create_company_wellbeing_programs_table.php` — `company_wellbeing_programs` + `wellbeing_quota_ledger` (arch §3.3+3.4; tambahkan juga kolom snapshot `monthly_price decimal(14,2) nullable` + `assessment_domains json nullable` di program — dipakai T4 invoice/scope; tambah `selected_employee_ids json nullable` + `notice_requested_at timestamp nullable` + `terminated_at date nullable` untuk R4/R6). UNIQUE(`perusahaan_session_id`,`event`) di ledger.
  - 0 ALTER tabel existing. 0 kolom existing berubah. TEXT string, bukan native enum (sqlite-safe).
- Models `app/Models/WellbeingPackage.php`, `WellbeingPricingBand.php`, `CompanyWellbeingProgram.php`, `WellbeingQuotaLedger.php` — `$guarded = ['id']` (pola Package.php), relasi per arch §3.5 (Package hasMany Band + Program; Program belongsTo Perusahaan/WellbeingPricingBand/User(created_by) + hasMany Ledger; Perusahaan hasMany Program — tambahkan method `programs()` di `app/Models/Perusahaan.php`, hanya 1 method baru, jangan sentuh yang lain).
- Seeder `database/seeders/WellbeingPackageSeeder.php`: 4 paket + 15 band rows, `firstOrCreate` per `code` / (`wellbeing_package_id`,`band_min`). TIDAK dipanggil dari `DatabaseSeeder.php`. `MissionPackageSeeder` & `Wellness Basic` TIDAK diubah.
- File baru: setelah tulis jalankan `chgrp hermes <file> && chmod g+r <file>`.
- No PII di fixture/test data. Conventional commits English (`feat: ...`).

## ACCEPTANCE
1. `WellbeingSchemaTest` baru (`tests/Feature/WellbeingSchemaTest.php`, RefreshDatabase, pola `W1FoundationSchemaTest`): assert 4 tabel + kolom kunci ada (`wellbeing_packages.code` unique, `wellbeing_pricing_bands.mhcu_quota`, `company_wellbeing_programs.status` default `active`, `wellbeing_quota_ledger.bucket`).
2. Seeder test: `artisan('db:seed --class=WellbeingPackageSeeder')->assertExitCode(0)` → 4 paket (discovery is_free=true, impact quarterly_review=true), 15 band, harga eksak 3 spot-check per PRODUCT-DOC-SUMMARY §2; re-run seeder → count tidak berubah (idempotent).
3. Full suite: `/usr/local/bin/php artisan test` → 712 + test baru, semua passed, exit 0. 0 edit file test existing.
4. `php artisan migrate:fresh` jalan bersih (sqlite :memory:).

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil / deviations / blockers
