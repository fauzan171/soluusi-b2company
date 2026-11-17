# Superadmin Control Center

Branch: `goal/superadmin-control-center` (base `b61552c`). Worktree: `/home/seva-ojan/wt/soluusib2b-production-foundation`.

## Goal
Satu control center superadmin: kelola 3 vertikal tenant (klinik/perusahaan/sekolah), impersonation penuh read-only ke semua role, dan pervorma/commission associate untuk 3 vertikal.

## Context
- Route admin ada di `routes/web.php:158-201` (grup `role:admin`); portal tenant pakai `role:X,admin` KECUALI `consultant` (web.php:138) dan `associate` (web.php:152) → superadmin 403 di dua portal itu.
- ViewAs sekarang employee-only (`app/Support/ViewAs.php:16`), read-only via guard manual di `EmployeeController.php:54-69` (bukan middleware; POST `booking.cancel` + `start-chat` lolos guard), hanya `PerusahaanContext` yang kenal view-as, tanpa expiry, tanpa UI start.
- Provisioning terpecah: perusahaan = form transaksi (`AdminPerusahaanController::store`), klinik = self-register publik → approval queue, sekolah = SEEDER SAJA (tanpa UI admin sama sekali).
- Associate HAP: `kliniks.associate_id`, tabel `hap_pervormas` unique(klinik_id,stage), `HapPervormaService` 3-stage 5/25/40 window 90 hari idempotent. Playbook: HAP 250K/250K/500K; SAP 300K/400K/800K; CAP 600K/800K/1.6M (stage 1=tenant activated, 2=50 sesi, 3=150 sesi).

## Architecture Decision
- Additive pages di prefix `admin` existing (tiru pattern `AdminPerusahaanController` + blade `resources/views/admin/*.blade.php`); NO new packages (no spatie/filament) — pattern-nya saja yang ditiru.
- Impersonation: generalize `ViewAs` struct session `{target_user_id, target_role, vertical, tenant_id, started_at, expires_at}` + middleware read-only deny-by-default di grup `web` (tutup 2 lubang POST existing sekaligus). Reject: per-route guard manual (sudah terbukti bocor) dan act-on-behalf tulis-as-target (riskan untuk data healthcare; read-only cukup — YAGNI).
- Pervorma 3 vertikal: tabel mirror `perusahaan_pervormas` + `sekolah_pervormas` (schema identik `hap_pervormas`, unique(tenant_id,stage)) — konsisten dengan pattern tabel-per-vertikal codebase. Reject: tabel polymorphic tunggal + rename `hap_pervormas` (diff besar, migrasi data, beda dari konvensi codebase).

## Phases
Format task: `id (size, parallel_group) — deskripsi — files — verify`.
Est: S=<½ hari, M=1 hari, L=2 hari. Semua aksi admin baru WAJIB `AdminAction::record` + test audit.

### Phase A — Fondasi control center
**Dependency: A1→A2→A3→A4 (chain, sama-sama menyentuh `resources/views/admin/tenants/` + routes admin). A5/A6/A7 independen — parallel_group `a-indep`, boleh jalan paralel setelah A2 (butuh status/kolom A2 untuk badge A7) DAN paralel terhadap chain A3/A4.**

- [x] A1 (L, `a-tenant`) — Tenant management pages: list+detail+edit untuk klinik+perusahaan, list+create+detail+edit untuk sekolah (dari nol, sekarang seeder saja). Search/filter nama+status, layout konsisten (max-w-7xl, Tailwind, flash `session('success')`). Controller `Admin\Tenant\{Klinik,Perusahaan,Sekolah}Controller` + blade `resources/views/admin/tenants/{klinik,perusahaan,sekolah}/index|show|edit.blade.php`.
  verify: `php artisan test --filter=TenantManagementTest` hijau (list 3 vertikal, search, detail render, edit update ter-audit)
- [x] A2 (M, `a-tenant`) — Tenant lifecycle: kolom `status` (active/suspended/archived — migration ke 3 tabel tenant, default active) + suspend/activate route dengan alasan WAJIB (validated) + `AdminAction::record`. Suspend = blokir login admin tenant (cek di login/middleware role + tenant suspended → ditolak) + blokir context resolve portal.
  verify: `php artisan test --filter=TenantLifecycleTest` hijau (suspend tanpa alasan → 422; suspended → admin tenant login/portal ditolak; audit row ada; active tenant tidak terpengaruh)
- [x] A3 (L, `a-tenant`) — Provisioning wizard 1 pintu: form create 3 tipe tenant + akun admin tenant dalam 1 transaksi DB (pola `AdminPerusahaanController::store`: tenant + user + role attach), assign consultant pool opsional (klinik/perusahaan saja). Route `GET/POST admin/provision`. Klinik self-register `/daftar-mitra` TETAP jalan → masuk approval queue (jangan duplikasi).
  verify: `php artisan test --filter=ProvisioningWizardTest` hijau (3 vertikal create + user admin + audit; rollback saat user invalid tidak meninggalkan tenant yatim)
- [x] A4 (S, `a-tenant`) — Tenant switcher navbar admin: dropdown di layout admin list tenant, ganti `?{tenant}_id=` context override existing (PerusahaanContext/KlinikContext/SekolahContext sudah support).
  verify: `php artisan test --filter=TenantSwitcherTest` hijau (pilih tenant → context admin berubah; tenant lain tidak bocor); grep dropdown ada di `resources/views/layouts/`
- [x] A5 (M, `a-indep`) — Audit log viewer: `GET admin/audit-logs` list `admin_actions` + filter (admin, action, target type, tanggal) + pagination. Payload TIDAK dirender mentah: values ter-mask (email→`e***@domain`, nama→inisial) atau keys saja. Migration index `admin_id` + `created_at` (tabel sudah ada).
  verify: `php artisan test --filter=AuditLogViewerTest` hijau (filter bekerja; email user TIDAK muncul utuh di response — assert masked substring; pagination 25/page)
- [x] A6 (M, `a-indep`) — System health: `GET admin/system-health` — failed_jobs list + retry/delete action (route POST, audit), queue size, schedule last-run (cache timestamp `schedule:last-run:{command}` via event callback — no new table).
  verify: `php artisan test --filter=SystemHealthTest` hijau (failed job muncul, retry requeue, delete ter-audit)
- [x] A7 (M, `a-indep`) — Ops work queue terpadu: `GET admin/ops-queue` — satu halaman antrean gabungan read-only: sesi `needs_ops_review` + klinik pending approval + pervorma payout due + aktivasi perusahaan/sekolah pending. Tab/filter `?type=`, link ke aksi existing (verify/approve/pay/activate route yang sudah ada — JANGAN duplikasi write path). Badge count di `admin.dashboard`.
  verify: `php artisan test --filter=OpsQueueTest` hijau (item tiap tipe muncul sesuai fixture; aksi link ke route existing; badge count = jumlah antrean); `AdminOpsReviewDashboardTest` tetap hijau

### Phase B — Impersonation penuh (guardrail healthcare)
**Dependency chain: B1→B2→B3→B4→B5→B6. File menyentuh `app/Support/ViewAs.php`, 3 Context, routes, middleware — serialize.**

- [x] B1 (M) — Generalize ViewAs: struct session `{target_user_id, target_role, vertical, tenant_id, started_at, expires_at}`. Target semua role kecuali `admin` (blok impersonate sesama admin → 422). Route start/stop TETAP `role:admin` SAJA prefix admin. Resolve tenant dari identitas target user (bukan dari context admin).
  verify: `php artisan test --filter=ViewAsTest` hijau — update test dengan asersi baru (multi-role start sukses; start target admin → 422; anonymous start → redirect login)
- [x] B2 (M) — Context wiring: `KlinikContext` + `SekolahContext` override saat view-as aktif (ikuti pola `PerusahaanContext.php:24-26`); tenant resolve dari target user. `PerusahaanSessionPolicy` tetap resolve REAL user identity (jangan dilonggarkan).
  verify: `php artisan test --filter=TenancyIsolationTest` hijau; test baru: view-as hr_admin → data ter-scope ke tenant target, bukan tenant admin
- [x] B3 (S) — Route grup consultant+associate tambah admin: `role:consultant,admin` (web.php:138) + `role:associate,admin` (web.php:152).
  verify: `php artisan route:list --path=consultant` exit 0; test: admin GET `/consultant/sessions` + `/associate/dashboard` → 200 (dengan view-as aktif sesuai B1)
- [x] B4 (M) — Middleware read-only: `app/Http/Middleware/ViewAsReadOnly.php` dipasang di grup `web` setelah auth — saat view-as aktif: tolak semua non-GET di luar prefix `admin` + whitelist eksplisit (`view-as.stop`, form-filter POST kecil) → 403. Deny-by-default. Otomatis tutup 2 lubang existing (`booking.cancel`, chat store saat view-as).
  verify: `php artisan test --filter=ViewAsReadOnlyTest` hijau (POST booking.cancel/start-chat saat view-as → 403; POST saat tidak view-as → normal; GET → 200; stop view-as POST → 200)
- [x] B5 (S) — Expiry 30 menit: middleware cek `expires_at` → auto-stop + flash "view-as expired". Audit `view_as.start`/`view_as.stop` full payload (target role, tenant, reason optional). 30 menit hardcode konstanta (ponytail: naikkan ke config saat ada tenant-level SLA).
  verify: `php artisan test --filter=ViewAsTest` hijau (manipulate session `expires_at` ke masa lalu → next request auto-stop + flash + `admin_actions` row)
- [x] B6 (S) — UI: form start view-as di detail tenant (A1 show page) — pilih user tenant + reason optional + tombol "Masuk sebagai". Banner existing `layouts/app.blade.php:150-156` + info sisa waktu + tombol stop.
  verify: `php artisan test --filter=ViewAsUiTest` hijau (form render di detail tenant; start dari form → session view-as aktif + banner menampilkan role target + sisa waktu)

### Phase C — Associate 3 vertikal
**Dependency chain: C1→C2→C3→C4→C5→C6. Migration → config → service → admin → dashboard — serialize.**

- [x] C1 (M) — Migrasi: `perusahaans.associate_id` + `sekolahs.associate_id` (nullable FK users, mirror `kliniks.associate_id` 2026_08_05_000010). Tabel `perusahaan_pervormas` + `sekolah_pervormas` mirror schema `hap_pervormas` (unique(tenant_id, stage), field payout sama). `hap_pervormas` TIDAK di-rename/diubah.
  verify: `php artisan migrate` exit 0; `php artisan test --filter=HpaKlinikPortalTest` tetap hijau (HAP existing tidak regresi)
- [x] C2 (S) — `config/pervorma.php`: stage rates per vertikal — hap 250K/250K/500K @5/25/40 sesi (window 90 hari stage-1 tetap di service), sap 300K/400K/800K @5/25/40, cap 600K/800K/1.6M @trigger 1=mou_at(activated)/2=50 sesi/3=150 sesi. Default persis playbook.
  verify: `php artisan test --filter=PervormaConfigTest` hijau (assert nilai rates + stage 3 vertikal dari config)
- [x] C3 (L) — `PervormaService` generalize: driver per vertikal (hap/sap/cap), evaluator dipanggil dari titik trigger yang sama seperti sekarang: klinik=verify sesi (`SessionVerificationController`), perusahaan/sekolah=sesi done + tenant activated. Idempotent firstOrCreate per (tenant, stage). `HapPervormaService` jadi delegasi ke driver hap (smallest diff — caller existing tidak berubah).
  verify: `php artisan test --filter=PervormaServiceTest` hijau (3 vertikal: stage naik di trigger benar; CAP stage-1 saat mou_at saja; idempotent — evaluator 2x = 1 row; `KlinikMoneyFlowTest` tetap hijau)
- [x] C4 (S) — Admin assign associate ke tenant 3 vertikal: form di detail tenant (A1 show, section associate) + POST route + audit. Validasi: user role associate.
  verify: `php artisan test --filter=AssociateAssignmentTest` hijau (assign 3 vertikal ter-audit; non-associate user → 422)
- [x] C5 (M) — Associate dashboard multi-vertikal: tab klinik/perusahaan/sekolah di `resources/views/associate/dashboard.blade.php`, list tenant per vertikal (ganti hardcode `Klinik::where('associate_id')` di `Associate/DashboardController.php:22,61,68`), pervorma progress per vertikal, request payout per item (route existing generalized).
  verify: `php artisan test --filter=AssociateMultiVerticalTest` hijau (associate dengan tenant 3 vertikal lihat semuanya; abort_unless pervorma ownership tetap — pervorma tenant lain → 403)
- [x] C6 (M) — Admin pervorma pay 3 vertikal: `AdminDashboardController::pervormaSessions` + `Admin\PervormaController::pay` handle 3 tabel (filter `?vertical=`), evaluator trigger CAP stage-2/3 dari titik yang sama dengan C3. Pay ter-audit (pattern existing).
  verify: `php artisan test --filter=AdminPervormaPayTest` hijau (pay per vertikal ter-audit + status paid; filter vertical bekerja)

## Success Criteria
- [x] `php artisan test` hijau total (baseline + semua suite baru); test existing yang merah by-design (view-as read-only) di-update dengan asersi lebih spesifik, TIDAK di-disable.
- [x] Superadmin bisa: kelola 3 vertikal tenant (list/create/edit/suspend), impersonate semua non-admin role read-only 30 menit, lihat antrean ops terpadu + audit log ter-mask.
- [x] Associate lihat + request payout 3 vertikal; admin pay 3 vertikal; HAP existing tidak regresi (`HpaKlinikPortalTest`, `KlinikMoneyFlowTest` hijau).
- [x] Semua aksi admin baru (suspend, provisioning, retry/delete job, view-as start/stop, assign associate, pay pervorma) menghasilkan row `admin_actions` — diverifikasi via test.
- [x] `php artisan route:cache` + `migrate` exit 0 di worktree.
- [x] Tidak ada PII utuh di audit viewer response, log test, atau fixture (fake data saja).

## Out of Scope
- Act-on-behalf (aksi tulis sebagai user target saat view-as) — SKIP, YAGNI: read-only sudah cover kebutuhan "masuk semua dashboard"; aksi tetap lewat admin action sendiri. Naikkan kalau ada permintaan support yang benar-benar butuh tulis-as-target, dengan audit yang memisahkan actor real vs actor effective.
- Consultant assignment untuk klinik pool (consultant_pools tidak punya klinik_id — struktur beda).
- Rename/refactor tabel `hap_pervormas` ke polymorphic tunggal.
- Package admin (spatie, filament).
- UI start view-as per-user di luar halaman detail tenant (form B6 di detail tenant cukup).

## Status: DONE — QA PASS (319 tests / 1287 assertions, 0 failures, 2026-11-17)
