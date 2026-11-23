# wb-T2 — WellbeingQuotaService + routing & gating 4 call-site

Status: READY
Depends: wb-T1 (model + tabel). Blocking T3–T5.

## GOAL
Buat adapter `WellbeingQuotaService` (kuota kontrak-level, 2 bucket) + routing if-guard di 4 call-site QuotaService + gate assessment participant cap — perusahaan TANPA program aktif tetap jalur legacy identik (712 test tak tersentuh).

## CONTEXT
- Desain service EKSAK: `docs/plan/research-arch.md` §4 (signature: `activeProgram`, `bucketBalance`, `reserve`, `consume`, `forfeit`, `release`, `refund`) — mirror pola internal `app/Services/QuotaService.php` (append-only, UNIQUE(session,event) idempotent, `DB::transaction`, saldo = snapshot − distinct(reserved|used|forfeited) + released/refunded per (program_id,bucket), TANPA `period_ym`; window = `status=active && starts_at <= today <= ends_at`).
- Call-site (file:line, verifikasi ulang sebelum edit):
  - `app/Services/BookingService.php:200` — `QuotaService::reserve`
  - `app/Http/Controllers/Perusahaan/EmployeeController.php` `startChat` (~:511) — `QuotaService::reserve`
  - `app/Http/Controllers/ChatController.php:73,94` — `QuotaService::consume`
  - `app/Console/Commands/SweepNoShowSessions.php:35` — `QuotaService::forfeit`
- Bucket routing: `consultation_type chat` → bucket `expert_chat`; `one_on_one|online_meeting` → bucket `deep_support`. Mode A kontrak (`deep_support_mode='mhcu'`) → 1-on-1 masuk bucket `deep_support` (band MHCU); mode B sama bucket `deep_support`. Gate: `expert_chat_quota=0` → booking chat abort 403 pesan jelas.
- Aturan biz: `research-bizrules.md` R1 (hangus), R2 (A XOR B), R3 (max 40 peserta assessment distinct employee, retake lolos; program `assessment_max_participants=null` → tak dibatasi), R11 (no-show forfeit branch).
- R3 enforcement point: `app/Http/Controllers/Perusahaan/AssessmentController.php` `store` — tambah guard sebelum create (hitung distinct employee di `perusahaan_assessments` program aktif).

## CONSTRAINTS
- `QuotaService` public API (`assignmentFor`, `periodOf`, `balanceFor`, `packageLabel`, `reserve`, `consume`, `forfeit`, `refund`, `release`) TIDAK diubah sama sekali.
- Routing = if-guard murni di call-site: `if ($program = WellbeingQuotaService::activeProgram($company)) { ...wellbeing... } else { ...QuotaService existing... }` — single branch, JANGAN keduanya (anti double-reserve). Di `SweepNoShowSessions`/`ChatController::consume`: cek row `reserved` wellbeing ada → append wellbeing; else QuotaService.
- Edit existing = minimal diff, no reformat, comment singkat `// WB:` penanda branch.
- File baru `chgrp hermes` + `g+r`.
- Test fixture tanpa `company_wellbeing_programs` → semua path lama harus identik (regression gate).

## ACCEPTANCE
1. `WellbeingQuotaRoutingTest` baru (RefreshDatabase, pola fixture `QuotaServiceTest`): (a) perusahaan + program aktif growth → `reserve` menulis row `wellbeing_quota_ledger`, 0 row baru `consultation_quota_ledger`; (b) saldo kontrak berkurang; (c) lewat `ends_at` → reserve abort 403; (d) bucket expert_chat & deep_support terpisah; (e) option A vs B mengubah kuota snapshot deep_support; (f) no-show sweep sesi wellbeing → event `forfeited` di wellbeing ledger saja.
2. `WellbeingAssessmentCapTest` baru: 40 employee beda submit → ke-41 abort 403 "kuota peserta assessment penuh"; employee #1 retake → lolos; program max=null → tidak dibatasi; perusahaan tanpa program → tidak dibatasi (path lama).
3. Full suite `/usr/local/bin/php artisan test`: 712 + baru, semua hijau, 0 edit test existing.
4. Perusahaan tanpa program: booking/startChat/consume/forfeit menghasilkan DB state identik sebelum perubahan (assert via test (a) negatif + suite lama hijau).

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil / deviations / blockers
