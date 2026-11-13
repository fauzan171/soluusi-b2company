# Soluusi B2B — Production Foundation (goal/production-foundation)

Baseline: `b5170cf` main. Integration branch: `goal/production-foundation`.
Worktree integrasi: `C:/Users/User/soluusib2b-goal-20261113`.

## Pre-flight (mandor)
- [x] Git Windows verified 2.56.0
- [x] Worktree manual tersedia 4 (3 audit + 1 goal)
- [ ] PHP + Composer terinstal dan di PATH → **BLOCKER untuk semua test**
- [ ] `.env` lokal dibuat manual (tidak auto-seed, tidak overwrite)
- [ ] Smoke test `tg/glm5.3` headless sukses

## Phase 0 — Environment [model: sonnet]
- [ ] T0.1 Install PHP 8.2+ dan Composer. Verifikasi `php -v` ≥ 8.2 dan `composer --version`.
  verify: `php -v && composer --version` exit 0
- [ ] T0.2 `composer install` di worktree goal. JANGAN ubah `.env` kalau ada. JANGAN `key:generate` kalau APP_KEY sudah ada.
  verify: `vendor/autoload.php` ada; `php artisan --version` exit 0
- [ ] T0.3 Buat `.env` lokal dari `.env.example`, set `DB_CONNECTION=sqlite`, `DB_DATABASE=/abs/path/database/database.sqlite`, `APP_ENV=local`, `APP_DEBUG=true`. Generate key hanya jika belum ada.
  verify: `php artisan migrate:status` exit 0 (bukan error koneksi)
- [ ] T0.4 Baseline test run: `php artisan test`. Catat pass/fail count. Tidak wajib 66/66 — yang penting terdokumentasi.
  verify: output test tertulis di `docs/baseline-test-2026-11-14.txt`

## Phase 1 — P0 Quick Wins [model: sonnet] (independent, bisa paralel)
- [ ] T1.1 Fix Alpine `requestConsult` state. Tambah `requestConsult: false` ke `x-data` di `resources/views/perusahaan/employee.blade.php:16`. Tambah reset di Escape handler. File spec: `docs/tasks/open/T1.1-alpine-requestconsult.md`
  verify: modal buka/tutup via klik + Escape (manual browser); tidak ada console error
- [ ] T1.2 Hapus placeholder logo + fake stats dari `resources/views/landing.blade.php` (12 "Logo N", "300+ konsultan", "5.000+ kasus"). Ganti dengan copy jujur ATAU hapus section. Spec: `docs/tasks/open/T1.2-landing-placeholder.md`
  verify: `grep -n "Logo [0-9]" resources/views/landing.blade.php` kosong; `grep -n "5.000+ kasus\|300+ konsultan" resources/views/landing.blade.php` kosong
- [ ] T1.3 Gate `/progress` di belakang `auth` + `role:admin`. Update `routes/web.php:67`. Spec: `docs/tasks/open/T1.3-gate-progress.md`
  verify: `php artisan test --filter=ProgressGateTest` hijau (test baru); manual: anonymous GET `/progress` → redirect login
- [ ] T1.4 Gate dummy payment routes di belakang `APP_ENV != 'production'`. Tambah middleware `env.notprod` atau kondisi di controller. Spec: `docs/tasks/open/T1.4-gate-dummy-payment.md`
  verify: dengan `APP_ENV=production php artisan config:cache`, GET `/checkout/{token}/dummy-payment` → 404
- [ ] T1.5 Rate limit endpoint chat store + booking store + reschedule. Tambah `throttle:30,1` (chat) dan `throttle:10,1` (booking/reschedule). Spec: `docs/tasks/open/T1.5-rate-limit.md`
  verify: `grep "throttle" routes/web.php | wc -l` ≥ 8; test hit ke-11 pada booking → 429

## Phase 2 — Mass-assignment + Policy [model: sonnet]
- [ ] T2.1 Ganti `$guarded = ['id']` dan `$guarded = []` menjadi `$fillable` eksplisit di: `ConsultationSession`, `ChatMessage`, `ConsultantRating`, `ConsultantPool`, `Payment`, `SocialAccount`, `ConsultantCompany`. Spec: `docs/tasks/open/T2.1-fillable.md`
  verify: `grep -rn "guarded = \[\]" app/Models` kosong; `grep -rn "guarded = \['id'\]" app/Models` kosong; semua test hijau
- [ ] T2.2 Buat `PerusahaanSessionPolicy` (view/send/end/rate) + `ConsultationSessionPolicy` (checkIn/markDone/verify). Register di `AppServiceProvider` ATAU `AuthServiceProvider`. Refactor `ChatController` (5 inline abort_unless) dan `Klinik\SessionController::authorizeConsultant` untuk pakai `$this->authorize(...)`. Spec: `docs/tasks/open/T2.2-policies.md`
  verify: `grep -c "abort_unless" app/Http/Controllers/ChatController.php` = 0; test negatif: employee A tidak bisa akses session employee B perusahaan lain (403)
- [ ] T2.3 Tambah test cross-tenant isolation: `tests/Feature/TenancyIsolationTest.php`. Cover: employee A perusahaan X tidak bisa GET/POST session employee B perusahaan Y; consultant tidak bisa akses session di luar assignment; HR tidak bisa akses dashboard perusahaan lain. Spec: `docs/tasks/open/T2.3-tenancy-tests.md`
  verify: `php artisan test --filter=TenancyIsolationTest` hijau (minimal 6 skenario negatif)

## Phase 3 — Inline admin closures → controllers [model: sonnet]
- [ ] T3.1 Pindahkan 6 inline closures di `routes/web.php:153-211` ke controller: `Admin\PervormaController@pay`, `Admin\KlinikApprovalController@store`, `Admin\PerusahaanActivationController@store`, `Admin\SekolahActivationController@store`, `Admin\SplitSettlementController@settle`, `Admin\SessionVerificationController@store`. Masing-masing pakai Form Request untuk validasi. Tambah audit log row per aksi (table `admin_actions`: admin_id, action, target_type, target_id, payload_json, created_at). Spec: `docs/tasks/open/T3.1-admin-controllers.md`
  verify: `grep -c "Route::post.*function" routes/web.php` = 0; `php artisan route:cache` exit 0; test tiap endpoint hijau; `admin_actions` row terisi saat aksi dipicu

## Phase 4 — Foundation refactor [model: opus]
- [ ] T4.1 Selesaikan Laravel 11+ skeleton: pindahkan middleware alias dari `app/Http/Kernel.php` ke `bootstrap/app.php` `withMiddleware()`; hapus `app/Http/Kernel.php` dan `app/Console/Kernel.php`; pindahkan `$commands` dan schedule ke `bootstrap/app.php` / `routes/console.php`. Spec: `docs/tasks/open/T4.1-laravel12-skeleton.md`
  verify: `app/Http/Kernel.php` tidak ada; `php artisan route:list` exit 0; semua test hijau
- [ ] T4.2 Extract `App\Services\Klinik\CheckoutFlow` dari `CheckoutController` (payment start, reserve, dummy, voucher math). Extract `App\Support\Checkout\CustomerDashboardState` untuk state machine array. Controller tinggal HTTP verbs + validasi. Spec: `docs/tasks/open/T4.2-checkout-extract.md`
  verify: `wc -l app/Http/Controllers/CheckoutController.php` < 200; test `KlinikMoneyFlowTest` tetap hijau
- [ ] T4.3 Buat `App\Services\SessionStateMachine` sebagai single source untuk transisi status (book/cancel/reschedule/start/end/checkIn/markDone/verify). Refactor `BookingService`, `ChatController`, `Klinik\SessionController` untuk delegasi ke state machine. Spec: `docs/tasks/open/T4.3-state-machine.md`
  verify: `grep -rn "status.*=.*'" app/Services/BookingService.php app/Http/Controllers/ChatController.php app/Http/Controllers/Klinik/SessionController.php | wc -l` < 10 (sisanya via state machine); semua test hijau

## Phase 5 — UX P1 [model: sonnet]
- [ ] T5.1 Wire `POST /lead` ke form di landing `resources/views/landing.blade.php` ATAU hapus route + controller. Pilih satu — jangan biarkan yatim. Spec: `docs/tasks/open/T5.1-lead-form.md`
  verify: POST `/lead` dari form berhasil (201/redirect) ATAU `grep "/lead" routes/web.php` kosong
- [ ] T5.2 Rebrand login page ke netral: ganti copy di `resources/views/auth/login.blade.php:38-40` jadi "Portal Soluusi Mitra" + subcopy generik. Spec: `docs/tasks/open/T5.2-login-rebrand.md`
  verify: `grep "Portal B2B Klinik" resources/views/auth/login.blade.php` kosong; manual browser pass
- [ ] T5.3 Tampilkan card "Menunggu ACC konsultan" di employee dashboard + tombol cancel. Update `Perusahaan\EmployeeController::index` untuk load pending request; tambah route `POST /employee/sessions/{session}/cancel-pending`. Spec: `docs/tasks/open/T5.3-pending-acc-card.md`
  verify: test `RequestConsultationTest::pending_request_visible_and_cancellable` hijau
- [ ] T5.4 Ubah CTA "Jadi Mitra" di landing + 3 vertical pages agar ke `/daftar-mitra` (bukan `#kontak`). Spec: `docs/tasks/open/T5.4-jadi-mitra-cta.md`
  verify: `grep -n "#kontak" resources/views/landing.blade.php resources/views/verticals/*.blade.php` tidak ada di button CTA "Jadi Mitra"
- [ ] T5.5 Implementasi password reset standar Laravel (routes + views + mail). Spec: `docs/tasks/open/T5.5-password-reset.md`
  verify: manual flow email → reset → login dengan password baru (mailhog/mailpit lokal)

## Phase 6 — AI seam [model: opus]
- [ ] T6.1 Buat `App\Contracts\AIService` interface dengan method `generateInsight(array $payload): array`. Provider di-resolve via `config/ai.php` key `provider`. Buat `App\Services\AI\NullAIService` (default) dan `App\Services\AI\OpenAIService` (skeleton, tidak aktif). Tambah `ai_enabled` boolean ke table tenant (`kliniks`, `perusahaans`, `sekolahs`). Refactor `AiInsightService` untuk pakai interface. Spec: `docs/tasks/open/T6.1-ai-seam.md`
  verify: `interface_exists(App\Contracts\AIService::class)` true; test: flag off → tidak panggil provider; flag on → provider fake mengembalikan struktur valid
- [ ] T6.2 Tambah `MAX_SCHEDULES_PER_SESSION = 3` ke `config/session.php`. Enforcement di `SessionStateMachine::reschedule` (dari T4.3). Tambah kolom `reschedule_count` ke `consultation_sessions`. Spec: `docs/tasks/open/T6.2-max-schedules.md`
  verify: test: reschedule ke-4 ditolak dengan 422 + pesan jelas

## Phase 7 — Hardening P2 [model: sonnet]
- [ ] T7.1 Eager-load `departement` di `DashboardMetrics::build`. Pindahkan in-memory `groupBy` ke query-level `selectRaw`. Spec: `docs/tasks/open/T7.1-dashboard-n1.md`
  verify: `DB::enableQueryLog()` test: dashboard HR < 15 queries
- [ ] T7.2 Hapus broadcast scaffolding mati (`routes/channels.php`, `BroadcastServiceProvider.php`) ATAU commit Reverb. Pilih hapus (MVP). Update `config/broadcasting.php` default ke `log`. Spec: `docs/tasks/open/T7.2-broadcast-cleanup.md`
  verify: `ls app/Providers/BroadcastServiceProvider.php` gagal; `config/broadcasting.php` default `log`
- [ ] T7.3 Fix HR "Sesi aktif" filter overnight. Ganti `scheduled_at >= today` jadi `status = 'in_progress' OR scheduled_at >= now()->subHours(12)`. Spec: `docs/tasks/open/T7.3-active-sessions-filter.md`
  verify: test: sesi jam 23:00 masih muncul jam 01:00 keesokan hari
- [ ] T7.4 Hapus `app/Models/Model.php` (tidak ada yang extends). Spec: `docs/tasks/open/T7.4-remove-dead-model.md`
  verify: `grep -rn "extends Model" app/Models | grep -v "use Illuminate\\\\Database\\\\Eloquent\\\\Model"` kosong
- [ ] T7.5 Pindahkan "Rule demo" di `associate/dashboard.blade.php:20-22` ke `config/hap.php`. Spec: `docs/tasks/open/T7.5-hap-rule-config.md`
  verify: `grep "Rule demo" resources/views/associate/dashboard.blade.php` kosong; view render dari `config('hap.rule_demo')`
- [ ] T7.6 Tambah custom error pages 403/404/500 brand-neutral. Spec: `docs/tasks/open/T7.6-error-pages.md`
  verify: manual trigger 404 → halaman custom (bukan default Laravel)

## Phase 8 — QA independent [model: opus]
- [ ] T8.1 Jalankan full test suite di branch integration. Verify: `php artisan test` hijau (target: ≥ baseline pass count, 0 regression).
- [ ] T8.2 Browser pass manual 5 flow: (1) partner signup→approve→login, (2) employee request-consult→ACC→chat→end→rate, (3) HR dashboard review, (4) admin approve/split-settle/verify, (5) consultant sessions→checkin→mark-done→chat. Dokumentasikan di `docs/qa/QA-2026-11-XX.md`.
- [ ] T8.3 Review security: `grep -rn "guarded = \[\]" app/Models` kosong; `grep -rn "abort_unless" app/Http/Controllers` < 5; route:list tidak ada closure mutasi.
- [ ] T8.4 Verify tidak ada `.env` / APP_KEY / token / password di commit: `git diff main...HEAD -- .env*` kosong; `git grep -i "password\|secret\|app_key" -- '*.php' ':!config' ':!database'` hasil ditelaah manual.

## Status: READY-FOR-KULI (Phase 1 quick wins — tidak butuh PHP runtime)

**Blocker aktif:**
1. PHP + Composer tidak di PATH → Phase 0 (test runtime) BLOCKED sampai env tersedia. Owner install manual atau beri path kustom.

**Resolved:**
- ~~Model `tg/glm5.3`~~ → verified sebagai `tg/glm-5.3` (dengan dash). CLI headless MODEL_OK. Kuli sudah jalan untuk T1.1 + T1.2.

**Security findings tambahan (dari audit langsung):**
- High: `?employee_id=` superadmin preview tanpa audit log → sudah ditambahkan ke T3.1.
- High: race window pada checkout guest link → sudah ditambahkan ke T4.2 (extract CheckoutFlow + pastikan `lockForUpdate`).
- Med: chat email sinkron → T7.x queue.

**Yang bisa kuli kerjakan SEKARANG tanpa PHP:** T1.1, T1.2 (pure Blade edit, verify via grep + manual browser).
**Yang butuh PHP:** T1.3, T1.4, T1.5, T2.x, T3.x, dst. (verify pakai `php artisan test`).
