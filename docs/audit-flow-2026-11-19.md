# Audit Flow & User-Flow Gaps — 2026-11-19

Repo: soluusi-b2b-klinik, HEAD 2e82f6c (main). Audit cepat, read-only. Fokus: bug logic yang lolos test, flow patah per role, UI blade.

## Verifikasi route blade vs terdefinisi
Metode: `route:list --json` (195 nama route) vs grep `route('...')` di `resources/views`. Hasil: hanya `register` (welcome.blade.php:33) tidak terdefinisi — aman karena dibungkus `@if (Route::has('register'))`, jadi tidak fatal. `welcome.blade.php` tidak dirender controller mana pun (dead scaffold file).

## Klaim kandidat yang TERBANTAHKAN (bukan temuan)
- **"pervorma" bukan typo** — brand/schema name konsisten: tabel `hap_pervormas`/`perusahaan_pervormas`/`sekolah_pervormas`, `config/pervorma.php`, `HapPervormaService`. Route `/admin/pervorma-sessions` valid (AdminDashboardController::pervormaSessions, paginate(50) + withQueryString + filter klinik/stage).
- Race slot checkout **aman**: `CheckoutFlow::reserveSession()` (app/Services/Klinik/CheckoutFlow.php:237-262) pakai `DB::transaction` + `Schedule::lockForUpdate` + re-check blocking session + `claimLink()` atomic `UPDATE ... WHERE used_at IS NULL === 1` (line 308). Double-pay tercek `isUsable()` (used_at null + expiry + klinik aktif).
- Race booking internal **aman**: `BookingService::book()` (app/Services/BookingService.php:112-157) — lock Consultant → serialisasi writer → lock row → lock Schedule → quota check (line 135) di dalam lock → `booking_claims` insert. Throttle `10,1` di semua route booking (routes/web.php:123,138,180).
- Quota bypass tidak terlihat: quota check di dalam transaction+lock; status quota-consumed dihitung dari `quota_consumed` + status (BookingService.php:55-74).
- ViewAs TTL + expiry check ada (app/Support/ViewAs.php: TTL 30 menit, current() drop stale).
- `loginIndex` terdefinisi (routes/web.php:25) — landing.blade.php:314 aman.

## Temuan

### F1 — Medium / Bug — double-approve race HR & BK (no lock)
- File: app/Http/Controllers/Perusahaan/HrReviewController.php:75-99 (approve); pola sama app/Http/Controllers/Sekolah/BkReviewController.php:39,88-99
- `approve()` baca session tanpa `lockForUpdate`/transaction, lalu `SessionStateMachine::assign()` (app/Support/SessionStateMachine.php:30-40) juga tanpa lock — guard hanya `status === 'scheduled' && scheduled_at === null` dicek read-then-write.
- Dua HR admin (atau HR + BK di sekolah) approve bersamaan: keduanya lolos pre-check → keduanya `update(['consultant_id'])` → assignment kedua menimpa yang pertama + payload audit `admin_action`/log tercatat dobel dengan consultant berbeda. Test suite tidak menjalankan konkurensi → lolos.
- Dampak: konsultan salah/bertukar di menit-menit terakhir, audit trail bohong. Fix: `DB::transaction` + `lockForUpdate` di resolvePending/assign.

### F2 — Medium / Bug — view-as associate 403 di requestPayout (guard tidak konsisten)
- File: app/Http/Controllers/Associate/DashboardController.php:178-200 (requestPayout) vs index() line 36-42
- `index()` resolve `ViewAs::current()` → pakai target user; `requestPayout()` langsung `auth()->user()` → saat superadmin view-as associate (dashboard merender tombol "Minta cair" untuk tenant milik associate target), POST payout kena `abort_unless($pervorma->associate_id === $user->id, 403)` karena `$user` masih admin.
- Dampak: fitur audit view-as patah di action pertama yang penting; admin tidak bisa simulasi penuh. Fix: resolve view-as user sama seperti index().

### F3 — Low / UI — welcome.blade.php dead scaffold dengan route('register') hilang
- File: resources/views/welcome.blade.php:33
- `route('register')` tidak terdefinisi; hanya tidak meledak karena guard `@if (Route::has('register'))`. File tidak dipakai controller mana pun (landing pakai landing.blade.php).
- Dampak: jebakan kalau file ini di-render lagi (fatal RouteNotFoundException). Hapus file.

### F4 — Low / FlowGap — nav associate cuma 1 link, tanpa exit view-as contextual
- File: resources/views/layouts/app.blade.php:60-61
- Nav `associate.*` hanya `associate.dashboard`. Dashboard menampilkan 3 vertikal + payout, tapi tidak ada deep-link per vertikal/filter; admin view-as harus cari tombol stop view-as sendiri.
- Dampak: dead-end ringan; bukan blocker karena layout tetap punya navbar global.

### F5 — Low / Bug — customerConfirm benign race (idempotent outcome)
- File: app/Http/Controllers/Klinik/SessionController.php:87-105; dipanggil CheckoutController::confirmDone (line ~151)
- `confirmDone` abort kalau `completion_verified_at` sudah ada, tapi tanpa lock: dua submit bersamaan sama-sama baca null → dua-duanya set `customer_confirmed_at`/`completion_verified_at`. Hasil akhir sama (idempotent), hanya timestamp dobel. Tidak perlu fix sekarang.

### F6 — Low / Bug — voucher increment di luar transaksi reserve
- File: app/Services/Klinik/CheckoutFlow.php:195-197 (startDummyPayment; pola sama di snap path)
- `reserveSession()` commit (slot+link terklaim) baru `$voucher->increment('usage')`. Kalau increment gagal/crash di antara, sesi jalan tanpa usage tercatat → voucher bisa over-limit.
- Dampak: kebocoran limit voucher, jarang. Fix: pindahkan increment ke dalam transaction reserveSession.

### F7 — Low / FlowGap — chat thread cap 200 pesan, tidak ada pagination
- File: app/Http/Controllers/ChatController.php:195 (`->limit(200)`)
- Thread lebih dari 200 pesan: pesan lama tidak pernah bisa dilihat user (tidak ada load-more/pagination), komentar kode sadar akan ini (line 207) tapi hanya untuk unread marking.
- Dampak: riwayat konsultasi panjang hilang dari UI. Fix: pagination berbasis `before_id`.

### Terbantahkan juga
- "Missing validation error display" di blade: layouts/app.blade.php:203-205 punya global error banner; mayoritas view extend layouts.app → tercover. Bukan temuan.
- ChatController tidak paginate thread list, tapi cakupan datanya kecil per role; catat sebagai Low bila thread list tumbuh.

### F8 — Medium / FlowGap — seluruh modul tenant & ops admin tidak punya entry point UI
- File: resources/views/layouts/app.blade.php:56-59 (nav admin = Dashboard + Perusahaan B2B saja) + resources/views/admin/dashboard.blade.php
- Route `admin.tenants.kliniks.*` (index/show/edit/update/lifecycle/assign-associate), `admin.tenants.sekolah*`, `admin.tenants.perusahaan*`, `admin.audit-logs`, `admin.system-health` (index + failed-job retry/delete), `admin.consultant-assignment` (assign/remove) — semua terdefinisi dan controller-nya jalan (diverifikasi live: 403 utk non-admin, view ada), tapi TIDAK ADA satu pun link `route('admin.tenants...')` / `route('admin.audit-logs.index')` / `route('admin.system-health.index')` / `route('admin.consultant-assignment.index')` di blade mana pun (grep seluruh resources/views + controllers).
- Diverifikasi live 2026-11-19 dengan sesi klinik_admin: /admin/audit-logs, /admin/consultant-assignment, /admin/system-health, /admin/tenants/kliniks, /admin/tenants/kliniks/1, /admin/ops-queue, /admin/referral-overview, /admin/pervorma-sessions → semua 403 (guard jalan); /admin/tenants/sekolah → 404 (path route beda dgn tebakan; route exist sebagai path lain — cek route:list utk URL persisnya).
- Dampak: fitur manajemen tenant multi-tipe, audit log, system health, dan assignment konsultan tidak bisa dijangkau dari UI kecuali ketik URL manual. Superadmin nyangka fitur hilang. Fix: tambah grup menu "Tenant / Ops" di nav `admin.*` atau kartu link di dashboard.

### F9 — Low / FlowGap — nav associate & sekolah-staff satu link, dead-end view-as
- layouts/app.blade.php:60-62: nav `associate.*` cuma `associate.dashboard`; tidak ada deep-link payout request history. Ditambah F2 (requestPayout 403 saat view-as), alur audit associate lewat view-as patah di action pertama.

### F10 — Low / UI — views associate tanpa @error inline
- resources/views/associate/*.blade.php: grep `@error` tidak ada match — form payout dll hanya andalkan global error banner (layouts/app.blade.php:203-205). Feedback error tidak nempel di field. Bukan blocker.

## Verifikasi tambahan 2026-11-19 (sesi ini)
- Guard role live OK: semua endpoint admin di atas 403 utk klinik_admin, 302 utk guest. Tidak ada privilege leak.
- `admin.consultant-assignment/assign` POST-only (405 GET) — sesuai harapan.

## Belum diverifikasi penuh (budget)
- Pagination/empty-state per halaman HR/employee/Chat — ChatController tidak menemukan `paginate` di grep cepat (perlu cek ulang); EmployeeController/StudentController dibaca agent sebelumnya, kesimpulannya tidak tersimpan.
- Blade `$errors` display per form — tidak diaudit menyeluruh.
