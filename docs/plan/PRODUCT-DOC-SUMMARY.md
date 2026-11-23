# [All Wellbeing Product] — source-of-truth summary (fetched 2026-11-23)

Source: Google Doc "[All Wellbeing Product]" (link di chat, private). Ini
rangkuman lengkap buat semua agent — jangan fetch ulang, pakai file ini.

## 1. Empat paket Wellbeing B2Company

| | Discovery | Foundation | Growth | Impact |
|---|---|---|---|---|
| Durasi | 14 hari | 3 bulan | 6 bulan | 12 bulan |
| Harga | Gratis | per band | per band | per band |
| Assessment | 1 (WHO-5/DASS-21) | 1 domain (MH atau Nutrition) | 2 domain | 2 domain |
| Max peserta assessment | 40 | semua terdaftar | semua | semua |
| Personal result/recommendation | ✓ | ✓ | ✓ | ✓ |
| HR summary + action plan | ✓ | ✓ | ✓ | ✓ |
| Expert Chat | — (8 sesi psikolog utk 8 karyawan terpilih — inkonsistensi doc) | Terbatas, 1 expert | Terbatas, 2 expert | Terbatas, 2 expert |
| Deep Support | 8 sesi psikolog | — | **A:** 15 MHCU **atau B:** 15 sesi 1-on-1 (60m) — PILIH SALAH SATU | **A:** 30 MHCU **atau B:** 30 sesi 1-on-1 |
| Webinar | — | 1 | 3 | 6 |
| Quarterly review | — | — | — | ✓ (tiap 3 bulan) |
| Add-on | bayar terpisah (semua paket) | idem | idem | idem |

Kuota kontrak-level total (bukan bulanan), unused hangus, tak dipindahtuangkan.
Discovery: 8 sesi psikolog hanya untuk 8 karyawan terpilih + consent.

## 2. Tabel harga (Rp/bulan, per band karyawan)

| Band | Foundation | Growth | Impact | MHCU Growth | MHCU Impact |
|---|---:|---:|---:|---:|---:|
| 50–150 | 7.999.999 | 7.499.999 | 6.999.999 | 5 | 10 |
| 151–400 | 14.999.999 | 14.499.999 | 13.999.999 | 10 | 20 |
| 401–600 | 20.999.999 | 20.499.999 | 19.999.999 | 15 | 30 |
| 601–800 | 25.999.999 | 25.499.999 | 24.999.999 | 20 | 40 |
| 801–1.000 | 30.999.999 | 30.499.999 | 29.999.999 | 25 | 50 |

Pajak di luar harga. Discovery gratis. Harga exclude pajak.

## 3. Assessment library

DASS-21 (MH), WHO-5 (wellbeing), PSS-10 (stress), Big Five (kepribadian),
DISC (gaya perilaku), Nutrition Assessment, Ergonomic Assessment, MCMI-III
(klinis, indication-based, NO mass-test). Penempatan: Discovery=WHO-5/DASS-21;
Foundation=1 domain; Growth/Impact=MH+Nutrition. Semua adalah "wellbeing
assessment", bukan diagnosis.

## 4. Tiga dashboard terpisah

**Main Soluusi (internal)** — 8 menu: Overview, Company Management, Program
Management, Employee Management, Assessment Management, Expert & Consultation,
Reports & Insights, Billing & Contract. Fitur ops: program activation, bulk
employee invitation, automated reminder, expert assignment, quota monitoring,
report generator, renewal reminder, activity log. 6 role: Super Admin,
B2B Account Manager, Operations, Expert Coordinator, Finance, Expert.

**HR Dashboard** — 10 menu: Company Overview, Employee Engagement, Wellbeing
Assessment, Wellbeing Insights, Expert Utilization, Company Action Plan,
Program Progress, Reports, Program & Subscription, Company Profile.
Aggregate-only, threshold min **10 peserta per reporting group** di bawahnya
"data belum cukup". HR TIDAK lihat: hasil individual, isi chat, catatan
konsultan, jawaban individual.

**Employee Dashboard** — 8 menu: My Wellbeing, My Assessment, My Results,
My Recommendations, Expert Chat, My Consultations, My Wellbeing Activities,
Privacy & Consent.

## 5. Fitur tambahan (A–D)

A. Risk & Priority Map (area prioritas, bukan "karyawan high-risk").
B. Action Plan Tracker (status: completed/in progress/upcoming) — Growth+Impact.
C. Trend & Progress — Impact only, pre/post comparable, no causal claim.
D. Engagement Campaign — reminder, edukasi periodik, pengumuman.

## 6. S&K Perusahaan (19 pasal)

Poin implementasi: checkbox consent disabled sampai scroll full; "Daftar
Sekarang" enable setelah check; 30-day notice termination; kuota hangus;
harga exclude pajak; Scope di Program Order/Confirmation; aggregate threshold.

## 7. S&K Mitra Klinik (19 pasal)

Bagi hasil (max share klinik per harga konsultasi): 250–299k → 10%,
300–349k → 15%, 350–399k → 20%, ≥400k → 25%. Checkpoint 40 konsultasi.
3 skema: Associate Consultant, MHCU, Edukasi & Aktivasi. Konfidensial 2 tahun
post-termination. (Ini vertical klinik — SEPARATE dari misi perusahaan ini,
catat sebagai out-of-scope kecuali admin workflow bagi hasil perlu.)

## 8. Inkonsistensi doc (perlu keputusan owner, default dipilih yang paling spesifik)

1. Discovery: matrix Expert Chat "—" tapi component list "8 sesi psikolog
   utk 8 karyawan" → **dipilih: 8 sesi 1-on-1 utk 8 karyawan terpilih**
   (component list lebih detail, matrix "—" = Expert Chat reguler tidak ada).
2. Growth: "5–25 kuota MHCU" vs detail "15" → **15 per kontrak; band pricing
   tabel menang untuk angka per band (5/10/15/20/25)** — doc band table =
   MHCU per band, jadi 15 = contoh band 401–600. Gunakan band table.
3. Impact: "10–50" vs "30" → sama: gunakan band table (10/20/30/40/50).
   Detail "15"/"30" = contoh band tengah.
4. Doc bilang 10 menu HR dashboard di satu tempat, 11 di tempat lain →
   gunakan daftar 10 yang eksplisit.

## 9. Design principles (jaga di semua UX)

1. One package, one clear purpose.
2. HR sees organizational insights; employees own personal results.
3. Access all employees does not mean unlimited services.

## 10. Fitur "superadmin kirim paket"

Owner membaca dokumen sebagai: admin Soluusi mengelola katalog paket +
harga per band + assign/aktifasi program per perusahaan (lihat Main
Dashboard menu Program Management + Billing & Contract + program activation
ops feature). Ini core scope misi ini.
