# Recon R4 — WHO-5 Assessment & Screening Engine (Perusahaan/Klinik)

Repo: `/home/hermes/workspace/projects/soluusi-b2b-mission` (read-only recon). All paths relative to repo root unless absolute.

## 1. `perusahaan_assessments` schema + where WHO-5 lives

Schema: `database/migrations/2026_08_18_000001_create_perusahaan_assessments_table.php`
- `perusahaan_id` FK → perusahaans (cascade), `employee_id` FK → perusahaan_employees (nullOnDelete), `departement_id` FK, `answers` JSON (5 item WHO-5, nilai 0-5), `score` tinyint 0-100, `taken_at` datetime, index `(perusahaan_id, taken_at)` (:14-24).
- Migration header comment: "Hasil skrining WHO-5 karyawan. Skor 0-100 (sum answers * 4). CRITICAL: HR dashboard hanya baca AGREGAT (latest score per employee → distribusi band)."

WHO-5 is **fully hardcoded** — no table, no seeder:
- Question text (5 items, Indonesian): `app/Http/Controllers/Perusahaan/EmployeeController.php:20-26` const `WHO5_ITEMS`.
- Answer options (0-5 Likert labels): `EmployeeController.php:28-35` const `ANSWER_OPTIONS`.
- Scoring: `EmployeeController.php:125` — `'score' => array_sum($answers) * 4` (0-25 → 0-100, standard WHO-5 formula). Validation `answers size:5, int 0-5` at `EmployeeController.php:114-117`.
- Form rendering: `resources/views/perusahaan/assessment.blade.php` (radio grid per item; disclaimer "bukan diagnosis" + hotline 119 ext. 8).
- Sekolah duplicate: `app/Http/Controllers/Sekolah/StudentController.php:20` re-uses the const, scoring at :110. Band duplicated in both models (`app/Models/Perusahaan/PerusahaanAssessment.php:21-25`, `app/Models/Sekolah/SekolahAssessment.php:19-20` — comment "satu formula" but copy-pasted).
- Routes: `routes/web.php:109-110` (`GET/POST /employee/assessment`).

## 2. Screening tables — already a definition-driven engine?

Migration `database/migrations/2026_10_21_000002_create_screening_tables.php`:
- `screening_questions` (:15-29): `vertical` (perusahaan|sekolah), `section` (mental_health|nutrition_lifestyle|ergonomic), `code` (Q5…Q34), `text`, `type` (freq0-4|pain4|yesno|single|multi|scale1-10), `options` JSON, `reverse` bool, `ord`, unique `(vertical, code)`.
- `screening_answers` (:31-41): polymorphic-ish `assessable_type/assessable_id` (points at PerusahaanAssessment / SekolahAssessment), `question_code`, `value`, `other_text`, `answered_at`, unique `(assessable_type, assessable_id, question_code)`. No FK.
- Extends both assessment tables with `section` (default `mental_health`), `status` (pending|in_progress|completed), `consultant_id`, `notes`, `conclusion`, `concluded_at`, new index `(tenant, section, taken_at)` (:44-52, :54-63).
- Header comment: "Bank pertanyaan disimpan sebagai DATA ber-vertical, bukan kode… WHO-5 lama tetap valid dan dipetakan ke section 'mental_health'." Design keeps time-series (no unique per employee/section).

Question bank: `database/seeders/ScreeningQuestionSeeder.php:16-55` const `QUESTIONS` ("My Health Index Assessment" Soluusi × TVS, adopted from kiosk TVS `pertanyaan.md`/`0002_seed.sql`): 9 PSS-style freq items (Q5-Q13, reverse Q8/Q9/Q11/Q12), 6 nutrition (Q15-Q20), 8 ergonomic pain items (Q21-Q28) + context Q29-Q34 (only Q31 scale1-10 scored). Idempotent `updateOrInsert` per `(vertical, code)` (:60-73).

Scoring engine: `app/Services/Screening/ScoringService.php` (port of kiosk TVS `functions/_lib/scoring.ts`):
- `scoreSection()` :36-77 — normalizes each section to 0-100 = raw/max*100; **denominator = ALL scored questions, not answered ones** (comment at :51-56: prevents gaming by leaving blanks). Reverse scoring computed here only, raw values never overwritten. `typeMax()` :80-88 — hardcoded weights (`freq0-4`=4, `pain4`=3, `yesno`=1, `scale1-10`=10) + special-case `$nutrition` map for Q15-Q20 weights (2,2,2,2,1,1) at :83-85.
- `DEFAULT_BANDS` :17-22: 0-24 low, 25-49 medium, 50-74 high, 75-100 critical, with `consult` 0-3.
- `bandFor()` :90-99, `recommend()` :102-125 (worst-across-domains, not average — kiosk sdd.md §6.2), `colorLabel()` :127-136 (Hijau/Kuning/Oranye/Merah).

Test: `tests/Screening/ScoringCheck.php` — standalone PHP script (not PHPUnit), `php tests/Screening/ScoringCheck.php`. Verifies band boundaries (24/25/49/50/74/75), mental_health max=36 denominator, reverse scoring, context questions skipped, worst-domain recommendation, nutrition fixed weights.

**Is it a generic engine already?** Partially — data-driven question bank (DB rows per vertical), section-agnostic scoring, testable band config, multi-vertical. But:
- No production flow writes `screening_answers` yet. The only consumer is `app/Http/Controllers/ProgressController.php:16-45` — a demo/status page (`/progress`, view `resources/views/progress.blade.php`) that runs ScoringService on **hardcoded sample answers** (:18-24), never on real stored answers. No route/controller does intake of screening answers.
- No Eloquent model for `screening_questions`/`screening_answers` (raw `DB::table` queries only).
- `typeMax()` hardcodes question codes (Q15-Q20) inside the engine — breaks "definition-driven" claim; weights should be columns.
- No `instrument`/versioning concept; `section` is the only grouping.

## 3. Scoring + interpretation (WHO-5 path)

- Raw: sum of 5 answers (0-25) × 4 → 0-100. `EmployeeController.php:125`, `StudentController.php:110`.
- Bands: `app/Models/Perusahaan/PerusahaanAssessment.php:21-25` — `score >= 75` → 'Sehat', `>= 50` → 'Perlu Perhatian', else 'Berisiko Tinggi'. (Doc comment mentions low-threshold 28 but is not implemented in code.)
- Feature test: `tests/Feature/PerusahaanWellbeingTest.php:39-51` — answers `[3,4,2,5,1]` → score 60 → band 'Perlu Perhatian'.
- Screening path bands: `ScoringService.php:17-22` (green/yellow/orange/red, consult 0-3).

## 4. Results → HR dashboards

- `app/Services/DashboardMetrics.php:108-113` — "NPS wellbeing" = avg of **latest score per person** in last 90 days, /20 → 0-5 scale; `risk_high_pct` passed to InsightEngine at :119-121 (gated by n≥5).
- `app/Services/ProgramAggregate.php:15-23` — monthly: latest assessment per person in month, binned into `Sehat`/`Perlu Perhatian`/`Berisiko Tinggi` (`>=75`, `50-74`, `<50`).
- `app/Http/Controllers/Perusahaan/DashboardController.php` passes metrics to view (quota/aggregate tiles, :37, :52).
- Report view: `resources/views/perusahaan/report.blade.php:251-257` renders `$riskDist` (or suppressed notice).
- Employee sees own score/trend: `resources/views/perusahaan/employee.blade.php:48-55, 336-344, 373-380` (Chart.js trend, band colors).

## 5. Privacy / n≥5 suppression

- `app/Support/AggregatePrivacy.php:7-10` — `safe()` = every count is 0 or ≥5.
- `ProgramAggregate.php:41-48` — whole-family suppression on statuses/topics/types/ratings/days/groups; `riskSafe` additionally requires `assessed->count() >= 5`; `suppressed`/`riskSuppressed` flags + nulls at :51, :60-61.
- `DashboardMetrics.php:119-121` — `risk_high_pct` only computed when `count() >= 5`, else null.
- Blade messaging on suppression: `resources/views/perusahaan/report.blade.php:251` (`$riskDist === null` branch).

## 6. Assessment lifecycle

- Who fills: the employee themselves via `/employee/assessment` (auth role `employee`; consent + active program required for writes — `EmployeeController.php:45-49`). Super admin can GET-preview only (`EmployeeController.php:57-64`).
- When: employee portal shows a nudge `needsAssessment` when no latest or latest > 30 days — `EmployeeController.php:79` (retake rule: **soft prompt only, no enforcement**).
- Retake: unlimited, each submit inserts a new row (time-series); uniqueness only in screening table per question answer.
- Retake cadence for trend: last 6 assessments charted (`EmployeeController.php:77-78`).
- Screening lifecycle columns (`status` pending|in_progress|completed, consultant conclusion) exist in schema but no code writes them yet.

## 7. Recommendation: extend the screening engine, or build new?

**Recommendation: extend the screening engine as the base — but first finish it (it is half-wired).**

Evidence for:
1. It is already definition-driven where it counts: questions/options/reverse/order live in `screening_questions` rows per vertical (`2026_10_21_000002_create_screening_tables.php:15-29`, `ScreeningQuestionSeeder.php`), while WHO-5 questions are PHP constants duplicated across two controllers + two band() model copies (`EmployeeController.php:20`, `StudentController.php:20`).
2. Scoring is already instrument-shaped: section-agnostic 0-100 normalization, reverse scoring, configurable bands (`ScoringService.php:36-99`), and cross-domain recommendation with worst-domain semantics (:102-125) — the exact shape a multi-instrument engine needs.
3. It already spans both verticals (perusahaan + sekolah) with one engine — the new engine's core requirement.
4. The assessment tables were already extended to host it (`section`/`status`/`conclusion` columns, :44-63), and the migration comment explicitly says WHO-5 maps into section `mental_health` — the intended migration path is documented in the schema itself.
5. It has a runnable self-check (`tests/Screening/ScoringCheck.php`) covering the tricky parts (denominator-on-all-questions, reverse items, context questions).

Gaps to close before it is a real engine (would be the actual work items):
- No intake flow: nothing writes `screening_answers`; only a demo page runs scoring on hardcoded samples (`ProgressController.php:18-24`). Need a controller/form equivalent of `assessmentStore` that persists answers and computes `score` per section via ScoringService.
- No Eloquent models for the two screening tables; polymorphic `assessable_*` has no index beyond the unique key.
- Weights are partly hardcoded in the engine (`ScoringService.php:83-85` Q15-Q20) — move to a column (e.g. `weight`) to stay definition-driven.
- No instrument/version identity: `section` is the only key; a generic engine needs `instrument_code` (who5, pss, my-health-index) + version, and a mapping from instrument → scoring strategy (WHO-5 uses sum×4 linear, screening uses percent-of-max with bands — both fit "percent-of-max" if WHO-5's 4× multiplier is expressed as max=25/25).
- Suppression/aggregation (points 4-5 above) currently reads `score` from the assessment row only; a multi-instrument dashboard needs `AggregatePrivacy` applied per instrument — the existing helpers are already parameterized by count collections, so they generalize cleanly.

Bottom line: a new parallel engine would duplicate `ScoringService` + `screening_questions` + the vertical pattern that already exist; the cheaper path is to add instrument identity, weight columns, an intake flow, and models to the screening stack, then port WHO-5 onto it (its 5 items become `mental_health` questions with type `freq0-5`), keeping `band()` labels ('Sehat'/'Perlu Perhatian'/'Berisiko Tinggi') as an instrument-level band config.
