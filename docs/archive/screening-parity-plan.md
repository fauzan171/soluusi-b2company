# Screening multi-domain: adopsi flow kiosk TVS ke Soluusi B2B

Sumber flow: `github.com/fauzan171/mental-screening-kiosk-tvs` (Next.js + Cloudflare Workers + D1).
Target: `soluusi-b2b-klinik`, segmen **perusahaan** dulu, **sekolah** mengikuti (konten diganti).

Status dokumen: analisis + rencana. Ditulis sebelum perubahan kode.

---

## 1. Apa yang sebenarnya berbeda

Dua sistem ini **bukan** duplikat. Yang sudah ada di Soluusi dan yang perlu diadopsi:

| Aspek | Soluusi sekarang | Kiosk TVS |
|---|---|---|
| Instrumen | WHO-5 saja (5 item, self-service) | 3 domain: Mental Health (PSS-10, Q5–Q13), Nutrition & Lifestyle (Q15–Q20), Ergonomic (Q21–Q34) |
| Pengisi | Karyawan isi sendiri di portal | **Ahli** yang men-tap; peserta menjawab lisan |
| Jumlah ahli | 1 konsultan per sesi booking | 3 ahli terpisah, satu per domain, PIN sendiri |
| Scoring | `sum(answers) * 4` → 0–100, 1 band | Per domain, reverse-score, band per domain, rekomendasi = **maksimum antar 3 domain** |
| Output | Tren skor + booking | Skor + warna + jumlah konsultasi + temuan/kebutuhan review |
| Rekap | Agregat bulanan + CSV | Insight keseluruhan, detail per karyawan, baseline, export 3 format |

**Temuan kunci:** 3 ahli di kiosk **sudah ada padanannya** di Soluusi —
`konsultan_type` booking Soluusi = `psikolog` / `nutrisionis` / `fisioterapis` / `karir`.
Peta domain kiosk → tipe konsultan Soluusi:

- `mental_health` → `psikolog`
- `nutrition_lifestyle` → `nutrisionis`
- `ergonomic` → `fisioterapis`

Jadi tidak perlu entitas "ahli" baru. **`perusahaan_sessions.consultant_id` sudah kolomnya.**
Yang belum ada: bank pertanyaan multi-domain, jawaban per domain, mesin skor, dan layar
asesmen yang diisi ahli.

## 2. Yang diadopsi, dan yang TIDAK

Diadopsi (inti nilai produk):
1. Bank pertanyaan per domain (data, bukan kode) + tipe skala.
2. Asesmen per domain per peserta (`perusahaan_assessments` sekarang hanya 1 baris WHO-5).
3. Mesin skor: normalisasi per domain, reverse-score, band, rekomendasi maksimum.
4. Layar asesmen yang diisi **ahli** sambil wawancara (write-as-you-go).
5. Rekap: agregat per domain + rekomendasi konsultasi per karyawan.

TIDAK diadopsi (kiosk-only, tak relevan di SaaS multi-tenant):
- State machine `IDLE → IDENTITY → HANDOFF_*` satu device — Soluusi multi-user, tiap
  orang punya akun. Handoff device tak ada artinya.
- PIN per asesmen — Soluusi sudah punya auth + role. PIN = gerbang perangkat fisik.
- Sesi aktif tunggal / reset 5 detik — artefak kiosk satu tablet.
- Cloudflare D1, Pages, Workers runtime — Soluusi Laravel + SQLite.
- Baseline CHK matching — butuh dokumen eksternal, fase lanjut.

## 3. Bentuk di Soluusi

Satu subsistem generik ber-`$vertical`, mengikuti pola yang sudah ada
(`ProgramAggregate::monthly($tenant, $vertical)`, `BookingService::book($vertical, …)`):

```
screening_questions   code, vertical, section, text, type, options, reverse, ord
screening_answers     assessment_id, question_code, value, other_text
perusahaan_assessments / sekolah_assessments  (diperluas)
```

Perluasan tabel assessment yang ada — jangan bikin tabel baru yang menyaingi:
- tambah `section` (mental_health|nutrition_lifestyle|ergonomic)
- tambah `status` (pending|in_progress|completed)
- tambah `conclusion`, `notes` (diisi ahli)
- tambah `consultant_id` (ahli yang mengisi)
- `unique(employee_id, section)` — satu asesmen per domain per orang, dapat direvisi

Backfill: baris WHO-5 lama dipetakan ke `section='mental_health'` (WHO-5 dan PSS-10
sama-sama instrumen stres), skor dinormalkan ke skala yang sama.

## 4. Mesin skor (satu kelas, dua pemakai)

`app/Services/Screening/ScoringService.php`

- Normalisasi tiap domain ke 0–100 supaya band bisa dibandingkan.
  - Mental Health: jumlah `freq0-4`, Q8/Q9/Q11/Q12 di-reverse → `raw/max*100`
  - Ergonomic: jumlah `pain4` + `scale1-10` → `raw/max*100`
  - Nutrition: bobot per item (alkohol/rokok/aktivitas/riwayat/alergi), reverse untuk
    aktivitas fisik → `raw/max*100`
- Band dari konfigurasi (bukan hardcode), default 4 tingkat: 0–24 / 25–49 / 74–74 / 75–100
  → consult 0 / 1 / 2 / 3. **Bisa diubah tanpa deploy.**
- **Rekomendasi = maksimum antar domain**, warna = tingkat terburuk. Bukan rata-rata —
  satu temuan terburuk yang menentukan kebutuhan konsultasi.
- Nilai mentah disimpan apa adanya; reverse hanya di mesin skor.

Self-check wajib: batas band (0, 24, 25, 49, 50, 74, 75, 100) + kasus reverse.

## 5. Alur di Soluusi (per segmen)

Perusahaan:
1. HR daftarkan karyawan (roster — sudah ada).
2. Konsultan (psikolog) buka sesi → layar asesmen domain `mental_health` → tap jawaban
   sambil wawancara → isi kesimpulan → simpan.
3. `nutrisionis` domain `nutrition_lifestyle`, `fisioterapis` domain `ergonomic` — sama.
4. Portal karyawan menampilkan hasil: skor 3 domain + rekomendasi jumlah konsultasi.
5. Dashboard HR: agregat per domain + sebaran band + siapa perlu tindak lanjut.

Sekolah: identik, hanya label (karyawan→siswa, HR→BK, departemen→kelas) dan portal BK.

## 6. Urutan kerja

1. Migrasi: perluas assessment + tabel pertanyaan/jawaban generik. Seed bank pertanyaan.
2. `ScoringService` + self-check.
3. Layar asesmen ahli (controller + view) per domain, write-as-you-go.
4. Portal karyawan: tampilkan 3 domain + rekomendasi.
5. Dashboard HR: agregat per domain + rekomendasi.
6. Sekolah: ulangi 3–5 dengan konten/label sekolah.
7. Export/report menyesuaikan 3 domain.

## 7. Peretasan yang sengaja dihindari

- Tidak menyalin kiosk apa adanya (state machine, PIN, D1) — 60% kiosk adalah kode
  satu-perangkat yang tidak berlaku di SaaS.
- Tidak menambah entitas "ahli" baru — pakai `consultant` yang sudah ada.
- Tidak menghapus WHO-5 lama — dipetakan, bukan dibuang.
- Tidak menambah dependency: bank pertanyaan = data di DB, bukan kode.