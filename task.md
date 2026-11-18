# Onboarding Massal — Import CSV → Akun → Login Pertama

Worktree: buat sendiri, branch `feat/onboarding-bulk-accounts` dari `main`.
Base: `main` @ `82888ec`.
Spec detail per task: `docs/tasks/open/ONB.*.md`. Status mesin: `tasks.yaml` phase `batch7`.

## Goal

HR/BK bisa meng-upload satu file CSV berisi nama, email, password, dan
divisi/kelas; sistem membuat akun login + roster row untuk setiap baris valid
**tanpa mengirim email apa pun**. Saat karyawan/siswa login pertama kali, dia
wajib mengganti password, lalu mengonfirmasi nama dan memilih divisi/kelasnya.
HR/BK kemudian bisa menyalakan/mematikan sendiri konsultan mana yang aktif
untuk organisasinya, dibatasi pada konsultan yang sudah dikontrak Soluusi.

## Context

Kenapa arsitekturnya begini — baca ini sebelum menyentuh kode:

- **Email belum bisa dikirim.** Jalur onboarding existing adalah undangan
  signed-URL (`RosterInvitation::issue` + `SelfRegisterController`, 7 hari,
  sha256 token hash, wajib nama cocok persis). Jalur itu TETAP ADA dan tidak
  dibongkar — hanya tidak dipakai sampai SMTP jalan. CSV import adalah jalur
  paralel, bukan pengganti.
- **`departements` masih GLOBAL** (`2023_11_09_153649_create_departements_table.php`:
  cuma `id, name, created_at, updated_at`; `Departement::$fillable = ['name']`).
  Dua perusahaan berbagi divisi bernama "Finance". Ini harus diperbaiki dulu
  atau "pilih divisi" tidak bisa benar.
- **Bug laten** `app/Http/Controllers/Perusahaan/RosterController.php:42`:
  `Departement::whereKey($id)->where('perusahaan_id', $perusahaan->id)` —
  kolomnya tidak ada. Query tidak pernah match, jadi jalur itu selalu 422.
  Mati hari ini hanya karena form roster tidak pernah mengirim
  `departement_id`. ONB.1 memperbaiki guard ini, TIDAK menghapusnya.
- **Belum ada halaman ganti password.** `users` tidak punya
  `must_change_password` maupun `password_changed_at`. Satu-satunya jalur
  reset adalah `Auth/PasswordResetController` via email — yang belum bisa
  dipakai. ONB.3 membangunnya.
- **`sekolah_staff` belum ada di `main`.** E.1 (staf sekolah) hidup di branch
  `goal/sekolah-flow-parity` (worktree `/home/seva-ojan/wt/soluusib2b-goal-sekolah-flow`
  @ `436f7bb`, BELUM di-merge; menambah kolom `sekolah_students.type` +
  role `sekolah_staff`). Kalau branch itu sudah masuk main sebelum batch ini
  dikerjakan, pakai; kalau belum, **jangan** menduplikasi E.1 di sini —
  kerjakan ONB.1, ONB.3, ONB.4, ONB.5 lebih dulu dan tandai ONB.2 (varian
  sekolah) + ONB.6 sebagai blocked.
- `composer.json` tidak punya library spreadsheet. CSV dibaca dengan
  `fgetcsv`/`str_getcsv` (stdlib PHP) — sudah dipakai di
  `Perusahaan/DashboardController::export()` dan `Sekolah/DashboardController`.
  **Jangan** menambah dependency.
- Multi-tenant: `TenantScope` (`app/Models/Scopes/TenantScope.php`) aktif hanya
  saat auth ada DAN context resolve. `PerusahaanContext::current()` /
  `SekolahContext::current()` adalah sumber tenant. Superadmin bypass lewat
  `canSwitch()` + `?{tenant}_id=`.
- Audit: `AdminAction::record($actorId, $action, $target, $payload)` —
  append-only, payload TIDAK boleh mengandung PII (email/nama mentah).

## Architecture Decision

1. **Satu import service, dua vertikal.** `App\Services\RosterCsvImport` dengan
   parameter `$vertical` (`perusahaan`|`sekolah`), bukan dua service kembar.
   View juga satu: `resources/views/roster-import.blade.php` dengan label
   bercabang. Alasan: user secara eksplisit bilang dua flow "hampir sama,
   bedanya cuma tulisan".
2. **Preview → commit, dua langkah.** Preview menulis file ke
   `storage/app/private/imports/{vertical}-{tenantId}/{uuid}.csv` lalu
   menampilkan tabel per-baris. Commit membaca ulang file itu. Alasan: HR harus
   bisa melihat baris mana yang salah sebelum membuat 200 akun; dan preview
   tidak boleh menulis DB sama sekali (bisa dites).
3. **Commit per-baris dalam transaksi baris itu sendiri.** Satu baris gagal
   tidak boleh menggagalkan 199 baris lain, tapi satu baris yang gagal di
   tengah tidak boleh meninggalkan user setengah jadi. `DB::transaction()`
   per baris.
4. **Create only.** Tidak ada update, tidak ada upsert, tidak ada undo batch.
   Email yang sudah ada di `users` ATAU di roster tenant → dilewati dengan
   status `duplicate`, bukan error fatal dan bukan menimpa.
5. **Dua gerbang login pertama sebagai middleware terpisah**, bukan satu
   middleware gabungan: `ForcePasswordChange` lalu `EnsureProfileConfirmed`.
   Alasan: student/karyawan lama (dibuat lewat self-register) tidak boleh
   kena gerbang kedua; dan urutannya harus deterministic saat dua-duanya nyala.
6. **Pool konsultan: flag, bukan filter.** `consultant_pools.is_active`.
   Menambah baris pool tetap wewenang superadmin (kontrak & lisensi); HR/BK
   hanya bisa toggle yang sudah ada. Alasan: user minta "HR bisa ubah sendiri
   kapan saja" TAPI juga "guard against adding outside contract" — flag
   memenuhi dua-duanya tanpa memindahkan wewenang kontrak ke HR.
7. **Kelas sekolah tetap string.** `sekolah_students.kelas` sudah string,
   dipakai di banyak view. Ditambah `datalist` berisi kelas yang sudah ada di
   sekolah itu. Tidak ada tabel kelas baru.

**Rejected:**
- Menerjemahkan CSV ke undangan (`RosterInvitation`) lalu memaksa `SelfRegister`
  — butuh email, dan user secara eksplisit minta jalur tanpa email.
- Satu tabel `onboarding_imports` untuk riwayat batch — YAGNI. Riwayat cukup
  dari `admin_actions`; file mentah dihapus setelah commit.
- Menyimpan password awal di DB — jelas tidak.
- Form multi-step wizard di frontend — tiga halaman terpisah lebih mudah
  di-debug, dan redirect gerbang middleware sudah memberi urutan yang sama.

## Phases

Format task: `id (size, parallel_group) — deskripsi — files — verify`.
Est: S=<½ hari, M=1 hari, L=2 hari.

**No drive-by edits.** Jangan sentuh `EmployeeController` quota (`$monthStart`
di baris 78–83) — itu utang terpisah di `EmployeeController`, bukan di
`StudentController`/`StaffController` yang sudah dibersihkan di branch parity.
Jangan refactor `StudentController`↔`StaffController` yang duplikatif.

---

### Phase A — Fondasi: kolom, tenant scope, ganti password

> Phase ini tidak bergantung pada branch parity. Boleh mulai sekarang.
> ONB.1 dan ONB.3 menyentuh file yang berbeda (`departements` vs `users`) →
> boleh paralel.

- [x] **A1 / ONB.3 (M, `a-auth`) — Gerbang wajib ganti password.**
  Spec: `docs/tasks/open/ONB.3-forced-password-change.md`.
  **Files:**
  - `database/migrations/2026_11_18_000001_add_must_change_password_to_users.php` (new)
  - `app/Http/Middleware/ForcePasswordChange.php` (new)
  - `app/Http/Controllers/Auth/ChangePasswordController.php` (new)
  - `app/Http/Kernel.php` (alias `force.password`)
  - `routes/web.php` (route group `auth`: `GET|POST /ubah-password`, name `password.change`)
  - `app/Http/Controllers/AuthController.php` (`redirectByRole`: cek flag sebelum match role)
  - `resources/views/auth/change-password.blade.php` (new)
  - `tests/Feature/ForcePasswordChangeTest.php` (new)
  **Action:**
  - Migrasi: `must_change_password` boolean default `false`, `password_changed_at` timestamp nullable.
  - Middleware: kalau `auth()->user()->must_change_password` → redirect `password.change`, kecuali `routeIs('password.change*')` atau `logout`. Kalau tidak login → lewati (biar `auth` yang menangani).
  - Middleware **dipasang manual per-grup portal**, bukan di `web` global: grup `perusahaan` (`role:hr_admin,admin` + `role:employee,admin`), `sekolah-portal` (`role:bk,admin` + `role:student,admin`), `consultant`, `associate`, `klinik`. **JANGAN** di grup `admin`. **JANGAN** di `web` global — halaman landing/login harus tetap jalan.
  - Controller: validasi `current_password` benar (`Hash::check`), `password` `min:8|confirmed`, `different:current`. Simpan → flag false + `password_changed_at`. Redirect pakai `AuthController::redirectByRole()` — jangan duplikasi match role.
  - Throttle `throttle:6,1` di POST.
  **Verify:** `php artisan test --filter=ForcePasswordChangeTest`
  **Acceptance:** flag nyala → semua route portal redirect; `/ubah-password` sendiri tidak redirect loop; password <8 → 422; password sama → 422; sukses → flag false + mendarat di dashboard role yang benar; user normal tidak pernah di-redirect; superadmin bypass; `php -l` semua file berubah.

- [ ] **A2 / ONB.1 (M, `a-tenant`) — `departements` jadi milik perusahaan.**
  Spec: `docs/tasks/open/ONB.1-departement-tenant-scoping.md`.
  **Files:**
  - `database/migrations/2026_11_18_000002_add_perusahaan_id_to_departements.php` (new)
  - `app/Models/Departement.php`
  - `database/seeders/PerusahaanSeeder.php` (baris ~33–36)
  - `app/Http/Controllers/Perusahaan/RosterController.php` (`store()` + `index()`)
  - `resources/views/perusahaan/roster.blade.php` (dropdown divisi)
  - `tests/Feature/DepartementTenantTest.php` (new)
  **Action:**
  - Migrasi: `perusahaan_id` nullable + FK `nullOnDelete` + index. **Nullable** supaya row legacy dan `consultant_education` (FK ke `departements`) tidak putus.
  - Model: `perusahaan_id` masuk `$fillable`, relasi `perusahaan()`, dan `static forPerusahaan(int $id)` → koleksi divisi (milik tenant + row legacy `perusahaan_id IS NULL`, urut `name`).
  - Seeder: `Departement::firstOrCreate(['perusahaan_id' => $p->id, 'name' => $name])` di dalam loop perusahaan, bukan sekali di luar.
  - `RosterController::store()`: **perbaiki bug laten**. Ganti
    `abort_unless(...->where('perusahaan_id', ...)->exists())` (kolom tidak ada → dead) dengan
    pengecekan `Departement::forPerusahaan($perusahaan->id)->contains('id', $data['departement_id'])`. Bug ini diperbaiki, bukan dihapus.
  - `index()`: kirim `$departements` ke view.
  **Verify:** `php artisan test --filter=DepartementTenantTest`
  **Acceptance:** divisi tenant A tidak muncul di dropdown B; POST divisi milik B → 422; divisi sendiri → tersimpan; seeder dua kali tidak menduplikasi; `departement_id` null tetap boleh; `php -l`.

---

### Phase B — Gerbang onboarding login pertama

> Butuh Phase A selesai (kolom `departement_id` yang benar + middleware
> sudah punya pola pemasangan).

- [ ] **B1 / ONB.4 (M, `b-onboard`) — Konfirmasi nama + pilih divisi/kelas.**
  Spec: `docs/tasks/open/ONB.4-first-login-profile.md`.
  **Files:**
  - `database/migrations/2026_11_18_000003_add_profile_confirmed_at_to_roster_tables.php` (new)
  - `app/Models/Perusahaan/PerusahaanEmployee.php` (fillable + casts)
  - `app/Models/Sekolah/SekolahStudent.php` (fillable + casts)
  - `app/Http/Middleware/EnsureProfileConfirmed.php` (new)
  - `app/Http/Controllers/Onboarding/ProfileController.php` (new)
  - `app/Http/Kernel.php` (alias `profile.confirmed`)
  - `routes/web.php` (2 route: `perusahaan.profile`, `sekolah.profile`)
  - `resources/views/onboarding/profile.blade.php` (new — satu view, `$vertical`)
  - `tests/Feature/FirstLoginProfileTest.php` (new)
  **Action:**
  - Migrasi: `profile_confirmed_at` timestamp nullable di `perusahaan_employees` **dan** `sekolah_students`.
  - Middleware: cari row roster milik user lewat relasi (`PerusahaanEmployee::where('user_id',…)` / `SekolahStudent::where('user_id',…)`). Ada row dengan `profile_confirmed_at IS NULL` → redirect `{vertical}.profile`. Tidak punya row → lewati. Role `admin`/`hr_admin`/`bk`/`consultant`/`associate`/`klinik_admin` → lewati.
  - Controller: `show()` prefill nama dari row; opsi divisi dari `Departement::forPerusahaan()` (perusahaan) atau daftar distinct `kelas` milik sekolah (sekolah, input bebas + `datalist`). `store()`: `name` wajib `max:255`; `departement_id` harus lolos `forPerusahaan()` (guard yang sama dengan roster); `kelas` wajib di sekolah. Set row + `profile_confirmed_at = now()`.
  - **Jangan sentuh** `consent_at` / `parent_consent_at` — consent tetap lewat `ProgramConsent` oleh HR/BK.
  - Middleware dipasang SETELAH `force.password` di grup yang sama.
  **Verify:** `php artisan test --filter=FirstLoginProfileTest`
  **Acceptance:** karyawan hasil import → redirect profil; simpan → flag terisi + divisi benar; divisi tenant lain → 422; nama kosong → 422; siswa simpan kelas → kolom ter-update; user terkonfirmasi tidak di-redirect lagi; `consent_at` tetap NULL; dua gerbang nyala → `/ubah-password` menang; `php -l`.

---

### Phase C — Import CSV

> Butuh Phase A + Phase B (butuh `must_change_password` dan
> `profile_confirmed_at` supaya hasil import langsung memicu dua gerbang).

- [ ] **C1 / ONB.2 (L, `c-import`) — Service + controller + route + view import (perusahaan).**
  Spec: `docs/tasks/open/ONB.2-csv-bulk-import.md`.
  **Files:**
  - `app/Services/RosterCsvImport.php` (new)
  - `app/Http/Controllers/Perusahaan/RosterImportController.php` (new)
  - `routes/web.php` (3 route di grup `role:hr_admin,admin`:
    `GET /perusahaan/employees/import`,
    `POST /perusahaan/employees/import` → preview,
    `POST /perusahaan/employees/import/commit` → commit)
  - `resources/views/perusahaan/roster-import.blade.php` (new)
  - `resources/views/perusahaan/roster.blade.php` (link ke halaman import)
  - `tests/Feature/RosterCsvImportTest.php` (new)
  **Action:**
  - `RosterCsvImport::preview($path, string $vertical, int $tenantId): array` →
    `['rows' => [[line, name, email, division, status, error]], 'summary' => [valid, duplicate, error]]`.
    `status` ∈ `valid|duplicate|error`. Validasi per baris: `name` wajib; `email` format valid + lowercase-trim; `password` opsional (kosong → nanti acak); divisi/kelas opsional tapi kalau diisi harus milik tenant.
  - `RosterCsvImport::commit(...)`: baca ulang file; per baris `DB::transaction()`. Buat `User` (`uuid`, `username` unik, `firstname`/`lastname` di-split, `email`, `Hash::make($password ?: Str::random(12))`, `is_active = true`, `role_id` = `employee`/`student`, `must_change_password = true`), lalu link `roster.user_id`, dan `profile_confirmed_at` tetap NULL (memunculkan gerbang B1). Perusahaan: `departement_id` kalau kolom divisi cocok. Sekolah: `kelas` dari kolom.
  - Duplikat: email sudah ada di `users` **atau** di roster tenant → `duplicate`, dilewati. Jangan menimpa password user lama.
  - Upload: simpan ke `storage/app/private/imports/{vertical}-{tenantId}/{uuid}.csv` (disk `local`, root `storage/app`), TTL 1 jam, hapus setelah commit sukses. Batas: 5 MB, 2000 baris, ekstensi `.csv` saja.
  - Header CSV wajib cocok: `name,email,password,divisi` (perusahaan) / `name,email,password,kelas` (sekolah). Header salah → 422 tanpa menulis apa pun.
  - Audit `AdminAction::record(...)` dengan **jumlah** baris (ok/skip) saja — **tanpa** email/nama di payload.
  - Guard: `abort_unless($perusahaan->is_active)` — ikut gerbang POST roster existing.
  **Verify:** `php artisan test --filter=RosterCsvImportTest`
  **Acceptance:** 3 baris valid → 3 user + 3 row ter-link; tanpa password → acak + `must_change_password` true; duplikat dalam file → baris kedua ditolak, lainnya jalan; email sudah ada di `users` → dilewati & password lama utuh; header salah → 422 tanpa row; divisi tenant lain → baris error; HR A tidak bisa import ke B (403); file >5 MB / >2000 baris → 422; **preview tidak menulis DB**; `php -l`.

- [ ] **C2 / ONB.2-lanjutan (M, `c-import`) — Varian sekolah dari import.**
  **Files:**
  - `app/Http/Controllers/Sekolah/RosterImportController.php` (new, tipis — delegasi ke service)
  - `routes/web.php` (3 route di grup `role:bk,admin` sekolah)
  - `resources/views/sekolah/roster-import.blade.php` (new)
  - `resources/views/sekolah/roster.blade.php` (link)
  - `tests/Feature/SekolahRosterCsvImportTest.php` (new)
  **Action:** header `name,email,password,kelas`. **BLOKER:** kolom `type` di `sekolah_students` dan role `sekolah_staff` baru ada di branch `goal/sekolah-flow-parity`. Kalau branch itu sudah di main, dukung kolom opsional `tipe=staff|siswa` (default siswa) dan untuk baris staff buat `SekolahAdmin(role='sekolah_staff')`. Kalau belum → **kerjakan versi siswa saja**, tandai `[-]` blocked untuk varian staff, dan tulis satu baris alasan di task.md.
  **Verify:** `php artisan test --filter=SekolahRosterCsvImportTest`
  **Acceptance:** CSV sekolah → user role `student` + row `user_id` terisi; kelas tersimpan; BK sekolah A tidak bisa import ke B; header salah → 422.

---

### Phase D — Pool konsultan self-service + paritas

> Boleh paralel dengan Phase C (file berbeda), tapi butuh Phase A selesai.

- [ ] **D1 / ONB.5 (M, `d-pool`) — HR/BK toggle konsultan aktif di pool tenant.**
  Spec: `docs/tasks/open/ONB.5-tenant-consultant-pool-self-service.md`.
  **Files:**
  - `database/migrations/2026_11_18_000004_add_is_active_to_consultant_pools.php` (new)
  - `app/Models/ConsultantPool.php` (`poolFor` filter `is_active`)
  - `app/Http/Controllers/Perusahaan/ConsultantPoolController.php` (new)
  - `app/Http/Controllers/Sekolah/ConsultantPoolController.php` (new — tipis)
  - `routes/web.php` (`GET /perusahaan/konsultan`, `POST /perusahaan/konsultan/toggle` di `role:hr_admin,admin`; mirror di `role:bk,admin` sekolah)
  - `resources/views/tenant/consultant-pool.blade.php` (new — satu view, `$vertical`)
  - `tests/Feature/TenantConsultantPoolTest.php` (new)
  **Action:**
  - Migrasi: `consultant_pools.is_active` boolean default `true`. **Default true** supaya pool existing tidak hilang diam-diam.
  - `poolFor()` tambahkan `->where('is_active', true)`.
  - Controller: `index()` daftar konsultan di pool tenant (nama, kategori, status); `toggle()` flip `is_active` untuk row milik tenant sendiri. Guard kepemilikan tenant keras (403 kalau row bukan milik tenant ter-resolve).
  - **Jangan** menyediakan jalur menambah konsultan baru — itu tetap `Admin\ConsultantAssignmentController`.
  - Audit `AdminAction::record(auth()->id(), 'consultant_pool.toggle', $tenant, ['consultant_id','is_active'])` tanpa PII. Kalau semantik `admin_id` jadi rancu karena aktornya HR, **dokumentasikan di docblock `AdminAction`** bahwa kolom itu berarti "aktor"; jangan bikin tabel audit kedua.
  **Verify:** `php artisan test --filter=TenantConsultantPoolTest`
  **Acceptance:** HR A hanya lihat pool A; toggle off → hilang dari halaman booking karyawan; toggle on → muncul lagi; HR A tidak bisa toggle row B; BK bisa toggle sekolahnya; karyawan/siswa → 403; tidak ada jalur tambah konsultan baru; audit tercatat tanpa PII; konsultan `status != accept` tidak muncul; `php -l`.

- [ ] **D2 / ONB.6 (M, `d-parity`) — Paritas dua vertikal + `sekolah_staff` redirect.**
  Spec: `docs/tasks/open/ONB.6-parity-and-role-redirect.md`.
  **Files:**
  - `app/Http/Controllers/AuthController.php` (`redirectByRole`: verifikasi case `sekolah_staff` ada)
  - `tests/Feature/OnboardingParityTest.php` (new)
  **Action:**
  - Cek apakah `'sekolah_staff' => redirect()->route('sekolah.staff')` ada di `redirectByRole()`. Di `main` role itu **belum ada sama sekali**; di branch parity sudah. Kalau branch parity sudah merge → cukup verifikasi. Kalau belum merge → tandai task ini `[-]` blocked, jangan bikin role `sekolah_staff` baru di sini.
  - Pastikan urutan gerbang konsisten untuk semua role portal: `force.password` → `profile.confirmed` → dashboard.
  - Test paritas data-provider: skenario identik dijalankan untuk perusahaan (divisi) dan sekolah (kelas); hasil harus identik.
  **Verify:** `php artisan test --filter=OnboardingParityTest`
  **Acceptance:** login `sekolah_staff` mendarat di `/sekolah-portal/staff`; staf sekolah kena dua gerbang; data provider 2 vertikal hijau; `php -l`.

---

## Success Criteria

- [ ] HR bisa upload CSV 3 baris → 3 akun karyawan aktif dengan divisi terisi, tanpa email terkirim.
- [ ] BK bisa upload CSV 3 baris → 3 akun siswa aktif dengan kelas terisi.
- [ ] Karyawan login pertama → dipaksa ganti password → dipaksa konfirmasi nama + divisi → baru masuk portal.
- [ ] Siswa login pertama → alur sama, kosakata "kelas".
- [ ] Preview import menampilkan baris error per baris tanpa menulis DB.
- [ ] HR toggle konsultan off → konsultan hilang dari halaman booking karyawan; toggle on → kembali.
- [ ] HR tidak punya jalan menambah konsultan di luar pool kontrak.
- [ ] Divisi perusahaan A tidak bocor ke perusahaan B (dropdown, POST, import).
- [ ] Semua test pass: `php artisan test --testsuite=Feature` → 0 failures.
- [ ] Lint: `./vendor/bin/pint` **tidak terpasang** di repo ini — verifikasi lewat `php -l` di setiap file berubah + suite Feature hijau.

## Constraints (dari global working agreement)

- Tidak push, tidak merge ke main, tidak deploy, tidak menyentuh remote.
- Migrasi baru: **tidak dijalankan di DB non-lokal** tanpa izin. Dev/test SQLite saja.
- Jangan menambah dependency Composer.
- Tidak ada PII di log, test, commit, atau payload audit.
- Jangan menonaktifkan/melonggarkan test untuk hijau.
- Commit Conventional Commits, satu perubahan logis per commit, akhiran
  `Co-Authored-By: Claude Code <noreply@anthropic.com>`.
- `guard.sh` hook salah resolve cwd — selalu pakai `git -C <worktree>`.

## Out of Scope

- Mengirim email apa pun (SMTP belum ada). `RosterInvitation` tetap tidak dipakai.
- XLSX/XLS (butuh dependency baru).
- Update massal / upsert / undo batch import.
- HR memilih konsultan dari seluruh database Soluusi.
- Billing PEPM/subscription otomatis.
- Refactor duplikasi `StudentController` ↔ `StaffController`.
- Fix `$monthStart` quota di `Perusahaan/EmployeeController:78-83` (utang terpisah).
- Edit profil setelah onboarding (tetap wewenang HR/BK).

## Status

Belum dieksekusi. 0 baris kode. Semua task `[ ]`.