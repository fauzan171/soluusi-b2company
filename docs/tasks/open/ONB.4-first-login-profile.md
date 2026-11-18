# ONB.4 — Konfirmasi nama + pilih divisi/kelas saat login pertama

## Goal
Setelah ganti password, karyawan/siswa mengonfirmasi nama mereka dan memilih
divisi (perusahaan) atau kelas (sekolah). Data yang dipilih dipakai untuk
agregat dashboard, bukan untuk menampilkan isi sesi.

## Current State
- `perusahaan_employees.departement_id` FK ke `departements` — global, tanpa
  tenant (lihat ONB.1). Nilainya boleh NULL.
- `sekolah_students.kelas` string, wajib diisi saat `store()` manual
  (`RosterController::store()` sekolah), tapi baris hasil import bisa kosong.
- `PerusahaanEmployee::$fillable` = `perusahaan_id, departement_id, user_id,
  name, email, consent_at`. `SekolahStudent::$fillable` termasuk `kelas, type,
  user_id, consent_at, parent_consent_at`.
- `consent_at` / `parent_consent_at` sengaja TIDAK disentuh alur ini — consent
  dicatat HR/BK lewat `ProgramConsent`, bukan oleh karyawan sendiri.
- Tidak ada kolom "sudah konfirmasi profil".

## Perubahan

1. Migrasi `add_profile_confirmed_at_to_roster_tables`:
   `profile_confirmed_at` timestamp nullable di `perusahaan_employees` **dan**
   `sekolah_students`. Tambahkan ke `$fillable` + `$casts` dua model.
2. Middleware `App\Http\Middleware\EnsureProfileConfirmed` — dipasang SETELAH
   `ForcePasswordChange` di grup portal:
   - `perusahaan_employees` milik user dengan `profile_confirmed_at = NULL`
     → redirect `onboarding.profile`.
   - `sekolah_students` (type student/staff) — sama.
   - Admin/superadmin, hr_admin, bk, konsultan: bypass (tidak punya row roster).
   - Kalau user tidak punya row roster sama sekali → bypass, biarkan controller
     yang menolak (perilaku sekarang).
3. `Onboarding\ProfileController`:
   - `show()` (GET `/{vertical}/profil-awal`): tampilkan nama dari roster
     (prefill, editable), dan pilihan divisi (perusahaan) / kelas (sekolah).
     - Divisi: `Departement::forPerusahaan($id)` (dari ONB.1).
     - Kelas: daftar distinct `kelas` milik sekolah itu + input bebas
       (`datalist`), supaya sekolah bisa memakai format kelasnya sendiri.
   - `store()`: validasi `name` wajib `max:255`; `departement_id` harus milik
     tenant (guard yang sama dengan `RosterController::store()`);
     `kelas` wajib di sekolah. Update row + set `profile_confirmed_at`.
   - Redirect ke portal role masing-masing setelah sukses.
4. View `onboarding/profile.blade.php` — satu view, label/opsi bercabang dari
   variabel `$vertical` (`perusahaan`|`sekolah`), bukan dua view duplikat.
5. Revisi `AuthController::redirectByRole()`: urutan gerbang
   `must_change_password` (ONB.3) → profil belum dikonfirmasi (kalau ada row)
   → dashboard role.

## Verify
- [ ] Test: karyawan hasil import langsung buka `/perusahaan/employee` →
      redirect `/perusahaan/profil-awal`
- [ ] Test: simpan profil → `profile_confirmed_at` terisi, divisi tersimpan
- [ ] Test: pilih divisi milik perusahaan lain → 422
- [ ] Test: nama kosong → 422
- [ ] Test: siswa simpan kelas → `sekolah_students.kelas` ter-update
- [ ] Test: user yang sudah konfirmasi tidak pernah di-redirect lagi
- [ ] Test: consent flags tidak berubah oleh alur ini (`consent_at` tetap NULL)
- [ ] Test: gerbang password jalan sebelum gerbang profil (user dengan dua flag
      nyala mendarat di `/ubah-password`)
- [ ] Suite Feature hijau + `php -l`

## Out of scope
- Edit profil lagi setelah onboarding (tetap lewat HR/BK seperti sekarang).
- Ganti divisi sendiri berkala.
- Karyawan mencatat consent sendiri.