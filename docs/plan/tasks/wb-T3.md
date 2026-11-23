# wb-T3 — Superadmin katalog CRUD + harga per band UI

Status: READY
Depends: wb-T2 (model final). Blocking wb-T4 (nav + route section dibuat di sini).

## GOAL
Halaman admin "Katalog Paket Wellbeing": list 4 paket + edit paket (fitur) + edit harga 5 band inline per paket — role `admin` saja, pola `KlinikPackageController` + Blade flat.

## CONTEXT
- UX EKSAK: `docs/plan/research-ux.md` §3a (list) + §3b (edit 1 paket per halaman, harga band inline 5 baris, pola `admin/markup-rules.blade.php:32-39`), heading/deskripsi/copy §2 (heading "Katalog Paket Wellbeing", deskripsi "Empat paket siap pakai...", label "Harga per Band Karyawan", copy paket alternatif (a)).
- Pola controller: `app/Http/Controllers/Klinik/KlinikPackageController.php` (CRUD superadmin); audit: `AdminAction::record($adminId, $action, $target, $payload)` per mutasi; pola view: `admin/perusahaans.blade.php` (form-atas-tabel-bawah, status chip, empty state, `Rp number_format($x, 0, ',', '.')`).
- Route group: `routes/web.php` admin group (`prefix('admin')` + `['auth','role:admin']`, baris ~252) — tambah section route BARU saja, jangan sentuh baris existing. Nav: entry `$navItems` admin branch di `resources/views/layouts/app.blade.php:21-76` — icon 'sparkles', label "Katalog Paket Wellbeing".
- Harga display selalu suffix "+ pajak" (R5, K13). Edit harga band → hanya ubah master katalog (kontrak snapshot tidak berubah — assert di test B3).
- Discovery: harga tidak editable (is_free → tampil "Gratis", band hidden/disabled).

## CONSTRAINTS
- Files: `app/Http/Controllers/Admin/WellbeingPackageController.php` (index, edit, update package; updateBand per band), `resources/views/admin/wellbeing-packages/index.blade.php` + `edit.blade.php`, route section baru `admin.wellbeing-packages.*` (GET index, GET {package}/edit, PUT {package}, PUT bands/{band}), 1 nav entry.
- Validasi: band `band_min < band_max`, `monthly_price >= 0`, band overlap dalam 1 paket ditolak (validation error, DB bersih).
- TIDAK ada create/delete paket (katalog = 4 paket seeder; edit saja) — YAGNI, seeder jadi sumber paket.
- `AdminAction::record` di setiap update. Flash success pattern layouts/app:205.
- Blade flat, no component class. Emerald button admin. File baru `chgrp hermes` + `g+r`.
- JANGAN edit route/nav section milik perusahaan (HR) — itu T5.

## ACCEPTANCE
1. `WellbeingCatalogAdminTest` baru (pola `AdminActionsAuditTest`): admin GET index 200 + 4 paket tampil; PUT update package fields → `assertDatabaseHas` + `AdminAction` row; PUT band harga baru → tersimpan; band overlap → validation error, DB bersih; hr_admin/employee → 403 semua verb (A3); Discovery → harga tidak editable (form disabled / PUT band discovery ditolak 422).
2. Test B3: edit harga band setelah ada kontrak aktif → `company_wellbeing_programs.monthly_price` snapshot TIDAK berubah.
3. Full suite `/usr/local/bin/php artisan test` hijau (712 + semua test wb sebelumnya + baru), 0 edit test existing.

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil / deviations / blockers
