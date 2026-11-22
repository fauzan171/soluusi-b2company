# Changelog

Format: ringkas per mission/periode. Detail per worker: `docs/plan/PROGRESS.md`.

## 2026-11-22 — Perusahaan mission (W1–W8)

Upgrade + fitur platform B2B perusahaan di atas base `upgrade/laravel-12`
(Laravel 12). Baseline masuk: 66 test. Baseline keluar: **199 test, 798
assertions, hijau**.

### Added

- **Kuota paket (K2)**: tabel `packages` / `employee_packages` /
  `consultation_quota_ledger` (append-only, UNIQUE(session,event)
  idempotent), `QuotaService` (reserve/consume/forfeit/refund/release),
  entrypoint tunggal `BookingService::book` + `requestConsultation`, sweeper
  `sessions:sweep-no-show` (hangus >24h), refund HR via
  `perusahaan.quota.refund` + audit_logs. Fallback legacy tenant-flat kuota.
- **Assignment konsultan (K6/K8)**: `consultant_pools` + status/contract,
  `consultant_work_hours`, `consultant_availability_exceptions`,
  `AvailabilityService` (slot computed-on-read K4), direktori konsultan
  employee-facing + rating agregat, flag per-perusahaan
  `consultant_request_enabled` + flow request-consultation.
- **Tipe konsultasi & ruang (K9/K10)**: `perusahaan_sessions.consultation_type`
  (online_meeting/chat/one_on_one), `rooms` + `RoomService::pickFreeRoom`
  anti-konflik, override `meeting_url` (konsultan/HR) via route meeting-link.
- **Chat (K11)**: `chat_messages.sender_role`, chat polling dipertahankan,
  Prunable 90 hari, guard canSend (hanya sesi terjadwal).
- **Catatan sesi (K14)**: `consultation_notes` (encrypted cast), 
  `consultation_results`, `consultation_reviews` (rating 1–5 + komentar,
  agregat anonim untuk konsultan).
- **Audit (K16)**: `audit_logs` minimal (note view/update, refund) + ip.
- **Assessment engine (K12)**: `assessment_instruments` sebagai DATA
  (who5/phq9/gad7/cbi via seeder), `screening_questions` +
  instrument_code/version/weight, `perusahaan_assessments.instrument_code` +
  `escalated` (PHQ-9 item-9), band parity dengan legacy WHO-5.
- **Dashboard "Sesi Saya"** employee (riwayat + status + link chat).
- **Deploy hardening (W8)**: systemd user units scheduler
  (`deploy/soluusi-scheduler.*`), `docs/dev/deploy.md`, ERD delta
  (`docs/plan/ERD.md`), FINAL-REPORT (`docs/plan/FINAL-REPORT.md`),
  CHANGELOG ini.
- **Tests**: matriks isolasi lintas tenant satu file
  (`W8CrossTenantIsolationTest`, 15 skenario 403/404) + edge kuota
  (`W8QuotaEdgeFinalTest`).

### Changed

- Booking perusahaan: validasi slot tenant-match + work_hours vs tipe
  konsultasi, cancel late-cutoff 2 jam (kuota hangus), reschedule = cancel +
  book baru.
- Docs dirapikan (recon-R6): 9 file stale diarsipkan ke `docs/archive/`
  (git mv, riwayat terjaga), PDF duplikat dihapus (md5 identik).

### Known deviations / open items

- Rating dual path: `perusahaan_sessions.rating` (legacy W3) vs agregat
  `consultation_reviews` (W7) — lihat FINAL-REPORT open items.
- Chat tetap HTTP polling (K11) — broadcast scaffolding tidak dipakai.
- Prod scheduler mati sampai `soluusi-scheduler.timer` diaktifkan owner.

## Sebelum 2026-11-20

- Base `upgrade/laravel-12`: upgrade skeleton Laravel 12 (66 test) —
  lihat `docs/brief-presentasi.md` di tree prod.
- Histori improvement-live: `docs/archive/` dan `git log`.
