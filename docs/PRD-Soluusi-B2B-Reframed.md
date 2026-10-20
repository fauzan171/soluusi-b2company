# PRD + SDD Soluusi B2B Website Reframe

Versi: 2.0

Tanggal: August 7, 2026

Status: implementation-ready draft

Owner: Founder Soluusi

Primary use: source of truth untuk agent coding

Scope dokumen ini: website information architecture, content hierarchy, route structure, page breakdown, phase plan, implementation constraint, acceptance criteria, dan run sequence untuk pengerjaan landing page B2B Soluusi

---

## 0. Start Here

Dokumen ini bukan hanya PRD. Dokumen ini juga berfungsi sebagai SDD ringan untuk agent coding.

Kalau agent coding membaca dokumen ini, urutan kerjanya harus:

1. pahami objective bisnis
2. pahami struktur page baru
3. pahami phase implementation
4. pahami file/route yang harus diubah
5. pahami acceptance criteria
6. baru mulai coding

Agent coding tidak boleh:

- mengubah positioning bisnis sendiri
- mencampur ulang vertical menjadi satu landing page detail lagi
- menaruh copy spesifik Klinik di main landing page tanpa alasan jelas
- membuat struktur route baru di luar yang didefinisikan di dokumen ini tanpa kebutuhan teknis yang kuat

---

## 1. Summary

Soluusi B2B saat ini masih memakai satu landing page panjang yang mencampur 3 vertical bisnis:

- Klinik
- Sekolah
- Perusahaan

Struktur ini tidak lagi cocok dengan bentuk bisnis Soluusi yang sebenarnya. Setiap vertical punya problem, buyer, bukti, narasi, dan model penawaran yang berbeda. Karena itu, website harus dipecah menjadi:

- 1 main landing page untuk pengenalan Soluusi B2B
- 3 detail page vertical untuk Klinik, Sekolah, dan Perusahaan

Tujuan utama perubahan ini adalah membuat website lebih jelas untuk calon partner, lebih mudah dipakai tim sales, dan lebih aman dijadikan dasar coding agent untuk pengembangan selanjutnya.

Klinik tetap menjadi vertical prioritas untuk product implementation. Tetapi dari sisi website, ketiga vertical harus disusun rapi sejak sekarang.

---

## 2. Contacts

| Nama | Role | Tanggung jawab |
|---|---|---|
| Aisyah Humaerah | Founder / Product Owner | final approval positioning, offer, CTA, dan narasi bisnis |
| PM / Product | PRD owner | menjaga scope dan prioritas implementasi |
| Design | UI/UX | menerjemahkan structure jadi layout dan visual hierarchy |
| Engineering | Web implementation | route, blade, component, asset, navigation, CTA |
| Partnership team | HAP / SAP / CAP validation | memastikan copy sesuai cara jual di lapangan |

---

## 3. Background

### 3.1 Problem saat ini

Landing page B2B sekarang memuat terlalu banyak hal dalam satu halaman:

- pengenalan Soluusi B2B
- detail vertical Klinik
- detail vertical Sekolah
- detail vertical Perusahaan
- paket dan framing yang sangat vertical-specific

Dampaknya:

- user harus scroll terlalu panjang
- narasi bercampur
- CTA terlalu generik
- context switching tinggi
- tim sales sulit mengirim halaman yang benar-benar relevan ke prospect

### 3.2 Problem copy yang sudah terlihat

Ada beberapa pesan yang terlalu spesifik untuk satu vertical tetapi tampil di konteks umum.

Contoh paling jelas:

- `0 karyawan`
- `tanpa rekrut SDM tetap`

Pesan seperti ini valid untuk vertical Klinik, karena salah satu value proposition Klinik memang menambah layanan tanpa merekrut psikolog atau tenaga ahli tetap.

Tetapi pesan ini tidak cocok menjadi headline umum untuk Soluusi B2B secara keseluruhan, karena:

- Sekolah tidak bicara dalam bahasa `karyawan`
- Perusahaan justru memang berurusan dengan karyawan
- Main LP harus berbicara dalam bahasa payung, bukan bahasa satu vertical

### 3.3 Product knowledge yang dipakai

Dokumen ini dirumuskan dari sumber internal yang ada di project:

- `docs/01 - Soluusi HAP Handbook.pdf`
- `docs/02 - Soluusi HAP 90Day Success Plan.pdf`
- `docs/04 - Soluusi Partnership Playbook HPA.pdf`
- `docs/1. Holistic Employee Wellbeing Program - Soluusi For Company (1).pdf`
- `docs/School - Mental Health Program (Ind).pdf`
- `docs/Soluusi B2B Platform - PRD.md`
- `docs/Soluusi B2B - Klinik Vertical Spec.md`
- `docs/memory.md`

### 3.4 Product thesis yang harus tercermin

Soluusi B2B adalah payung untuk layanan wellbeing profesional yang melayani 3 jalur:

- For Health Facilities
- For School
- For Company

Dengan support talent pool multi-profesional:

- psikolog
- nutrisionis
- fisioterapis
- konsultan karir
- tenaga ahli pendukung lain

Jadi website harus menunjukkan 2 layer:

1. satu identitas B2B Soluusi
2. tiga vertical solution yang berdiri jelas

---

## 4. Objective

### 4.1 Business objective

Membuat website B2B yang:

- menjelaskan Soluusi B2B secara singkat di main landing page
- memisahkan detail narasi per vertical
- mempermudah tim partnership mengirim page yang relevan ke calon partner
- mengurangi kebingungan user
- menjadi fondasi aman untuk pengembangan coding berikutnya

### 4.2 Product objective

Menyusun ulang website menjadi struktur yang:

- modular
- scalable
- mudah dirawat
- mudah dipakai ulang untuk page per vertical
- tidak mengunci seluruh narasi dalam satu file landing page besar

### 4.3 Success criteria

- main LP hanya berisi pengenalan dan overview
- vertical detail dipisah per route
- message spesifik vertical pindah ke page yang benar
- CTA per vertical lebih fokus
- dokumen ini cukup jelas untuk dipakai agent coding tanpa menebak-nebak structure

---

## 5. Market Segments

### 5.1 Klinik / Fasilitas Kesehatan

Masalah utama:

- ingin menambah layanan seperti psikolog, nutrisionis, fisioterapis
- tidak ingin rekrut tenaga tetap terlalu cepat
- ingin menjaga brand klinik di depan pasien
- butuh model yang ringan secara operasional

Buyer utama:

- owner klinik
- direktur operasional
- manajer klinik
- HR untuk klinik korporat

### 5.2 Sekolah

Masalah utama:

- butuh wellbeing program untuk siswa, guru, dan orang tua
- belum punya support system profesional yang lengkap
- perlu edukasi, konseling, dan assesment yang lebih terstruktur

Buyer utama:

- kepala sekolah
- yayasan
- wakil bidang student support
- HR / people lead untuk sekolah besar

### 5.3 Perusahaan

Masalah utama:

- burnout
- disengagement
- turnover
- absenteeism
- kebutuhan support employee wellbeing yang lebih nyata

Buyer utama:

- HR manager
- people and culture lead
- CHRO
- business leader yang pegang workforce strategy

---

## 6. Value Proposition

### 6.1 Umbrella value proposition

Soluusi membantu organisasi menyediakan layanan wellbeing profesional tanpa harus membangun seluruh sistem layanan dari nol.

### 6.2 Value proposition per vertical

#### Klinik

Tambah layanan profesional tanpa harus langsung membangun tim tenaga ahli tetap sendiri.

#### Sekolah

Bangun dukungan wellbeing terstruktur untuk siswa, guru, dan orang tua dengan partner profesional.

#### Perusahaan

Jalankan employee wellbeing program yang relevan dengan strategi HR dan kondisi nyata karyawan.

---

## 7. Scope

### 7.1 In scope

Dokumen ini mengatur:

- struktur halaman marketing B2B
- pembagian narasi per vertical
- route baru
- aturan CTA
- prioritas phase implementasi
- acceptance criteria
- dependency teknis minimal

### 7.2 Out of scope

Dokumen ini tidak mengatur detail implementasi backend operational module seperti:

- white-label checkout engine
- schedule management logic
- consultant portal logic
- split payment logic
- CRM pipeline automation

Semua itu tetap mengacu ke PRD platform dan vertical spec yang sudah ada.

Dokumen ini hanya memastikan website marketing dan architecture-nya benar dulu.

---

## 8. Solution Overview

### 8.1 Struktur halaman target

Website harus dipecah menjadi:

- `/` → main landing page B2B
- `/klinik` → detail vertical Klinik
- `/sekolah` → detail vertical Sekolah
- `/perusahaan` → detail vertical Perusahaan

Optional future:

- `/kontak`
- `/tentang`
- `/mitra`

### 8.2 Prinsip struktur

Main landing page:

- pengenalan
- overview
- cross-vertical credibility
- CTA ke detail page

Detail page vertical:

- problem
- solution
- service structure
- proof
- CTA

---

## 9. Information Architecture

### 9.1 Main landing page `/`

Tujuan:

- menjelaskan siapa Soluusi B2B
- menunjukkan 3 vertical secara ringkas
- memberi jalan ke vertical detail

Main LP tidak boleh menjadi halaman tempat semua detail offer dimasukkan.

#### Section order yang wajib

1. Hero umum
2. Overview siapa yang dilayani
3. Talent pool / layanan profesional
4. Kenapa Soluusi berbeda
5. Vertical cards
6. Credibility / social proof
7. CTA diskusi

#### Section order yang dilarang

- detail package lengkap per vertical
- pricing table khusus Klinik di halaman utama
- proof spesifik vertical tanpa diberi label jelas

### 9.2 Detail page `/klinik`

Tujuan:

- menjelaskan solusi Klinik secara fokus
- jadi halaman kirim untuk prospect klinik

#### Section wajib

1. Hero Klinik
2. Problem klinik
3. Offer / skema kerja sama
4. How it works
5. Nilai bisnis untuk klinik
6. CTA diskusi

#### Message yang boleh dominan

- tambah layanan tanpa rekrut tenaga tetap
- white-label
- online dan offline
- layanan profesional atas nama klinik

#### Message yang aman dipakai di sini

- tanpa tambah SDM tetap
- tanpa rekrut psikolog full-time di awal
- aktifkan layanan baru dengan model lebih ringan

### 9.3 Detail page `/sekolah`

Tujuan:

- menjelaskan school wellbeing proposition dengan fokus

#### Section wajib

1. Hero Sekolah
2. Kenapa school wellbeing penting
3. Layanan untuk siswa, guru, orang tua
4. Data / insight
5. Struktur program / paket
6. CTA diskusi

### 9.4 Detail page `/perusahaan`

Tujuan:

- menjelaskan employee wellbeing proposition dengan bahasa HR dan bisnis

#### Section wajib

1. Hero Perusahaan
2. Masalah bisnis / workforce
3. Program / pilar layanan
4. Data / proof
5. Kaitan dengan HR strategy
6. CTA diskusi

---

## 10. Content Rules

### 10.1 Main LP copy rules

Main LP harus memakai bahasa payung.

Contoh framing yang benar:

- layanan wellbeing profesional untuk organisasi
- dukung Klinik, Sekolah, dan Perusahaan
- akses tenaga ahli profesional secara lebih fleksibel

Contoh framing yang jangan dijadikan headline utama:

- `0 karyawan`
- `tanpa rekrut psikolog` sebagai headline umum
- `margin klinik`
- `pasien`

Semua itu terlalu vertical-specific.

### 10.2 Klinik copy rules

Di halaman Klinik, copy boleh lebih tajam dan operasional.

Contoh framing yang benar:

- tambah layanan tanpa rekrut tenaga ahli tetap
- jaga relasi pasien tetap atas nama klinik
- white-label experience

### 10.3 Sekolah copy rules

Bahasa harus:

- suportif
- kredibel
- dekat ke isu siswa, guru, dan orang tua

### 10.4 Perusahaan copy rules

Bahasa harus:

- business-friendly
- dekat ke HR outcome
- tidak terlalu klinis

---

## 11. Functional Requirements

### FR-1 Main LP route

System harus menyediakan halaman `/` sebagai main landing page B2B.

### FR-2 Vertical routes

System harus menyediakan route:

- `/klinik`
- `/sekolah`
- `/perusahaan`

### FR-3 Navigation

Main navigation harus memberi akses jelas ke ketiga vertical.

### FR-4 CTA routing

CTA di main LP harus mengarah ke vertical detail, bukan hanya ke anchor di halaman yang sama.

### FR-5 Reusable structure

Agent coding harus membangun page dengan structure yang reusable. Hindari menaruh semua layout dan semua section dalam satu blade besar jika bisa dipisah ke partial/component.

### FR-6 Copy isolation

Copy spesifik vertical tidak boleh muncul dominan di page vertical lain atau di main LP tanpa konteks.

### FR-7 Current landing content migration

Konten yang ada sekarang harus dipetakan ke:

- tetap di main LP
- pindah ke `/klinik`
- pindah ke `/sekolah`
- pindah ke `/perusahaan`
- dihapus

### FR-8 Backward safety

Perubahan route dan page tidak boleh merusak:

- login flow
- klinik portal route
- checkout route
- consultant portal route

### FR-9 Non-breaking asset rendering

Page baru harus tetap memakai asset pipeline existing dan tidak merusak build CSS/JS yang sudah dibetulkan.

---

## 12. Non-Functional Requirements

### NFR-1 Maintainability

Page structure harus mudah dibaca agent lain dan developer berikutnya.

### NFR-2 Modularity

Section yang mirip sebaiknya reusable, tetapi jangan dipaksa abstrak berlebihan.

### NFR-3 Safety

Perubahan harus tetap aman dijalankan secara local tanpa butuh perubahan deployment flow yang besar.

### NFR-4 Performance

Jangan tambahkan dependency frontend baru kalau tidak dibutuhkan.

### NFR-5 Content clarity

User harus bisa paham:

- Soluusi ini apa
- vertical mana yang relevan
- next action apa yang harus diambil

---

## 13. Technical Constraints

### 13.1 Existing stack

Ikuti stack existing di project ini:

- Laravel Blade
- Vite
- Tailwind

### 13.2 Do not do

Agent coding tidak perlu:

- migrate framework
- tambahkan SPA
- ubah seluruh website jadi React/Next
- ubah route operational yang sudah jalan

### 13.3 Safe implementation preference

Urutan aman implementasi:

1. split content
2. add routes
3. add new blade files
4. connect navigation and CTA
5. verify asset build

---

## 14. Phase Plan

### Phase 0 — PRD and content alignment

Goal:

- finalkan structure dan content rule

Deliverables:

- PRD ini
- section mapping lama ke baru

Exit criteria:

- founder / PM setuju main LP jadi pengenalan saja
- founder / PM setuju detail page dipisah

### Phase 1 — Route and page split

Goal:

- pecah page menjadi 4 route utama

Deliverables:

- `/`
- `/klinik`
- `/sekolah`
- `/perusahaan`

Exit criteria:

- semua route render
- navigation dan CTA benar
- tidak ada broken route utama

### Phase 2 — Main LP rewrite

Goal:

- jadikan `/` sebagai halaman pengenalan

Deliverables:

- hero umum
- overview 3 vertical
- credibility
- CTA ke detail page

Exit criteria:

- konten yang terlalu detail sudah keluar dari main LP
- `0 karyawan` tidak lagi dipakai sebagai pesan utama halaman umum

### Phase 3 — Vertical detail pages

Goal:

- setiap vertical punya halaman yang fokus

Deliverables:

- page Klinik
- page Sekolah
- page Perusahaan

Exit criteria:

- tiap page punya problem, offer, proof, CTA sendiri
- copy antar vertical tidak tertukar

### Phase 4 — Content and UX refinement

Goal:

- rapikan hierarchy dan consistency

Deliverables:

- wording yang lebih matang
- CTA alignment
- visual consistency

Exit criteria:

- page mudah dipakai sales
- page mudah dipahami prospect

### Phase 5 — Optional product-website connection

Goal:

- sambungkan marketing pages dengan flow operational jika diperlukan

Deliverables:

- CTA ke diskusi partnership
- CTA ke relevant inquiry flow

Exit criteria:

- jalur dari page ke action jelas

---

## 15. Implementation Notes for Coding Agent

### 15.1 Minimum route contract

Agent coding harus menghasilkan route berikut:

- `landing`
- `landing.klinik`
- `landing.sekolah`
- `landing.perusahaan`

Nama route bisa menyesuaikan style existing project, tetapi harus konsisten dan jelas.

### 15.2 Minimum view contract

Agent coding minimal harus punya:

- satu blade untuk main LP
- satu blade untuk Klinik
- satu blade untuk Sekolah
- satu blade untuk Perusahaan

Boleh memakai partial/component bila perlu.

### 15.3 Suggested file structure

Contoh aman:

- `resources/views/landing.blade.php`
- `resources/views/verticals/klinik.blade.php`
- `resources/views/verticals/sekolah.blade.php`
- `resources/views/verticals/perusahaan.blade.php`

atau

- `resources/views/landing/index.blade.php`
- `resources/views/landing/klinik.blade.php`
- `resources/views/landing/sekolah.blade.php`
- `resources/views/landing/perusahaan.blade.php`

Yang penting: tidak mencampur semua detail lagi dalam satu file besar.

### 15.4 Current content migration rule

Agent coding harus memetakan ulang isi landing lama dengan 4 label:

- keep on main
- move to klinik
- move to sekolah
- move to perusahaan
- remove

### 15.5 Safe coding sequence

1. duplicate current landing safely
2. extract sections by vertical
3. create new pages
4. simplify main LP
5. rewire CTA
6. rebuild asset
7. smoke test routes

---

## 16. Acceptance Criteria

### AC-1 Main LP

- halaman `/` bisa dibuka
- menjelaskan Soluusi B2B secara umum
- tidak lagi menjadi detail page semua vertical

### AC-2 Vertical detail pages

- `/klinik`, `/sekolah`, `/perusahaan` semua bisa dibuka
- masing-masing punya headline, problem, offer, proof, CTA

### AC-3 Message separation

- pesan `0 karyawan` atau framing setara tidak menjadi pesan umum di main LP
- jika dipakai, ditempatkan di halaman Klinik atau diubah ke bentuk yang lebih umum

### AC-4 Navigation

- dari main LP user bisa masuk ke masing-masing vertical
- dari vertical user bisa kembali atau lanjut ke CTA diskusi

### AC-5 No regression

- route operational existing tetap jalan
- CSS tetap termuat
- asset build tidak rusak

### AC-6 Coding agent usability

Dokumen ini cukup jelas untuk dipakai agent coding tanpa harus menebak:

- page apa yang harus dibuat
- route apa yang harus ada
- message apa yang harus pindah
- apa yang dilarang

---

## 17. Run and Verification

### 17.1 Local run sequence

Setelah implementasi, jalankan:

```bash
php artisan serve --host=127.0.0.1 --port=8000
npm run build
```

Jika dev mode dipakai:

```bash
npm run dev
```

### 17.2 Manual verification checklist

1. buka `/`
2. cek CTA ke `/klinik`
3. cek CTA ke `/sekolah`
4. cek CTA ke `/perusahaan`
5. pastikan halaman utama lebih ringkas dari sebelumnya
6. pastikan CSS termuat
7. pastikan route lain tidak rusak

---

## 18. Open Items

Item berikut belum memblok implementasi struktur page, tapi perlu keputusan untuk copy final:

1. headline final main LP
2. wording final pengganti `0 karyawan`
3. CTA final:
   - `Diskusi Kemitraan`
   - `Jadwalkan Diskusi`
   - `Lihat Solusi untuk Klinik`
4. apakah akan ada form terpisah per vertical atau satu form umum

Fallback aman untuk agent coding:

- pakai satu CTA umum `Diskusi Kemitraan`
- pakai satu form umum dulu
- bedakan source vertical lewat route asal

---

## 19. Recommended Next Task After This PRD

Setelah dokumen ini dianggap fix, task coding paling aman adalah:

1. split route dan blade untuk 4 page utama
2. simplifikasi main LP
3. pindahkan konten vertical ke page masing-masing
4. verifikasi CTA dan asset

Jangan mulai dari redesign visual total. Mulai dari pemisahan structure dulu.

---

## 20. Short Decision Summary

- Main LP = pengenalan saja
- Detail vertical = dipisah
- Klinik specific copy tidak boleh mendominasi main LP
- `0 karyawan` tidak aman sebagai headline universal
- Dokumen ini adalah source untuk agent coding
- Implementasi harus non-breaking dan runnable

