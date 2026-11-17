# PRD — Soluusi B2B Platform (Current State)

> Versi: 3.0 (rebased ke kode)
> Tanggal: 16 Nov 2026
> Status: **current-state** — mendeskripsikan sistem yang SUDAH ADA, bukan aspirasi
> Sumber kebenaran: branch `goal/production-foundation` @ `3f23cfb` (PR #3, menunggu merge/deploy)
> Menggantikan: `Soluusi B2B Platform - PRD.md` v1.2 (5 Agu 2026) — dokumen itu tetap north-star bisnis, tapi 16 klaim di dalamnya sudah usang vs kode (lihat §14)

---

## 0. Start Here

Dokumen ini PRD **faktual**: apa yang sistem kerjakan hari ini, dari kode. Kalau ada konflik antara dokumen lama dan dokumen ini — dokumen ini menang untuk "apa yang ada", dokumen lama menang untuk "kenapa bisnisnya begini".

Agent coding yang baca ini: urutannya pahami §1-§3 (produk, role, vertikal), §4-§8 (flow + aturan per vertikal), §9 (uang), §10 (AI + privasi), §11 (ops/admin), §12 (teknis), §13 (batas eksplisit), §14 (dokumen mana yang usang).

---

## 1. Identitas Produk

**Soluusi B2B** = platform wellbeing on-demand untuk organisasi. Tesis: organisasi butuh layanan mental-health/wellbeing profesional tanpa merekrut SDM tetap. Soluusi men-supply talent pool multi-profesional (psikolog, nutrisionis, fisioterapis, konsultan karir) ke tiga vertikal:

| Vertikal | Program | Buyer | End user |
|---|---|---|---|
| Klinik | HAP (Healthcare/facility Partnership) | Owner/direktur/HR klinik | Pasien klinik (guest, tanpa akun) |
| Sekolah | SAP (School Partnership) | Yayasan/kepsek/BK | Siswa (self-register, consent ortu) |
| Perusahaan | CAP (Company Partnership) | HR/P&C/CHRO | Karyawan (roster/self-register) |

Konteks: B2C soluusi.com sudah live terpisah (prod lama Hostinger, jangan diganggu). Repo ini standalone: `github.com/fauzan171/soluusib2b`. Situs marketing = 4 route: `/` (landing utama) + `/klinik`, `/sekolah`, `/perusahaan` (detail per vertikal).

Scale klaim bisnis (dari playbook, belum terverifikasi sistem): 400+ konsultan, 28 provinsi, 300+ psikolog berlisensi.

---

## 2. Persona & Role

Sistem punya 8 role efektif (tabel `roles`, middleware `role:` string-match, plus `klinik.active`):

| Role | Scope | Bisa apa (ringkas) |
|---|---|---|
| `superadmin` (role name `admin`) | Global | Semua dashboard, CRUD perusahaan/paket/markup, approve klinik, settle split, verify sesi manual, pervorma payout, **view-as** karyawan (audit), assign konsultan ke pool tenant |
| `klinik_admin` | 1 klinik | Dashboard agregat, kelola konsultan/jadwal, generate checkout link, markup (superadmin juga bisa akses dashboard klinik) |
| `associate` | 1+ klinik referral | Dashboard pervorma, request payout |
| `hr_admin` | 1 perusahaan | Dashboard AGREGAT, roster karyawan, review & approve request konsultasi, export CSV, AI insight agregat |
| `employee` | 1 perusahaan | WHO-5 assessment, booking/reschedule/cancel, request konsultasi, chat sesi, rating, saran AI pasca-sesi |
| `consultant` | Pool lintas vertikal | Jadwal, sesi list, check-in/mark-done (klinik), schedule langsung sesi perusahaan, slot proposal, chat (perusahaan), summary AI sesi |
| `bk` | 1 sekolah | Dashboard agregat, roster siswa, refer siswa (wajib consent ortu), export, AI insight |
| `student` | 1 sekolah | WHO-5, booking (psikolog/karir), reschedule/cancel. TANPA chat |

Prinsip privasi struktural: HR/BK tidak pernah melihat isi sesi; dashboard mereka agregat-only, grup <5 kontributor disuppress. `rating` dan `employee_id` disembunyikan dari HR.

---

## 3. Model Tenant & Auth

- Multi-tenant per vertikal: `kliniks` / `perusahaans` / `sekolahs`. Row tenant punya `brand`, `domain` (klinik), `ai_enabled` (bool, default false).
- Isolasi: `TenantScope` global scope di 6 model tenant-scoped (PerusahaanSession/Employee/Assessment + mirror Sekolah). Aktif HANYA saat auth + context ter-resolve; inert di console/queue/guest. Sengaja TIDAK di Schedule/CheckoutLink (ponytail).
- Auth: single guard `web`, password + Google OAuth (Socialite, token di `social_accounts.provider_token`), password reset broker bawaan (throttle 5/m). View-as superadmin = session-scoped, read-only, ter-audit.

---

## 4. Vertikal Klinik (HAP) — Checkout-Guest + Ledger Split

### 4.1 Karakter
Pasien TIDAK punya akun (Model C). Admin klinik generate checkout link → pasien bayar via Midtrans Snap → sesi auto-terjadwal → dual-confirm → settle. White-label: brand Soluusi disembunyikan dari pasien.

### 4.2 Flow
1. Admin klinik buat `checkout_links` (token 48-char, snapshot `net_price`/`markup_amount`/`final_price`, `expires_at` 24 jam, `used_at`). Jalur alternatif tanpa link: pasien browse klinik via `/booking/klinik/{uuid}` lalu bayar langsung (white-label clinic page).
2. Pasien buka `/checkout/{token}` → isi data transaksional → `/pay` (voucher opsional, math di `CheckoutFlow`).
3. `reserveSession`: DB transaction + `lockForUpdate` jadwal + re-check slot → `claimLink` (conditional update atomik, 1 link = 1 sesi) → Snap URL → mail instruksi bayar.
4. Webhook `api/midtrans-callback`: signature verify → gross match → guard terminal-session → `SplitSettlementService::record` (net→Soluusi, margin→klinik, idempotent) → gcal best-effort → mail lunas. Endpoint lain: `api/midtrans-cancel` (cancel dari dashboard Midtrans) dan `api/midtrans/schedule-availability` (cek slot). Voucher mengurangi NET Soluusi; margin klinik tetap.
5. Sesi: konsultan check-in → `markDone` → pasien confirm via status page → `tryVerify` set `completion_verified_at` + trigger HAP pervorma. Fallback: sweep hourly → `needs_ops_review` >24 jam tanpa confirm → ops verify manual.
6. Refund → `SplitSettlementService::reverse`.

### 4.3 HAP pervorma (commission anti-fraud)
3 stage per klinik, window 90 hari: 5 sesi verified → Rp250K; 25 → Rp250K; 40 → Rp500K. Idempotent (firstOrCreate). Associate request payout → admin tandai paid manual.

### 4.4 Batas
- Xendit: config-only, TIDAK ada kode.
- Gateway-level split (Midtrans Connect/Xendit Marketplace): flag `SPLIT_ON` default false, belum implement — settle margin manual oleh admin.
- Payment palsu (dummy) hanya di non-prod: dua gate — middleware `notprod` (APP_ENV check) + flag `midtrans.dummy_enabled`.

---

## 5. Vertikal Perusahaan (CAP) — Full Loop + Chat + AI Summary

Vertikal paling lengkap. Loop: roster → assessment → request/booking → HR review → slot → chat sesi → AI summary → purge.

### 5.1 Booking & scheduling

Onboarding karyawan: HR input roster manual atau undang via signed-URL `/daftar/{type}` (self-register, token undangan roster).
- `BookingService`: pool konsultan (`ConsultantPool`), overlap check, kuota per karyawan/bulan (`sessions_per_employee_month`), claim table, cancel cutoff 2 jam.
- **Request tanpa slot**: karyawan kirim request (dengan `request_notes`) → masuk HR review queue (encoded: `status=scheduled + scheduled_at NULL + consultant_id NULL`) → HR approve (+opsi override konsultan + reason, audit) / reject (kuota balik).
- **Slot proposal**: konsultan propose 2-3 slot (`session_slot_proposals`) → karyawan confirm 1 → auto-book (`lockForUpdate` + overlap re-check).
- ADR 001: `schedule_version` max 3 total per sesi (1 awal + 2 reschedule); ke-4 → 422.
- **Booking UI (17 Nov 2026)**: halaman booking karyawan kini pola kartu — hero gradient + badge kuota, grid kartu konsultan pool (avatar inisial, kategori, filter `?q`/`?category`), panel slot sticky per konsultan terpilih (`?consultant_id=`, dikelompokkan per tanggal, radio `schedule_id`). Deep-link dari katalog konsultan membawa `consultant_id`. Tanpa JS framework (GET-param only). Spec: `docs/tasks/open/desain.md`.

### 5.2 Chat sesi
- Room = `perusahaan_sessions` row. Polling 5 detik (backoff 10s/30s), incremental `?after=`, tanpa websocket.
- Anonim: peer dilabeli "Konsultan"/"Karyawan".
- Gate kirim: status `scheduled` + konsultan assigned + `scheduled_at` terisi. Start = konsultan set `checked_in_at` (via `consultantStart` di chat, atau `checkIn` di portal konsultan klinik); end (konsultan atau karyawan) → `done`. Guard: sesi cancelled tidak bisa di-end (422).
- Karyawan bisa mulai chat dari picker konsultan (`/employee/consultants` → start-chat).
- Notif mail untuk pesan pertama.

### 5.3 Sweep lifecycle (`sessions:cancel-expired`, hourly)
- Scheduled tanpa check-in >24 jam → auto-cancel, kuota balik.
- Pending HR review >72 jam → auto-cancel.
- Zombie checked-in >4 jam → auto-done.
- Cleanup booking_claims yatim + reject proposal pending.

### 5.4 AI
- **Agregat (HR)**: `AiInsightService` — konteks agregat + catatan konsultan ter-redaksi → insight bulanan, cache per `input_hash`, lease 3 menit. Fallback rule-based `InsightEngine` saat AI off.
- **Per-sesi**: `GenerateSessionSummary` job saat sesi end — transkrip ter-redaksi (cap 8000 char) → `{summary, client_advice}`. Karyawan lihat `client_advice` di dashboard ("Saran dari sesi kamu"); konsultan lihat `summary`. HR TIDAK lihat per-sesi (keputusan user: agregat only). Idempotent via `input_hash`; gagal AI tidak menggagalkan end.
- **Purge**: `chat:purge-ended` harian — `chat_messages` sesi done ≥24 jam di-hard-delete (chunk 200). Row sesi & `session_summaries` TETAP (dashboard butuh).

### 5.5 Batas
- `request_notes` ditulis tapi belum ada UI konsultan yang membacanya (half-implemented).
- Tidak ada notifikasi ke konsultan saat slot proposal di-confirm (ponytail).
- Booking CAP sudah slot-nyata + anti double-book (claim table) — klaim lama "bisa double-book" sudah usang.

---

## 6. Vertikal Sekolah (SAP) — Subset CAP

- BK roster siswa + **refer** (booking langsung, origin `bk_referral`, WAJIB `parent_consent`).
- Siswa: WHO-5, booking (psikolog/karir saja), reschedule/cancel (cutoff 24 jam).
- Dashboard BK agregat + AI insight agregat (gate sama).
- **Tidak ada chat, tidak ada AI per-sesi, tidak ada request-tanpa-slot.**

### Batas
- Consent ortu: diterima di form, jalur pencatatan belum rapi (audit lama).
- Instrumen skrining: hanya WHO-5. DASS-21/MMCI/MMPI/DISC = north-star, belum ada (lisensi MHCU masih NEEDS FOUNDER).

---

## 7. Screening

`screening_questions` (unik per `vertical`+`code`) + `screening_answers` polimorfik. WHO-5 live di CAP & SAP. Skor → distribusi risiko agregat (≥75 sehat / 50-74 perhatian / <50 berisiko) untuk konteks AI insight.

---

## 8. Admin Ops (superadmin)

Akuisisi mitra dimulai publik: calon partner daftar via `/daftar-mitra` (throttle 5/m) → masuk approval queue → superadmin approve (klinik) / aktivasi (perusahaan, sekolah).

Semua di-gate `role:admin`, setiap aksi ter-audit di `admin_actions`:
- Overview tenant + referral; approve klinik; aktivasi perusahaan/sekolah; CRUD perusahaan, paket klinik, markup rules.
- Settle split manual; verify sesi manual (fallback dual-confirm); pervorma payout.
- Consultant assignment ke pool tenant.
- View-as karyawan (read-only, audit start/stop).
- `/progress` halaman status presentasi (gated).

### 8.1 Roadmap: Superadmin Control Center (approved 16 Nov 2026)

Arah: superadmin jadi pusat kendali penuh lintas vertikal. 3 batch, urutan eksekusi A → B → C (detail teknis di `task.md` branch `goal/superadmin-control-center`):

**Batch A — Fondasi control center** (additive, blast radius kecil):
1. Tenant management 3 vertikal: list/detail/edit/activate/**suspend** untuk klinik+perusahaan+sekolah (sekolah dari nol), search/filter. Suspend = blokir login admin tenant + alasan wajib + audit.
2. Provisioning wizard 1 pintu: create tenant 3 tipe + akun admin tenant (transaksi DB) + assign konsultan pool opsional. Jalur `/daftar-mitra` tetap → approval queue.
3. Tenant switcher: navbar dropdown admin (pengganti URL param `?{tenant}_id=`).
4. Audit log viewer: `admin_actions` + filter; payload di-mask (PII rule).
5. System health: failed jobs (retry/delete), queue depth, last-run scheduler.
6. Ops work queue terpadu: sesi `needs_ops_review` + approval mitra + pervorma payout due + aktivasi pending → satu halaman, filter per tipe, link ke aksi existing (tanpa duplikasi write path).

**Batch B — Impersonation penuh** (guardrail healthcare):
- View-as SEMUA role: HR, BK, klinik admin, konsultan, siswa, karyawan. Route impersonasi `role:admin` only; blok impersonate sesama admin.
- Guardrail: banner persisten, **read-only default via middleware** (tutup 2 lubang POST existing), expiry 30 menit, audit start/stop + payload.
- KlinikContext/SekolahContext belajar view-as (paritas dengan PerusahaanContext); grup route `consultant`/`associate` dibuka untuk admin.
- Act-on-behalf (aksi tulis atas nama tenant user): out-of-scope sengaja — aksi admin tetap lewat route admin sendiri.

**Batch C — Associate 3 vertikal (HAP/SAP/CAP)**:
- `associate_id` di `perusahaans` + `sekolahs`; tabel pervorma mirror per vertikal (`perusahaan_pervormas`, `sekolah_pervormas` — konsisten pattern tabel-per-vertikal codebase).
- `PervormaService` generalisasi, stage dari config: HAP 5/25/40 = Rp250K/250K/500K; SAP Rp300K/400K/800K; CAP aktivasi/50/150 = Rp600K/800K/1.6M.
- Superadmin assign associate ke tenant 3 vertikal; dashboard associate multi-vertikal; payout admin per vertikal.

---

## 9. Uang — Apa yang Nyata vs Stub

| Komponen | Status |
|---|---|
| Midtrans Snap checkout + webhook signature verify | **Implemented** |
| Voucher (diskon/potongan/free_trial) | Implemented (mengurangi net Soluusi) |
| Markup klinik dengan cap per price range | Implemented (`markup_rules`, config caps) |
| Ledger split lokal `split_settlements` + reverse refund | Implemented |
| Settle margin ke klinik | Manual oleh admin |
| HAP pervorma 3-stage | Implemented |
| Xendit | **Stub config-only** |
| Gateway-level split (Midtrans Connect / Xendit Marketplace) | **Belum implement** (`SPLIT_ON=false`) |
| PEPM invoicing (CAP) / subscription & MHCU flat (SAP) | **Belum ada di kode** — billing B2B masih di luar sistem |

Harga referensi bisnis (dari playbook/spec, indikatif, parameterize): net price psikolog Rp250-350K, fee konsultan Rp120-150K/sesi, PEPM Rp80-150K/karyawan/bulan, sekolah Rp15-35K/siswa/tahun.

---

## 10. AI, Privasi, Retensi

- Provider seam: `App\Contracts\AIService`, `config/ai.php` (`null` default → `NullAIService`; `tokengateway` → glm-5.3-flash via `AI_INSIGHT_*` env). Per-tenant gate `ai_enabled` dicek PERTAMA.
- Redaksi PII sebelum ke LLM: email, telepon, nama ber-honorific (Pak/Bu/…). Batas diketahui: nama tanpa honorific bisa lolos (ponytail, upgrade path: NER/master-data).
- Suppression: grup agregat <5 kontributor → null.
- Retensi chat CAP: transkrip hidup 24 jam pasca-end, lalu hard-delete. Turunan ter-redaksi (`session_summaries`) persist.
- Data sensitif: tidak ada PII di log/test/commit.

---

## 11. Teknologi & Operasi

- Laravel 12, PHP 8.2+ (dev 8.5.4), Blade + Tailwind, vanilla JS (chat polling).
- DB: SQLite dev/test, MySQL prod. 131 migrasi.
- Queue: `database` (sync di test). Cache: database. Session: database. Broadcast: log (tidak dipakai).
- Scheduler (`schedule:run` wajib aktif):
  - every 5 min: sweep expired payment
  - hourly: `sessions:cancel-expired` (auto-cancel + zombie + proposal cleanup)
  - hourly: dual-confirm ops-review sweep (sesi klinik >24 jam tanpa confirm → `needs_ops_review`)
  - daily: `chat:purge-ended`
- Rate limit: login 10/m, reset 5/m, booking/cancel/reschedule 10/m, chat send 60/m, admin assign 20/m (22 titik).
- Testing: 222 tests / 852 assertions / 0 failures (43 Feature files + unit). Route cache + view cache OK.
- Keamanan aktif: policies sesi, `$fillable` eksplisit (50 model), tenant scope, throttle, webhook verify, error pages brand-neutral, audit trail admin.

---

## 12. Status Delivery

- Branch `goal/production-foundation` @ `3f23cfb`, PR #3 open (6 commit terbaru: AI seam, UI polish, CheckoutFlow extract, chat fixes, session summary, purge). PR #1 & #2 sudah merged.
- Deploy blocker (eksternal): merge PR #3 → `php artisan migrate` (2 migrasi baru) → env AI dengan **key Tokengateway yang SUDAH di-rotate** (key lama bocor di chat) → set `ai_enabled=1` per tenant → pastikan cron `schedule:run` jalan.

---

## 13. Batas Eksplisit (jangan diasumsikan ada)

1. Xendit & gateway-level split: config/stub saja.
2. Billing B2B (PEPM invoice, subscription sekolah, MHCU flat fee): belum ter-automasi.
3. Google Calendar: half-wired — hanya dipanggil di 1 titik (settlement klinik); tidak ada untuk CAP/SAP.
4. Realtime: polling, bukan websocket.
5. CRM pipeline associate (M1), cross-sell tracker (M9), voucher campaign engine (M7): north-star, belum di kode.
6. Chat & AI per-sesi: CAP only.
7. Roles flat (string), tidak ada permission granular.
8. Prod lama B2C Hostinger: terpisah, jangan disentuh.

---

## 14. Peta Dokumen — Mana yang Masih Berlaku

| Dokumen | Status | Pakai untuk |
|---|---|---|
| **Dokumen ini** | current-state v3.0 | Kebenaran teknis "apa yang ada" |
| Master Playbook | VALID | GTM, pricing indikatif, komisi, persona buyer |
| PRD-Soluusi-B2B-Reframed (v2.0) | VALID | Marketing site structure (4 route) — sudah implemented |
| Vertical Specs (Klinik/Sekolah/Perusahaan) | PARTIAL | Model bisnis & pricing; flow detail banyak yang usang |
| PLAN.md | VALID | Production-foundation plan (G1-G30), sebagian besar done |
| NORTH-STAR-COVERAGE.md | VALID | Gap analysis vs north star |
| Platform PRD v1.2 (Agu) | PARTIAL→SUPERSEDED | North-star bisnis; stack L9→L12, repo merge→standalone, MVP Klinik-first→CAP/SAP-mature sudah usang |
| memory.md (Agu) | STALE | Snapshot pre-development; jangan dijadikan acuan status |
| mom-client-updates.md | STALE | Draft kickoff, klaim banyak yang sudah tidak akurat |

Kontras kunci yang sudah berubah sejak dokumen lama: Laravel 9→12; repo standalone; employee/student punya akun (self-register) walau pasien klinik tetap guest; chat+AI summary+purge baru ada (batch 6); split sudah ledger lokal; Midtrans webhook verify sudah aktif.
