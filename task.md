# Soluusi B2B — Production Foundation (goal/production-foundation)

Baseline: `b5170cf` main. Integration branch: `goal/production-foundation`.
Worktree integrasi: `C:/Users/User/soluusib2b-goal-20261113`.
Model implementer: `tg/glm-5.3` (CLI headless).

## Pre-flight (mandor)
- [x] Git Windows verified 2.56.0
- [x] Worktree manual tersedia 4 (3 audit + 1 goal)
- [ ] PHP portable + Composer PHAR di user dir → **BLOCKER untuk semua test**
- [ ] `.env` lokal diverifikasi existing (tidak auto-seed, tidak overwrite, tidak ganti APP_KEY)
- [x] Smoke test `tg/glm-5.3` headless sukses (MODEL_OK)

## Phase 0 — Environment [model: tg/glm-5.3]
- [ ] T0.1 Install PHP 8.2+ portable ZIP ke `%USERPROFILE%\php` + Composer PHAR. Set PATH hanya untuk proses kuli. Verifikasi `php -v` ≥ 8.2 dan `composer --version`.
  verify: `php -v && composer --version` exit 0
- [ ] T0.2 `composer install` di worktree goal. JANGAN ubah `.env` kalau ada. JANGAN `key:generate` kalau APP_KEY sudah ada. Verifikasi composer.lock kompatibel dengan PHP version.
  verify: `vendor/autoload.php` ada; `php artisan --version` exit 0
- [ ] T0.3 Verifikasi `.env` lokal: file exists, DB_CONNECTION=sqlite, DB_DATABASE path valid, APP_KEY exists. JANGAN buat baru jika sudah ada. JANGAN migrate sebelum DB lokal terisolasi.
  verify: `php artisan migrate:status` exit 0 (bukan error koneksi); `.env` tidak di-overwrite
- [ ] T0.4 Baseline test run: `php artisan test`. Catat pass/fail count. Tidak wajib 66/66 — yang penting terdokumentasi.
  verify: output test tertulis di `docs/baseline-test-2026-11-14.txt`

## Phase 1 — P0 Quick Wins [model: tg/glm-5.3] (independent, bisa paralel)
- [x] T1.1 Fix Alpine `requestConsult` state. Tambah `requestConsult: false` ke `x-data` di `resources/views/perusahaan/employee.blade.php:16`. Tambah reset di Escape handler. **Committed `b9d066d`.**
  verify: modal buka/tutup via klik + Escape (manual browser); tidak ada console error
- [x] T1.2 Hapus placeholder logo + fake stats dari `resources/views/landing.blade.php`. **Committed `85ed9ff`.**
  verify: `grep -n "Logo [0-9]" resources/views/landing.blade.php` kosong; `grep -n "5.000+ kasus\|300+ konsultan" resources/views/landing.blade.php` kosong
- [ ] T1.3 Gate `/progress` di belakang `auth` + `role:admin`. Update `routes/web.php:67`. Spec: `docs/tasks/open/T1.3-gate-progress.md`
  verify: `php artisan test --filter=ProgressGateTest` hijau (test baru); manual: anonymous GET `/progress` → redirect login
- [ ] T1.4 Gate dummy payment routes di belakang `APP_ENV != 'production'`. Tambah middleware `env.notprod` atau kondisi di controller. Spec: `docs/tasks/open/T1.4-gate-dummy-payment.md`
  verify: dengan `APP_ENV=production php artisan config:cache`, GET `/checkout/{token}/dummy-payment` → 404
- [ ] T1.5 Rate limit endpoint chat store + booking store + reschedule. Tambah `throttle:30,1` (chat) dan `throttle:10,1` (booking/reschedule). Spec: `docs/tasks/open/T1.5-rate-limit.md`
  verify: `grep "throttle" routes/web.php | wc -l` ≥ 8; test hit ke-11 pada booking → 429

## Phase 2 — Mass-assignment + Policy + Tenant Enforcement [model: tg/glm-5.3]
- [ ] T2.1 Ganti `$guarded = ['id']` dan `$guarded = []` menjadi `$fillable` eksplisit di semua model money/PII. Spec: `docs/tasks/open/T2.1-fillable.md`
  verify: `grep -rn "guarded = \[\]" app/Models` kosong; `grep -rn "guarded = \['id'\]" app/Models` kosong; semua test hijau
- [ ] T2.2 Buat `PerusahaanSessionPolicy` + `ConsultationSessionPolicy`. Refactor `ChatController` dan `Klinik\SessionController` untuk pakai `$this->authorize(...)`. Spec: `docs/tasks/open/T2.2-policies.md`
  verify: `grep -c "abort_unless" app/Http/Controllers/ChatController.php` < 3; test negatif: employee A tidak bisa akses session employee B perusahaan lain (403)
- [ ] T2.3 Tambah test cross-tenant isolation: `tests/Feature/TenancyIsolationTest.php`. Spec: `docs/tasks/open/T2.3-tenancy-tests.md`
  verify: `php artisan test --filter=TenancyIsolationTest` hijau (minimal 6 skenario negatif)
- [ ] **T2.4 Tenant enforcement di layer data (BARU)**. Kolom `perusahaan_id` saja bukan isolasi. Tambah global scope / query enforcement di model tenant-scoped: `PerusahaanSession`, `PerusahaanEmployee`, `SekolahSession`, `SekolahStudent`, `CheckoutLink`, `Schedule`. Pastikan query tanpa tenant filter otomatis di-scope. Spec: `docs/tasks/open/T2.4-tenant-data-enforcement.md`
  verify: test: query langsung tanpa scope tidak return data tenant lain; test negatif tetap hijau

## Phase 3 — Inline admin closures → controllers [model: tg/glm-5.3]
- [ ] T3.1 Pindahkan 6 inline closures di `routes/web.php:153-211` ke controller + Form Request + audit log (`admin_actions` table). Spec: `docs/tasks/open/T3.1-admin-controllers.md`
  verify: `grep -c "Route::post.*function" routes/web.php` = 0; `php artisan route:cache` exit 0; test tiap endpoint hijau; `admin_actions` row terisi

## Phase 4 — Foundation refactor [model: tg/glm-5.3]
- [ ] T4.1 Selesaikan Laravel 11+ skeleton: pindahkan middleware ke `bootstrap/app.php`, hapus `app/Http/Kernel.php` + `app/Console/Kernel.php`. Spec: `docs/tasks/open/T4.1-laravel12-skeleton.md`
  verify: `app/Http/Kernel.php` tidak ada; `php artisan route:list` exit 0; semua test hijau
- [ ] T4.2 Extract `CheckoutFlow` service dari `CheckoutController`. Pastikan `reserveSession` atomic dengan `lockForUpdate`. Spec: `docs/tasks/open/T4.2-checkout-extract.md`
  verify: `wc -l app/Http/Controllers/CheckoutController.php` < 200; test `KlinikMoneyFlowTest` tetap hijau; test race condition
- [ ] T4.3 Buat `SessionStateMachine` untuk transisi status terpusat. Refactor `BookingService`, `ChatController`, `Klinik\SessionController`. Spec: `docs/tasks/open/T4.3-state-machine.md`
  verify: direct `$session->update(['status' => ...])` di controllers < 5; semua test hijau

## Phase 5 — UX P1 [model: tg/glm-5.3]
- [ ] T5.1 Wire atau hapus `POST /lead`. Spec: `docs/tasks/open/T5.1-lead-form.md`
  verify: POST `/lead` berhasil ATAU `grep "/lead" routes/web.php` kosong
- [ ] T5.2 Rebrand login page ke netral. Spec: `docs/tasks/open/T5.2-login-rebrand.md`
  verify: `grep "Portal B2B Klinik" resources/views/auth/login.blade.php` kosong
- [ ] T5.3 Tampilkan card "Menunggu ACC" + cancel di employee dashboard. Spec: `docs/tasks/open/T5.3-pending-acc-card.md`
  verify: test `RequestConsultationTest::pending_request_visible_and_cancellable` hijau
- [ ] T5.4 Ubah CTA "Jadi Mitra" ke `/daftar-mitra`. Spec: `docs/tasks/open/T5.4-jadi-mitra-cta.md`
  verify: `grep -n "#kontak" resources/views/landing.blade.php resources/views/verticals/*.blade.php` tidak ada di CTA
- [ ] T5.5 Implementasi password reset standar Laravel. Spec: `docs/tasks/open/T5.5-password-reset.md`
  verify: manual flow email → reset → login berhasil

## Phase 6 — AI seam + Config [model: tg/glm-5.3]
- [ ] T6.1 Buat `AIService` interface + provider via env + `ai_enabled` flag tenant. Spec: `docs/tasks/open/T6.1-ai-seam.md`
  verify: `interface_exists(App\Contracts\AIService::class)` true; test: flag off → tidak panggil provider
- [ ] T6.2 `MAX_SCHEDULES_PER_SESSION = 3` (total jadwal). Tambah `schedule_version` tracking. Enforcement: **reschedule ke-3 ditolak** (total 4 jadwal). Spec: `docs/tasks/open/T6.2-max-schedules.md` + ADR 001
  verify: test: jadwal awal + 2 reschedule = OK, reschedule ke-3 (total 4) ditolak 422

## Phase 7 — Hardening P2 [model: tg/glm-5.3]
- [ ] T7.1 Eager-load `departement` di `DashboardMetrics::build`. Spec: `docs/tasks/open/T7.1-dashboard-n1.md`
  verify: `DB::enableQueryLog()` test: dashboard HR < 15 queries
- [ ] T7.2 Hapus broadcast scaffolding mati. Spec: `docs/tasks/open/T7.2-broadcast-cleanup.md`
  verify: `ls app/Providers/BroadcastServiceProvider.php` gagal; `config/broadcasting.php` default `log`
- [ ] T7.3 Fix HR "Sesi aktif" filter overnight. Spec: `docs/tasks/open/T7.3-active-sessions-filter.md`
  verify: test: sesi jam 23:00 masih muncul jam 01:00 keesokan hari
- [ ] ~~T7.4 Hapus `app/Models/Model.php`~~ **CANCELLED** — masih dipakai 18 model. Spec: `docs/tasks/open/T7.4-remove-dead-model.md`
- [ ] T7.5 Pindahkan "Rule demo" ke `config/hap.php`. Spec: `docs/tasks/open/T7.5-hap-rule-config.md`
  verify: `grep "Rule demo" resources/views/associate/dashboard.blade.php` kosong
- [ ] T7.6 Tambah custom error pages 403/404/500. Spec: `docs/tasks/open/T7.6-error-pages.md`
  verify: manual trigger 404 → halaman custom

## Phase 8 — Flow Inti (P0 blocker) [model: tg/glm-5.3]
**Renumbered dari T8.x untuk hindari tabrakan dengan QA T8.x.**

- [ ] F1.1 HR review request queue. List pending requests, approve/reject, assign consultant. Spec: `docs/tasks/open/F1.1-hr-review-queue.md`
  verify: test: HR bisa approve request, consultant ter-assign, employee ter-notifikasi
- [ ] F1.2 Consultant slot proposal. Consultant propose 2-3 slot options untuk request yang di-ACC. Spec: `docs/tasks/open/F1.2-consultant-slot-proposal.md`
  verify: test: consultant propose slots, employee/HR lihat options
- [ ] F1.3 Employee slot confirmation. Employee pilih 1 slot dari proposal, auto-book. Spec: `docs/tasks/open/F1.3-employee-slot-confirmation.md`
  verify: test: employee confirm slot, session ter-book, status berubah
- [ ] F1.4 Session expiry sweep. Auto-cancel scheduled sessions yang lewat tanpa check-in. Spec: `docs/tasks/open/F1.4-session-expiry-sweep.md`
  verify: test: scheduled session > X jam tanpa check-in → auto-cancelled

## Phase 9 — Operasional (P1) [model: tg/glm-5.3]
- [ ] F2.1 Consultant assignment management (superadmin). Assign/remove consultant dari perusahaan/klinik pool. Spec: `docs/tasks/open/F2.1-consultant-assignment.md`
  verify: test: superadmin assign consultant ke perusahaan, consultant muncul di pool
- [ ] F2.2 View-as middleware + audit. Superadmin impersonasi tenant admin dengan logging. Spec: `docs/tasks/open/F2.2-view-as-audit.md`
  verify: test: view-as tercatat di `admin_actions`, data tenant ter-scope
- [ ] F2.3 HR consultant allocation approval. HR approve/override consultant assignment. Spec: `docs/tasks/open/F2.3-hr-allocation-approval.md`
  verify: test: HR bisa ganti consultant yang di-assign system
- [ ] F2.4 Full flow integration test. request → HR review → assignment → slot proposed → confirmed → in-session → completed. Spec: `docs/tasks/open/F2.4-full-flow-test.md`
  verify: test end-to-end hijau

## Phase 10 — Bootstrap workflow [model: tg/glm-5.3]
- [ ] F3.1 CLAUDE.md + AGENTS.md untuk workflow mandor/kuli. Spec: `docs/tasks/open/F3.1-claude-md.md`
  verify: file ada, dokumentasi workflow jelas
- [ ] F3.2 Subagent definitions (auditor, backend, frontend, test, docs, reviewer, server-ops). Spec: `docs/tasks/open/F3.2-subagents.md`
  verify: subagent bisa di-spawn dengan role spesifik
- [ ] F3.3 Commands: mandor, dispatch, review, ship, docs-sync. Spec: `docs/tasks/open/F3.3-commands.md`
  verify: commands executable
- [ ] F3.4 scripts/gate.sh — pre-merge checks (lint, test, no secret). Spec: `docs/tasks/open/F3.4-gate-sh.md`
  verify: script executable, fail jika ada secret/test fail
- [ ] F3.5 scripts/kuli.sh — launcher dengan model routing tg/glm-5.3. Spec: `docs/tasks/open/F3.5-kuli-sh.md`
  verify: script executable, dispatch kuli dengan model benar

## Phase QA — Independent QA [model: tg/glm-5.3]
- [ ] QA.1 Jalankan full test suite di branch integration. Verify: `php artisan test` hijau (target: ≥ baseline pass count, 0 regression).
- [ ] QA.2 Browser pass manual 5 flow. Dokumentasikan di `docs/qa/QA-2026-11-XX.md`.
- [ ] QA.3 Review security: grep mass-assignment, inline closures, abort_unless.
- [ ] QA.4 Verify tidak ada `.env` / APP_KEY / token / password di commit.

## Status: READY-FOR-KULI (Phase 1 quick wins — tidak butuh PHP runtime)

**Blocker aktif:**
1. PHP portable + Composer PHAR belum diinstall → Phase 0 BLOCKED. Owner izinkan download/setup lokal.

**Resolved:**
- ~~Model `tg/glm5.3`~~ → verified sebagai `tg/glm-5.3` (dengan dash). CLI headless MODEL_OK. Kuli sudah jalan untuk T1.1 + T1.2.

**Security findings tambahan:**
- High: `?employee_id=` superadmin preview tanpa audit log → sudah ditambahkan ke T3.1.
- High: race window pada checkout guest link → sudah ditambahkan ke T4.2.
- Med: chat email sinkron → T7.x queue.

**Yang bisa kuli kerjakan SEKARANG tanpa PHP:** T1.1, T1.2 (sudah committed).
**Yang butuh PHP:** T1.3–T1.5, T2.x, T3.x, dst. (verify pakai `php artisan test`).
