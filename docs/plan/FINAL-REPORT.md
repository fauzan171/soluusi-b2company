# FINAL-REPORT — Perusahaan Mission (W1–W8)

Tanggal: 2026-11-22. Branch final: `w8/qa-deploy-docs` (merge W1–W7).
Baseline masuk: `upgrade/laravel-12` 66 test / 321 assertions.

## 1. Ringkasan — 9 requirement meeting → status

Dokumen "9 requirement" eksplisit tidak ditemukan di repo; daftar berikut
direkonstruksi dari judul mission MASTER-PLAN ("Package Quota, Multi-Company
Consultant, Consultation Types, Multi-Assessment, Session Dashboard") +
rincian task W1–W8. Semua implement + teruji.

| # | Requirement | Implementasi | Bukti |
|---|---|---|---|
| 1 | Kuota paket per karyawan + bulanan (hangus/refund) | `packages`, `employee_packages`, `consultation_quota_ledger` append-only, `QuotaService` (K2), sweeper no-show, refund HR | QuotaServiceTest, NoShowSweepAndRefundTest, W8QuotaEdgeFinalTest |
| 2 | Assignment konsultan multi-perusahaan + kontrak | extend `consultant_pools` (+status/contract, K6), `consultant_work_hours`, `consultant_availability_exceptions`, slot computed-on-read (K4) | W3ConsultantDirectoryTest, W6TypeSafetyBookingTest |
| 3 | Lock "minta konsultan" per perusahaan | `perusahaans.consultant_request_enabled` default OFF + guard server-side (K8) | QuotaEntryPointsTest, request-toggle admin |
| 4 | 3 tipe konsultasi + ruang 1-on-1 | `perusahaan_sessions.consultation_type` (K9), `rooms` + `RoomService::pickFreeRoom` anti-konflik | W5MeetingLinkRoomTest, W6TypeSafetyBookingTest |
| 5 | Meeting link per sesi | endpoint meeting-link (konsultan/HR, validasi gmeet/zoom https, K10) | W5MeetingLinkRoomTest |
| 6 | Chat konsultasi (dipertahankan polling) + retensi | `chat_messages.sender_role`, Prunable 90 hari (K11) | W6EmployeeSessionsTest, W5 |
| 7 | Multi-assessment + eskalasi | `assessment_instruments` sebagai data (who5/phq9/gad7/cbi, K12/K13), `escalated` PHQ-9 item-9, band parity legacy | W4AssessmentEngineTest |
| 8 | Catatan sesi + hasil + review rating | `consultation_notes` (encrypted), `consultation_results`, `consultation_reviews` + agregat (K14) | W7NotesResultsReviewsTest |
| 9 | Dashboard karyawan "Sesi Saya" + privacy audit | dashboard riwayat/status (W6) + `audit_logs` minimal (K16) | W6EmployeeSessionsTest, W7 |

## 2. Test counts

- Masuk: **66 test, 321 assertions** (base upgrade/laravel-12).
- Keluar (branch W8, suite penuh): **199 test, 798 assertions, hijau** (~9.6s,
  sqlite :memory:, `artisan test`).
- Tambah W8: `W8CrossTenantIsolationTest` (15 test matriks lintas tenant),
  `W8QuotaEdgeFinalTest` (3 test edge).
- Rollback migrasi diverifikasi: `W1RollbackCheckTest` (9 langkah down + re-migrate).

## 3. Keputusan arsitektur utama (K-decisions, ringkas)

- **K2 ledger append-only** (bukan counter): audit natural, hangus+refund =
  event, UNIQUE(session,event) idempotent.
- **K4 slot computed-on-read** dari template work hours: tanpa materialisasi,
  ms-level di skala 40 konsultan.
- **K6 extend `consultant_pools`** (bukan tabel baru): satu sumber kebenaran
  consultant↔tenant.
- **K8 flag per-perusahaan** `consultant_request_enabled` default OFF.
- **K9 `consultation_type` terpisah `konsultan_type`**: hindari migrasi data
  overloading.
- **K11 chat tetap HTTP polling** (5s): php -S 6-worker math + PDP.
- **K12 instrumen = data** (seeder), extend mesin screening existing.
- **K14/K16 notes encrypted cast, audit_logs custom minimal** (bukan spatie).

Detail + alasan: `docs/plan/MASTER-PLAN.md` §0.

## 4. Deviasi & catatan

1. **Rating dual path**: `perusahaan_sessions.rating` (legacy W3, route
   chat.rate) vs `consultation_reviews` agregat (W7, route reviews.store) —
   dua sumber rating hidup bersama. Keputusan: reviews table = source of
   truth untuk agregat konsultan (anonym, comment tak terlihat konsultan);
   kolom legacy read-only. Open item di bawah.
2. **Scheduler prod mati**: seluruh `Kernel::$schedule` (sweep no-show, expiry,
   dual-confirm, prune) dead code sampai owner mengaktifkan
   `soluusi-scheduler.timer` (file unit W8 disiapkan, aktivasi manual —
   larangan start service dari agent).
3. **Chat tetap polling** — upgrade ke broadcast/Reverb sengaja ditunda (K11).
4. **`QuotaService::release()`** terdefinisi tapi belum dipanggil kode prod
   (cancel path pakai quota_consumed flag, bukan release event) — behavior
   di-lock oleh test W8 supaya kontrak API-nya jelas.
5. **brief-presentasi.md** (di-update prod tree) tidak ada di branch ini —
   file itu milik tree prod (commit 5f13e12 bukan ancestor). Link ke
   MASTER-PLAN ditambahkan via CHANGELOG.md; update brief-presentasi = kerja
   saat deploy di prod tree.
6. **Cherry-pick prod fixes** (44bed0d/52954db/3d48aed + finfo polyfill)
   TIDAK di-apply di branch ini — disengaja: dilakukan saat pre-deploy di
   branch deploy (urutan + konflik index.php didokumentasikan deploy.md).

## 5. Langkah deploy

→ `docs/dev/deploy.md` (lengkap). Inti:

1. **BASE SWAP warning**: mission base = upgrade/laravel-12 (66-test
   baseline); prod tree = improvement-live (L11-style, 556 test + fix prod).
   Deploy branch ini = ganti base, keputusan owner.
2. Backup `database.sqlite` SEBELUM `migrate --force`.
3. Cherry-pick 44bed0d → 52954db → 3d48aed (index.php resolve manual).
4. Aktifkan `soluusi-scheduler.timer` (systemd user unit, manual).
5. Smoke: login 3 role, booking+chat end-to-end, ledger terisi, timer live.

## 6. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Base swap merusak fitur improvement-live yang tak ter-cover mission suite | checklist deploy.md: jalankan suite di tree deploy + smoke 5 langkah; keputusan go/no-go owner |
| Migrasi sqlite di prod (rebuild tabel enum) | semua migrasi additive + guarded + reversible; W1RollbackCheckTest; backup sqlite wajib |
| Scheduler belum jalan → no_show tak pernah ter-set | deploy.md langkah aktivasi timer + smoke list-timers |
| php -S worker stale classmap pasca-deploy | cherry-pick 44bed0d + RestartWebServer job (52954db) |
| Data legacy tanpa employee_packages | fallback kuota flat tenant (read-only) + auto-assign default saat reserve; upgrade path `quota:backfill` |

## 7. Open items untuk owner

1. **Unify rating**: migrasi `perusahaan_sessions.rating` →
   `consultation_reviews` (atau deprecate chat.rate); hingga itu, dua
   endpoint rating hidup (4.1).
2. **Aktivasi scheduler** di prod (manual, deploy.md §aktivasi).
3. **Cherry-pick prod fixes** saat pre-deploy (deploy.md tabel sha).
4. **README.md root** masih stock Laravel — recon-R6 menyarankan rewrite
   10-baris (di luar scope W8).
5. **Chat broadcast upgrade** (K11): bila traffic tumbuh, evaluasi Reverb —
   scaffolding broadcast lama masih ada.
6. **`docs/plan/` untracked policy**: folder mission docs belum
   diputuskan tracking-nya (commit atau biarkan local-only).

## 8. Artefak mission

- Plan: `docs/plan/MASTER-PLAN.md` (K1–K16, frozen), `PROGRESS.md` (log merge).
- ERD delta: `docs/plan/ERD.md` (Mermaid).
- Deploy: `docs/dev/deploy.md`, `deploy/soluusi-scheduler.{service,timer}`.
- Changelog: `CHANGELOG.md`.
- Docs stale: `docs/archive/` (9 file, git mv; 1 PDF duplikat dihapus).
