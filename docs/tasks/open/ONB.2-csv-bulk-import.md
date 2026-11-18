# ONB.2 — Import CSV: bikin akun massal (karyawan / siswa)

## Goal
HR/BK upload satu file CSV → sistem membuat roster row **plus** akun login
(email + password awal dari kolom file) untuk semua baris valid, tanpa
mengirim email apa pun.

## Current State
- Belum ada jalur import. Jalur yang ada:
  - `RosterController::store()` — satu karyawan per submit.
  - `RosterController::invite()` — kirim email undangan signed-URL
    (`RosterInvitation::issue`). **Blocker: email belum bisa dikirim.**
  - `SelfRegisterController` — konsumsi undangan, wajib nama cocok persis,
    kedaluwarsa 7 hari. Dipakai juga untuk melink akun lama.
- `composer.json` tidak punya library spreadsheet. `fputcsv`/`str_getcsv`
  (stdlib PHP) sudah dipakai untuk export laporan.
- `perusahaan_employees` dan `sekolah_students` sudah punya `user_id`
  (nullable) — akun baru tinggal di-link.
- `Total: 131→135 migrasi`, tidak ada tabel import log.

## Perubahan

1. **Format file** (keputusan user: CSV saja, tanpa dependency baru):
   - Perusahaan: `name,email,password,divisi` (`divisi` opsional)
   - Sekolah: `name,email,password,kelas` (`kelas` opsional kalau baris sudah
     punya kelas default dari form)
   - Password opsional per baris → fallback acak 12 char (`Str::password`).
2. `app/Services/RosterCsvImport.php` — satu service dipakai dua vertikal:
   - `preview(string $path, string $vertical, int $tenantId): array` →
     `[rows => [{line, name, email, divisi, status, error}], summary]`.
     `status`: `valid` | `duplicate_email` | `error`.
   - `commit(...)` membaca ulang file, dalam **satu transaksi** per baris
     (baris gagal tidak menggagalkan yang lain) dan mengembalikan laporan yang
     sama dengan tambahan `user_id`.
   - Idempotensi: email yang sudah ada di `users` **atau** di roster tenant →
     `duplicate_email`, dilewati (bukan error fatal).
   - `email` divalidasi format + lowercase-trim; `name` wajib.
3. Controller + route (di grup `role:hr_admin,admin` / `role:bk,admin`):
   - `GET  /perusahaan/employees/import` → form upload + preview
   - `POST /perusahaan/employees/import` → `preview()` (simpan file ke
     `storage/app/private/imports/{tenant}/…`, TTL 1 jam)
   - `POST /perusahaan/employees/import/commit` → `commit()`
   - mirror untuk `/sekolah-portal/students/import*`
4. Akun dibuat dengan `must_change_password = true` (kolom dari ONB.3) dan
   `profile_confirmed_at = NULL` (kolom dari ONB.4) → memicu dua gerbang
   login pertama.
5. `AdminAction::record('roster.import', $tenant, ['rows_ok'=>N,'rows_skip'=>M])`
   untuk perusahaan. Sekolah pakai jalur audit yang sama (tanpa PII di payload).
6. View `perusahaan/roster-import.blade.php` + `sekolah/roster-import.blade.php`
   — tabel preview dengan baris merah per error, tombol "Import N baris".

## Verify
- [ ] Test: CSV 3 baris valid → 3 user + 3 roster row ter-link, `user_id` terisi
- [ ] Test: baris tanpa password → password acak, `must_change_password` true
- [ ] Test: email duplikat di dalam file → baris kedua ditolak, baris lain jalan
- [ ] Test: email sudah ada di `users` → dilewati, tidak menimpa password lama
- [ ] Test: header CSV salah → 422 dengan pesan jelas, tidak ada row dibuat
- [ ] Test: divisi/kelas di luar milik tenant → baris error, bukan tersimpan
- [ ] Test: HR perusahaan A tidak bisa import ke perusahaan B (403)
- [ ] Test: file > 5 MB atau > 2000 baris → 422
- [ ] Test: preview tidak menulis apa pun ke DB
- [ ] Suite Feature hijau + `php -l`

## Out of scope
- Kirim email ke hasil import (email belum tersedia).
- XLSX/XLS (butuh dependency — tambah nanti kalau HR menolak CSV).
- Update massal karyawan yang sudah ada (import = create only).
- Undo/rollback satu batch import.

## Dependency / blocker
- Butuh ONB.1 (`departements.perusahaan_id`) dan ONB.3 + ONB.4 (kolom
  `must_change_password` / `profile_confirmed_at`) supaya akun hasil import
  langsung memicu dua gerbang login pertama.
- Varian **sekolah** dengan `tipe=staff`: kolom `sekolah_students.type` dan role
  `sekolah_staff` baru ada di branch `goal/sekolah-flow-parity` (belum merge).
  Kalau branch itu belum masuk `main`, kerjakan varian siswa saja dan tandai
  bagian staff sebagai blocked — **jangan** menduplikasi E.1 di sini.
- `storage/app/private/imports/` harus writable; disk `local` (root
  `storage/app`) sudah cukup, tidak perlu disk baru.