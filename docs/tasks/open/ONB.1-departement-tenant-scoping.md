# ONB.1 — Departemen per tenant (prasyarat pilih divisi)

## Goal
`departements` jadi milik satu perusahaan, supaya karyawan bisa memilih divisi
yang benar saat login pertama dan HR tidak bisa memasukkan divisi perusahaan lain.

## Current State
- Tabel `departements` GLOBAL: kolom cuma `id, name, created_at, updated_at`
  (`2023_11_09_153649_create_departements_table.php`). Tidak ada `perusahaan_id`,
  tidak ada global scope.
- `Departement::fillable = ['name']`.
- `database/seeders/PerusahaanSeeder.php:35` — `Departement::firstOrCreate(['name' => $name])`
  tanpa tenant → dua perusahaan dengan divisi "Finance" berbagi satu row.
- **Bug laten** `app/Http/Controllers/Perusahaan/RosterController.php:42`:
  `Departement::whereKey($id)->where('perusahaan_id', $perusahaan->id)` — kolom
  `perusahaan_id` TIDAK ADA. Query tidak pernah match → selalu 422 "Departemen
  bukan milik perusahaan." Mati hari ini hanya karena form roster tidak pernah
  mengirim `departement_id`.
- FK `departements.id` dipakai `perusahaan_employees`, `perusahaan_sessions`,
  `perusahaan_assessments`, `consultant_education`.

## Perubahan

1. Migrasi `add_perusahaan_id_to_departements`:
   - `perusahaan_id` nullable + FK `nullOnDelete` + index.
   - Nullable supaya row lama (dan `consultant_education`) tidak putus.
2. `Departement`: tambah `perusahaan_id` ke `$fillable`, relasi `perusahaan()`,
   dan static helper `forPerusahaan(int $perusahaanId)` → daftar divisi tenant
   (union: milik tenant + global/null legacy, urut `name`).
3. `PerusahaanSeeder`: isi `perusahaan_id` saat seed; idempotent per
   (perusahaan_id, name).
4. `RosterController::store()`: ganti `abort_unless(... where('perusahaan_id'))`
   yang selama ini dead → validasi `exists:departements,id` **plus** guard
   kepemilikan lewat `forPerusahaan()`. Ini memperbaiki bug laten, bukan
   menghapus guard.
5. `RosterController::index()`: kirim daftar divisi tenant ke view supaya HR
   bisa memilih divisi saat menambah/mengimpor karyawan.

## Verify
- [ ] Test: divisi milik perusahaan A tidak muncul di dropdown perusahaan B
- [ ] Test: HR perusahaan A POST `departement_id` milik perusahaan B → 422
- [ ] Test: HR perusahaan A POST divisi miliknya sendiri → tersimpan
- [ ] Test: seeder dua kali dijalankan → tidak ada duplikat row
- [ ] Test: `departement_id` null tetap boleh (opsional saat import)
- [ ] `php -l` semua file yang berubah + suite Feature hijau

## Out of scope
- Backfill data historis (`departement_id` lama tetap menunjuk row global).
- Rename/merge divisi di UI.
- Divisi untuk sekolah — kelas siswa sudah berupa string `kelas` di
  `sekolah_students`, tidak perlu tabel baru.