# Research Wave 2 — B: 5 subsystems + expert gate (2026-11-23)

Verbatim from research agent (r-infra). Source of truth for implementers WV-A/B/C.

## 1. Assessment instruments

Existing:
- `assessment_instruments` table → `app/Models/AssessmentInstrument.php` (code, scoring_type `sum|sum_x4|mean_x25`, bands JSON, escalate_items JSON, is_active, bandLabelFor/bandNoteFor). Items: `screening_questions` with instrument_code/version/weight; seeder `AssessmentInstrumentSeeder.php` (who5, phq9, gad7, cbi). `ScreeningQuestionSeeder.php` kiosk bank has sections mental_health, nutrition_lifestyle, ergonomic (Nutrition/Ergonomic content already exists as sections, not instruments).
- Scoring engine: `app/Services/Screening/InstrumentScoring.php` — definition-driven; reverse items, weights, escalate check; `aggregate()` for HR with `AggregatePrivacy::safe()` (default 5). Legacy: `ScoringService.php` + hardcoded WHO-5 path in `EmployeeController::assessmentForm/Store` + `PerusahaanAssessment::band()` — do not break.
- Flow: `Perusahaan/AssessmentController.php` (index/form/store/result; store applies participant-cap only). Views: `perusahaan/assessment-instruments{,-instrument,-result}.blade.php`.
- HR aggregate: `ReportController::assessment` → `report-assessment.blade.php`.
- `assessment_domains`: seeded on packages (discovery ['mh','wellbeing'], foundation ['mh'], growth/impact ['mh','nutrition']), snapshotted to `company_wellbeing_programs.assessment_domains` at assign — **never read at assessment time**.

Gap: no domain/placement field on instruments; no instrument gating per program; multi-dimension (DASS-21 3 subscales, Big Five 5 scales, DISC 4 dims) doesn't fit single score; MCMI-III needs indication-based gating (no mass test).

Minimal build:
- Migration: `assessment_instruments.domain` (nullable string) + backfill; MCMI-III row is_active=false.
- Gating in AssessmentController::form/store: active program → instrument.domain ∈ program.assessment_domains; inactive → 404; MCMI only via explicit per-employee grant.
- Multi-dim: add optional `dimensions` JSON (question→dimension) + result view per-dimension rendering; covers DASS-21/Big Five/DISC uniformly. PSS-10 fits `sum` with reverse items as-is.
- Wire threshold 10 into `InstrumentScoring::aggregate` for perusahaan (K14/R10 says 10; today 5).
- Foundation "1 domain (MH or Nutrition)" — add admin choice at assign (assessment_domain override in wizard) OR accept seeder default; plan decision: allow optional override field in wizard.

## 2. Webinar

Existing: columns `wellbeing_packages.webinar_count`, snapshot `company_wellbeing_programs.webinar_quota`; HR page counts ledger rows bucket='webinar' events used/forfeited — nothing ever writes a webinar ledger row (only a test does).

Minimal build (fits pattern):
- Model `WellbeingWebinar` + migration: program_id FK, title, scheduled_at, status planned|done|cancelled, nullable consultant_id (speaker), notes. No attendance table v1 (1 webinar = 1 ledger row).
- On mark-done: insert WellbeingQuotaLedger (bucket='webinar', event='used', no session id — per-row counting already supported). Admin can insert forfeited for no-show.
- Admin controller + views under `admin/wellbeing-programs/`; HR read-only list in program-subscription page.
- Tests: quota decrement + cap 1/3/6 + hangus at ends_at (balance returns 0 past window).

## 3. Quarterly review (Impact only)

Existing: flag `wellbeing_packages.quarterly_review` only.

Minimal build:
- Model `WellbeingProgramReview` + migration: program_id, period_no (1–4), due_at (= starts_at + 3×period months), status upcoming|in_review|completed, summary text, created_by. Unique(program_id, period_no).
- Generate 4 rows at assign in same transaction if quarterly_review.
- HR page: list + per-period aggregate reusing InstrumentScoring::aggregate + ProgramAggregate with AggregatePrivacy::safe($counts, 10).
- Tests: only impact gets 4 rows; suppression at n<10.

## 4. Quotation + Program Order / S&K Perusahaan

Existing: full working scroll-gate S&K pattern in `resources/views/auth/partials/terms-klinik.blade.php` (19 pasal) + `auth/register-partner.blade.php:172-224` (checkbox disabled until scrolled, progress bar, terms 'accepted', terms_agreed_at). 30-day notice EXISTS (`WellbeingProgramController::requestTermination`). Print pattern: `perusahaan/report.blade.php` @media print + window.print(). No PDF lib. No public company "Daftar Sekarang" flow today (company onboarding is post-login; /daftar/{type} is signed-invitation self-register).

Minimal build:
- `resources/views/auth/partials/terms-perusahaan.blade.php` (19 pasal: 30-day notice, kuota hangus, harga exclude pajak, aggregate threshold, privacy) — copy terms-klinik structure.
- Public `/daftar-perusahaan` (`PerusahaanRegisterController`, mirror PartnerRegisterController): terms accepted + terms_agreed_at, creates inactive company + hr_admin user, ops approves (lead-capture).
- Program Order view: `admin/wellbeing-programs/order.blade.php` — render snapshot columns (package, band, monthly_price "+ pajak", quotas, domains, selected employees, dates) + window.print(); link from wizard success. Zero new tables (program row IS the document).

## 5. Klinik revenue share

Existing: tier rule implemented as markup caps (`config/klinik.php` markup_caps 10/15/20/25% by price tier — identical to doc); `MarkupService`, per-klinik `MarkupRule` overrides; `SplitSettlementService` (idempotent, reverse on refund) + admin settle. Checkpoint 40: only S&K text + HAP stage3 trigger. 3 schemes: text + `kliniks.service_interest` enum only.
Gap (out of scope per MASTER-PLAN §6): no checkpoint-40 progress view per klinik (trivial query). Minimal: admin view `admin/klinik-checkpoints.blade.php` with completed-session count per klinik + alert at ≥40, one controller method, no migration.

## 6. Expert gate

Existing: `consultant_pools` + admin `/consultant-assignment*` (ConsultantAssignmentController, throttled, audited). Catalog counts `wellbeing_packages.expert_chat_experts` (0/1/2/2). No snapshot of expert count on program; no gate (TODO program-subscription.blade.php:116; test E2 documents no gate).

Minimal build:
- Gate at wizard store: `ConsultantPool::where('perusahaan_id',…)->count() >= required` (required from package expert_chat_experts); validation error otherwise. Optional soft warning on HR program-subscription page.
- Test: extend WellbeingScenarioGapTest E2.
