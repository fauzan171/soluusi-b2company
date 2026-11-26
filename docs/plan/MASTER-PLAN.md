# MASTER-PLAN — Wellbeing Product (katalog 4 paket + pricing band + assign program + kuota kontrak-level)

Status: FROZEN v1 (siap eksekusi engineer agents)
Supersedes: MASTER-PLAN mission-package-quota (arsip di git history @ `a4b73fb..5dce21f`)
Worktree: `/home/hermes/workspace/projects/soluusi-b2b-wellbeing` · Branch `feat/wellbeing-product` @ `5dce21f`
Baseline: **712 passed / 2553 assertions** (`/usr/local/bin/php artisan test`, sqlite `:memory:`, RefreshDatabase)
Sources: `PRODUCT-DOC-SUMMARY.md` (source of truth) + `research-arch.md`, `research-ux.md`, `research-bizrules.md`, `research-tests.md`.
Resolusi inkonsistensi doc = PRODUCT-DOC-SUMMARY §8. Tidak ada keputusan baru di bawah ini — semua diturunkan dari research.

---

## 1. Arsitektur ringkas

4 tabel baru, 2 migrasi aditif, 0 kolom tabel existing berubah:

```
wellbeing_packages          katalog 4 paket (DATA seeder: discovery|foundation|growth|impact)
  └─ wellbeing_pricing_bands  5 band × paket berbayar, monthly_price + mhcu_quota (DATA seeder)
company_wellbeing_programs  KONTRAK: assign oleh superadmin, SNAPSHOT kuota+band+option+harga, status active|ended|cancelled
  └─ wellbeing_quota_ledger   append-only mirror M2, 2 bucket (expert_chat|deep_support), TANPA period_ym
```

- Kuota = **kontrak-level total** (bukan bulanan): saldo = snapshot − distinct(reserved|used|forfeited) + released/refunded per (`program_id`,`bucket`); window = `status=active && starts_at ≤ today ≤ ends_at`. Unused hangus otomatis.
- `WellbeingQuotaService` adapter terpisah (QuotaService existing TIDAK diubah). Routing if-guard di 4 call-site: program aktif → wellbeing ledger; tanpa program → jalur legacy identik.
- Katalog + harga = data seeder `WellbeingPackageSeeder` (idempotent `firstOrCreate`), editable runtime via admin UI.
- Admin UI mengikuti pola `KlinikPackageController` + `PerusahaanActivationController` (Blade flat + Tailwind, `role:admin`).
- Bayar v1 = pencatatan manual (no Midtrans). Discovery gratis.

## 2. Keputusan (K1..K14 — dari research, FROZEN)

| # | Keputusan | Sumber |
|---|---|---|
| K1 | 4 tabel baru terpisah, BUKAN extend `packages`/`employee_packages`/ledger existing | arch §5.2 |
| K2 | 2 migrasi: `2026_11_23_000001` (katalog+bands), `2026_11_23_000002` (programs+ledger). TEXT enum, sqlite-safe | arch §3.6 |
| K3 | Katalog+harga = DATA seeder idempotent (`firstOrCreate` per `code`/band), editable runtime | arch §5.1 |
| K4 | Snapshot kuota+band+option+harga di `company_wellbeing_programs` saat assign (kebal perubahan katalog) | arch §5.4, biz R9 |
| K5 | Band dari `perusahaans.employee_count` (deklaratif MO) saat assign → snapshot `pricing_band_id` + `employee_band_size`; validasi ≥50 kecuali discovery; band tidak bergeser mid-contract | biz §3 |
| K6 | 2 bucket kuota terpisah: `expert_chat` (chat) vs `deep_support` (1-on-1/MHCU/online_meeting) | arch §3.1 |
| K7 | Option A (MHCU) XOR B (1-on-1) per kontrak, `deep_support_mode NOT NULL` wajib utk growth/impact; band MHCU menang atas default katalog (resolusi §8.2/8.3) | biz R2 |
| K8 | 1 program aktif per perusahaan — guard app-level (sqlite tanpa partial unique) | biz §4 |
| K9 | No pro-rate / no auto-upgrade v1: kontrak baru hanya setelah ended/cancelled | biz §4 |
| K10 | `WellbeingQuotaService` adapter terpisah + routing if-guard di 4 call-site (BookingService:200, EmployeeController::startChat:511, ChatController:73,94, SweepNoShowSessions:35); fallback legacy = jalur lama identik | arch §4 |
| K11 | 30-day notice termination: validasi efektif ≥ today+30; program tetap aktif s.d. tanggal efektif; daily command flip `ended` | biz R6 |
| K12 | Discovery: max 40 peserta assessment (distinct employee, retake tidak menambah) + 8 sesi deep support hanya utk karyawan terpilih (list `selected_employee_ids` JSON di program, null = semua) + consent gate existing | biz R3/R4, §8.1 |
| K13 | Harga exclude pajak: suffix "+ pajak" di view (no tax column); invoice = harga × bulan | biz R5 |
| K14 | `AggregatePrivacy::safe($counts, int $min = 5)` parameterize; report wellbeing program pass 10; legacy caller tetap 5 | biz R10 |

Naming UI (research-ux §2): admin "Katalog Paket Wellbeing" + "Assign Program"; HR "Program & Subscription"; "Band Karyawan"; copy paket alternatif (a); tone-of-voice rules ux §5 (no istilah klinis, no "diagnosis", "sisa kuota program" bukan bulanan).

## 3. Task DAG + parallel groups

```
T1 Foundation (migrasi + 4 model + seeder + schema test)
 └─> T2 WellbeingQuotaService + routing/gating 4 call-site
      └─> T3 Superadmin katalog CRUD + harga band UI (routes admin group + nav admin)
           ├─> T4 Assign program wizard + aktivasi + termination + AdminAction   ┐ PARALLEL
           └─> T5 HR "Program & Subscription" + AggregatePrivacy parameterize    ┘ (file disjoint)
                └─> T6 Test suite baru (±18 scenario) + full regression 712
```

- T1 blocking semua (skema+model adalah dependensi T2–T5).
- T2 blocking T3–T5 (routing butuh service + program model final).
- T3 blocking T4 (T4 route masuk group nav yang dibuat T3; T4 controller assign = 1 form per halaman terpisah dari katalog T3).
- T4 ∥ T5: disjoint (T4 = `admin` route section + `admin/wellbeing-programs/*` views; T5 = `perusahaan` route section + `perusahaan/program-subscription.blade.php` + nav hr_admin + `AggregatePrivacy`). Kedua-duanya boleh sentuh `routes/web.php` dan `layouts/app.blade.php` hanya dengan baris BARU di section berbeda (merge serial di integration branch).
- T6 terakhir: 5–6 file test baru, 0 edit test existing.

## 4. Task summary (detail brief: `tasks/wb-T*.md`)

| Task | Judul | Mode | Files inti |
|---|---|---|---|
| wb-T1 | Migrasi + model + seeder katalog | serial | 2 migration, 4 model, `WellbeingPackageSeeder`, 1 test baru |
| wb-T2 | WellbeingQuotaService + routing & gating call-site | serial | `WellbeingQuotaService.php`, edit 4 call-site + `AssessmentController::store`, 1 test baru |
| wb-T3 | Superadmin katalog CRUD + harga band UI | serial | `Admin/WellbeingPackageController.php`, `admin/wellbeing-packages/*` views, routes+nav, 1 test baru |
| wb-T4 | Assign program wizard + aktivasi + 30-day notice | parallel | `Admin/WellbeingProgramController.php`, `admin/wellbeing-programs/*`, `wb:expire-programs` command, 1 test baru |
| wb-T5 | HR Program & Subscription + threshold 10 | parallel | `Perusahaan/ProgramSubscriptionController.php`, `perusahaan/program-subscription.blade.php`, `AggregatePrivacy`, 1 test baru |
| wb-T6 | Suite baru ±18 scenario + regression | serial | 5–6 file test Feature |

Semua task verify: `/usr/local/bin/php artisan test` (full suite, 712 + N baru hijau).

## 5. Risk register

| Risiko | Mitigasi |
|---|---|
| Double-reserve (sesi masuk 2 ledger) | routing single if/else; UNIQUE(session,event) kedua tabel; T6 assert "program aktif → 0 row baru di consultation_quota_ledger" |
| 712 baseline break via sentuh call-site | if-guard murni; fixture existing tanpa program → path lama identik; full suite tiap task |
| Seeder menimpa kontrak snapshot | snapshot di kontrak; seeder `firstOrCreate` idempotent |
| `deep_support_mode` mismatch (booking chat di kontrak tanpa expert chat) | gate 403 pesan jelas di T2; UI menyembunyikan opsi (T5) |
| Merge conflict T4/T5 (routes/nav sama-sama disentuh) | constraint "baris baru saja, no reformat"; merge serial satu-satu ke integration branch + re-run suite |
| Scope creep (dashboard 6-role, klinik bagi hasil, MCMI-III, Midtrans, add-on entity) | OUT of scope — backlog, tidak ada tabel/kode |
| File baru tidak kebaca (env gotcha) | tiap file baru: `chgrp hermes` + `g+r` setelah tulis |

## 6. Out of scope (backlog)

6-role internal dashboard penuh · klinik bagi-hasil (§7 doc) · MCMI-III · add-on purchase entity · Midtrans charge kontrak (prefix `WB-` + guard webhook) · prorated upgrade · top-up mid-contract.

## 7. Definition of Done mission

712 existing + ±18 scenario baru hijau, 0 edit test existing, `git diff` minimal per task, reviewer + security-auditor pada combined diff, tasks/wb-T*.md status updated.
