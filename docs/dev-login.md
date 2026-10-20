# Akun Login Development — Soluusi B2B Klinik

Semua akun di bawah memakai password: **`password`**

Data di bawah di-sync dengan isi database lokal (SQLite).

## 1. Super Admin (Business Owner)

| Email | Dashboard |
|---|---|
| `admin@klinik-konsultan.local` | `/admin/dashboard` — approve klinik, settle margin, verify sesi |

Halaman khusus super admin:
- `/admin/referral-overview` — afiliasi HAP → klinik, flag klinik bodong
- `/admin/consultant-per-klinik` — aktivitas konsultan per mitra
- `/admin/pervorma-sessions` — sesi kontributor pervorma HAP

## 2. HAP / Associate (Referral)

| Email | Klinik yang ditautkan |
|---|---|
| `associate@klinik-konsultan.local` | Klinik Sehat Demo, Klinik Prima Sehat |

Dashboard HAP: `/associate/dashboard` — tracking komisi pervorma 3-stage per klinik.

## 3. Admin Klinik (Partner Admin)

| Email | Klinik | Peran | Dashboard |
|---|---|---|---|
| `admin.prima@soluusi.local` | Klinik Prima Sehat | admin | `/klinik/dashboard` |
| `staff.prima@soluusi.local` | Klinik Prima Sehat | staff | `/klinik/dashboard` |
| `admin@demo.test` | Klinik Sehat Demo | admin | `/klinik/dashboard` |
| `mitra-baru@test.id` | Klinik Baru (pending) | admin | `/klinik/dashboard` |

## 4. Konsultan (8 akun pool demo)

| Email | Profesi |
|---|---|
| `dr-maya-kusuma-mpsi-psikolog72@soluusi-demo.local` | Psikolog Klinis |
| `arief-rahman-spsi-mpsi52@soluusi-demo.local` | Psikolog Klinis Anak & Remaja |
| `nadia-putri-sgz-rd56@soluusi-demo.local` | Nutrisionis Klinis |
| `fajar-nugroho-sft-ftr31@soluusi-demo.local` | Fisioterapis Muskuloskeletal |
| `sari-indah-strot82@soluusi-demo.local` | Terapis Okupasi |
| `bimo-aditya-mpsi-psikolog98@soluusi-demo.local` | Konselor Keluarga & Pernikahan |
| `laras-wulandari-spsi27@soluusi-demo.local` | Psikolog Pendidikan |
| `doni-prasetyo-sgz90@soluusi-demo.local` | Nutrisionis Komunitas |

Dashboard konsultan: `/klinik/sessions` — daftar sesi, check-in, mark-done.

## 5. Customer / Pasien

Tidak perlu login (Model C guest) — buka link checkout token langsung, isi data, bayar.

## 6. HR Admin Perusahaan (vertikal CAP)

| Email | Perusahaan | Role | Landing |
|---|---|---|---|
| `hr@solusidigital.test` | PT Solusi Digital Indonesia (24 karyawan) | hr_admin | `/perusahaan/dashboard` |
| `hr@majubersama.test` | PT Maju Bersama Sejahtera (12 karyawan) | hr_admin | `/perusahaan/dashboard` |

## 7. Karyawan (employee — demo dashboard karyawan)

| Email | Perusahaan | Landing |
|---|---|---|
| `andi.pratama1-login@demo-karyawan.test` | PT Solusi Digital Indonesia | `/perusahaan/employee` |
| `andi.pratama2-login@demo-karyawan.test` | PT Maju Bersama Sejahtera | `/perusahaan/employee` |

Super admin bisa preview kedua dashboard + switch perusahaan via dropdown nav.
Semua password: `password`.

## Cara Membuat Ulang Data Dummy

```bash
php artisan db:seed --class=KlinikDemoSeeder      # klinik Prima Sehat + 8 konsultan + paket + jadwal + markup
php artisan db:seed --class=KlinikSessionSeeder   # 60 sesi dummy untuk chart dashboard (12 minggu)
php artisan db:seed --class=PerusahaanSeeder      # 2 perusahaan + karyawan + sesi + HR/employee user
```

Semua seeder idempotent — aman dijalankan ulang, akan skip kalau data sudah ada.
