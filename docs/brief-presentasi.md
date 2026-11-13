# Soluusi B2B Klinik — Brief Handoff

> Update: 2026-11-13. Repo: `fauzan171/soluusib2b`, branch **`upgrade/laravel-12`** (Laravel 12), semua sudah di-push sampai commit `65e3e6e`.

## Cara Menjalankan (VPS ini)

- App jalan di **127.0.0.1:8099** via systemd user service: `soluusi-b2b-klinik.service`
- Akses publik: **https://soluusi.tokengateway.ai.id** (tunnel cloudflared, service `kawanku-tunnel.service`)
- Restart bila perlu: `systemctl --user restart soluusi-b2b-klinik.service`
- Dev pakai `DB_CONNECTION=sqlite`, `SESSION_DRIVER=database`. Jangan tampilkan isi `.env`.

## Akun Demo (password semua: `password`)

| Role | Email | Perusahaan |
|---|---|---|
| Karyawan | `bayu.wirawan@demo-karyawan.test` | PT Solusi Digital (paling lengkap: sesi 9008 + chat) |
| Karyawan | `andi.pratama3@demo-karyawan.test` | PT Maju Bersama (multi-tenant) |
| Konsultan | `konsultan@klinik-konsultan.local` | — |
| HR Admin | `hr@solusidigital.test` | PT Solusi Digital |
| HR Admin | `hr@majubersama.test` | PT Maju Bersama |
| Super Admin | `admin@klinik-konsultan.local` | — |
| Klinik Admin | `admin2@uji.local` | — |

## Status QA (presentasi-safe)

- Tests: **66/66 pass** (321 assertions)
- Login live semua role verified via tunnel (employee/konsultan/HR)
- Log app: 0 error runtime (hanya Google Calendar `non-fatal` karena token tidak diset — normal di dev)
- Satu temuan & sudah diperbaiki: password 4 akun demo karyawan (dibuat via self-register, password custom) → di-reset ke `password` (data, bukan kode)

## Flow Demo (urutan cerita)

1. **Login karyawan (Bayu)** → dashboard kuota/riwayat → **booking sesi baru** (pilih jenis konsultan → slot 15 Nov 09:00/10:00 masih kosong, bisa live) → masuk **chat room** sesi 9008 → kirim pesan
2. **Login konsultan** (incognito) → buka sesi 9008 → chat → **Mulai sesi** → balas → **Akhiri sesi** → status completed
3. **Login HR** `hr@solusidigital.test` → dashboard kuota + sesi aktif perusahaan → ganti `hr@majubersama.test` → data beda → **proof multi-tenant**
4. **Super admin** (opsional) → overview semua perusahaan

Tips: pakai 2 browser (normal + incognito) biar gak perlu logout-login. Demo paling kuat = chat real-time (fitur terbaru, fix commit `65e3e6e`).

## Data Demo di DB (sqlite: `database/database.sqlite`)

- Sesi demo utama: **9008** (psikolog, scheduled 2026-11-16 09:00, employee Bayu, schedule 161, chat ada pesan)
- Slot kosong siap live-booking: schedule **159** (15 Nov 10:00) & **160** (15 Nov 09:00)
- Tabel penting: `schedules` (kolom `started_at`/`ended_at`, bukan `start`), `perusahaan_sessions` (kolom `konsultan_type`, bukan `type`), `booking_claims` (`schedule_id`, `owner_type`, `owner_id`)

## Sejarah Singkat / Catatan Penting

- Prod lama Hostinger = marketplace 1394 user aktif — **jangan diganggu**, jangan cutover tanpa izin. Staging L12 ada di `~/soluusi_l12` di VPS ini.
- Branch `main` lokal = state lama (NT-P1), jangan di-merge/di-push.
- Flow self-register: `app/Http/Controllers/SelfRegisterController.php:74` — password dari request (akun demo hasil flow ini punya password custom, bukan seeder).
- Seeder: `database/seeders/PerusahaanSeeder.php` (email karyawan dinamis `Str::slug($name,'.').$p->id.'@demo-karyawan.test'`).
- Fitur terbaru (urut commit terbaru): fix chat sender name (`65e3e6e`), filter sesi aktif HR dashboard (`ab154d4`), tombol Mulai/Akhiri sesi konsultan + tabel sesi aktif HR (`583b448`), provisioning perusahaan B2B super admin (`b41b1b4`).

## Kerja di Tempat Lain

```bash
git clone -b upgrade/laravel-12 https://github.com/fauzan171/soluusib2b.git
cd soluusib2b
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```
