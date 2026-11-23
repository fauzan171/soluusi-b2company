# Research Wave 2 — A: Dashboard gap map (2026-11-23)

Verbatim from research agent (r-dash). Source of truth for implementers WV-D.

## Main Soluusi internal dashboard (8 menus + ops features)

Nav source: `resources/views/layouts/app.blade.php` lines 60–72 (`admin.*` match arm). All admin routes gated `role:admin` only — single Super Admin role today.

| Doc menu | Verdict | Existing route+view | What's missing |
|---|---|---|---|
| Overview | PARTIAL | `GET /admin/dashboard` → `admin/dashboard.blade.php` | Klinik-oriented stats; no wellbeing-program overview counts |
| Company Management | EXISTS | `admin.perusahaans.index` + `admin.tenants.perusahaans.*` | Largely covered |
| Program Management | PARTIAL | `admin.wellbeing-programs.create` (wizard), `admin.wellbeing-packages.*` (catalog) | No list page of active programs; no quota monitoring view |
| Employee Management | MISSING | — (only tenant-scoped roster under `perusahaan.employees`) | Cross-company employee browser |
| Assessment Management | PARTIAL | Instruments seeded (who5, phq9, gad7, cbi); no admin CRUD/list page | New instruments absent; no admin UI |
| Expert & Consultation | PARTIAL | `admin.consultant-assignment.*`, `admin.assignments`, `admin.pervorma-sessions` | OK for v1; menu label only |
| Reports & Insights | PARTIAL | `admin.ops-queue.index`, HR-side report generator | No internal cross-company report/insights page |
| Billing & Contract | MISSING | — (schema ready: monthly_price, notice_requested_at) | Contract list with price, notice status, renewal reminder |

Ops features: program activation EXISTS; bulk invite PARTIAL (HR-side only); automated reminder MISSING (routes/console.php has no schedule entries); expert assignment EXISTS; quota monitoring PARTIAL (no internal view); report generator PARTIAL (HR side); renewal reminder MISSING (no notice email); activity log EXISTS (`admin.audit-logs.index`).

## HR Dashboard (10 menus)

Nav: `layouts/app.blade.php` lines 41–48 (hr_admin/admin arm) — 6 items today.

| Doc menu | Verdict | Existing route+view | What's missing |
|---|---|---|---|
| Company Overview | EXISTS | `perusahaan.dashboard` | Menu label only ("Dashboard") |
| Employee Engagement | MISSING | — | Engagement view (participation, active sessions aggregate) |
| Wellbeing Assessment | PARTIAL | `perusahaan.report.assessment` | No menu entry |
| Wellbeing Insights | PARTIAL | `perusahaan.wellbeing` (charts, risk dist, NPS, threshold) | No menu label ("Kesejahteraan"); area-level risk map (Feature A) missing |
| Expert Utilization | PARTIAL | `perusahaan.consultants.catalog` + `consultants.pool` | No per-expert utilization table |
| Company Action Plan | MISSING | — | Feature B tracker (Growth+Impact) |
| Program Progress | PARTIAL | `perusahaan.program-subscription` | No menu label; no trend/pre-post (Feature C) |
| Reports | EXISTS | `perusahaan.report` | Feature C missing |
| Program & Subscription | EXISTS | `perusahaan.program-subscription` | Termination display for HR absent |
| Company Profile | PARTIAL | onboarding `perusahaan.profile` (one-time) | No editable profile post-onboarding |

Aggregate threshold min 10: EXISTS in `ProgramAggregate` ($min = 10 perusahaan). HR isolation: EXISTS.

## Employee Dashboard (8 menus)

Nav: `layouts/app.blade.php` lines 49–53 — 3 items (Dashboard, Asesmen, Booking).

| Doc menu | Verdict | Existing route+view | What's missing |
|---|---|---|---|
| My Wellbeing | PARTIAL | `perusahaan.employee` (score, sessions, consent gate, quota) | Menu label only |
| My Assessment | EXISTS | `perusahaan.assessment.instruments` + form/store | — |
| My Results | PARTIAL | `perusahaan.assessment.result` | No history list |
| My Recommendations | PARTIAL | inline interpretation + booking hint | Per-domain recommendation content |
| Expert Chat | PARTIAL | `perusahaan.consultants`, chat rooms | Not in nav |
| My Consultations | PARTIAL | `perusahaan.booking` + sessions list | No nav entry |
| My Wellbeing Activities | MISSING | — | Webinars/campaigns (Feature D) |
| Privacy & Consent | PARTIAL | consent gate inline; `employees.consent` (HR sets) | Employee-facing consent status page |

## Features A–D

- A Risk & Priority Map — PARTIAL (people-level riskDist exists; area/domain-level missing)
- B Action Plan Tracker — MISSING entirely
- C Trend & Progress — MISSING (no pre/post)
- D Engagement Campaign — MISSING (no campaign model; no schedules in routes/console.php)

## Existing roles

Roles (DatabaseSeeder): admin, consultant, customer, klinik_admin, associate, hr_admin, employee, bk, student, sekolah_staff. Gate: `app/Http/Middleware/CheckRole.php` (single-role equality, `role:` alias), users.role_id FK.
Doc's 6 internal roles: Super Admin (=admin), B2B Account Manager, Operations, Expert Coordinator, Finance, Expert (consultant ≈ Expert). Missing: account_manager, operations, expert_coordinator, finance (+nav/gates).

## Build list

Group 1 schema: action_plans, engagement_campaigns(+targets), assessment period/pre-post tag, roles seeder extension, instrument seeder additions.
Group 2 admin pages: programs index, billing/contract, employee overview, instruments admin, report/insights, nav additions.
Group 3 HR pages: action plan, engagement, nav relabels + additions.
Group 4 employee pages: consent, results history, activities, nav additions.
Group 5 scheduler: routes/console.php schedules (renewal notice, campaign reminder, ExpireWellbeingPrograms).

Shared: `routes/web.php` + `layouts/app.blade.php` touched by groups 2–4 — append-only in own sections, merge serially.
