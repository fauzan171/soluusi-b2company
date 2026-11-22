# Recon R2 — Routes, Controllers, Middleware, Policies, Tenant Scoping

Scope: routes/web.php (206 lines), routes/api.php (19 lines), app/Http/Controllers/ (24 files), app/Http/Middleware/, app/Support/ context classes. READ-ONLY recon. All file:line refs relative to repo root.

Laravel version: Laravel 12-style structure but legacy (non-templated) Kernel at `app/Http/Kernel.php` — route middleware aliases live there, NOT in bootstrap/app.php.

## 0. Auth & role model (prereq)

- Single `users` table, `role_id` FK → `roles.name`. 9 roles seeded: `admin, consultant, customer, klinik_admin, associate, hr_admin, employee, bk, student` (`database/seeders/DatabaseSeeder.php:25`).
- Login: `POST /login` (throttle 10,1) — `AuthController::postLogin` (`app/Http/Controllers/AuthController.php:25`); Google SSO callback rejects unknown emails (no self-serve signup) `AuthController.php:63-68`.
- Post-login redirect by role: `AuthController::redirectByRole` (`AuthController.php:86-97`) — admin→admin.dashboard, klinik_admin→klinik.dashboard, associate→associate.dashboard, consultant→consultant.sessions, hr_admin→perusahaan.dashboard, employee→perusahaan.employee, bk→sekolah.dashboard, student→sekolah.student.

## 1. Full route inventory (routes/web.php)

Role middleware = `role:...` alias → `CheckRole` (see §3). All portal groups also carry `auth`.

### Public / guest (no auth)
| Method | Path | Name | Handler | Notes |
|---|---|---|---|---|
| GET | /booking/klinik/{klinik:uuid} | checkout.klinik | CheckoutController@clinic | web.php:14 |
| POST | /booking/klinik/{klinik:uuid}/pay | checkout.klinik.pay | CheckoutController@clinicPay | web.php:15 |
| GET/POST | /checkout/{token}(/pay, /dummy-payment, /dummy-payment/success, /status, /confirm-done) | checkout.* | CheckoutController | web.php:16-21 — Model C guest, token-based |
| GET/POST | /login, /auth/{provider}, /auth/{provider}/callback | login*, login.google | AuthController | web.php:24-31, logout auth-only |
| GET | /, /klinik, /sekolah, /perusahaan | landing* | LandingController / inline views | web.php:34-37 |
| POST | /lead | landing.lead | LandingController@storeLead (throttle 5,1) | web.php:38 |
| GET/POST | /daftar-mitra | partner.register* | PartnerRegisterController (store throttle 5,1) | web.php:43-44 |
| GET/POST | /daftar/{type} | selfregister.* | SelfRegisterController (signed + throttle 10,1) | web.php:63-64 — karyawan/siswa self-register against roster |
| GET | /progress | progress | ProgressController | web.php:67 (dev-status page) |

### Klinik portal — prefix /klinik, auth + role:klinik_admin,admin (web.php:47)
| Method | Path | Name | Handler | Extra mw |
|---|---|---|---|---|
| GET | /klinik/dashboard | klinik.dashboard | Klinik\DashboardController@index | — |
| GET | /klinik/consultants | klinik.consultants.index | Klinik\ConsultantController@index | klinik.active |
| GET | /klinik/consultants/{consultant} | klinik.consultants.show | Klinik\ConsultantController@show | klinik.active |
| GET/POST | /klinik/schedules, /schedules/create | klinik.schedules.* | Klinik\ScheduleController | klinik.active |
| GET/POST | /klinik/links, /links/create | klinik.links.* | Klinik\CheckoutLinkController | klinik.active |

### Perusahaan HR portal — prefix /perusahaan, auth + role:hr_admin,admin (web.php:96)
| Method | Path | Name | Handler |
|---|---|---|---|
| GET | /perusahaan/dashboard | perusahaan.dashboard | Perusahaan\DashboardController@index |
| GET | /perusahaan/dashboard/export | perusahaan.dashboard.export | DashboardController@export (CSV) |
| GET/POST | /perusahaan/dashboard/ai-insight | perusahaan.dashboard.ai-insight | DashboardController@aiInsight (JSON) |
| GET | /perusahaan/report | perusahaan.report | Perusahaan\ReportController@index |
| GET/POST | /perusahaan/employees | perusahaan.employees(.store) | Perusahaan\RosterController |
| POST | /perusahaan/employees/{employee}/consent | perusahaan.employees.consent | RosterController@consent |
| POST | /perusahaan/employees/invite | perusahaan.employees.invite | RosterController@invite (signed invite email) |

### Perusahaan employee (karyawan) portal — same prefix, role:employee,admin (web.php:107)
| Method | Path | Name | Handler |
|---|---|---|---|
| GET | /perusahaan/employee | perusahaan.employee | EmployeeController@index (dashboard: quota, history, WHO-5 trend) |
| GET/POST | /perusahaan/employee/assessment | perusahaan.assessment.form/.store | EmployeeController (WHO-5) |
| GET | /perusahaan/employee/consultants | perusahaan.consultants | EmployeeController@consultants (pool picker) |
| POST | /perusahaan/employee/consultants/{consultant}/start-chat | perusahaan.consultants.start-chat | EmployeeController@startChat (instant session) |
| GET/POST | /perusahaan/employee/booking | perusahaan.booking(.store) | EmployeeController (slot booking) |
| POST | /perusahaan/employee/request-consultation | perusahaan.request-consultation | EmployeeController@requestConsultation (Halodoc-style, auto-assign) |
| POST | /perusahaan/employee/sessions/{session}/cancel, /reschedule | perusahaan.booking.cancel/.reschedule | EmployeeController |
| GET/POST | /perusahaan/employee/sessions/{session}/chat | perusahaan.chat(.store) | ChatController@employee / @employeeStore |
| POST | /perusahaan/employee/sessions/{session}/end | perusahaan.chat.end | ChatController@employeeEnd |
| POST | /perusahaan/employee/sessions/{session}/rate | perusahaan.chat.rate | ChatController@employeeRate |

### Sekolah portal — prefix /sekolah-portal, auth (web.php:70)
BK (role:bk,admin, web.php:71): dashboard, dashboard/export, dashboard/ai-insight, report, students (RosterController index/store/refer/consent/invite).
Student (role:student,admin, web.php:83): /siswa (StudentController@index), assessment form/store, booking form/store, sessions/{session}/cancel, /reschedule. NO chat route for student (chat exists only for Perusahaan CAP).

### Consultant portal — prefix /consultant, auth + role:consultant (web.php:126)
| Method | Path | Name | Handler |
|---|---|---|---|
| GET | /consultant/sessions | consultant.sessions | Klinik\SessionController@index |
| POST | /consultant/sessions/{session}/check-in | consultant.sessions.checkIn | SessionController@checkIn |
| POST | /consultant/sessions/{session}/mark-done | consultant.sessions.markDone | SessionController@markDone (dual-confirm step 1) |
| POST | /consultant/sessions/{session}/schedule | consultant.sessions.schedule | SessionController@schedulePerusahaan (set jadwal for CAP requests) |
| GET/POST | /consultant/sessions/{session}/chat | consultant.chat(.store) | ChatController@consultant / @consultantStore |

NOTE: `{session}` is TWO different models on this prefix — `ConsultationSession` (klinik guest flow) for check-in/mark-done, `PerusahaanSession` (CAP) for chat/schedule. Route model binding by param name `session` differs per route (web.php:127-132).

### Associate (HAP) — prefix /associate, role:associate (web.php:136)
- GET /associate/dashboard → Associate\DashboardController@index
- POST /associate/pervormas/{pervorma}/request-payout → DashboardController@requestPayout

### Superadmin — prefix /admin, role:admin (web.php:142)
| Method | Path | Name | Handler |
|---|---|---|---|
| GET | /admin/dashboard | admin.dashboard | AdminDashboardController@index (ops-review queue, pending kliniks, revenue charts) |
| GET | /admin/referral-overview | admin.referral-overview | AdminDashboardController@referralOverview |
| GET | /admin/consultant-per-klinik | admin.consultant-per-klinik | AdminDashboardController@consultantPerKlinik |
| GET | /admin/pervorma-sessions | admin.pervorma-sessions | AdminDashboardController@pervormaSessions |
| POST | /admin/pervormas/{pervorma}/pay | admin.pervormas.pay | inline closure, web.php:148-155 |
| POST | /admin/kliniks/{klinik}/approve | admin.kliniks.approve | inline closure, web.php:158-163 (klinik provisioning/approval) |
| GET/POST/PUT/DELETE | /admin/kliniks/{klinik}/packages, /packages/{package} | admin.kliniks.packages.* | Klinik\KlinikPackageController (web.php:166-169) |
| GET/POST | /admin/kliniks/{klinik}/markups | admin.kliniks.markups.* | Klinik\MarkupRuleController (web.php:171-172) |
| POST | /admin/perusahaans/{perusahaan}/activate | admin.perusahaans.activate | inline closure, web.php:175-179 (CAP provisioning) |
| POST | /admin/sekolahs/{sekolah}/activate | admin.sekolahs.activate | inline closure, web.php:182-186 (sekolah provisioning) |
| POST | /admin/splits/{split}/settle | admin.splits.settle | inline closure, web.php:189-195 |
| POST | /admin/sessions/{session}/verify | admin.sessions.verify | inline closure calling SessionController::customerConfirm, web.php:198-205 (dual-confirm ops fallback) |

### API (routes/api.php)
- GET /api/user (auth:sanctum) — api.php:6
- POST /api/midtrans-callback, /api/midtrans-cancel — MidtransController (signature verified in controller) — api.php:10-11
- GET /api/midtrans/schedule-availability — MidtransController@checkScheduleAvailability — api.php:12

## 2. Employee/karyawan dashboard today

- Route `/perusahaan/employee` (web.php:108) → `Perusahaan\EmployeeController@index` (`app/Http/Controllers/Perusahaan/EmployeeController.php:63-93`): quota used/scheduled this month vs `perusahaan.sessions_per_employee_month`, last-10 session history, latest WHO-5 assessment + 6-point trend, `needsAssessment` flag.
- **Booking flow** (3 entry points):
  1. Slot booking: `bookingForm` (EmployeeController.php:133-153) lists pool consultants (`ConsultantPool::poolFor('perusahaan', id)`) + available slots (`BookingService::available('perusahaan', id)`) → `bookingStore` (php:181-195) → `BookingService::book` (quota enforced in service, `app/Services/BookingService.php:45-52`).
  2. Direct chat (Halodoc-style): `consultants` (php:198-229) → `startChat` (php:232-270) creates `PerusahaanSession` status=scheduled, scheduled_at=now, dedupes active session with same consultant, redirects to chat room.
  3. Request-consultation: `requestConsultation` (php:273-316) — quota check, auto-assign pool consultant with least scheduled load, creates session + first ChatMessage.
- **Chat access**: `GET/POST /perusahaan/employee/sessions/{session}/chat` (web.php:118-119) → `ChatController@employee/employeeStore` (`app/Http/Controllers/ChatController.php:17-33`); ownership check `session->employee_id === employee->id` (403). Polling 5s via same URL with `?after=<id>` (JSON, incremental, 200 max) — `ChatController::payload` php:144-158. Participant names never sent (anonymity).
- **Session list**: `$employee->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)` on dashboard (EmployeeController.php:74). Cancel/reschedule via web.php:116-117 with ownership abort (EmployeeController.php:159, 172). End + rate via ChatController (php:53-84).
- Employee POST actions additionally gated: `abort_unless($employee->perusahaan?->is_active && $employee->consent_at, 403)` for non-GET except cancel/start-chat (EmployeeController.php:46-48).

## 3. Tenant scoping mechanism

NO policies, NO gates. `app/Policies/` does not exist; `AuthServiceProvider::$policies` empty (`app/Providers/AuthServiceProvider.php:15-17`); `can:` middleware alias registered (Kernel.php:60) but unused in routes. Scoping is 100% role middleware + per-controller `abort_*` + context resolution:

1. **Role gate**: `role:` alias → `CheckRole` (`app/Http/Middleware/CheckRole.php:17-35`): 403 unless `user->is_active` and `user->role->name` ∈ allowed list. Registered at `app/Http/Kernel.php:66-67`.
2. **Tenant context resolvers** (per vertical, static `current()`):
   - `KlinikContext::current()` (`app/Support/KlinikContext.php:17-39`): klinik_admin → `$user->klinik` (via klinik_admins table); associate → first owned klinik; superadmin → `?klinik_id=` request param or `session('admin_klinik_id')`, default first active klinik (full cross-tenant switch).
   - `PerusahaanContext::current()` (`app/Support/PerusahaanContext.php:16-40`): hr_admin → membership via `perusahaan_admins` where user_id; **>1 membership = 403 "Membership ambigu"** (php:36); inactive tenant = 403 unless on dashboard route (php:38); superadmin switch via `?perusahaan_id=`.
   - `SekolahContext::current()` (`app/Support/SekolahContext.php:16-40`): identical mirror for `sekolah_admins`/bk.
3. **Explicit per-record ownership guards in controllers** (no global scoping middleware):
   - HR roster: `Perusahaan\RosterController` — context + `abort_unless($perusahaan …)` at php:20-21, 34-35, 51, 59; invite guard `emp->perusahaan_id === $perusahaan->id` php:64; department ownership check php:42.
   - Employee: `resolveEmployee()` EmployeeController.php:38-61 (single-membership rule php:44, consent+active gate php:46-48, superadmin GET-only preview php:53).
   - Chat: `ChatController` — employee ownership php:21/30/56/73; consultant ownership `$session->consultant_id === $consultant->id` php:39/47.
   - Consultant sessions: `SessionController::authorizeConsultant` php:98-102; `schedulePerusahaan` ownership php:107-108 + slot must belong to the consultant and be free php:113-118.
   - Klinik portal: `KlinikContext::current()` + `abort_unless($klinik, 403)` in ScheduleController.php:102-104, CheckoutLinkController.php:24-25/165-166, DashboardController.php:125-126; schedule ownership `$schedule->klinik_id === $this->klinikId()` CheckoutLinkController.php:91.
   - Employee/Student booking cancel/reschedule: `$session->employee_id === $employee->id` (EmployeeController.php:159,172) / `$session->student_id === $student->id` (Sekolah\StudentController.php:160,173).
4. **Pool scoping**: `ConsultantPool::poolFor($tenant, $tenantId)` (`app/Models/ConsultantPool.php:49-63`) — `where("{$tenant}_id", $tenantId)` + consultant accept/active; `validChoice()` php:68-71 for booking validation. Slot availability: `BookingService::available` scopes `Schedule::where($vertical.'_id', $tenantId)` (BookingService.php:17).
5. **Program activation gating** (soft feature gate): `klinik.active` middleware (`app/Http/Middleware/KlinikActive.php:11-18`) redirects inactive klinik to pending dashboard; Perusahaan/Sekolah enforce is_active inside Context/Controllers instead (PerusahaanContext.php:38, DashboardController.php:100-102 pending view).

## 4. Consultant portal (per brief)

Routes at web.php:126-133 (see table §1). Sessions list = `SessionController@index` (`app/Http/Controllers/Klinik/SessionController.php:19-37`): klinik guest `ConsultationSession` (paginate 20) **plus** CAP `PerusahaanSession` assigned to the consultant (limit 50) — both in one view `consultant.sessions`.

- Start/end session buttons (per brief):
  - Start = **check-in**: `POST /consultant/sessions/{session}/check-in` → `SessionController@checkIn` php:40-49 (only status=scheduled, sets `konsultan_checked_in_at`).
  - End = **mark-done** (dual-confirm step 1): `POST /consultant/sessions/{session}/mark-done` → php:52-70, stores confidential `note` + shareable `solution`, sets `konsultan_done_at`, calls `tryVerify` (php:82-96) which on both confirms → status done + `HapPervormaService::evaluate($session)` release. Customer confirm step 2 = guest token page → `SessionController::customerConfirm` (static, php:73-79). 24h no-confirm → `needs_ops_review` flag, ops manual verify via `admin.sessions.verify` (web.php:198-205).
  - For CAP chat sessions the "end" is employee-side only (`ChatController@employeeEnd` ChatController.php:53-67) — consultant has NO end-session route for CAP sessions.
- Chat (CAP only): web.php:131-132 → `ChatController@consultant/consultantStore`.
- Scheduling on behalf: `schedulePerusahaan` php:104-122 sets `schedule_id`+`scheduled_at` for CAP requests created without a slot (from `requestConsultation`).

## 5. HR portal routes

Full table §1 (web.php:96-106). Dashboard = aggregate-only metrics via `DashboardMetrics::build` + `ProgramAggregate::monthly` (`app/Http/Controllers/Perusahaan/DashboardController.php:96-148`), CSV export php:162-190 (AggregatePrivacy-sanitized), AI insight php:151-160 (AiInsightService). Roster: `Perusahaan\RosterController` — list employees, store (dept tenant-checked), record consent with provenance (`ProgramConsent::record`, RosterController.php:52), invite (signed self-register URL via `RosterInvitation::issue`, php:68).

**No scheduling-on-behalf-of-employees route exists for HR** — employees self-book only. HR refer capability exists only in Sekolah (`students.refer`, web.php:79); Perusahaan has no equivalent.

## 6. Superadmin routes

role:admin group web.php:142-206 (table §1). Provisioning:
- Klinik: `admin.kliniks.approve` (is_active+approved_at, web.php:158-163); packages CRUD web.php:166-169; markup rules web.php:171-172.
- Perusahaan (CAP): `admin.perusahaans.activate` web.php:175-179 — this is the perusahaan provisioning path (the "b41b1b4" item from the brief is NOT referenced anywhere in routes/controllers/code; no match in repo. It presumably refers to a brief/doc item outside this codebase, or a to-be-built perusahaan provisioning flow — currently only the activate closure exists).
- Sekolah: `admin.sekolahs.activate` web.php:182-186.
- Consultant management: NO dedicated superadmin consultant CRUD routes. Closest: `admin.consultant-per-klinik` reporting (AdminDashboardController.php:109+) and `admin.pervorma-sessions`. Consultant lifecycle (accept status, pool membership) is managed via DB/seeders (database/seeders/ConsultantSeeder.php, ConsultantPoolSeeder.php) and klinik-side ConsultantController views. **Gap: no superadmin UI for consultant management or per-company pool assignment.**
- Finance: pervormas.pay, splits.settle (inline closures).
- Ops: sessions.verify (dual-confirm fallback).

Superadmin cross-tenant access is via Context switch (`?klinik_id=/?perusahaan_id=/?sekolah_id=` + session persistence, §3) and role lists always include `admin` on every portal group (web.php:47, 71, 83, 96, 107).

## 7. Feature-flag / config gating already present

No feature-flag system (no spatie/laravel-feature, no Feature facade). Config-value gating only:
- `config/midtrans.php:9` `dummy_enabled` — dummy payment flow gate; `CheckoutController.php:452` allows dummy payment in local env OR flag.
- `config('midtrans.server_key')` blank check → skip payment provider (CheckoutController.php:201).
- `config/klinik.php` — HAP stage triggers/rates (env-driven, klinik.php:8-15), markup caps php:20-25, dual-confirm window `confirm_window_hours` php:28.
- `config/whitelabel.php` — brand defaults; per-klinik override in DB (whitelabel.php:33), used by email view composer (`app/Providers/AppServiceProvider.php:28-35`).
- `AiInsightService` gates on `services.ai_insight.key` presence (`app/Services/AiInsightService.php:58`).
- Activation gates as runtime flags: `kliniks/perusahaans/sekolahs.is_active` (§3 item 5) — effectively per-tenant feature flags enforced by middleware/controller aborts, not config.

## 5 impactful findings

1. **No policies/gates at all** — authorization is ad-hoc `abort_*` in 10+ controllers. Any new route must hand-roll its own ownership check; high risk of a missed guard (e.g. employee preview path allows superadmin GET of any employee data with `?employee_id=` across tenants — EmployeeController.php:53-58 has no tenant check on the preview lookup beyond Context default).
2. **`role:…` always includes `admin`** on every portal group (web.php:47,71,83,96,107,126 — note consultant group at 126 does NOT include admin; superadmin cannot preview consultant portal) — inconsistent: consultant portal is the only one superadmin can't enter.
3. **`{session}` binds two different models under `/consultant` prefix** (ConsultationSession vs PerusahaanSession, web.php:127-132) — fragile; adding routes with `{session}` there risks silent wrong-model binding.
4. **CAP session lifecycle asymmetry**: only employee can end/rate a chat session (ChatController.php:53-84); consultant mark-done applies only to klinik ConsultationSession. Consultant CAP sessions can never reach `done` except via employee action — quota and metrics depend on employee clicking end.
5. **Provisioning gaps vs brief**: no superadmin consultant management or per-company pool assignment UI (DB-only), no HR schedule-on-behalf, and "b41b1b4" is not implemented/referenced in code — only `admin.perusahaans.activate` exists as the CAP provisioning step.
