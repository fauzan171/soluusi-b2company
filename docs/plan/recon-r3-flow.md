# Recon R3 — Consultation Booking Flow (End-to-End) & Mission Touchpoints

Scope: booking, session lifecycle, chat, quota, no_show/expiry — vertical **Perusahaan (CAP)** primarily (mission scope), klinik vertical for context. Base: `feat/mission-package-quota` = `upgrade/laravel-12` @ `9e60184`. READ-ONLY recon. All refs relative to repo root `/home/hermes/workspace/projects/soluusi-b2b-mission`.

Source-of-truth brief: `docs/brief-presentasi.md` (in the prod tree at `/home/hermes/workspace/projects/soluusi-b2b-klinik/docs/brief-presentasi.md`; NOT present in mission worktree docs/ — itself a gap, see §8).

## 1. Booking flow as implemented (Perusahaan / karyawan)

Route: `GET/POST /perusahaan/employee/booking` (`routes/web.php:130-131`), middleware `auth + role:employee,admin`.

1. **Dashboard** — `EmployeeController@index` (`app/Http/Controllers/Perusahaan/EmployeeController.php:63`): shows quota (used/done + scheduled this month vs `perusahaans.sessions_per_employee_month`), 10-session history (`EmployeeController.php:71-79`), WHO-5 trend.
2. **Booking form** — `EmployeeController@bookingForm` (`EmployeeController.php:133`):
   - Types dropdown hard-coded: `psikolog|nutrisionis|fisioterapis|karir` (`EmployeeController.php:147`).
   - Slots from `BookingService::available('perusahaan', $perusahaan->id)` (`EmployeeController.php:150`) — view `resources/views/perusahaan/booking.blade.php:60-70` renders flat `<select>` of all future slots (no type filter client-side; validity re-checked at book time).
3. **Slot source** — `BookingService::available` (`app/Services/BookingService.php:14-22`): `schedules` rows where `perusahaan_id = tenant`, `klinik_id NULL`, `status = confirmed`, future `started_at`, has `ended_at`, **not in `booking_claims`** (claim table: PK = `schedule_id`, `owner_type`/`owner_id` → session; migration `database/migrations/2026_10_05_000003_add_program_booking_boundaries.php:36-42`), then filtered by `blocked()` (overlap vs ConsultationSession, other booking_claims, live CheckoutLinks, and cross-vertical Perusahaan/SekolahSession overlap — `BookingService.php:25-43`).
4. **Schedules created by** klinik admin manually: `Klinik\ScheduleController@store` (`app/Http/Controllers/Klinik/ScheduleController.php:56-106`) — klinik-scoped (`klinik_id` set), with overlap check; **perusahaan-slot creation path**: slots for CAP are schedules with `perusahaan_id` + `klinik_id NULL` (seeder/manual), there is NO UI in the perusahaan/HR portal to create them (gap). `schedules` columns `mode`/`status`/`meeting_url` from `database/migrations/2026_08_07_170200_add_klinik_fields_to_schedules_table.php:14-18`.
5. **Book** — `EmployeeController@bookingStore` (`EmployeeController.php:181`) validates `konsultan_type` + `schedule_id` → `BookingService::book` (`BookingService.php:85-119`): transaction, `lockForUpdate` on consultant + employee row + slot, guards (program active, consent, slot tenant match, consultant pool `ConsultantPool::validChoice`, consultant ACCEPT+active), **quota check** (see §5), `blocked()` re-check, then `PerusahaanSession::create` with `status='scheduled'` + `booking_claims` insert (`BookingService.php:107-116`).
6. **Statuses on create**: `scheduled` (enum `scheduled|done|no_show` + later `cancelled`; `database/migrations/2026_08_13_000004_create_perusahaan_sessions_table.php:25`, cancelled added via `2026_08_18_080001` + `2026_10_21_000001` SQLite rebuild).
7. **Alternative entry points** (skip slot):
   - Halodoc-style instant chat: `EmployeeController@startChat` (`EmployeeController.php:232-271`) — creates session `status='scheduled'`, `scheduled_at=now()`, NO schedule_id, NO quota check (gap), dedup via active-session lookup (`EmployeeController.php:251-258`).
   - Request-consultation auto-assign: `EmployeeController@requestConsultation` (`EmployeeController.php:273-317`) — least-loaded consultant from pool, session `scheduled` with `scheduled_at NULL` + first chat message; quota checked inline (`EmployeeController.php:284-287`). Consultant later sets a slot: `Klinik\SessionController@schedulePerusahaan` (`app/Http/Controllers/Klinik/SessionController.php:107-123`).
8. **Cancel/Reschedule**: `EmployeeController@bookingCancel` (`EmployeeController.php:156`) → `BookingService::cancel` (`BookingService.php:54-67`): late-cancel cutoff 2h (perusahaan) / 24h (sekolah) decides `quota_consumed`; deletes booking_claims row. Reschedule = cancel + re-book (`BookingService.php:69-83`).

## 2. "3 jadwal per sesi" mechanism

**Does NOT exist in the mission base (`9e60184`).** Two related mechanisms exist only on branch **`improvement-live`** (prod working tree, NOT merged into `upgrade/laravel-12`):

- **Reschedule cap (ADR 001)**: `schedule_version` column + `config('booking.max_schedules_per_session', 3)` (`config/booking.php:54` on improvement-live); enforced in `SessionStateMachine::reschedule` (`app/Support/SessionStateMachine.php:53-90` on improvement-live) and `EmployeeController@bookingReschedule` (improvement-live `EmployeeController.php:307-311`). Meaning: max 3 schedule versions per session, 4th reschedule → 422.
- **Slot proposals (F1.2/F1.3)**: `session_slot_proposals` table (`app/Models/SessionSlotProposal.php` on improvement-live) — konsultan proposes **2-3 slot options** (`SlotProposalController@store`, validates `slots min:2 max:3`) for pending requests; employee/HR picks one → `EmployeeController@confirmSlot` books it.
- On mission base, the closest thing is the consultant "Set jadwal…" single-select dropdown on their sessions page (`resources/views/consultant/sessions.blade.php:60-68`) → `consultant.sessions.schedule` route (`routes/web.php:139`).

The brief's "3 jadwal" wording ("slot 15 Nov 09:00/10:00") does NOT describe a 3-offer mechanism in this base — it's just two open seed slots. Mission must decide: port the improvement-live `schedule_version`/proposal mechanism or build fresh.

## 3. Chat flow

- **Room = perusahaan_sessions row** (one thread per session). Table `chat_messages`: `perusahaan_session_id` FK, `sender_id` FK users, `body` text, `read_at` nullable (`database/migrations/2026_11_04_000001_create_chat_messages_table.php:16-23`). No attachment/thread table.
- Routes: employee `GET/POST /perusahaan/employee/sessions/{session}/chat` (`routes/web.php:132-133`), consultant `GET/POST /consultant/sessions/{session}/chat` (`routes/web.php:138-139`). Ownership guarded per side (`ChatController.php:17-50`).
- **Polling, not realtime**: vanilla JS `setInterval(poll, 5000)` (`resources/views/chat/room.blade.php:150`), incremental fetch `?after={lastId}` max 200 rows (`ChatController::payload` `ChatController.php:144-157`). JSON mode when `wantsJson`/`after` (`ChatController::room` `ChatController.php:86-101`). ponytail comment at `room.blade.php:2`: upgrade to SSE/broadcast when >50 active sessions.
- **canSend** = `status === 'scheduled' && consultant_id` (`ChatController.php:137-141`) — note base does NOT require `scheduled_at`; the brief tip `65e3e6e` adds `&& scheduled_at !== null` (gate chat until consultant ACCs a slot — commit `96795f9`, absent from base).
- **Start/End buttons**: only END exists in base, employee-side only: "Akhiri sesi" form (`room.blade.php:33`) → `ChatController@employeeEnd` (`ChatController.php:53-68`) sets `status='done'`, `completed_at`. Rating 1-5 after done (`ChatController@employeeRate` `ChatController.php:70-84`, form `room.blade.php:36-47`). The brief's "konsultan: Mulai sesi → Akhiri sesi" = commits `583b448` (`consultantStart` check-in / `consultantEnd`, routes `chat.start`/`chat.end` on `routes/web.php:133-134` of `65e3e6e`) — **missing from mission base** (see §8).
- First message triggers email to peer (`ChatController::save` `ChatController.php:103-134`).

## 4. Session lifecycle statuses (Perusahaan)

`perusahaan_sessions.status`: `scheduled` → `done` | `cancelled` ( | `no_show` enum value, unused in base).

Transitions in the mission base:

| Transition | Who | Where |
|---|---|---|
| create `scheduled` | employee (book/start-chat/request) | `BookingService::book` `BookingService.php:107`; `EmployeeController.php:265, 305` |
| → `done` | employee ends chat | `ChatController::employeeEnd` `ChatController.php:53-68` |
| → `done` | **no consultant path in base** (only on `65e3e6e`: `consultantEnd`) | — |
| → `cancelled` | employee cancel/reschedule | `BookingService::cancel` `BookingService.php:63` |
| `no_show` | **nobody** — value never set anywhere in base |
| check-in | `checked_in_at` set by consultant on klinik ConsultationSession only (`SessionController@checkIn` `app/Http/Controllers/Klinik/SessionController.php:33-42`); perusahaan `checked_in_at` column exists (`2026_10_05_000003:21`) but never written in base |

Klinik vertical (ConsultationSession) has the richer machine: statuses `waiting/scheduled/done/canceled/no_show` (`app/Models/ConsultationSession.php:13-17`), dual-confirm: consultant mark-done w/ note+solution (`SessionController@markDone` `app/Http/Controllers/Klinik/SessionController.php:50-67`) → customer confirm (`customerConfirm` `SessionController.php:70-76`, called from checkout status page + ops manual verify `routes/web.php:203-211`) → `tryVerify` → `done` + HAP pervorma release (`SessionController.php:79-95`).

**Cron** (`app/Console/Kernel.php`):
1. `everyFiveMinutes` — Midtrans payment expiry sweep (`Kernel.php:19-21`) → ConsultationSession payment cancel only, no effect on PerusahaanSession.
2. `hourly` — dual-confirm 24h fallback: flags `needs_ops_review` on ConsultationSession (`Kernel.php:25-30`). Explicitly "tidak auto-complete".

**Nothing scheduled touches perusahaan_sessions in the base.** (On `improvement-live`: `sessions:cancel-expired` hourly + `chat:purge-ended` daily + more — see §6/§8.)

## 5. Quota check today (`sessions_per_employee_month` = 2)

- Column: `perusahaans.sessions_per_employee_month` default 2 (`database/migrations/2026_08_13_000001_create_perusahaans_table.php:21`); seeded 2 (`database/seeders/PerusahaanSeeder.php:49`).
- **Enforced at booking**: `BookingService::book` `BookingService.php:103` — `quotaUsed() >= sessions_per_employee_month` → 403. `quotaUsed` (`BookingService.php:45-52`) counts sessions in the month **of the slot's date** (not current month) with status scheduled/done, or cancelled/no_show with `quota_consumed=true`.
- Also inline in `requestConsultation` (`EmployeeController.php:284-287`) — but counts current month with scheduled/done only.
- **NOT enforced in `startChat`** (instant chat path, `EmployeeController.php:232-271` — no quota check; only dedup). Gap.
- UI pre-computes `quotaLeft` (`EmployeeController.php:140-141`, disables submit at ≤0 `booking.blade.php:28, 90`) — display only, real gate is the 403.
- Per-tenant flat value on the tenant row. No package, no ledger, no per-employee allocation, no balance carry-over — mission item (a).

## 6. no_show / expired handling today (mission base)

- `no_show`: enum value exists on perusahaan_sessions + ConsultationSession; `no_show_party`, `delivery_status` columns exist (`2026_10_05_000003:24-25`); **no code path ever sets it** in the base. Only read for quota counting (`BookingService.php:51`) and dashboards (`AdminDashboardController.php:117`, `ProgramAggregate.php:54`, `AiInsightService.php:198`).
- Expired sessions: **nothing automatic** in base. A `scheduled` session whose time passes stays `scheduled` forever (chat stays open — `canSend` only needs status). Only ConsultationSession payment expiry + dual-confirm ops flag run on cron.
- Late-cancel quota burn is the only "missed session" consequence (`BookingService.php:62-63`).
- For contrast, `improvement-live` has `sessions:cancel-expired` (hourly): expired-no-check-in → auto-cancel + quota restore; stale pending request 72h → cancel; zombie checked-in >4h past schedule → auto-`done` + pervorma; plus `chat:purge-ended` daily hard-delete of chat on done sessions ≥24h. None of this is on the mission base.

## 7. Mission touchpoint list (files to change)

Mission = `feat/mission-package-quota` (package + quota ledger + request lock + auto schedules + 3 consultation types + employee dashboard + notes/results/reviews).

| # | Mission feature | Files needing changes |
|---|---|---|
| a | **Package + quota ledger** (package balance per perusahaan/employee instead of flat `sessions_per_employee_month`) | New migration (ledger table + `klinik_packages`-style per-tenant package or extend `perusahaans`); `app/Services/BookingService.php:45-52, 103` (quotaUsed → ledger lookup); `EmployeeController.php:73, 140-141, 284-287` (display + gates); `startChat` path `EmployeeController.php:232-271` (add missing check); views `resources/views/perusahaan/employee.blade.php`, `booking.blade.php:15-17, 28, 90`; HR dashboard `app/Http/Controllers/Perusahaan/DashboardController.php` + `resources/views/perusahaan/dashboard.blade.php`; super-admin provisioning (on brief tip: `app/Http/Controllers/AdminPerusahaanController.php`, absent in base); `app/Services/DashboardMetrics.php:20`, `AiInsightService.php:189` (quota metric sources) |
| b | **Consultant request lock** (request → locked to a consultant until resolved) | `EmployeeController@requestConsultation` (`EmployeeController.php:273-317`); `ConsultantPool` assignment logic; likely new column/migration on `perusahaan_sessions` (e.g. lock/assignment state, cf. improvement-live `SessionStateMachine::assign`); `Klinik\SessionController@schedulePerusahaan` (`SessionController.php:107-123`); consultant view `resources/views/consultant/sessions.blade.php:40-70` |
| c | **Auto-filled consultant schedules** (system generates slot offers instead of manual klinik-created schedules) | `BookingService::available/blocked` (`BookingService.php:14-43`); schedule creation currently only klinik-side `Klinik\ScheduleController@store` (`ScheduleController.php:56-106`) — needs a generator service + new scheduler command in `app/Console/Kernel.php`; reference design: improvement-live `SlotProposalController` + `SessionSlotProposal` model |
| d | **3 consultation types split (online_meeting / chat / one_on_one)** | `perusahaan_sessions.konsultan_type` (`2026_08_13_000004:23`) is consultant discipline — needs new delivery-mode column; `EmployeeController@bookingStore` validation (`EmployeeController.php:182-190`); types list `EmployeeController.php:147`; booking form `booking.blade.php:40-70`; `ChatController` gating (`ChatController.php:137-141`) — chat type vs meeting type; `schedules.mode` (`2026_08_07_170200:14`) already online/offline; session view `consultant/sessions.blade.php`; sekolah parity `Sekolah\StudentController` (same BookingService) |
| e | **Employee "Sesi Saya" dashboard** (own sessions hub: upcoming/past/notes/results) | `EmployeeController@index` (`EmployeeController.php:63-93`); `resources/views/perusahaan/employee.blade.php` (already has history + chat links `employee.blade.php:225, 242, 302-303`; needs dedicated "Sesi Saya" section — note: nav label "Sesi Saya" today is the CONSULTANT's page, `resources/views/layouts/app.blade.php:28`); chat back-links `room.blade.php:9` |
| f | **Notes / results / reviews** (consultant writes session output; employee sees result; review beyond star rating) | `perusahaan_sessions` needs note/result columns (migration; today only `rating` `2026_08_13_000004:26`); consultant side: new write path + view (`consultant/sessions.blade.php`, cf. klinik `markDone` note/solution pattern `SessionController.php:54-60`); employee side display in session detail (`employee.blade.php` detail modal ~`:302`); privacy constraint: HR must NOT see content (per `2026_08_13_000004` migration comments + rating NPS comment) — keep out of `DashboardController`/`ProgramAggregate.php:54` |

Cross-cutting: tests `tests/Feature/ChatTest.php`, `PerusahaanWellbeingTest.php`, `RequestConsultationTest.php`, `ConsultantPickerTest.php` all encode current flow semantics and will need updates; `app/Console/Kernel.php` for any new sweeps.

## 8. Gaps: brief-presentasi.md vs mission base code

- **Base is 16 commits behind the brief tip.** Brief says "sudah di-push sampai commit `65e3e6e`", but mission branch base is `9e60184`; `9e60184..65e3e6e` = 16 commits (verified in this worktree's git: `b41b1b4` super-admin perusahaan provisioning, `583b448` consultant start/end chat session controls, `ab154d4` HR active-session filter, `65e3e6e` chat sender-name fix, quota anti-spam `b6116ad`, chat gate `96795f9`, ended_at-NULL overlap fix `0d1d1a9`, plus F1-F11 UX fixes). Demo flow items 2 (Mulai/Akhiri sesi konsultan) and 4 (super admin perusahaan provisioning) **do not exist in the mission base**.
- **Test count mismatch**: brief claims 66/66 (321 assertions); base runs 52 passed (per PROGRESS.md; R5 investigating).
- **"3 jadwal per sesi"**: not in base at all; exists in two different shapes on `improvement-live` (schedule_version cap + slot proposals), which itself is NOT an ancestor of `65e3e6e` (diverged after `9e60184`... actually improvement-live descends separately; 433 commits incl. F1.4 sweep, chat purge, CSV import, HR review flow). Mission must pick which lineage to build on — brief describes `65e3e6e` (no 3-jadwal mechanism).
- **Quota not enforced on instant-chat path** (`startChat`) in base — even though brief demo touts chat as newest feature.
- **No automatic no_show/expiry in base** — `no_show` never written by any code; scheduled sessions never auto-close. (improvement-live has the full sweep; brief doesn't mention it.)
- **Slot supply for CAP has no UI**: perusahaan-scoped schedules (`perusahaan_id` set, `klinik_id` NULL) only exist via seed/manual insert; only klinik admins have a schedule UI, and it writes `klinik_id`-scoped rows that `available()` explicitly excludes (`BookingService.php:15` `whereNull('klinik_id')`).
- **brief-presentasi.md itself is not in the mission worktree docs/** — lives in the prod tree (`/home/hermes/workspace/projects/soluusi-b2b-klinik/docs/brief-presentasi.md`).
- Minor: brief table names confirmed (`schedules.started_at`, `perusahaan_sessions.konsultan_type`, `booking_claims` shape) — accurate vs base.

## 9. Five most impactful findings

1. **Wrong/short base**: mission branched at `9e60184` but brief tip is `65e3e6e` — consultant start/end session, HR active-session table, admin perusahaan provisioning, and 14 other fixes are missing. Rebase or cherry-pick before building.
2. **Quota hole**: `EmployeeController@startChat` (instant chat) bypasses the quota gate entirely — any mission quota/ledger design must close this path, not just `BookingService::book`.
3. **No lifecycle automation for perusahaan sessions**: nothing sets `no_show`, nothing expires `scheduled` sessions, cron only serves klinik payments/dual-confirm. Mission's auto-filled schedules + lifecycle needs a new scheduler entry in `app/Console/Kernel.php` (design exists on improvement-live `sessions:cancel-expired` to port).
4. **The "3 jadwal" mechanism is ambiguous**: base has none; improvement-live has BOTH a reschedule cap (`schedule_version`, max 3) AND consultant 2-3 slot proposals (`session_slot_proposals`). Decide which the brief means before designing mission item (c).
5. **CAP slot supply is manual/seeded**: no UI or generator creates perusahaan-scoped `schedules`; mission item (c) "auto-filled consultant schedules" is greenfield on this base — no existing generator to extend.
