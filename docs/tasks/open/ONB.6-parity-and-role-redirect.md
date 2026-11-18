# ONB.6 — Paritas alur perusahaan ↔ sekolah + tutup celah redirect role

## Goal
Alur onboarding baru (import → ganti password → konfirmasi profil → portal)
berperilaku identik di dua vertikal; bedanya hanya kata ("divisi" vs "kelas",
"HR" vs "BK"). Plus satu celah lama: role `sekolah_staff` tidak punya case di
`redirectByRole()`.

## Current State
- **Di `main` role `sekolah_staff` belum ada sama sekali** — tidak ada di
  `app/`, tidak ada di seeder `roles`. Seluruh E.1 (staf sekolah) hidup di
  branch `goal/sekolah-flow-parity` (worktree
  `/home/seva-ojan/wt/soluusib2b-goal-sekolah-flow`, HEAD `436f7bb`, BELUM
  di-merge). Di branch itu `redirectByRole()` sudah punya
  `'sekolah_staff' => redirect()->route('sekolah.staff')` (baris 131).
- `AuthController::redirectByRole()` di main: `admin, klinik_admin, associate,
  consultant, hr_admin, employee, bk, student` → `default` =
  "Role tidak dikenal."
- Portal sekolah punya grup route terpisah `role:sekolah_staff,admin`
  (`sekolah.staff*`), portal perusahaan satu role `employee`.
- Roster sekolah menyimpan `kelas` sebagai string; perusahaan memakai tabel
  `departements`.

## Perubahan

1. **Urutan merge menentukan pekerjaan ini.** ONB.1–ONB.5 dieksekusi setelah
   `goal/sekolah-flow-parity` masuk main; kalau tidak, gerbang
   `ForcePasswordChange`/`EnsureProfileConfirmed` hanya terpasang di satu
   grup role dan staf sekolah tidak pernah lewat onboarding.
2. Setelah merge: verifikasi (bukan tulis ulang) bahwa
   `'sekolah_staff' => redirect()->route('sekolah.staff')` masih ada di
   `redirectByRole()`. Kalau merge menghilangkannya → tambahkan kembali (bug,
   bukan fitur).
3. Pastikan urutan gerbang di `redirectByRole()` konsisten untuk semua role
   portal: `must_change_password` (ONB.3) → profil belum dikonfirmasi (ONB.4)
   → dashboard. Termasuk `sekolah_staff`.
4. Import service (ONB.2) dipakai bersama: pastikan `type` siswa vs staff
   ditangani. Kalau CSV sekolah mengikutkan staff, kolom tambahan
   `tipe=staff|siswa` opsional — default `siswa`, dan baris staff juga
   membuat `SekolahAdmin(role='sekolah_staff')` agar tenant-nya ter-resolve.
5. Test paritas: satu test file `tests/Feature/OnboardingParityTest.php` yang
   menjalankan skenario yang sama untuk dua vertikal lewat data provider
   (perusahaan/divisi vs sekolah/kelas), supaya perilaku tidak melenceng lagi
   di kemudian hari.

## Verify
- [ ] Test: login `sekolah_staff` → redirect `/sekolah-portal/staff` (bukan error)
- [ ] Test: staf sekolah hasil import kena gerbang password lalu gerbang profil
- [ ] Test: data provider paritas — 2 vertikal, langkah identik, hasil identik
- [ ] Test: CSV sekolah tanpa kolom `tipe` → semua jadi siswa
- [ ] Suite Feature hijau + `php -l`

## Out of scope
- Menyatukan tabel roster perusahaan dan sekolah (sengaja terpisah, pattern
  tabel-per-vertikal sudah jadi konvensi repo).
- Refactor `StudentController`/`StaffController` yang masih duplikatif.