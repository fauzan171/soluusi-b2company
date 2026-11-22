# MASTER-PLAN — Soluusi B2B Klinik: Package Quota, Multi-Company Consultant, Consultation Types, Multi-Assessment, Session Dashboard

Status: FROZEN (Phase 2 complete — research A-F masuk semua; F ditulis mandor karena agent mati 2x oleh API 503)
Base: `feat/mission-package-quota` @ `80ac781` (= brief tip `65e3e6e` + recon). Tests: 66/66.
Inputs: `recon-SUMMARY.md`, `research-a..f-*.md`.

---

## 0. Keputusan arsitektur (dengan alasan)

| # | Keputusan | Alasan |
|---|---|---|
| K1 | Naming ikut codebase: `perusahaan_*` (bukan `company_*`) | recon-R1: seluruh domain pakai Indonesia naming; mencampur = kebingungan migrasi+model |
| K2 | Quota = **ledger append-only** `consultation_quota_ledger` (reserved/used/forfeited/refunded/released) + unique constraint, BUKAN counter | research-A (Stripe pattern); audit + hangus+refund natural; sqlite-safe |
| K3 | Periode kuota = **calendar month** (`period_ym` "YYYY-MM", timezone Asia/Jakarta) | research-A: HR report = GROUP BY periode |
| K4 | Slot = **computed-on-read** dari workhour template — TANPA materialisasi slot rows, TANPA rolling job | research-B (Cal.com pattern); skala 40 konsultan = ms-level |
| K5 | Anti-double-book = overlap check half-open dalam `DB::transaction` + `BEGIN IMMEDIATE` (sqlite single-writer) + unique(consultant_id, started_at) sebagai guard tambahan | research-B |
| K6 | Assignment konsultan = extend `consultant_pools` → tambah kolom kontrak (contract_start/end, status) DARIPADA tabel baru `consultant_company` | recon-R1: `consultant_pools` sudah hub live consultant↔tenant; duplikat tabel = dua sumber kebenaran |
| K7 | Workhour = 2 lapis: `consultant_work_hours` (template mingguan per assignment `consultant_pool_id`) + `consultant_availability_exceptions` (per consultant, global semua perusahaan) | default desain mission, divalidasi research-B (override = 1 tabel, libur = NULL hours) |
| K8 | Lock request konsultan = feature flag **per perusahaan** `perusahaans.consultant_request_enabled` default OFF, guard **server-side** di controller + tombol hidden | catatan meeting; recon-R2: pola guard harus eksplisit |
| K9 | Tipe konsultasi = kolom baru `consultation_type` (online_meeting/chat/one_on_one) di `perusahaan_sessions` — TERPISAH dari `konsultan_type` (jenis profesional) | recon-R1: `konsultan_type` = psikolog/nutrisionis; overloading = migrasi data berbahaya |
| K10 | Meeting link: pertahankan arsitektur existing (`link_meeting` + GoogleCalendarService), tambah endpoint override manual per sesi; NO Zoom v1 | research-E |
| K11 | Chat: **tetap polling** (5s, `?after_id=`, adaptive 3s/10s) + field-encrypt `chat_messages.body` + purge 90 hari Prunable. NO Reverb/paid service v1 | research-D: php -S 6 worker math; PDP burden |
| K12 | Assessment engine = **extend mesin screening existing** (`screening_questions`/`screening_answers` + ScoringService) jadi engine definition-driven; WHO-5 diport jadi definition + data; instrumen baru = seeder | recon-R4: setengah engine sudah ada; buat baru = duplikasi |
| K13 | Instrumen tambahan: PHQ-9 + GAD-7 + CBI (semua free/public domain, tervalidasi Indonesia). MBI NO-GO (paid), PSS-10 skip (permission-required) | research-C |
| K14 | Catatan/hasil/review: `consultation_notes` (encrypted cast, konsultan-only), `consultation_results` (ringkasan, employee+consultant boleh lihat), `consultation_reviews` (rating 1-5 + komentar, hanya setelah done) | mission default + research-F |
| K16 | Privacy (research-F): encrypted cast di notes+chat-body; **custom `audit_logs` table minimal** (bukan spatie — minim dependency); n≥5 dipertahankan; retensi notes = permanent, chat = purge 90 hari Prunable; chat self-host polling (konsisten PDP cross-border avoidance) | research-F |
| K15 | Semua route baru wajib policy/gate eksplisit; buat 1 trait/helper guard tenant konsisten untuk menggantikan ad-hoc abort_* di kode BARU (kode lama tidak di-refactor — surgical) | recon-R2 + hard rules |

## 1. ERD target (delta dari existing)

(existing: users, roles, perusahaans, perusahaan_employees, perusahaan_sessions, schedules, booking_claims, consultants, consultant_pools, chat_messages, perusahaan_assessments, screening_questions, screening_answers)

BARU:
```
packages(id, name, monthly_quota default 2, is_active)
employee_packages(id, perusahaan_employee_id FK, package_id FK, perusahaan_id FK, starts_at, ends_at, status)  -- aktif 1 per employee
consultation_quota_ledger(id, employee_package_id FK, perusahaan_session_id FK nullable, period_ym TEXT, event ENUM(reserved|used|forfeited|refunded|released), reason TEXT nullable, actor_id nullable FK users, created_at)
  UNIQUE(perusahaan_session_id, event)  -- idempotent
consultant_work_hours(id, consultant_pool_id FK, weekday TINYINT 0-6, start_time TIME, end_time TIME, consultation_types JSON nullable, slot_minutes INT default 60)
consultant_availability_exceptions(id, consultant_id FK, date DATE, start_time TIME nullable, end_time TIME nullable)  -- NULL time = full-day block
consultation_notes(id, perusahaan_session_id FK, consultant_id FK, body TEXT encrypted, created_at, updated_at)
consultation_results(id, perusahaan_session_id FK, summary TEXT, recommendation TEXT nullable, created_at, updated_at)
consultation_reviews(id, perusahaan_session_id FK UNIQUE, perusahaan_employee_id FK, rating TINYINT 1-5, comment TEXT nullable, created_at)
rooms(id, perusahaan_id FK, name, is_active)  -- 1-on-1 physical rooms
```
UBAH:
```
perusahaans: + consultant_request_enabled BOOLEAN default 0
perusahaan_sessions: + consultation_type TEXT default 'chat' (backfill: 'chat'), + meeting_url nullable, + room_id nullable FK
consultant_pools: + contract_start DATE nullable, + contract_end DATE nullable, + status TEXT default 'active'
```

## 2. Urutan migration (semua additive, idempotent, sqlite-safe)

M1 packages + employee_packages
M2 consultation_quota_ledger (+ backfill ledger dari sessions existing per bulan — data-safe: derived, bukan delete)
M3 consultant_pools alter (additive columns) + consultant_work_hours + consultant_availability_exceptions
M4 perusahaans.consultant_request_enabled + perusahaan_sessions.consultation_type/meeting_url/room_id + backfill 'chat'
M5 consultation_notes + consultation_results + consultation_reviews + rooms
M6 assessment definitions: alter screening_questions (+instrument_code, +version, +weight nullable) — memperluas engine existing
M7 audit_logs (K16)
(pola: guard hasColumn/hasTable per 9e60184; enum = TEXT + CHECK via driver-dispatch bila perlu)

## 3. Dependency graph + worker split

W1 Foundation (M1-M6, models, factories, seeder) ──┬─> W2 Quota & Package (service reserve/forfeit/refund, no_show sweeper command + scheduler, label package UI)
                                                   ├─> W3 Consultant & Schedule (superadmin assignment+workhour CRUD, availability compute, kalender konsultan, direktori+detail, flag lock)
                                                   ├─> W4 Assessment (engine definition-driven, port WHO-5, seeder PHQ-9/GAD-7/CBI, UI + agregat)
                                                   └─> W5 Types & Chat (3 tipe di booking, meeting link override endpoint, room 1-on-1, chat polling+encrypt)
W2+W3 ─> W6 Employee "Sesi Saya" (dashboard kartu sesi + tombol Minta Konsultasi dengan guard quota+flag)
W2..W5 ─> W7 Notes/Results/Reviews (+ agregat rating ke detail konsultan)
W8 QA & Docs (regression + tenant isolation + quota edge cases + authz tests, rapikan docs)

## 4. Risk register

| Risiko | Mitigasi |
|---|---|
| Quota hole startChat/requestConsultation belum ketutup → ledger bypass | W2: semua jalur create session wajib lewat QuotaService (refactor entrypoint tunggal) |
| Zero policies → route baru bocor authz | K15: guard helper + W8 authz test matrix per route baru |
| Scheduler prod mati (schedule:run gak jalan) → sweeper no-show tidak pernah jalan | W2: dokumentasikan aktivasi cron/systemd timer di deploy doc; QA cek manual command |
| php -S prod fixes gak ada di branch ini → deploy = empty-500 | Pre-deploy: cherry-pick 44bed0d/52954db/3d48aed + finfo polyfill dari improvement-live (W8 step) |
| sqlite enum/CHECK rebuild bikin migrasi rusak di prod | pola idempotent + driver-dispatch (M4 pakai TEXT default, hindari CHECK baru) |
| Backfill ledger salah hitung bulan existing | M2 backfill derived-only + test verifikasi count vs query lama |
| HR lihat catatan klinis (privacy) | K14 + W8 test authorization (HR 403 pada notes/results individual) |
| Chat plaintext body (PDP) | K11/K16: encrypted cast — pesan baru; existing rows re-encrypt via one-time command (W5) |

## 5. Rollback plan

- Semua migration additive → `migrate:rollback` aman (drop kolom/tabel baru).
- Ledger/quota: kalau quota service meledak → set flag `perusahaans.sessions_per_employee_month` path lama masih jalan? NO — jangan dual-path. Rollback = deploy commit sebelumnya (git), DB kolom lama tidak disentuh.
- Feature flag request lock default OFF = aman rollback behavior (lock on = default baru anyway).

## 6. Definition of Done per worker

(tertulis di docs/plan/tasks/Wn.md saat brief dibuat; umum: test hijau 66+baru, tenant isolation test, lint, diff minimal, review mandor)

## 7. Pertanyaan terbuka (untuk GATE)

1. Chat lama (existing rows): encrypt saat migrasi atau biarkan plaintext (hanya baru yang encrypted)?
2. `requestConsultation` (minta tanpa pilih slot): tetap ada untuk employee, atau employee hanya bisa pilih slot? (Meeting notes: karyawan TIDAK boleh request konsultan = lock; tapi "Minta Konsultasi" button di poin 8 = pilih slot booking biasa?)
3. Rating konsultan existing (`consultant_ratings` di klinik vertical) vs `consultation_reviews` baru: pakai tabel baru untuk perusahaan vertical (K14) atau unify?
