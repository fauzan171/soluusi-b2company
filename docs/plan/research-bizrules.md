# Research — Business rules: pricing band & kontrak program B2Company

Date: 2026-11-23. Input: `docs/plan/PRODUCT-DOC-SUMMARY.md` (tabel harga §2,
S&K perusahaan §6 + §8 inkonsistensi), `docs/plan/research-arch.md` (skema
`wellbeing_packages` / `wellbeing_pricing_bands` / `company_wellbeing_programs`
/ `wellbeing_quota_ledger` — SUDAH DISEPUAKI, doc ini tidak mendesain ulang
skema, hanya aturan bisnis + enforcement-nya). Fokus: bagian doc yang
berdampak ke kode.

---

## 1. Survey flow pembayaran existing (file:line)

### 1.1 Vertical klinik/B2C — SATU-SATUNYA alur Midtrans yang ada

| Komponen | Lokasi | Catatan |
|---|---|---|
| Webhook utama | `app/Http/Controllers/MidtransController.php:25-176` (`callback`) | POST `api/midtrans-callback` (`routes/api.php:17`) |
| Signature verify | `MidtransController.php:34-40` | sha512(order_id+status_code+gross_amount+server_key), `hash_equals`, reject 403 sebelum proses |
| Amount match | `MidtransController.php:47` | `abort_unless(gross == session->amount, 422)` |
| Terminal-state guard | `MidtransController.php:51-67` | sesi canceled/failed/refunded tidak dihidupkan ulang oleh notifikasi susulan → flag `needs_ops_review` |
| Idempotent settle | `MidtransController.php:75-84` | settlement berulang → pastikan split ledger ada, return "Already processed" |
| Settle flow | `MidtransController.php:96-143` | `payment_status=verified`, `status=scheduled`, split ledger, gcal best-effort, mail |
| Refund | `MidtransController.php:157-163` | `payment_status=refunded` + `SplitSettlementService::reverse` |
| Expiry sweep | `MidtransController.php:266-274` (`updateExpiredPayments`) | `waiting` + `expired_at < now` → canceled |
| Status enum | `app/Models/ConsultationSession.php:20-24` | `waiting|verified|canceled|failed|refunded` (TEXT, sqlite-safe) |
| Snap charge | `app/Services/Klinik/CheckoutFlow.php:91-170` (`startPayment`) | voucher → slot re-check → `Snap::getSnapUrl` (`:150`) → reserve session atomik; order_number prefix `KB-` (`:112`); expiry 1 jam (`:126-129`) |
| Dummy gate | `CheckoutFlow.php:331-335` (`dummyEnabled`) | local / `midtrans.dummy_enabled` — Snap tanpa server key |
| Split settlement | `app/Services/Klinik/SplitSettlementService.php` (record/reverse) | ledger net→Soluusi / margin→klinik — KHUSUS klinik, tidak relevan untuk kontrak perusahaan |
| Voucher | `MidtransController.php:215-248` (`nominalWithVoucher`) | diskon/potongan/free_trial — per-transaksi, tidak cocok kontrak |
| Test contoh | `tests/Feature/MidtransWebhookTerminalTest.php`, `CheckoutFlowAtomicityTest.php`, `DummyPaymentGateTest.php` | pola test webhook+gate |

### 1.2 Vertical perusahaan (B2Company) — HARI INI TIDAK ADA PEMBAYARAN

- Kontrak = kolom flat di tenant: `perusahaans.pepm_rate` (Rp/employee/month,
  default 15.000) + `sessions_per_employee_month` (default 2)
  (`database/migrations/2026_08_13_000001_create_perusahaans_table.php:20-21`).
  Tidak ada invoice, tidak ada entity pembayaran, tidak ada Midtrans.
- Provisioning + aktivasi 100% manual superadmin:
  `app/Http/Controllers/Admin/ProvisioningController.php:113-134`
  (`createPerusahaan`, set `mou_at`, `is_active`) dan
  `app/Http/Controllers/Admin/PerusahaanActivationController.php:14-25`
  (`activate` → `PervormaService::evaluate('cap')` untuk komisi associate).
- Model `app/Models/Payment.php` = katalog metode pembayaran + admin fee
  (`adminFee()`), tidak dipakai alur apa pun yang hidup — jangan di-reuse.
- Kuota B2B existing = bulanan per karyawan (bukan kontrak-level):
  `packages.monthly_quota` (`2026_11_20_000001_create_mission_m1_packages_tables.php:14`),
  ledger `consultation_quota_ledger` (`2026_11_20_000002_...`, UNIQUE
  `perusahaan_session_id+event`), service `app/Services/QuotaService.php`
  (reserve `:107`, consume `:138`, forfeit `:144`, refund `:159`, release `:192`).
  Call-site: `BookingService.php:200`, `EmployeeController.php:511` (startChat),
  `ChatController.php:73,94` (consume), `SweepNoShowSessions.php:35` (forfeit),
  `QuotaAdminController.php:28` (refund HR).

### 1.3 Apa yang bisa dipakai ulang untuk billing kontrak B2Company

| Pola existing | Pakai ulang? | Cara |
|---|---|---|
| Snap charge + redirect | OPSIONAL v2 | `CheckoutFlow::startPayment` bentuknya terikat `CheckoutLink`+`ConsultationSession`; charge kontrak = nilai besar sekali bayar (harga × bulan) — invoice/transfer lebih realistis B2B Indonesia. v1: manual. |
| Webhook signature verify + amount match + terminal guard + idempotent settle | YA — sebagai POLA jika nanti charge via Midtrans | copy 4 guard `MidtransController.php:34-84` ke callback kontrak; order_number prefix baru (mis. `WB-`) + dispatch lookup by prefix |
| payment_status enum TEXT | YA | kontrak/invoice kontrak pakai enum TEXT sama gaya (`unpaid|paid|void`), sqlite-safe |
| Expiry sweep + scheduler | YA | pola `Console/Kernel.php:24,45` (command + `$schedule`) untuk renewal-reminder 30-day |
| Dummy payment gate | YA | `dummyEnabled()` pattern untuk test/local bayar kontrak |
| SplitSettlement / Voucher | TIDAK | klinik-only / per-transaksi |

**Keputusan pembayaran kontrak v1**: superadmin (role Finance di roadmap)
catat pembayaran manual di Billing & Contract (kolom `paid_at`, `amount`,
`method`, `order_number` bebas) — mengikuti kenyataan bahwa B2B dibayar via
invoice/transfer, dan repo hari ini juga 100% manual di vertical ini. Midtrans
charge untuk kontrak = backlog (opsi implementasi: prefix `WB-` + guard yang
dicontoh di atas). Ini selaras research-arch §5 keputusan #8 (admin UI dulu).

---

## 2. S&K 19 pasal → aturan enforceable di kode

Ground truth = PRODUCT-DOC-SUMMARY §6 (poin implementasi) + §1-§2. Aturan
yang TIDAK menyentuh kode (mis. pasal tentang kepatuhan mitra, force majeure)
tidak masuk tabel.

| # | Aturan (dari doc) | Enforcement point | Bentuk kode | Test scenario |
|---|---|---|---|---|
| R1 | Kuota kontrak-level TOTAL, unused **hangus**, tidak dipindahtuangkan | `WellbeingQuotaService::bucketBalance` + reserve gate | saldo dihitung HANYA untuk program `status=active` DAN `starts_at <= today <= ends_at` (research-arch §3.4: tanpa `period_ym`); tidak ada migrasi sisa kuota ke kontrak baru; refund tetap eksplisit (mirror `QuotaService::refund` + `QuotaAdminController` pattern) | (a) reserve sampai batas → booking ke-13 (kuota 12) abort 403; (b) `ends_at` lewat 1 hari → semua booking abort; (c) kontrak baru → saldo mulai dari snapshot, bukan sisa lama |
| R2 | Option A (MHCU) vs B (1-on-1) **mutually exclusive per kontrak** | validasi assign (controller/service create program) + routing booking | `deep_support_mode` NOT NULL untuk growth/impact (`'mhcu'|'one_on_one'`), validation `required|in:mhcu,one_on_one` hanya untuk paket berbayar; discovery/foundation → kolom di-set nilai default paket, form menonaktifkan pilihan. Routing: `consultation_type` sesi dikonversi ke bucket sesuai mode kontrak (chat→expert_chat; 1-on-1→deep_support); mode A → booking 1-on-1 reguler ditolak (semua 1-on-1 = sesi MHCU), mode B sebaliknya | (a) assign growth tanpa mode → validation error; (b) assign dengan nilai di luar enum → error; (c) kontrak mode A → booking one_on_one masuk bucket MHCU, bukan bucket sesi reguler; (d) tidak ada kontrak dengan dua mode aktif |
| R3 | Discovery: max **40 peserta** assessment | `AssessmentController::store` (`app/Http/Controllers/Perusahaan/AssessmentController.php:58`) | hitung distinct `employee_id` pada `perusahaan_assessments` program aktif; ≥ `assessment_max_participants` (snapshot 40) → abort 423/403 "kuota peserta assessment penuh". Karyawan yang sudah ikut boleh retake tanpa menambah count (distinct) | (a) 40 employee beda menyelesaikan → submit ke-41 abort; (b) employee #1 submit kedua kali → tetap lolos (distinct); (c) program Foundation (`max=null`) → tidak dibatasi |
| R4 | Discovery: **8 sesi psikolog untuk 8 karyawan terpilih + consent** (resolusi inkonsistensi §8.1) | booking gate untuk paket discovery | `deep_support_quota=8` snapshot; mekanisme terpilih: HR menandai karyawan via roster (flag `selected_for_deep_support` atau list di program — implementasi di tasks.yaml); employee tidak terpilih → booking 1-on-1 abort 403; consent gate existing dipakai (`EmployeeController.php:50` `consent_at` + `ProgramConsent` service + `program_consent_history` migration `2026_10_05_000002`) | (a) 9 karyawan terpilih → assign gagal / ke-9 tidak bisa booking; (b) karyawan tidak terpilih booking → 403; (c) karyawan terpilih TANPA consent → 403 (gate existing); (d) sesi ke-9 oleh karyawan terpilih → 403 kuota habis |
| R5 | Harga **exclude pajak** | UI pricing + label | `monthly_price` di `wellbeing_pricing_bands` = nilai exclude pajak (schema research-arch §3.2 sudah begitu); semua tampilan harga admin/HR append "+ pajak" (suffix konstanta view, BUKAN kolom tax — YAGNI, belum ada keputusan tarif PPN); kontrak/invoice mencatat total = harga × bulan, exclude pajak, catatan "+ pajak" | (a) view admin assign menampilkan "Rp 7.499.999/bln + pajak"; (b) invoice kontrak band 50-150 growth 6 bln = 7.499.999×6 tanpa tambahan; (c) snapshot `monthly_price` tersimpan di kontrak saat assign |
| R6 | **30-day notice** termination | service program + command scheduler | kolom `terminated_at`/`notice_requested_at` + `status`; admin aksi "ajukan terminasi" → validasi tanggal efektif ≥ today+30 hari (else 422); program TETAP aktif sampai tanggal efektif (kuota jalan); command harian (pola `Console/Kernel.php`) memindahkan `status active→ended` saat tanggal efektif tiba + kirim notif. Tanpa kode pemotongan refund (kuota memang hangus, R1) | (a) terminasi efektif +20 hari → 422; (b) +30 hari → accepted, program masih aktif 29 hari ke depan, booking masih jalan; (c) hari ke-30 → status ended, booking abort; (d) ops: termination sebelum ends_at vs renewal tidak bentrok |
| R7 | Add-on **dibayar terpisah** (semua paket) | TIDAK dimodelkan v1 | keputusan: add-on di luar entity kontrak (manual, atau kontrak add-on terpisah di fase lanjut). Alasan: doc hanya bilang "bayar terpisah", tanpa katalog add-on; S&K pasal max 40-kuota dsb tidak menyebut mekanisme; YAGNI | (a) test negatif: struktur kontrak tidak punya kolom add-on; (b) doc: dicatat backlog "add-on purchase entity" |
| R8 | Consent S&K: checkbox disabled sampai scroll full, "Daftar Sekarang" enable setelah check | Blade + JS view program-order/HR register | copy pola TERBUKTI `resources/views/auth/register-partner.blade.php:200-220` (progress bar scroll, `unlockAgreement()`, `check.disabled=false`, `submit.disabled=!check.checked`) + server-side record consent via `ProgramConsent::record` (`app/Services/ProgramConsent.php:10`) | (a) GET halaman → checkbox disabled; (b) POST consent tanpa record JS unlock (伪造 request) → server tetap validasi `terms=1` + record; (c) integration: check setelah scroll → submit enabled |
| R9 | "Scope di Program Order/Confirmation" | entity kontrak | snapshot `assessment_domains`, `webinar_quota`, `expert_chat_quota`, `deep_support_quota`, `deep_support_mode`, `monthly_price` DI-LOCK di `company_wellbeing_programs` saat assign (research-arch §3.3 + keputusan #4) — perubahan katalog tidak mengubah kontrak jalan | (a) ubah harga band setelah assign → invoice/kuota kontrak tidak berubah; (b) ubah `deep_support_quota` katalog → snapshot kontrak tetap |
| R10 | Threshold agregat HR "min 10 peserta per reporting group" | `AggregatePrivacy::safe()` (`app/Support/AggregatePrivacy.php:7-10`) — DISCREPANCY: kode = **n≥5**, doc = **10** | keputusan: parameterize `safe($counts, int $min = 5)`; report wellbeing program memanggil dengan `$min=10` (doc menang untuk fitur baru); legacy report tetap 5 (test `AggregatePrivacySuppressionTest` hijau) | (a) grup 7 peserta: legacy report tampil, wellbeing report suppressed "data belum cukup"; (b) grup 11 → keduanya tampil |
| R11 | Kuota no-show/late-cancel hangus (S&K kuota, konsisten research-a) | routing branch di `SweepNoShowSessions.php:35` + cancel flow | mirror M2: `WellbeingQuotaService::forfeit` append event `forfeited` (terminal, saldo tetap terpakai) — hanya bila sesi punya row reserved di ledger wellbeing (routing research-arch §4) | (a) sesi program wellbeing no_show → event forfeited di `wellbeing_quota_ledger`, saldo berkurang; (b) sesi legacy tanpa program → hanya `consultation_quota_ledger` (path lama); (c) tidak ada sesi yang menulis ke DUA ledger |

---

## 3. Band karyawan — keputusan

**Band ditentukan SAAT ASSIGN kontrak, dari input superadmin yang di-auto-suggest
dari `perusahaans.employee_count` (kolom sudah ada,
`2026_08_13_000001_create_perusahaans_table.php:22`), lalu di-LOCK sebagai
snapshot (`pricing_band_id` + `employee_band_size` di kontrak — research-arch
§3.3 keputusan #5).**

- Sumber angka: input admin (bukan `employees()->count()` live) karena (1)
  `employee_count` adalah angka deklaratif MO yang sudah dipakai `DashboardMetrics.php:45`,
  (2) roster bisa belum ter-import penuh saat assign — auto-count live akan
  salah arah (band terlalu rendah), (3) S&K bilang harga dikunci saat order.
- **Edge: karyawan bertambah melewati band mid-contract** → band TIDAK
  bergeser. S&K: "perubahan jumlah peserta bisa incur additional fees" →
  peserta tambahan di luar kuota ditangani sebagai **top-up add-on** (R7,
  dibayar terpisah, entity fase lanjut), bukan re-price kontrak jalan.
  Kontrak baru/renewal menyesuaikan band baru.
- Karyawan di bawah 50: doc tidak menetapkan harga band (<50) — superadmin
  input manual override / anggap band pertama dengan diskusi manual; v1:
  validasi `employee_band_size >= 50` kecuali `is_free` (discovery). Ditandai
  sebagai pertanyaan GATE ringan.

## 4. Upgrade path paket — keputusan

**v1: TANPA auto pro-rated upgrade. Upgrade = kontrak BARU setelah kontrak
berjalan selesai (atau diterminasi via 30-day notice), dengan band di-set
ulang.**

Alasan:
1. Doc TIDAK menyuruh pro-rate (matrix paket hanya membatasi fitur per paket;
   tidak ada pasal kredit sisa).
2. Kuota kontrak-level hangus (R1) → tidak ada "sisa nilai" yang bisa
   di-pro-rate; satu-satunya besaran pro-rate = harga, dan itu keputusan
   finansial yang harus jadi kebijakan owner dulu (bukan default kode).
3. Enforceable & sederhana: guard "1 program aktif per perusahaan"
   (app-level, pola comment M1 `2026_11_20_000001` — sqlite tanpa partial
   unique) otomatis mencegah upgrade overlap; assign paket baru ditolak
   selama masih ada program aktif.

Konsekuensi test: (a) assign kontrak kedua saat program pertama aktif →
422 "masih ada program aktif"; (b) setelah ended → assign baru sukses; (c)
Discovery (gratis, 14 hari) → langsung bisa lanjut Foundation.

ponytail: jika owner nanti minta prorated upgrade, tambahkan kolom
`upgraded_from_program_id` + service hitung sisa hari — upgrade path jelas,
tapi belum ada pasal S&K yang menjustifikasi.

---

## 5. Tabel aturan final (ringkas — rule → enforcement → test)

(Lengkap dengan skenario di §2; ini versi satu-baris untuk tasks.yaml.)

| Rule | Enforcement | Test inti |
|---|---|---|
| Kuota kontrak hangus, non-transferable | `WellbeingQuotaService` balance hanya saat program aktif | booking setelah ends_at → 403 |
| Option A/B exclusive per kontrak | validasi assign + routing bucket booking | assign tanpa mode → 422 |
| Discovery ≤40 peserta assessment | `AssessmentController::store` distinct-employee cap | submit #41 → abort |
| Discovery 8 sesi utk 8 terpilih + consent | booking gate flag terpilih + `consent_at` | employee tak terpilih → 403 |
| Harga exclude pajak | suffix "+ pajak" di view; snapshot harga di kontrak | invoice = harga×bulan |
| 30-day notice termination | validasi tanggal efektif + daily command move-to-ended | notice +20d → 422 |
| Add-on terpisah | TIDAK dimodelkan v1 (backlog) | — |
| S&K scroll-consent | copy pola `register-partner.blade.php:200-220` + `ProgramConsent::record` | checkbox disabled awal |
| Snapshot scope di order | kolom snapshot kontrak (R9) | ubah katalog ≠ ubah kontrak |
| Threshold agregat 10 | `AggregatePrivacy::safe($counts, $min)` default 5, wellbeing pass 10 | grup 7 → wellbeing suppressed |
| No-show hangus di program | `SweepNoShowSessions` branch → `WellbeingQuotaService::forfeit` | 1 sesi = 1 ledger saja |
| Band lock saat assign | snapshot `pricing_band_id`/`employee_band_size` | tambah karyawan ≠ ubah harga |
| 1 program aktif per perusahaan | app-level guard saat assign | assign ke-2 saat aktif → 422 |
| No pro-rated upgrade v1 | tidak ada kode upgrade; guard di atas | kontrak baru hanya setelah ended |

---

## 6. Hal yang perlu keputusan owner (GATE, non-blocking)

1. **Threshold 5 vs 10** (R10): default dipilih parameterize (5 legacy / 10
   wellbeing) — aman untuk 712 test, tapi konfirmasi apakah legacy B2B report
   ikut naik ke 10.
2. **Karyawan < 50**: validasi minimal 50 atau manual price — doc diam.
3. **Metode bayar kontrak v1** = pencatatan manual Finance (keputusan §1.3);
   konfirmasi kalau owner mau Snap sejak hari pertama (implementasi prefix
   `WB-` + guard webhook, ±1 hari kerja).
4. **Top-up/add-on entity** — backlog; hanya perlu kalau ada klien nyata
   melewati band mid-contract.
