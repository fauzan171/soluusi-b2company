# Phase 0 — Recon Summary (66/66 green at 80ac781)

Base: `feat/mission-package-quota` @ `80ac781` = brief tip `65e3e6e` + recon commits. Full detail: `recon-r1..r6-*.md`.

## Architecture reality (vs mission doc assumptions)

| Mission doc says | Codebase reality |
|---|---|
| "company" | `perusahaans` (Indonesian naming throughout) |
| consultation sessions | `perusahaan_sessions` (kolom `konsultan_type`: psikolog/nutrisionis/karir/fisioterapis — ini **jenis konsultan**, bukan tipe media konsultasi) |
| consultant assignment | `consultant_pools` (per-tenant, consultant+vertical_id) — `consultant_companies` lama = pivot CV, bukan assignment |
| schedules + slots | `schedules` (shared perusahaan/sekolah/klinik) + `booking_claims` (PK-lock anti race) |
| quota | `perusahaans.sessions_per_employee_month` (default 2) — ditegakkan di `BookingService::book` saja |

## Critical findings (ranked)

1. **Quota hole** — `EmployeeController::startChat` (232) & `requestConsultation` (277) bypass quota gate entirely; dua jalur pembuatan sesi divergen (BookingService vs langsung). W2 ledger harus menutup SEMUA jalur.
2. **Zero policies/gates** — authz = ad-hoc `abort_*` per controller (R2). Semua route baru mission wajib pakai pola guard server-side yang eksplisit; risk register entry.
3. **Schema ahead of code** — `delivery_status`, `no_show_party`, `perusahaan_assessments.section/status/consultant_id/conclusion`, `screening_questions/answers` + `ScoringService` sudah ada tapi tidak dipakai produksi. Bisa jadi fondasi W4/W7 daripada buat baru.
4. **No lifecycle automation** — `no_show` tidak pernah di-set oleh kode apapun; scheduler prod MATI (tidak ada cron untuk `schedule:run`, 5 job = dead code). W2 (forfeit scheduler) wajib menghidupkan mekanisme scheduler.
5. **CAP slot supply manual/seeded** — tidak ada UI pembuatan jadwal perusahaan (klinik-only). Workhour + slot generator (W3) = greenfield; `schedules` pola existing bisa dipakai.
6. **php -S prod fixes belum di mission branch** — classmap guard, RestartWebServer job, finfo polyfill, REQUEST_URI fix hanya di `improvement-live`. DEPLOY RISK: mission merge-to-deploy tanpa cherry-pick ini = empty-500.
7. **WHO-5 hardcoded** di 2 controller (Perusahaan+Sekolah duplicate); screening engine setengah-jadi = basis natural W4.
8. **Superadmin tak bisa masuk portal konsultan** (role list beda); `{session}` binding ambigu 2 model di `/consultant` prefix.
9. **sqlite enum = varchar+CHECK** — tambah status/enum = rebuild tabel per-driver; migration baru WAJIB idempotent + driver-dispatch.
10. **Docs**: 12/24 md stale, `memory.md` misleading, deploy doc tidak ada.

## Env facts for workers

- Tests: `php vendor/bin/phpunit` → 66/66 (321 assertions, ~3s). Fresh worktree butuh: `mkdir -p storage/framework/{views,cache,sessions}` + `composer install --ignore-platform-req=ext-fileinfo` (binary `/home/hermes/.local/bin/composer`) + `npm install && npm run build`.
- t3 umask 660 → semua file baru `chmod 664` (hermes processes harus bisa baca).
- safelock noise di semua stderr → filter `grep -viE "safelock|userdatadomains"`.
- Baseline DoD: 66/66 + new tests, migration idempotent (guard `hasColumn`/`hasTable`), sqlite-compat.

## Verification (mandor, not agent claims)

- quota hole startChat: dibaca langsung, confirmed (232-255, tanpa cek kuota).
- dual session path: BookingService::book (85) vs startChat/requestConsultation, confirmed.
- base 16-commit gap → di-fast-forward ke `65e3e6e`, re-migrate + 66/66 green, confirmed.
