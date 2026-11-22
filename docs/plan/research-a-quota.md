# Research A — Monthly consultation quota + package system

Date: 2026-11-22. Scope: quota period model, ledger vs counter, no-show semantics, concurrency on SQLite, period keys/timezone. Context: prod DB = sqlite (file), tests = sqlite :memory:. Stack = Laravel.

---

## 1. Calendar-month vs rolling-30-day quota period

**Rolling 30-day** ("quota from each booking +30d"): per-employee window, no shared reset. HR reporting is painful: "November usage" is ill-defined — an event on Nov 22 may belong to the window started Oct 25. Every report becomes an approximation; two employees with identical behavior show different utilization.

**Calendar-month**: all employees reset at the same boundary (1st 00:00 company tz). Usage aggregates cleanly per `period_ym` — one GROUP BY answers "how many of Company X's 50 allotments were used in November". This matches how billing platforms model it: Stripe anchors each subscription to a discrete `current_period_start/end` and aggregates usage per invoice period (https://docs.stripe.com/billing/subscriptions/overview), and HR/monthly reporting is exactly the same shape: discrete periods, fixed boundaries.

Cost of calendar-month: edge loading (bookings on the 30th-31st, quota "resets" next day). Acceptable for wellness consults; HR prefers the clean month anyway.

**Recommendation: calendar month keyed on company timezone.**

## 2. Ledger (append-only events) vs decrement counter

Counter model: `quota_remaining INT`, `UPDATE ... SET quota_remaining = quota_remaining - 1`. Problems: no history (why is it 1?), no-show accounting requires reconstructing what happened, refunds mutate the number with no trace, double-charge from a retried request silently loses a credit, and audit ("show me employee Y's November events") is impossible without log archaeology.

Ledger model: one row per event, never UPDATEd. State of the world = fold over events. This is how the real systems do it:

- Stripe Credit Balance: "A credit grant is backed by an immutable, append-only ledger." Transactions are credits/debits against a grant; grants have states pending/granted/depleted/expired/voided; **voiding an invoice reinstates the applied balance**, credit notes require a *new* grant rather than mutating the old one (https://docs.stripe.com/billing/subscriptions/usage-based/billing-credits).
- Stripe usage records / meter events: timestamped usage events submitted with an idempotency key, aggregated at period end — the event stream is the source of truth, never edited (https://docs.stripe.com/billing/subscriptions/usage-based/recording-usage, https://docs.stripe.com/api/idempotent_requests).
- Stripe idempotency keys: server recognizes retries of the same request and returns the saved result instead of performing the operation twice (https://docs.stripe.com/api/idempotent_requests). The equivalent here: a `ref`/idempotency key column with a UNIQUE index so a retried booking request can't create two ledger rows.

Event types for this domain (mirroring the meeting notes' vocabulary):

| Event | Meaning | Quota effect (signed amount) |
|---|---|---|
| `reserved` | Booking created; holds 1 slot | -1 |
| `used` | Session completed | 0 (replaces reserved) |
| `released` | Employee cancelled outside cutoff, before session | +1 |
| `forfeited` | `no_show` or `expired` — hangus, TERHITUNG terpakai | 0 (reserved becomes terminal) |
| `refunded` | HR/superadmin grants the credit back | +1 |

Balance per (employee, period) = SUM of amounts. `forfeited` is terminal with zero delta — the -1 from `reserved` already consumed the slot, which is exactly the "no_show TERHITUNG terpakai" rule. Refund is a *new compensating event* with its own actor + reason, never a mutation — same shape as Stripe's "issue a new grant, don't mutate the old one".

Deriving "quota remaining" is a GROUP BY; with per-employee-per-month volumes (single digits) no materialized counter is needed. ponytail: if reporting gets slow, add a `quota_balances` summary table refreshed on write — upgrade path, not needed now.

**Recommendation: append-only `consultation_quota_events` ledger; balance always computed.**

## 3. No-show / cancel / reschedule semantics

Industry pattern for medical/therapy booking (widely documented across practice-management guidance; live pages mostly bot-walled so cited from the Stripe lifecycle semantics + standard practice): a cancellation window (typically 24h, sometimes 48h) before the appointment; cancelling inside the window, or not showing, is treated as a used session — the provider's time was consumed. Fees/policies vary, but the invariant is constant: **late cancel == no-show == consumed**, early cancel == free, reschedule is cancel + rebook.

Mapping (matches meeting notes):

- `cancelled` before cutoff → `released` (+1), employee keeps quota.
- `cancelled` within cutoff (late cancel) or `no_show` → `forfeited` (hangus), stays counted as used. No automatic refund.
- Reschedule = `released` + new `reserved`, only if reschedule happens before cutoff; late reschedule forfeits per the same rule.
- `expired` (session window passed without completion) → `forfeited`.
- Refund: never automatic. Explicit HR/superadmin action creating a `refunded` (+1) event with `actor_id` + `reason` recorded — audit trail in the ledger itself (mirrors Stripe's voiding-invoice reinstates-balance and new-grant-on-credit-note patterns, https://docs.stripe.com/billing/subscriptions/usage-based/billing-credits).

**Recommendation: forfeit on no_show/late-cancel/expiry; refund only via explicit privileged action logged as its own event.**

## 4. Concurrency: two employees of the same company booking the last slot

Classic pattern on Postgres/MySQL: wrap check-then-insert in a transaction with `SELECT ... FOR UPDATE` on the row being decremented (Laravel: `lockForUpdate()` — https://laravel.com/docs/12.x/queries#pessimistic-locking), plus `DB::transaction($fn, attempts: N)` to retry deadlocks (https://laravel.com/docs/12.x/database#database-transactions).

**SQLite breaks that pattern**: SQLite has no row locks at all — locking is per-database-file, five lock states, only one writer at a time (https://www.sqlite.org/lockingv3.html, https://www.sqlite.org/faq.html). `lockForUpdate()` is a no-op-ish on SQLite (grammar not truly enforcing row locks), and in WAL mode a deferred transaction upgrading to write can hit SQLITE_BUSY immediately, bypassing the busy handler. SQLite's own guidance: use `BEGIN IMMEDIATE` for any transaction that will write, and set `busy_timeout` (https://www.sqlite.org/wal.html).

Design that works identically on sqlite :memory: (tests) and sqlite file (prod):

1. **Unique constraint as the primary guard** (idiomatic everywhere, decisive on SQLite): the quota invariant becomes a uniqueness rule. Simplest: `UNIQUE(employee_id, period_ym, slot_no)` on the ledger table where `slot_no ∈ 1..allotment` is the package slot consumed — two concurrent reservations of the same company-period cannot claim the same slot. Alternative without slot numbers: partial unique index on active (non-compensated) `-1` events per employee capped by a CHECK/trigger — but slot_no is simpler and testable.
2. **Wrap in a transaction** anyway (both inserts: booking + ledger row are atomic). On Laravel+PDO sqlite, keep write transactions short; set `PRAGMA busy_timeout` (e.g. via connection config `busy_timeout` / options) so concurrent writers wait instead of erroring.
3. **Idempotency key** (`ref` column, UNIQUE) so request retries don't double-consume (Stripe pattern, https://docs.stripe.com/api/idempotent_requests).
4. On constraint violation (unique hit / SQLITE_BUSY beyond timeout): catch, return "quota exhausted / try again" — do NOT loop-retry business logic inside the request.

Notes on the Laravel knobs named in the brief: there is no `retry_until` queue config; queue-level is `retry_after` (connection lease) and job-level `retryUntil()` (https://laravel.com/docs/12.x/queues). `DB::transaction($fn, attempts:)` retries on deadlock exceptions — useful on MySQL, irrelevant on SQLite where the unique index settles contention deterministically. Keep `lockForUpdate()` out of the design; correctness comes from the constraint.

**Recommendation: transaction + unique(employee, period, slot) + unique idempotency ref; busy_timeout in connection config.**

## 5. Period key + timezone

- Store `period_ym` as TEXT `"YYYY-MM"` (e.g. `2026-11`) on every ledger event. Lexically sortable, directly groupable, stable in URLs/reports.
- Derive it from the *session start time* (the consultation's local date), not the booking time — the quota answers "which month's allotment was consumed by this session".
- Timestamps (`reserved_at`, `session_at`) stored as UTC (`created_at` convention already in Laravel). Convert to Asia/Jakarta only when computing `period_ym` and when rendering. Month boundary in Jakarta (UTC+7, no DST) = 17:00 UTC on the last day of the month. Deriving per-event: `$sessionAt->copy()->timezone('Asia/Jakarta')->format('Y-m')`.
- Company-level option later: per-company timezone. ponytail: hardcode `Asia/Jakarta` now; make it a company column when a non-WIB client appears — the derivation is one helper, so the upgrade path is a single function signature change.

**Recommendation: `period_ym` TEXT "YYYY-MM" derived from session time in Asia/Jakarta; all timestamps UTC.**

---

## Decision (recommendation)

1. **Period = calendar month**, keyed by `period_ym` TEXT `"YYYY-MM"`; derive from session start time in Asia/Jakarta; store all timestamps UTC.
2. **Ledger, not counter**: append-only `consultation_quota_events` (employee_id, period_ym, type, amount, ref, actor_id, reason, timestamps). Types: `reserved` -1, `used` 0, `released` +1, `forfeited` 0, `refunded` +1. Balance = SUM(amount). No UPDATEs, no deletes.
3. **Semantics**: cancel before cutoff → `released`; no_show / late cancel / expired → `forfeited` (counts as used, hangus); reschedule = released + re-reserved (before cutoff only); refund = explicit HR/superadmin `refunded` event with actor + reason, never automatic.
4. **Concurrency on SQLite**: UNIQUE(employee_id, period_ym, slot_no) on the ledger + UNIQUE(ref) idempotency key; insert booking + event inside one `DB::transaction`; set sqlite `busy_timeout` in connection config; catch constraint violations and surface "quota full". Skip `lockForUpdate()` entirely (no row locks in SQLite) — works identically on sqlite :memory: and prod file.
5. **Skipped for now**: materialized balance table (add if reports slow), per-company timezone (add when a non-WIB client signs), post-period reconciliation job (ledger already answers it via query).

## Sources

- SQLite locking model, no row locks, single writer: https://www.sqlite.org/lockingv3.html
- SQLite FAQ — concurrency, busy_timeout: https://www.sqlite.org/faq.html
- SQLite WAL — snapshot isolation, BEGIN IMMEDIATE, busy handling: https://www.sqlite.org/wal.html
- Laravel pessimistic locking (`lockForUpdate`, `sharedLock`): https://laravel.com/docs/12.x/queries#pessimistic-locking
- Laravel transactions + deadlock retry (`attempts`): https://laravel.com/docs/12.x/database#database-transactions
- Laravel queues — `retry_after` vs `retryUntil()` (no `retry_until` config): https://laravel.com/docs/12.x/queues
- Stripe idempotency keys (retry-safe request dedup): https://docs.stripe.com/api/idempotent_requests
- Stripe billing credits — immutable append-only ledger, grant states, void/reinstate/refund-as-new-grant: https://docs.stripe.com/billing/subscriptions/usage-based/billing-credits
- Stripe usage recording — timestamped events + aggregation per period: https://docs.stripe.com/billing/subscriptions/usage-based/recording-usage
- Stripe subscriptions — discrete billing periods anchored to period start: https://docs.stripe.com/billing/subscriptions/overview
- No-show / 24h-cancellation industry norm: general medical practice-management convention (standard late-cancel == no-show == consumed rule); live policy pages (SimplePractice, PatientPop, PsychologyToday) were bot-walled/404 at fetch time, so treated as domain knowledge rather than cited text.
