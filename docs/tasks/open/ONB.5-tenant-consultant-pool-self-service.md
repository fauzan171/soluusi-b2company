# ONB.5 — HR/BK mengelola sendiri pool konsultan tenant

## Goal
Setelah MOU, HR (perusahaan) dan BK (sekolah) bisa mengubah sendiri konsultan
mana yang aktif untuk organisasinya — tanpa menunggu superadmin — dengan batas
tegas bahwa mereka hanya bisa memilih dari konsultan yang **sudah** masuk
kontrak Soluusi untuk tenant itu.

## Current State
- Pool = tabel `consultant_pools` (`consultant_id`, `sekolah_id`,
  `perusahaan_id`). Row dibuat superadmin di
  `Admin\ConsultantAssignmentController::assign()` (dan saat provisioning
  `ProvisioningController::createPerusahaan()` lewat `consultant_ids`).
- Konsumennya: `ConsultantPool::poolFor($tenant, $tenantId)` — dipakai halaman
  booking karyawan/siswa dan katalog konsultan.
- HR/BK **tidak punya** UI apa pun. Satu-satunya jalan adalah superadmin.
- Audit sudah tersedia: `AdminAction::record('consultant_pool.assign'|
  'consultant_pool.remove', ...)`. Tapi `AdminAction` secara semantik
  "aksi admin" — perlu dicek apakah aman dipakai untuk aktor non-admin.
- `Consultant` punya `status = Consultant::ACCEPT`.

## Perubahan

1. **Model batas kontrak** (keputusan desain): HR/BK tidak bisa menambah
   konsultan yang belum pernah di-assign Soluusi. Yang mereka ubah adalah
   **status aktif** dari himpunan yang sudah ada.
   - Migrasi `add_is_active_to_consultant_pools`: `is_active` boolean
     default `true`.
   - `ConsultantPool::poolFor()` hanya mengembalikan `is_active = true`.
   - Konsekuensi: `assign()` superadmin tetap satu-satunya jalan masuk pool;
     HR/BK jalan untuk menyalakan/mematikan.
2. Controller `Perusahaan\ConsultantPoolController` + `Sekolah\ConsultantPoolController`
   (dua file tipis, atau satu dengan `$vertical` — pilih satu file dengan
   parameter supaya tidak duplikat logika):
   - `index()` — daftar konsultan di pool tenant: nama, kategori, status aktif.
   - `toggle()` — flip `is_active` milik tenant sendiri.
   - Guard: row harus milik tenant yang ter-resolve dari context (superadmin
     view-as ikut aturan read-only existing).
3. Route di grup `role:hr_admin,admin` (`/perusahaan/konsultan`) dan
   `role:bk,admin` (`/sekolah-portal/konsultan`), throttle `20,1`.
4. Audit: tulis `AdminAction::record(auth()->id(), 'consultant_pool.toggle',
   $tenant, ['consultant_id' => N, 'is_active' => bool, 'actor_role' => ...])`.
   Tanpa PII. Kalau semantik tabel jadi rancu, cukup dokumentasikan di docblock
   model bahwa kolom `admin_id` berarti "aktor" sejak ONB.5 — jangan bikin tabel
   audit kedua.
5. View `tenant/consultant-pool.blade.php` (satu view dua vertikal, label
   bercabang) + link di dashboard HR/BK.

## Verify
- [ ] Test: HR perusahaan A melihat hanya pool perusahaan A
- [ ] Test: HR toggle off → konsultan hilang dari halaman booking karyawan
- [ ] Test: HR toggle on kembali → muncul lagi
- [ ] Test: HR perusahaan A tidak bisa toggle row perusahaan B (403/404)
- [ ] Test: BK sekolah bisa toggle pool sekolahnya sendiri
- [ ] Test: karyawan/siswa tidak bisa akses halaman (403)
- [ ] Test: tidak ada jalan bagi HR untuk menambah konsultan baru di luar pool
- [ ] Test: toggle tercatat di `admin_actions` dengan payload tanpa PII
- [ ] Test: konsultan `status != ACCEPT` tidak muncul di halaman
- [ ] Suite Feature hijau + `php -l`

## Out of scope
- HR memilih konsultan dari seluruh database Soluusi (itu tetap wewenang
  superadmin/ops — kontrak & lisensi).
- Riwayat perubahan pool di UI (audit tetap ada di DB, viewer admin sudah ada).
- Slot/jadwal konsultan per tenant (tetap global `schedules`).
- Pool klinik (`consultant_pools` belum punya `klinik_id`).