# Research B — Scheduling & availability for multi-company consultants

Context: sqlite prod, `php -S` single VPS, ~40 consultants, per-tenant `schedules` table exists (perusahaan_id + klinik_id nullable), booking_claims PK lock. Target scale: <100 consultants, <50k sessions.

## Q1. Availability modeling: rules + exceptions vs materialized slots

### How the big players do it

**Cal.com (open source)** — pure rules + computed-on-read, no slot rows:

- `Schedule` → `Availability[]`. Recurring weekly: `days Int[]` + `startTime`/`endTime` (TIME, `date` null). Date override: same table, `date @db.Date` populated — one table holds both rules and per-date exceptions ([schema.prisma](https://github.com/calcom/cal.com/blob/main/packages/prisma/schema.prisma)).
- `GET /v2/slots` returns "A map of available slots indexed by date" — computed per request from availability minus busy bookings; `bookingUidToReschedule` excludes the original slot from busy time so it reappears as free ([API v2 slots](https://cal.com/docs/api-reference/v2/slots)).
- Date overrides either set custom hours for one date or "mark themselves entirely unavailable"; past overrides auto-delete ([availability settings](https://cal.com/docs/atoms/availability-settings.md)). Create-schedule API: overrides require `date` + `startTime`/`endTime`, no unavailable flag in API schema ([create a schedule](https://cal.com/docs/api-reference/v2/schedules/create-a-schedule)).
- Idempotency: `idempotencyKey String? @unique` and `uid @unique` on Booking — cheap unique constraints, not slot locks.
- A separate "Reserve a Slot" hold API exists for the Booker UI (hold while user fills the form), but availability itself is always computed, never stored ([custom slot select flow](https://cal.com/docs/atoms/guides/custom-slot-select-flow.md)).

**Calendly** — same shape:

- `AvailabilityRule`: `type: "wday" | "date"`; `wday` rules required for all 7 days (empty `intervals` = unavailable that day); `date` rules are "optional overrides on top of the weekly rules"; times are 24h local to schedule timezone ([Calendly OpenAPI](https://developer.calendly.com/openapi/calendly-api.yaml)).
- `GET /user_busy_times` max range 1 week; "No free-slot endpoint. Compute free slots = schedule intervals minus busy times, client-side." Busy includes external calendars with "Check for conflicts".
- PATCH `rules` = full replacement (no partial edits).

**Fowler, "Recurring Events for Calendars"** ([PDF](https://martinfowler.com/apsupp/recurring.pdf)) — the classic pattern: composable temporal expressions (DayInMonth, RangeEachYear, set ops) behind a `Schedule` interface queried per date (`occurrences(Event, DateRange)`, `isOccurring(Event, Date)`); exceptions modeled as a `DifferenceTE` (e.g. "first/third Monday Apr–Oct minus state holidays"). Explicitly compute-on-read, bounded by the queried date range ("not from the epoch to the end of time").

### Tradeoffs at our scale

| | Rules + compute-on-read | Materialized slot rows |
|---|---|---|
| Storage | Tiny (40 consultants × ~5 rules) | 40 consultants × 60d × ~8 slots/day ≈ 19k rows, constantly regenerated |
| Consistency | One source of truth; leave edit = 1 row | Must regenerate on every rule/leave change or drift |
| Query cost | Per page-view: expand ~5 rules over 14–60d, subtract bookings — trivial at this scale | SELECT on indexed rows, also trivial |
| Race safety | Booking validity = rule check + overlap check at insert time | Slot row itself can act as lock target |
| Failure mode | Logic bug shows wrong slots everywhere until fixed | Stale rows after missed job/regeneration bug |

Verdict for <100 consultants: **compute-on-read wins**. Materialization only pays off at Calendly/Cal.com scale (they both rejected it), or when you need pre-reserved holds per slot. Our scale never needs that.

## Q2. Preventing cross-company double-booking (sqlite)

Consultant works for multiple companies → bookings from different tenants can collide on the same consultant.

- Postgres answer is `EXCLUDE USING gist (consultant_id WITH =, during WITH &&)` with `btree_gist` and half-open `[)` ranges — atomic, index-backed, skip triggers ([PostgreSQL btree_gist docs](https://www.postgresql.org/docs/current/btree-gist.html)). Not available in sqlite.
- sqlite: UNIQUE is equality-only and CHECK is per-row, so a plain `UNIQUE(consultant_id, started_at)` is insufficient — it only blocks same-start bookings, not 10:00–11:30 vs 10:30 overlaps.
- sqlite-correct approach ([SQLite transactions docs](https://www.sqlite.org/lang_transaction.html)): sqlite serializes all writers (one write transaction at a time); `BEGIN IMMEDIATE` takes the write lock up front, so a read-then-write overlap check inside that transaction cannot race. Pattern:

  ```sql
  BEGIN IMMEDIATE;  -- takes write lock before the SELECT
  -- SELECT count(*) FROM bookings WHERE consultant_id=? AND status='booked'
  --   AND started_at < :new_end AND ended_at > :new_start;
  -- if 0 → INSERT; else abort
  COMMIT;
  ```

  Half-open predicate `a.start < b.end AND a.end > b.start` allows adjacent bookings (10–11, 11–12). Set `PRAGMA busy_timeout`. The existing `booking_claims` PK lock already serializes per-booking; the overlap check rides the same single-writer guarantee.
- Belt-and-braces: keep a partial unique index if slot starts are always aligned to a grid (`CREATE UNIQUE INDEX ... ON bookings(consultant_id, started_at) WHERE status='booked'`) — free extra guard, still do the range check.
- Laravel: `DB::transaction(fn() => ..., attempts: 3)` inside a `beginTransaction()` that resolves to immediate mode (or `PRAGMA` via connection config / `DB::statement('BEGIN IMMEDIATE')` workaround).

## Q3. Timezone: store UTC, display Asia/Jakarta

- Store all `started_at`/`ended_at` as UTC. Cal.com's own API: `start` "must be UTC, aka without time zone" ([bookings API](https://cal.com/docs/api-reference/v2/bookings)). Calendly rules store local wall-clock times ("local to the timezone in the Availability Schedule", OpenAPI above) but everything crossing the wire as instants is UTC.
- Indonesia does not observe DST anywhere; Asia/Jakarta = WIB, fixed UTC+07:00 ([Wikipedia: Time in Indonesia](https://en.wikipedia.org/wiki/Time_in_Indonesia)). This removes the classic DST slot-generation hazards (23h/25h days, ambiguous local times) — wall-clock arithmetic is stable year-round.
- Carbon: `setTimezone` keeps the instant and relabels (12:00Z → 19:00+07), `shiftTimezone` keeps wall-clock digits and moves the instant — for UTC→WIB display use `setTimezone('Asia/Jakarta')` ([Carbon reference](https://carbon.nesbot.com/develop/reference.html)).
- Slot generation at week boundaries: since WIB is a whole UTC+7 offset with no DST, Jakarta Monday 00:00 == Sunday 17:00 UTC exactly, always. Generate slot instants by iterating local dates, constructing local wall-clock start times, then converting to UTC (`Carbon::parse($date.' '.$time, 'Asia/Jakarta')->setTimezone('UTC')` for storage). Keep Laravel app timezone UTC; convert only at the display edge and at rule-expansion time.

## Q4. Slot generation: rolling window job vs on-the-fly

- Neither Cal.com nor Calendly materializes slots (see Q1). Fowler's pattern explicitly bounds computation to the requested range.
- At 40 consultants: a page-view expanding 5 weekly rules × 60 days = ~300 candidate intervals per consultant, minus a single indexed query over bookings — single-digit milliseconds on sqlite. No job, no regeneration logic, no drift.
- Rolling window (30–60d via scheduled job) buys pre-computed rows but costs: a scheduler on a `php -S` single VPS (cron + `php artisan schedule:run`), regeneration triggers on every rule/leave edit, and stale-row risk if the job dies. We have no high traffic to justify it.
- Pick: **on-the-fly computation per consultant page-view**, cached in-process if needed. Rolling window only if slot pages become a measured hotspot or per-slot holds are required.

## Q5. Leave / exception modeling in existing apps

- Cal.com: date override = per-date replacement of the weekly pattern; either custom hours OR "mark themselves entirely unavailable"; past overrides auto-delete ([availability-settings](https://cal.com/docs/atoms/availability-settings.md), [create-a-schedule](https://cal.com/docs/api-reference/v2/schedules/create-a-schedule)).
- Calendly: `type: "date"` rules override weekly `wday` rules; empty `intervals` array = no availability that day — whole-day block is just the empty-interval degenerate case, no separate flag ([OpenAPI](https://developer.calendly.com/openapi/calendly-api.yaml)).
- Fowler: exceptions as set-difference from the recurrence (holidays subtracted), possibly with substitute days ([recurring.pdf](https://martinfowler.com/apsupp/recurring.pdf)).
- Consensus: **whole-day leave = one override row with empty/null hours, not a deleted/generated slot set**. Specific-slot exceptions = the same row with narrower hours. One table, two shapes.

## Recommendation (for soluusi-b2b)

1. Keep rules, don't materialize: `schedule_slots` table NOT needed. Compute slots on read from existing `schedules` (per-assignment weekly rules: weekday, start, end, slot_minutes, allowed consultation types).
2. Add `schedule_overrides (id, consultant_id, date, start_time NULL, end_time NULL)` — NULL/empty times = whole-day unavailability (Cal.com/Calendly pattern). Overrides win over weekly rules for that date; global per consultant (not per company) since leave is person-level.
3. Store `started_at`/`ended_at` UTC; expand rules in Asia/Jakarta wall-clock then convert; `setTimezone('Asia/Jakarta')` at display. No DST handling needed (fixed UTC+7).
4. Cross-company double-booking: overlap check (`start < :end AND end > :start`, half-open) inside `BEGIN IMMEDIATE` transaction — sqlite's single-writer serialization makes it race-free; optionally add partial `UNIQUE(consultant_id, started_at) WHERE status='booked'` as belt-and-braces.
5. Skip the rolling-window job and slot reservation holds (YAGNI at 40 consultants); revisit only if page-view latency measurably hurts or holds become a product requirement.

Sources:
- https://github.com/calcom/cal.com/blob/main/packages/prisma/schema.prisma
- https://cal.com/docs/api-reference/v2/slots
- https://cal.com/docs/api-reference/v2/bookings
- https://cal.com/docs/api-reference/v2/schedules/create-a-schedule
- https://cal.com/docs/atoms/availability-settings.md
- https://cal.com/docs/atoms/guides/custom-slot-select-flow.md
- https://developer.calendly.com/openapi/calendly-api.yaml
- https://www.postgresql.org/docs/current/btree-gist.html
- https://www.sqlite.org/lang_transaction.html
- https://martinfowler.com/apsupp/recurring.pdf
- https://en.wikipedia.org/wiki/Time_in_Indonesia
- https://carbon.nesbot.com/develop/reference.html
