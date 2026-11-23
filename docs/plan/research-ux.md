# Research UX — Paket Wellbeing (Discovery/Foundation/Growth/Impact)

Date: 2026-11-23. Scope: survey pattern UI existing, naming + copywriting Indonesia, flow UX superadmin (katalog paket → harga per band → assign → aktivasi), flow HR (Program & Subscription). Sumber produk: `docs/plan/PRODUCT-DOC-SUMMARY.md`.

---

## 1. Survey pattern UI existing (file:line)

### Layout & navigasi

| Pattern | Lokasi | Catatan |
|---|---|---|
| Single shared layout, sidebar role-aware via `match(true)` di `@php` | `resources/views/layouts/app.blade.php:21-76` | Menu baru = tambah entry di array navItems, route + pattern + label + icon |
| Sidebar gradient pine (`portal-sidebar`) | `layouts/app.blade.php:80`, CSS `resources/css/app.css:49-51` | Dark green, brand monogram huruf pertama |
| Icon set inline via partial | `resources/views/perusahaan/partials/icon.blade.php` (dipakai `layouts/app.blade.php:94`) | Icon name string ('chart','users','calendar','sparkles','heart','shield','bolt','receipt','clock','building','document-arrow-down') |
| Header sticky + breadcrumb "← Dashboard" | `layouts/app.blade.php:108`, contoh `admin/perusahaans.blade.php:6` | Link kembali = `text-sm text-emerald-700 font-semibold hover:underline` (admin) / `text-pine-700` (HR) |
| Tenant switcher superadmin (3 vertikal) | `layouts/app.blade.php:143-169` | Assign program bisa ikut pola "pilih perusahaan lalu aksi" |
| Flash message (success/error/info) | `layouts/app.blade.php:205-213` | `bg-pine-100 text-pine-800` / red / blue, `rounded-xl p-3 text-sm` |

### Admin Soluusi (pattern tabel + form)

| Pattern | Lokasi | Catatan |
|---|---|---|
| **Form inline di atas tabel** (satu halaman: create + list) | `admin/perusahaans.blade.php:14-45 (form) + 47-81 (tabel)` | Ini acuan utama untuk halaman katalog paket |
| Form grid `md:grid-cols-2`, label text-sm, input `border rounded p-2 mt-1` | `admin/perusahaans.blade.php:15,18-38` | Placeholder contoh riil ("PT Contoh Sejahtera") |
| Submit button emerald | `admin/perusahaans.blade.php:42` | `bg-emerald-600 text-white font-semibold rounded py-2` (admin pakai emerald, HR pakai pine) |
| Tabel: `overflow-x-auto bg-white border rounded-lg` + `table w-full text-sm` + thead `text-gray-500` | `admin/markup-rules.blade.php:17-22`, `admin/perusahaans.blade.php:47-55` | |
| **Edit nilai per baris via inline form + tombol "Simpan" di kolom Aksi** | `admin/markup-rules.blade.php:32-39` | Ini SUDAH pattern harga per baris — reuse untuk harga per band |
| Format harga `Rp{{ number_format($x, 0, ',', '.') }}` | `admin/perusahaans.blade.php:67`, `admin/markup-rules.blade.php:31,38` | Konsisten |
| Status chip: Aktif=emerald, Nonaktif=gray | `admin/perusahaans.blade.php:70-74` | `text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold` |
| Empty state tabel: `text-gray-400` satu baris kalimat pendek | `admin/perusahaans.blade.php:78`, `admin/markup-rules.blade.php:42` | Pattern: "Belum ada X. [Aksi]." |
| Halaman = `@extends('layouts.app')` + `@section('content')`, tanpa component Blade custom | semua admin views | Tidak ada x-component library — tulis Blade flat |

### HR / perusahaan (pattern card dashboard)

| Pattern | Lokasi | Catatan |
|---|---|---|
| `.dash-card` (rounded-1.25rem, border #e7e2d9, shadow lembut) + `.dash-section-title` + `.dash-section-subtitle` | didefinisikan inline `<style>` di `perusahaan/dashboard.blade.php:27-40` | Copy blok style ini ke view baru (tidak ada file CSS global untuk dashboard classes) |
| `.panel-surface` (inner surface gradient cream) | `perusahaan/dashboard.blade.php:35-40` | Untuk sub-card di dalam dash-card |
| Hero band gradient pine untuk halaman utama | `perusahaan/booking.blade.php:6-23` | Untuk "Program & Subscription" hero |
| Quota bar: `h-3 rounded-full bg-gray-100` + fill gradient `from-pine-700 via-pine-500 to-terra-400` + label `{{ used }} / {{ total }} sesi` + warna terra saat >=80% | `perusahaan/dashboard.blade.php:244-259` | **Reuse langsung untuk sisa kuota chat/deep support** |
| Alert strip tone red/terra/pine dengan icon | `perusahaan/dashboard.blade.php:92-100` | Untuk near-expiry |
| Stat card grid + toneClasses (pine/terra/red/slate) | `perusahaan/dashboard.blade.php:10-22` | Untuk ringkasan paket aktif |
| Empty state pending (icon lingkaran amber + judul + 1 paragraf + CTA superadmin) | `perusahaan/pending.blade.php:4-27` | **Acuan langsung empty state "belum ada paket aktif"** |
| Chart.js 4.4.7 CDN + palette PINE/TERRA/RED + font Instrument Sans | `perusahaan/wellbeing.blade.php:176-182` | Kalau perlu sparkline usage |

### Design language ringkas

- Tailwind utility + custom token pine/terra (`resources/css/app.css:13-23`), font Instrument Sans, `bg-paper text-ink` body.
- Border default `#e7e2d9` (cream line), radius besar (rounded-xl/2xl), shadow sangat halus.
- Admin pages: functional, flat, emerald button. HR pages: card-based, hero gradient, pine button, micro-animation `fade-up`.
- Bahasa UI: **Bahasa Indonesia semi-formal** — "Anda/kamu" dipakai selektif (HR view pakai "kamu" ke employee, admin pakai netral), istilah teknis Inggris dibiarkan (Dashboard, Ops Queue, Rate PEPM, Checkout Link). Empty state selalu kalimat pendek + arahan aksi.

---

## 2. Naming + copywriting

### Menu label (sidebar admin Soluusi)

Fitur baru: katalog paket + harga per band + assign ke perusahaan + aktivasi. 2-3 alternatif:

| Kebutuhan | Alt 1 (rekomendasi) | Alt 2 | Alt 3 |
|---|---|---|---|
| Menu katalog paket + harga | **Katalog Paket Wellbeing** | Paket & Harga | Paket B2B |
| Sub/menu assign + aktivasi | **Assign Program** | Program Perusahaan | Penempatan Program |
| Label band harga | **Band Karyawan** (50–150 dst) | Tier Ukuran Perusahaan | Segmen Karyawan |
| Menu HR | **Program & Subscription** (nama dari doc produk) | Paket & Program Berjalan | Program Aktif |
| Section harga per band di form paket | **Harga per Band** | Daftar Harga | Matriks Harga |

Rekomendasi final: `Katalog Paket Wellbeing` + `Assign Program` (dua menu admin, mengikuti panjang label existing seperti "Assignment Konsultan", "Perusahaan B2B"). HR: `Program & Subscription` sesuai daftar 10 menu produk.

### Heading halaman + deskripsi (1 kalimat per field)

| Halaman | Heading | Deskripsi di bawah heading |
|---|---|---|
| Admin — Katalog Paket | `Katalog Paket Wellbeing` | "Empat paket siap pakai: Discovery, Foundation, Growth, Impact. Atur isi dan harga per band karyawan di sini." |
| Admin — Assign Program | `Assign Program` | "Aktifkan paket untuk perusahaan: pilih paket, band, opsi Deep Support, dan tanggal mulai." |
| Admin — harga per band (dalam edit paket) | `Harga per Band Karyawan` | "Harga bulanan menyesuaikan jumlah karyawan. Angka terkunci saat program diaktifkan." |
| HR — Program & Subscription | `Program & Subscription` | "Paket aktif perusahaan {{brand}}, sisa kuota layanan, dan masa berlaku program." |

### Copy 1 kalimat per paket (prinsip: "One package, one clear purpose")

| Paket | Alternatif copy (pilih salah satu per kolom) |
|---|---|
| **Discovery** | a) "Foto kondisi kesejahteraan tim dalam 14 hari — gratis, tanpa komitmen." |
| | b) "Coba Soluusi 14 hari: asesmen ringan + 8 sesi psikolog untuk karyawan terpilih." |
| | c) "Langkah pertama melihat gambaran wellbeing tim, gratis dan ringkas." |
| **Foundation** | a) "Bangun fondasi wellbeing perusahaan dalam 3 bulan: asesmen 1 domain, chat expert, dan webinar pembuka." |
| | b) "Program 3 bulan untuk mulai membiasakan wellbeing di tempat kerja." |
| | c) "Mulai teratur: satu domain asesmen, dukungan expert, dan action plan pertama Anda." |
| **Growth** | a) "Intervensi terarah 6 bulan di dua domain, dengan pilihan Deep Support sesuai kebutuhan tim." |
| | b) "Naik level: 2 domain asesmen, 3 webinar, dan opsi Deep Support 15 sesi atau 15 MHCU." |
| | c) "Program 6 bulan untuk tim yang siap intervensi lebih dalam." |
| **Impact** | a) "Program setahun penuh dengan evaluasi berkala tiap 3 bulan — untuk perubahan yang terukur dan berkelanjutan." |
| | b) "Komitmen setahun: 2 domain asesmen, 6 webinar, review kuartalan, dan Deep Support terbesar." |
| | c) "Paket terlengkap: setahun program plus laporan tren pre/post dan review tiap kuartal." |

Rekomendasi: pakai alternatif (a) masing-masing — paling menjual, menyebut durasi + isi inti + benefit, tanpa jargon klinis.

### Label field form admin (konsisten existing)

- `Nama Paket` (select: Discovery/Foundation/Growth/Impact saat create, atau fixed saat edit)
- `Durasi` (14 hari / 3 bulan / 6 bulan / 12 bulan — readonly per paket)
- `Deskripsi Singkat` (1 kalimat, textarea 2 baris, placeholder = copy kolom (a) di atas)
- `Kuota Chat Expert` (number, "jumlah expert, bukan sesi")
- `Sesi Deep Support` (number per opsi)
- `Jumlah Webinar` (number)
- `Tanggal Mulai` (date)
- `Status` (chip Aktif/Nonaktif — reuse `admin/perusahaans.blade.php:70-74`)

---

## 3. Flow UX Superadmin (ASCII wireframe)

Urutan halaman: **Katalog Paket (list) → Edit Paket + Harga per Band → Assign Program (wizard 3 step) → Aktivasi**.

### 3a. Katalog Paket (list)

```
+------------------------------------------------------------------------------------+
| Katalog Paket Wellbeing                                            ← Dashboard       |
| Empat paket siap pakai: Discovery, Foundation, Growth, Impact.                      |
| Atur isi dan harga per band karyawan di sini.                                       |
+------------------------------------------------------------------------------------+
| [ + Tambah Paket Baru ]                                                            |
+--------------------+---------------+----------+-----------+----------+--------------+
| Paket             | Durasi        | Isi inti | Webinar   | Harga    | Aksi         |
+--------------------+---------------+----------+-----------+----------+--------------+
| Discovery          | 14 hari       | 1 asessm | —         | Gratis   | [Edit]       |
| Foundation         | 3 bulan       | 1 domain | 1         | per band | [Edit Harga] |
| Growth             | 6 bulan       | 2 domain | 3         | per band | [Edit Harga] |
| Impact             | 12 bulan      | 2 domain | 6         | per band | [Edit Harga] |
+--------------------+---------------+----------+-----------+----------+--------------+
| (empty: "Belum ada paket. Buat lewat form di atas." — pola perusahaans.blade.php)  |
+------------------------------------------------------------------------------------+
```

4 baris saja — tidak overwhelm. Kolom "Isi inti" ringkas; detail lengkap di halaman edit.

### 3b. Edit Paket — mengatasi tabel harga 5 band × 3 paket yang tidak overwhelm

KUNCI: **jangan tampilkan matriks 15 angka sekaligus**. Pattern `admin/markup-rules.blade.php` (edit inline per baris) di-scale per halaman:

- Satu halaman edit = SATU paket (bukan 3).
- Harga band = bagian bawah form paket, 5 baris (satu per band), edit inline ala markup-rules.

```
+------------------------------------------------------------------------------------+
| Edit Paket — Growth                                                 ← Katalog       |
+------------------------------------------------------------------------------------+
| [ Form: Deskripsi Singkat (textarea)  ]  [ Kuota Chat Expert: 2 ]                   |
| [ Sesi Deep Support A: 15 MHCU ] [ Sesi Deep Support B: 15 x 1-on-1 ]              |
| [ Jumlah Webinar: 3 ]              [ Pakai Option A/B: (radio) ]                    |
+------------------------------------------------------------------------------------+
| Harga per Band Karyawan (Rp/bulan)                                                 |
| Harga bulanan menyesuaikan jumlah karyawan. Angka terkunci saat program diaktifkan.|
+--------------------+---------------+---------------+------------------------------+
| Band Karyawan      | Harga (Rp)    | Simpan        |
+--------------------+---------------+---------------+
| 50–150 karyawan    | [14.499.999 ] | [Simpan]      |
| 151–400 karyawan   | [ ...       ] | [Simpan]      |
| 401–600 karyawan   | [ ...       ] | [Simpan]      |
| 601–800 karyawan   | [ ...       ] | [Simpan]      |
| 801–1.000 karyawan | [ ...       ] | [Simpan]      |
+--------------------+---------------+---------------+
```

5 input number per paket = batas kewajaran. Kalau kelak butuh view semua harga sekaligus (untuk Finance), buat read-only "Matriks Harga" di menu terpisah — YAGNI sekarang.

### 3c. Assign Program (wizard 3 step, satu form per halaman)

```
STEP 1 — Perusahaan & Paket
+---------------------------------------------------------------+
| Assign Program (1/3)                                          |
| Aktifkan paket untuk perusahaan: pilih paket, band, opsi      |
| Deep Support, dan tanggal mulai.                              |
+---------------------------------------------------------------+
| Perusahaan      : [ select — daftar perusahaan aktif ▼ ]      |
| Paket           : ( radio )  ○ Discovery   ○ Foundation       |
|                              ○ Growth    ○ Impact             |
+---------------------------------------------------------------+
|                                          [ Lanjut → ]         |
+---------------------------------------------------------------+

STEP 2 — Detail Paket
+---------------------------------------------------------------+
| Assign Program (2/3) — untuk PT Contoh / Growth               |
+---------------------------------------------------------------+
| Band Karyawan   : ( radio, auto-highlight band sesuai         |
|                    jumlah karyawan terdaftar: *401–600* )      |
| Harga terkunci  : Rp 20.499.999 / bulan (readonly,            |
|                  "Angka terkunci saat program diaktifkan.")   |
| Opsi Deep Support (hanya Growth/Impact):                      |
|   ( radio )  ○ Option A: 15 sesi MHCU                        |
|              ○ Option B: 15 sesi 1-on-1 (60 menit)            |
| Tanggal Mulai   : [ date, default = besok ]                   |
| Durasi Program  : 6 bulan (readonly)                          |
+---------------------------------------------------------------+
|                          [ ← Kembali ]  [ Lanjut → ]          |
+---------------------------------------------------------------+

STEP 3 — Konfirmasi & Aktivasi
+---------------------------------------------------------------+
| Assign Program (3/3) — Konfirmasi                             |
+---------------------------------------------------------------+
| PT Contoh · Paket Growth · Band 401–600                       |
| Rp 20.499.999/bulan · exclude pajak                           |
| Option B: 15 sesi 1-on-1 · Mulai 1 Des 2026 · Berakhir        |
| 31 Mei 2027 (6 bulan)                                         |
| [ ] Saya konfirmasi scope & harga sesuai Program Order        |
+---------------------------------------------------------------+
|              [ ← Kembali ]  [ Aktifkan Program ]              |
+---------------------------------------------------------------+
| → redirect + flash success (bg-pine-100, layouts/app:205)     |
+---------------------------------------------------------------+
```

Catatan UX:
- Band di-step 2 **auto-highlight** berdasarkan jumlah karyawan terdaftar, superadmin bisa override (kasus khusus).
- Opsi A/B hanya muncul untuk Growth/Impact (conditional render, `@if(in_array($paket, ['Growth','Impact']))`).
- Checkbox konfirmasi di step 3 mengikuti spirit S&K "checkbox consent" (PRODUCT-DOC-SUMMARY §6).
- Aktivasi langsung trigger status chip "Aktif" + email/flash ke HR.

### 3d. State setelah aktivasi

Tabel assign (bisa jadi bagian bawah halaman Assign Program, pola form-atas-tabel-bawah ala `perusahaans.blade.php`):

```
+-------------------------------------------------------------------------------+
| Program Berjalan                                                             |
+---------------+---------+----------+--------------+----------+---------------+
| Perusahaan    | Paket   | Band     | Opsi DS      | Periode  | Status        |
+---------------+---------+----------+--------------+----------+---------------+
| PT Contoh     | Growth  | 401–600  | B: 15 1-on-1 | Des–Mei  | [Aktif]       |
| PT Maju       | Foundation | 151–400 | —          | Nov–Jan  | [Aktif]       |
+-------------------------------------------------------------------------------+
```

---

## 4. Flow UX HR — halaman "Program & Subscription"

Struktur: hero band (paket aktif) + kartu kuota + tabel komponen + empty/near-expiry state.

```
+---------------------------------------------------------------------------+
| ###### hero band gradient pine (pola booking.blade.php:6) ######          |
|  PROGRAM & SUBSCRIPTION                    [ Paket aktif: Growth ]        |
|  Paket aktif perusahaan Contoh, sisa kuota layanan, dan masa berlaku.     |
|  Berlaku: 1 Des 2026 – 31 Mei 2027 · 6 bulan tersisa                      |
+---------------------------------------------------------------------------+
| ( stat cards, pola dashboard.blade.php:10 — 3 kartu )                     |
| +-----------------+ +-----------------+ +-----------------+               |
| | Chat Expert     | | Deep Support    | | Webinar         |               |
| | 12 / 30 sesi    | | 8 / 15 sesi     | | 1 / 3           |               |
| | [====progress   | | [==progress bar | | dijadwalkan     |               |
| |  bar pine]      | |  terra 53%]     | |                 |               |
| +-----------------+ +-----------------+ +-----------------+               |
+---------------------------------------------------------------------------+
| +------ dash-card (pola dashboard.blade.php:27) ---------------------+     |
| | Ringkasan Program Growth                                          |     |
| | Asesmen: 2 domain · Chat: 2 expert · Webinar: 3 · Review: —       |     |
| | [ panel-surface: komponen + status per komponen ]                 |     |
| +-------------------------------------------------------------------+     |
+---------------------------------------------------------------------------+
```

Progress bar kuota: reuse persis `perusahaan/dashboard.blade.php:244-259` (bar + `{{used}} / {{total}}` + label terra "Mendekati batas kapasitas" saat >=80%). Catatan penting: kuota kontrak-level total (bukan bulanan) — label jangan bilang "bulan ini", tulis "sisa kuota program".

### Empty state (belum ada paket aktif) — pola `perusahaan/pending.blade.php`

```
+-----------------------------------------------+
|        ( icon lingkaran amber, jam )          |
|           Program belum aktif                 |
|  Wellbeing program Contoh belum memiliki      |
|  paket aktif. Hubungi tim Soluusi untuk       |
|  memilih paket yang sesuai untuk perusahaan   |
|  Anda.                                        |
+-----------------------------------------------+
```

### Near-expiry state (≤30 hari sebelum berakhir) — pola alert strip `dashboard.blade.php:92`

```
+---------------------------------------------------------------------------+
| (! terra) Program Growth berakhir dalam 26 hari (31 Mei 2027).            |
| Hubungi account manager Soluusi untuk perpanjangan — kuota tersisa        |
| tidak dipindahtuangkan ke periode berikutnya.                             |
+---------------------------------------------------------------------------+
```

Tone: informasikan hangusnya kuota sebagai fakta netral + arahkan ke aksi (hubungi AM), bukan ancaman.

---

## 5. Tone-of-voice & larangan istilah

Aturan main copy UI (turunan design principle §9 + produk doc):

1. **Tidak ada istilah klinis di UI HR/admin.** MCMI-III hanya tampil di expert-view. Daftar assessment untuk HR = "Wellbeing Assessment" generik. DASS-21/WHO-5 boleh disebut nama instrumen di halaman Assessment Management (admin), tidak di HR marketing copy.
2. **Kata "diagnosis" dilarang.** Frasa pengganti: "gambaran wellbeing", "skor wellbeing", "area prioritas".
3. **Tidak ada "karyawan high-risk" / "Berisiko Tinggi" untuk individu.** Dalam agregat company-level existing code memakai label `Berisiko Tinggi` (`perusahaan/wellbeing.blade.php:7-11`) — untuk fitur paket baru gunakan **"area prioritas wellbeing"** (sesuai fitur A: Risk & Priority Map → "area prioritas, bukan karyawan high-risk"). Catatan: label `Berisiko Tinggi` di palette existing adalah distribusi agregat anonim — di nyatakan perlu di-review terpisah apakah ikut diganti; untuk scope fitur paket, jangan perkenalkan label risiko baru.
4. Sapaan: admin/superadmin = netral ("Atur harga per band"), HR = "Anda/kamu" sesuai konteks (existing: HR view "perusahaan kamu", employee "kamu"), employee-facing = "kamu" hangat.
5. Harga selalu `Rp + number_format(..., ',', '.')` + disclaimer "exclude pajak" dekat angka.
6. Empty state selalu: fakta singkat ("Belum ada X") + satu kalimat arahan aksi ("Buat lewat form di atas" / "Hubungi tim Soluusi"). Pattern existing `admin/perusahaans.blade.php:78`.
7. Bahasa Indonesia semi-formal; istilah produk Inggris dibiarkan (Deep Support, MHCU hanya di admin; HR-facing tulis "sesi psikolog 1-on-1" / "MHCU" hanya jika sudah umum di HR doc — default "sesi dukungan mendalam").
8. Angka kuota = level kontrak ("sisa kuota program"), tidak bulanan — mencegah ekspektasi reset bulanan yang salah (doc: unused hangus, kontrak-level total).

---

## 6. Referensi komponen reusable (checklist implementasi)

| Kebutuhan | Reuse dari | Bentuk |
|---|---|---|
| Layout + sidebar menu baru | `layouts/app.blade.php:21-76` | Tambah entry `$navItems` admin + hr_admin |
| Icon menu | `perusahaan/partials/icon.blade.php` | icon name: 'sparkles' (katalog), 'heart' (assign), 'receipt' (subscription) |
| Form create + tabel satu halaman | `admin/perusahaans.blade.php` | Copy struktur |
| Inline edit harga per baris | `admin/markup-rules.blade.php:32-39` | Form tersembunyi + tombol form attr |
| Status chip | `admin/perusahaans.blade.php:70-74` | Aktif/Nonaktif |
| dash-card + section title | `perusahaan/dashboard.blade.php:27-40` (copy style block) | HR cards |
| Quota progress bar | `perusahaan/dashboard.blade.php:244-259` | used/total + threshold terra 80% |
| Alert strip tone | `perusahaan/dashboard.blade.php:92-100` | near-expiry |
| Hero band | `perusahaan/booking.blade.php:6-23` | Program & Subscription header |
| Empty state icon+card | `perusahaan/pending.blade.php:4-27` | HR empty state |
| Flash + errors | `layouts/app.blade.php:205-218` | Sudah otomatis via session |
| Format Rupiah | `number_format($x, 0, ',', '.')` | Helper inline |

Ponytail: tidak ada Blade component class reusable — semua view flat. Kalau halaman paket bertambah banyak (>3), baru ekstrak `x-dash-card` / `x-status-chip`; sekarang copy-paste sesuai kebiasaan codebase.
