# ONB.3 — Wajib ganti password saat login pertama

## Goal
Akun hasil import dibuat dengan password dari CSV (atau acak). Pemilik harus
menggantinya sebelum bisa memakai portal — jadi password yang beredar di file
Excel HR tidak pernah menjadi password permanen.

## Current State
- Tabel `users` TIDAK punya kolom penanda apa pun soal password
  (`2014_10_12_000000_create_users_table.php`: `username, email, password,
  firstname, lastname, phone`; tambahan: `avatar, is_active, gender,
  date_of_birth, regency_id`).
- Tidak ada route/halaman change-password. Yang ada hanya
  `Auth/PasswordResetController` (lupa password via email — **email belum
  bisa dikirim**).
- `AuthController::redirectByRole()` adalah satu-satunya titik pasca-login.
  Role map: `admin, klinik_admin, associate, consultant, hr_admin, employee,
  bk, student`; tidak ada case `sekolah_staff`.
- `SelfRegisterController` — user yang mendaftar sendiri memilih password
  sendiri (min 8), jadi TIDAK perlu gerbang ini.

## Perubahan

1. Migrasi `add_must_change_password_to_users`:
   - `must_change_password` boolean, default `false`, index tidak perlu.
   - `password_changed_at` timestamp nullable (bukti kapan terakhir diganti).
2. Middleware `App\Http\Middleware\ForcePasswordChange`:
   - Kalau `auth()->user()->must_change_password` → redirect ke
     `password.change` kecuali request sudah di route `password.change*` atau
     `logout`.
   - Didaftarkan di grup route portal (perusahaan, sekolah, konsultan,
     klinik, associate) — TIDAK di grup `admin` (superadmin tidak pernah
     di-import) dan TIDAK di grup `auth` publik (login/logout/forgot).
   - Jangan pasang di `web` global: halaman landing/login harus tetap jalan.
3. `Auth\ChangePasswordController`:
   - `show()` — form: password baru + konfirmasi.
   - `store()` — validasi `current_password` (boleh apa saja termasuk password
     awal dari CSV, tapi tetap wajib benar), `password` `min:8|confirmed`,
     `different:current`. Set `password`, `must_change_password = false`,
     `password_changed_at = now()`, lalu redirect ke `redirectByRole()` milik
     `AuthController` (pakai ulang, jangan duplikasi map).
   - Throttle `password.change: 6,1`.
4. Route `GET|POST /ubah-password` name `password.change` di grup `auth`.
5. `redirectByRole()`: kalau `must_change_password` → route `password.change`
   lebih dulu, sebelum match role.

## Verify
- [ ] Test: user `must_change_password=true` buka `/perusahaan/employee` → redirect ke `/ubah-password`
- [ ] Test: user yang sama bisa GET `/ubah-password` (tidak redirect loop)
- [ ] Test: password baru < 8 char → 422, flag tetap true
- [ ] Test: password baru sama dengan yang lama → 422
- [ ] Test: ganti password sukses → flag false, `password_changed_at` terisi,
      redirect ke dashboard role yang benar
- [ ] Test: user normal (`must_change_password=false`) tidak pernah di-redirect
- [ ] Test: login sebagai user ber-flag → langsung mendarat di `/ubah-password`,
      bukan dashboard
- [ ] Test: superadmin (`admin`) tidak terkena gerbang walau flag nyala
- [ ] Suite Feature hijau + `php -l`

## Out of scope
- Kebijakan password historis / rotasi berkala.
- Password strength meter, 2FA.
- Notifikasi email "password kamu diganti" (email belum tersedia).