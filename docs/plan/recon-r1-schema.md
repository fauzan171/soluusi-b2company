# Recon R1 — Database Schema & Models (Soluusi B2B Klinik)

Repo: `/home/hermes/workspace/projects/soluusi-b2b-mission`, branch `feat/mission-package-quota` (last: `9e60184 fix(migrations): idempotent b2b alter + chat_messages create`; Laravel 9→12 upgrade in `ae8862f`). Read-only recon; no migrate/seed run. DB is SQLite in prod (see 2026_10_21_000001 comment).

All references relative to repo root. Migrations dir: `database/migrations/` (~123 files). Base model `app/Models/Model.php` is empty Eloquent subclass; all models `protected $guarded = ['id']` (mass-assignable except id) unless noted.

---

## 1. ERD — core B2B (perusahaan) tables

```
users ──< perusahaan_admins >── perusahaans ──< perusahaan_employees
  │                                │    │              │      │
  │ (associate_id FK)              │    │              │      └──< perusahaan_sessions (employee_id)
  └────────────────────────────────┘    │              │              │ │
                                        │              │              │ ├─ schedule_id ──> schedules
                                        │              │              │ ├─ consultant_id ─> consultants
                                        │              │              │ ├─ departement_id ─> departements
                                        │              │              │ └──< chat_messages (perusahaan_session_id)
                                        │              └──< perusahaan_assessments (employee_id)
                                        ├─< perusahaan_sessions (perusahaan_id)
                                        ├─< perusahaan_assessments (perusahaan_id)
                                        └─< consultant_pools (perusahaan_id, nullable)

schedules ──< booking_claims (schedule_id PK)      schedules ──< consultation_sessions (B2C/klinik)
     │
     └──< perusahaan_sessions / sekolah_sessions (schedule_id, nullable)

screening_questions (data bank, no FK)
screening_answers ──morph──> perusahaan_assessments | sekolah_assessments (assessable_type/assessable_id)
ai_insights (vertical + tenant_id, no FK; unique per tenant)
roster_invitations / program_consent_history (type + tenant_id/roster_id, no FK)
```

### perusahaans — `2026_08_13_000001_create_perusahaans_table.php:12-25`
| col | def |
|---|---|
| id | PK |
| name | string, legal name |
| brand | string, white-label brand |
| logo | string nullable |
| associate_id | FK users.id nullOnDelete (:17-18) |
| mou_at | dateTime nullable |
| pepm_rate | uint default 15000 (Rp/employee/month) (:20) |
| sessions_per_employee_month | tinyuint default 2 (:21) |
| employee_count | uint default 0 (:22) |
| nps_score | decimal(4,1) nullable — dummy MVP (:23) |
| is_active | bool default true (:24) |

Model `app/Models/Perusahaan/Perusahaan.php:20-41`: associate, admins, employees, sessions, assessments.

### perusahaan_admins — `2026_08_13_000002_create_perusahaan_admins_table.php:12-18`
perusahaan_id FK cascade, user_id FK cascade, **enum role ['hr_admin','employee'] default 'hr_admin'** (:16), unique(perusahaan_id,user_id) (:18). Comment: "mirror klinik_admins". Model `app/Models/Perusahaan/PerusahaanAdmin.php:15-21`.

### perusahaan_employees — `2026_08_13_000003_create_perusahaan_employees_table.php:14-27`
perusahaan_id FK cascade; departement_id FK departements nullOnDelete; **user_id nullable FK users nullOnDelete** (karyawan terdaftar dulu sebelum akun, :21-22); name; email nullable; **consent_at nullable (UU PDP)** (:25); index(perusahaan_id,departement_id) (:27). PII held by Soluusi, never shown on HR dashboard (migration header comment :7-8). Model `app/Models/Perusahaan/PerusahaanEmployee.php:20-41`.

### departements — `2023_11_09_153649_create_departements_table.php:16-21`
Legacy B2C table (typo'd name — intentional, do NOT rename): id, name, timestamps.

### users / roles
- roles: id, name (`2023_11_09_032135_create_roles_table.php:12-16`). Role names from `database/seeders/PerusahaanSeeder.php:27-30`: `hr_admin`, `employee` (plus legacy roles admin/consultant/associate used by route middleware `routes/web.php:96,111,131,145`).
- users: FK role_id (`app/Models/User.php:69-71` role()); is_active (`app/Models/User.php:39`, added `2024_05_10_035736`); firstname/lastname (fullname accessor `app/Models/User.php:164`); gender/dob/regency_id added later; phone removed `2024_12_02_231313`.

### consultants — `app/Models/Consultant.php:25-28` constants
STATUS: `ACCEPT='accept'`, `REJECT='reject'`, `PENDING='pending'`, `INTERVIEW='inteview'` (sic — typo'd constant value in code), `PAYMENT='payment'`. is_active added `2024_01_17_145724`. Link to users via `user_id` (`app/Models/User.php:79` hasMany consultants).

---

## 2. Naming conventions — mission plan vs codebase

Mission plan says "company"; **codebase uses `perusahaan`** everywhere for the CAP vertical:

| Concept | Exact code name |
|---|---|
| Company (tenant) | `perusahaans` table, `App\Models\Perusahaan\Perusahaan` |
| Employee | `perusahaan_employees`, `PerusahaanEmployee` (roster; NOT `users` with employee role — role `employee` on users is the login identity, linked via nullable `perusahaan_employees.user_id`) |
| Session | `perusahaan_sessions`, `PerusahaanSession` (NOT `consultation_sessions` — that is B2C/klinik) |
| Schedule | `schedules`, `Schedule` (shared across B2C klinik + perusahaan + sekolah) |
| Consultant assignment | `consultant_pools`, `ConsultantPool` (Soluusi-assigned pool per tenant) |
| HR admin pivot | `perusahaan_admins` with enum role `hr_admin` |
| School mirror | `sekolahs`, `sekolah_students`, `sekolah_sessions`, `sekolah_assessments` (Sekolah vertical, exact structural mirror — `2026_09_19_000001..000005`) |

Indonesian/English mix: tables/columns Indonesian (`perusahaan`, `departement`, `konsultan_type`, `kelas`, `tingkat`, `mou_at`), but `schedules`, `schedules.started_at`, `status`, `rating` are English. Column `konsultan_type` (Indonesian) inside an otherwise English column set — keep verbatim.

---

## 3. perusahaan_sessions — full columns + status values

Base `2026_08_13_000004_create_perusahaan_sessions_table.php:14-30`, extended `2026_10_05_000003_add_program_booking_boundaries.php:16-25`, status check fixed `2026_10_21_000001_fix_perusahaan_sessions_status_check_on_sqlite.php` (SQLite table rebuild — canonical final DDL at :26-58):

| col | def | source |
|---|---|---|
| id | PK | :15 |
| perusahaan_id | FK perusahaans cascade | :16 |
| employee_id | nullable FK perusahaan_employees nullOnDelete | :17-18 |
| departement_id | nullable FK departements nullOnDelete | :19-20 |
| consultant_id | nullable FK consultants nullOnDelete | :21-22 |
| konsultan_type | varchar default 'psikolog' — values `psikolog\|nutrisionis\|fisioterapis\|karir` (comment :23; validated `app/Http/Controllers/Perusahaan/EmployeeController.php:281` `in:psikolog,nutrisionis,fisioterapis,karir`; derived via `ConsultantPool::typeFromCategory` `app/Models/ConsultantPool.php:35-42`) | :23 |
| topic_category | varchar nullable, anonim (Stress, Kecemasan, Karir...) | :24 |
| status | enum/varchar CHECK in ('scheduled','done','no_show','cancelled') default 'scheduled' ('cancelled' added 2026_08_18_080001 + 2026_10_21_000001) | :25 |
| rating | tinyuint nullable 1-5, hidden from HR | :26 |
| completed_at | dateTime nullable | :27 |
| scheduled_at | dateTime nullable | :28 |
| timestamps | | :29 |
| schedule_id | nullable FK schedules (added 2026_10_05_000003:16) | |
| ended_at | timestamp nullable (:17) | |
| quota_consumed | bool default true (:18) | |
| cancel_reason | text nullable (:19) | |
| cancelled_at | timestamp nullable (:20) | |
| checked_in_at | timestamp nullable (:21) | |
| meeting_url | varchar nullable (:22) | |
| location | varchar nullable (:23) | |
| delivery_status | varchar default 'pending' (:24) — **no enum/check, and no writer found in app/** (grep zero hits; only migration defines it) | |
| no_show_party | varchar nullable (:25) — no writer in app/ either | |
| index | (perusahaan_id, status) | :30 |

Relations (`app/Models/Perusahaan/PerusahaanSession.php:22-47`): schedule (belongsTo Schedule), perusahaan, employee, departement, consultant, chatMessages (hasMany ChatMessage ordered by id).

Linking:
- **schedule/meeting**: `schedule_id` → schedules. `booking_claims` (PK schedule_id) maps a claimed slot to this row via owner_type/owner_id. `meeting_url`/`location` on the session mirror the schedule slot's values copied at booking time (`app/Services/BookingService.php:107-114`). Consultant can also self-assign a slot post-hoc: `app/Http/Controllers/Klinik/SessionController.php:105-122` `schedulePerusahaan` (only when status=scheduled AND scheduled_at null).
- **chat**: `chat_messages.perusahaan_session_id` — chat room IS the session row (migration header `2026_11_04_000001:7` "Room = sesi, bukan tabel baru"). End/rate: `app/Http/Controllers/ChatController.php:53-82` (employeeEnd sets status=done + completed_at; employeeRate sets rating 1-5).

Two creation paths, only one uses schedules:
1. `BookingService::book` (`app/Services/BookingService.php:85-118`) — slot-based: creates session from a schedules row with schedule_id/scheduled_at/ended_at, inserts booking_claims row.
2. Direct chat / request-consultation (`app/Http/Controllers/Perusahaan/EmployeeController.php:229-266` startChat, `:277+` requestConsultation) — creates PerusahaanSession with `scheduled_at = now()` and **NO schedule_id, NO booking_claims** (chat-first flow; consultant attaches schedule later via schedulePerusahaan).

---

## 4. schedules + booking_claims — who creates, semantics

### schedules
- Base: `2023_11_09_022656_create_schedules_table.php:11-16` (id, started_at, ended_at, timestamps only).
- consultant_id: added `2023_11_08/2023_11_09` relation migration (grep: `2023_11_09_160613_add_relation_each_table.php`).
- latest_updated_by: `2026_04_12_154351` (column `latest_updated_by` — note: also present in ScheduleController write `'klinik:'.auth()->id()` `app/Http/Controllers/Klinik/ScheduleController.php:88`).
- klinik_id, created_by, mode, status, location, meeting_url, notes: `2026_08_07_170200_add_klinik_fields_to_schedules_table.php:12-22`.
- **perusahaan_id, sekolah_id (nullable, indexed, NO FK constraint)**: `2026_10_05_000003_add_program_booking_boundaries.php:11-12`.

Constants (`app/Models/Schedule.php:21-24`): `MODE_ONLINE='online'`, `MODE_OFFLINE='offline'`, `STATUS_CONFIRMED='confirmed'`, `STATUS_CANCELLED='cancelled'`.

**Who creates**: only `app/Http/Controllers/Klinik/ScheduleController.php:82-93` (klinik admin UI, overlap-checked per consultant :73-80, sets klinik_id + created_by + status confirmed). Perusahaan slots (schedules rows with perusahaan_id, null klinik_id) are created **only in tests/seeders** — no admin UI found for creating perusahaan_id slots (`grep Schedule::create app/` → only ScheduleController + tests `tests/Feature/RequestConsultationTest.php:127,151`). Gap worth flagging in the mission plan.

### booking_claims — `2026_10_05_000003:28-32`
- `schedule_id` FK schedules **PRIMARY KEY** (one claim per slot).
- `owner_type` string(30) = vertical name: `'perusahaan'` or `'sekolah'` (written `app/Services/BookingService.php:64,115`; deleted on cancel :64).
- `owner_id` = perusahaan_sessions.id / sekolah_sessions.id.
Semantics: exclusive lock marking a schedule slot consumed by a program booking (parallel to B2C's consultation_sessions and klinik checkout_links). `BookingService::blocked` (`app/Services/BookingService.php:25-43`) treats any of {blocking consultation_sessions, booking_claims, unused checkout links, or perusahaan/sekolah sessions overlapping the same consultant} as blocking.

### Booking flow (`app/Services/BookingService.php`)
- `available()` :14-23 — slots where vertical_id matches, no klinik_id, consultant in pool, status confirmed, future, ended, not in booking_claims, not blocked.
- `book()` :85-118 — transaction; lockForUpdate on consultant + row + slot; aborts unless tenant active + employee consent (:97), slot belongs to tenant + no klinik + confirmed + future + valid pool consultant + consultant accept/active (:98-102); **quota check** (:103, see §7); blocked check (:104); create session + insert booking_claims (:107-115).
- `quotaUsed()` :45-52 — month window (startOfMonth of scheduled_at) on `$row->sessions()`; counts status scheduled/done, plus cancelled/no_show **only when quota_consumed=true**.
- `cancel()` :54-67 — late cancel = <2h before start (perusahaan) / <24h (sekolah) sets quota_consumed=true; deletes booking_claims row.
- `reschedule()` :69-83 — cancel old + book new.

---

## 5. Consultant ↔ perusahaan relation

**`consultant_pools`** — `2026_09_19_000006_create_consultant_pools_table.php:14-20`: consultant_id FK cascade, sekolah_id nullable FK cascade, perusahaan_id nullable FK cascade, unique(consultant_id, sekolah_id, perusahaan_id). One consultant can be in multiple tenants; a row with both tenant FKs null = global pool.

Model `app/Models/ConsultantPool.php`:
- `typeFromCategory()` :35-42 — maps consultant category name → `konsultan_type` (contains 'Gizi'→nutrisionis, 'Fisioterapi'→fisioterapis, 'Karir'→karir, default psikolog).
- `poolFor()` :49-63 — active pool for tenant: consultant status='accept' AND user is_active; returns [{id, name (users.fullname), category, type}].
- `validChoice()` :66-69 — consultant must be in tenant pool AND type match.

**No direct consultants↔perusahaans FK besides this pool.** Per-session assignment: `perusahaan_sessions.consultant_id` (nullable) — set at booking from the slot, or auto-assigned by lowest scheduled-load from pool (`app/Http/Controllers/Perusahaan/EmployeeController.php:295-296`). Legacy `consultant_companies` table (2024_05_11) is a CV/work-history pivot (start_at/end_at/position/description), NOT program assignment.

Pool admin UI: none found in controllers (pool rows only seeded / created via `EmployeeController` reads). `poolFor` consumed at `app/Http/Controllers/Perusahaan/DashboardController.php:49`, `EmployeeController.php:149,213,245,290`.

---

## 6. Availability/workhour concept

**None beyond `schedules` rows.** No workhours table, no recurring availability, no day-of-week template (grep `workhour|availab|day_` in migrations/models → only `jobs.available_at`). Availability = a consultant's concrete `schedules` rows (started_at/ended_at, created by klinik admin with overlap check `app/Http/Controllers/Klinik/ScheduleController.php:73-80`). `BookingService::available` derives bookable slots from these. Any "consultant sets own availability" mission feature is greenfield.

---

## 7. Quota: perusahaans.sessions_per_employee_month

Column: `2026_08_13_000001:21` (tinyuint default 2; sekolah mirror `sessions_per_student_month` `2026_09_19_000001:16`).

Enforcement points (all read-side checks, no DB constraint):
- `app/Services/BookingService.php:103` — hard abort 403 in `book()` via `quotaUsed()` (month of the SLOT date, not booking date).
- `app/Http/Controllers/Perusahaan/EmployeeController.php:287` — abort 422 in `requestConsultation()` (month of now, counts scheduled+done only — weaker than BookingService: ignores quota_consumed cancellations).
- Read-only display: `EmployeeController.php:73,142`, `RosterController.php:28`, `app/Services/AiInsightService.php:189`, `app/Services/DashboardMetrics.php:20`.
- Seeder default: `database/seeders/PerusahaanSeeder.php:49`.

`quota_consumed` flag on session (`2026_10_05_000003:18`) makes late-cancel (<2h) still consume quota (`BookingService.php:62-63`).

---

## 8. perusahaan_assessments — WHO-5

Base `2026_08_18_000001_create_perusahaan_assessments_table.php:13-24`: id, perusahaan_id FK cascade, employee_id nullable FK nullOnDelete, departement_id nullable FK, **answers JSON (5 items, 0-5)** (:20), **score tinyuint 0-100 = array_sum(answers) × 4** (computed `app/Http/Controllers/Perusahaan/EmployeeController.php:125`), taken_at dateTime, timestamps, index(perusahaan_id, taken_at).

Extended by `2026_10_21_000002_create_screening_tables.php:47-54`: `section` default 'mental_health' (values mental_health|nutrition_lifestyle|ergonomic), `status` default 'completed' (pending|in_progress|completed), `consultant_id` nullable (no FK), `notes` text, `conclusion` text, `concluded_at` timestamp, index(perusahaan_id, section, taken_at).

**BUT the runtime code only writes the WHO-5 path** (`EmployeeController.php:110-130`: validation `answers size:5, 0-5`, no section/status set). The section/status/consultant_id/conclusion columns are migration-only today (no writer). Band method `app/Models/Perusahaan/PerusahaanAssessment.php:21-24`: score ≥75 'Sehat', 50-74 'Perlu Perhatian', <50 'Berisiko Tinggi' (comment mentions low-threshold 28 → Berisiko, not implemented).

Sekolah mirror: `sekolah_assessments` (`2026_09_19_000005`) + same screening extension (`2026_10_21_000002:57-64`), student_id/kelas instead of employee_id/departement_id.

---

## 9. chat_messages — `2026_11_04_000001_create_chat_messages_table.php:16-23`

id; **perusahaan_session_id FK perusahaan_sessions cascade** (:18 — chat is perusahaan-only, no sekolah chat); sender_id FK users cascade (:19); body text; read_at nullable; timestamps; index(perusahaan_session_id, id) for polling (:23).

Model `app/Models/ChatMessage.php:13-19`: session() belongsTo PerusahaanSession, sender() belongsTo User. Controller `app/Http/Controllers/ChatController.php`: employee/consultant rooms (:17,36), canSend only while status scheduled (:137), polling via `after` param (:144). Chat ends via employeeEnd → status=done (:53-63); rating after (:70-82).

---

## 10. Screening tables + ai_insights

### screening_questions / screening_answers — `2026_10_21_000002_create_screening_tables.php`
- `screening_questions` :19-31: vertical (perusahaan|sekolah), section (mental_health|nutrition_lifestyle|ergonomic), code (Q5…Q34), text, type (freq0-4|pain4|yesno|single|multi|scale1-10), options JSON, reverse bool, ord, unique(vertical,code). Seeded `database/seeders/ScreeningQuestionSeeder.php:61`. Question bank is DATA, shared engine across verticals (migration header :7-13).
- `screening_answers` :35-43: **polymorphic assessable_type/assessable_id → PerusahaanAssessment|SekolahAssessment**, question_code, value text (raw int/string/JSON), other_text, answered_at, unique(assessable_type, assessable_id, question_code).
- **Relation to assessments: YES, by design** — answers morph to perusahaan_assessments rows; WHO-5 history maps to section 'mental_health' (:15-17 comment). **But no writer exists**: grep `screening_answers` in app/ → zero hits outside migrations. Only consumer is the demo `app/Http/Controllers/ProgressController.php:16` (progress page reads screening_questions, runs `ScoringService` on sample answers).

### ScoringService — `app/Services/Screening/ScoringService.php`
Bands :25-30: 0-24 low/0, 25-49 medium/1, 50-74 high/2, 75-100 critical/3 (consult = recommended consultations). Score = raw/max×100, max = ALL scored questions in section (unanswered = 0, prevents gaming, :50-62). Recommendation = MAX across domains (:17). Ported from kiosk TVS.

### ai_insights — `2026_10_24_000001_create_ai_insights_table.php:14-26`
Cache of AI-generated aggregate insight per tenant: vertical (perusahaan|sekolah), tenant_id (no FK), scope fixed 'tenant', input_hash 64 (stale detection), model, status (empty|running|completed|failed), result_json longText, error_code, started_at, generated_at, unique(vertical,tenant_id). Service `app/Services/AiInsightService.php`: lease generate (:64-77), aggregate-only prompt with strict no-PII rules (:175), input includes assessment band counts (:200-208) — **so ai_insights DO relate to assessments, via aggregate WHO-5 bands fed into the prompt**.

---

## Supporting tables (context)

- `roster_invitations` `2026_10_05_000001:9-22`: type/tenant_id/roster_id/email/token_hash unique/expires_at/revoked_at/consumed_at. Used by `app/Services/RosterInvitation.php`.
- `program_consent_history` `2026_10_05_000002:9-21`: type/tenant_id/roster_id/actor_id FK users/granted/evidence/agreed_at/recorded_at. Used by `app/Services/ProgramConsent.php`.
- B2C `consultation_sessions` `2023_11_09_155601_create_consultation_sessions_table.php:11-30`: schedule_id FK restrict, user_id nullable (guest Model C), consultant_packages FK, status enum (waiting|scheduled|done|canceled|no_show), order_number, payment/va_number, payment_status enum (waiting|verified|canceled|failed) +refunded (`2026_10_21_000000`), rating, order_at/payment_at; B2B additions `2026_08_05_000017` (klinik_id, checkout_link_id, guest PII, dual-confirm timestamps, check-in/no-show). `scopeBlocking` `app/Models/ConsultationSession.php:44`.
- Klinik vertical: kliniks, klinik_admins, checkout_links, split_settlements, hap_pervormas, markup_rules, consultant_fees, klinik_packages (`2026_08_05_000010-000017`, `2026_08_11_000001`).

## Gaps / gotchas for the mission plan

1. **schedule creation for perusahaan has no UI** — only klinik admin creates schedules (with klinik_id). Perusahaan slots (perusahaan_id set, klinik_id null) exist only in tests/seeders.
2. **Two divergent session-creation paths**: BookingService::book (schedule_id + booking_claims) vs startChat/requestConsultation (no schedule_id, scheduled_at=now, no claim, quota check weaker at EmployeeController:287).
3. `delivery_status`, `no_show_party`, and the screening extension columns (section/status/consultant_id/notes/conclusion/concluded_at) + `screening_answers` have **no writers** yet — schema ahead of code.
4. Quota semantics inconsistent: BookingService counts late-cancelled (quota_consumed) sessions; requestConsultation does not.
5. SQLite prod: enum = varchar+CHECK; the 'cancelled' fix required full table rebuild (`2026_10_21_000001`) — any future status addition must repeat that pattern for both DB drivers.
6. `Consultant::INTERVIEW = 'inteview'` typo is load-bearing constant value — do not "fix" casually.
