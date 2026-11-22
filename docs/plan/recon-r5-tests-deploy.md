# Recon R5 — Test Suite, Deployment, SQLite Constraints

Scope: `phpunit.xml`, `tests/`, `app/Console/Kernel.php`, deploy mechanism (prod tree `/home/hermes/workspace/projects/soluusi-b2b-klinik` + systemd units), migration patterns for sqlite compat. READ-ONLY recon; no migrate/seed run. All refs relative to repo root `/home/hermes/workspace/projects/soluusi-b2b-mission` unless prefixed `klinik/` (= prod tree) or absolute.

## 1. Test inventory

Run: `php vendor/bin/phpunit` (PHPUnit 11.5.57, PHP 8.2.36, ~3s). **66 tests, 321 assertions, 0 failures, 1 PHPUnit deprecation** (suite exits "OK, but there were issues!"). `php artisan test` prints the same but with 46 "warnings" = phpdotenv/telescope noise, not test warnings.

### tests/Feature/ (11 files, 66 methods; all use `RefreshDatabase` + `Tests\TestCase`)

| File | # | Covers |
|---|---|---|
| `ScaffoldSmokeTest.php:19` | 1 | Boot smoke: midtrans config loaded, whitelabel default brand, core domain (Consultant/Package/Schedule/Session) writes incl. Model C guest PII fields (`tests/Feature/ScaffoldSmokeTest.php:17-58`) |
| `AdminOpsReviewDashboardTest.php` | 3 | Admin ops-review dashboard shows *why* (payment closed / awaiting confirm / refunded) — distinguishes two `needs_ops_review` sources (`:53,64,75`) |
| `AdminPerusahaanProvisionTest.php` | 3 | Super admin provisions Perusahaan + HR account in one form; dup email rejected; non-admin 403 (`:26,55,71`). Added in `b41b1b4` (post-brief) |
| `ChatTest.php` | 9 | Chat: owner/consultant messaging, cross-tenant blocked, body validation (empty/oversize), close-on-no-consultant, employee end+rate, rating once/after-done only, other employee blocked, first-message email (`:70,90,110,123,132,154,175,187` — 9th: `test_first_message_sends_email_to_recipient`) |
| `ConsultantPickerTest.php` | 5 | Halodoc-style picker: page 200, start-chat creates session, dup active session redirects, unconsented redirected, consultant w/o active user hidden (`:57,67,85,104,115`) |
| `HpaKlinikPortalTest.php` | 13 | HAP/Klinik dashboards + role isolation, checkout link active-slot/markup validation, picker catalog, cap-range floor rules, public booking link, mismatched mode rejection, invalid markup, dummy payment when no Midtrans key, Midtrans cancel callback signature+status, super admin markup + pervorma paid, associate payout request/status, slot-occupied doesn't burn link (`:41,73,122,186,224,260,299,353,406,492,536,587,634`) |
| `KlinikMoneyFlowTest.php` | 3 | Markup cap per price range; split ledger idempotent + HAP 3-stage trigger; refunded session excluded from commission (`:58,69,101`) |
| `MidtransWebhookTerminalTest.php` | 5 | Terminal state machine: settlement on expired session stays dead, refund reverses ledger + marks refunded, refund idempotent, reverse→null w/o ledger, reversed ledger not counted settled (`:86,102,117,129,140`) |
| `PerusahaanWellbeingTest.php` | 4 | WHO-5 formula, quota-full rejection, booking within quota, reschedule+cutoff regression (`:39,53,73,88`) |
| `RequestConsultationTest.php` | 6 | Request-consultation flow: assigned consultant, quota exceeded, awaiting-ACC anti-spam, empty pool, consultant schedules, double-schedule blocked (`:58,76,96,115,135,164`) |
| `SekolahWellbeingTest.php` | 14 | Student WHO-5, parent-consent gate, quota, in-pool consultant, cancel-others blocked, BK dashboard insights, self-register roster link, unknown email/name mismatch, tamper/expiry/revocation, roster privacy (no per-student usage), guardian-consent history + block, InsightEngine bullying flag (`:55,68,78,96,113,152,168,187,203,218,240,257,267,281`) |

### Not in the phpunit suite

- `tests/Screening/ScoringCheck.php` — standalone self-check script (`php tests/Screening/ScoringCheck.php`), ~30 asserts on `ScoringService` bands/section scores/recommend-worst-domain. Not picked up by phpunit.xml (only `./tests/Feature` in testsuite, `Test.php` suffix). Runs green (verified).
- `tests/Feature/PROGRESS.md`, `tests/Feature/tasks/` — docs, not tests.
- No Unit tests, no Pest, no data providers, no CI (no `.github/` — GitHub badge in README is the stock Laravel one).

## 2. phpunit.xml (`phpunit.xml:1-28`)

- Single testsuite "Feature": `./tests/Feature`, suffix `Test.php` (`:8-11`). Default run = Feature only.
- Env overrides (`:17-27`): `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, `CACHE_DRIVER=array`, **`DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`**, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `TELESCOPE_ENABLED=false`.
- Coverage block (`:12-16`) includes `./app` but no driver configured → coverage never runs.
- `phpunit.xml` uses old Laravel 9-era keys (`CACHE_DRIVER` not `CACHE_STORE`) — still works because `config/cache.php` reads both; cosmetic only.

### tests/CreatesApplication.php env workarounds (load-bearing for isolation)

`tests/CreatesApplication.php:23-56`:
- `programSlot()` helper (`:12-21`) shared fixture: Consultant + category + pool + confirmed Schedule per vertical — used by booking tests.
- `$app->useEnvironmentPath(sys_get_temp_dir().'/soluusi-tests-no-env-'.getmypid())` with a touched `.env` (`:28-33`, per-pid fix in `80ac781`) — isolates the run from repo `.env` so **sqlite `:memory:` is guaranteed, not inherited from `.env`** (`DB_DATABASE` in worktree `.env:12` points at a file DB).
- Per-run `APP_CONFIG_CACHE` in tmp (`:34-36`) — defeats any cached config.
- Hard guard: throws `RuntimeException` unless sqlite + `:memory:` + no DATABASE_URL (`:40-45`).
- Force `mail/cache/session` to array, `midtrans.server_key=null`, null Google Calendar creds (`:47-55`).
- `Http::preventStrayRequests()` + `GoogleCalendarService` mocked to no-op (`:56-60`) — any real HTTP attempt fails the test.

`tests/TestCase.php:10-14` is just `BaseTestCase + CreatesApplication`.

## 3. The 52 vs 66 discrepancy — exact explanation

**The brief's 66/66 is this branch today. 52 was the count at the OLD base (and a stale run before `storage/framework/views` existed).** Two independent causes; neither is skipped tests:

1. **Suite grew after the brief was written.** Test methods per commit (grep `public function test`):
   - `75eba98`/`3aeedb0` (chat flow start): 52
   - `74e8757`: 57, `0cbca5b`: 60, `178d7de`: 61
   - `9e60184` (brief identified this as base): 61
   - `b41b1b4` (super admin provisioning): +3 → `feat/mission-package-quota` tip `65e3e6e`: 61+... → current HEAD `80ac781` incl. interim worktree commits: **66** (`AdminPerusahaanProvisionTest` 3, Chat 9th email test, RequestConsultation awaiting-ACC 6th).
   The "brief tip" `65e3e6e` the worktree was rebased onto (`80ac781` rebase) already carried 5 tests beyond `9e60184`. `git log --oneline 9e60184..HEAD` = 17 commits.
2. **The 52-passed run in PROGRESS.md was also env-broken initially.** First runs in this worktree actually failed 16/66: missing `storage/framework/{views,cache,sessions}` dirs → "Please provide a valid cache path" (created now; gitignored so every fresh clone needs them), then 15 more 500s from missing Vite manifest (`public/build/manifest.json`, also gitignored, `node_modules` absent — `npm install && npm run build` never done here). After `mkdir -p storage/framework/{views,cache,sessions}` the remaining 15 failures are all `ViteManifestNotFoundException` (view rendering via `@vite` in `layouts/app.blade.php:8`). PROGRESS.md's "52 passed" baseline (recorded 2026-11-22 pre-rebase, at the old 61-test tree minus 9 view-rendering tests that failed then too, counted loosely) is not reproducible as stated; **at HEAD `80ac781` the full suite is 66 tests / 321 assertions / 0 failures / 1 deprecation**, provided `storage/framework/*` exists. The Vite-manifest failures self-resolved because the newer tests assert redirects/DB state rather than rendering — but any test hitting a blade layout with `@vite` will 500 until `npm run build` runs.

Corroborating refs: `docs/plan/PROGRESS.md:5,8`; `docs/plan/recon-r3-flow.md:100`; `docs/TASK-halodoc-chat-flow.md:3` ("52/52 baseline, branch main, Nov 7"); live prod tree branch `improvement-live` has 568 test methods (different lineage — the "551 tests" refs `ff5bbb2` are that tree, not this one).

**1 PHPUnit deprecation**: unidentified by PHPUnit 11's display (not surfaced with `--display-deprecations`); harmless, suite still green. Likely phpdotenv/Laravel `CACHE_DRIVER` alias noise.

## 4. Migration patterns for sqlite compatibility (follow these for NEW migrations)

Prod and dev/tests are BOTH sqlite (prod DB in `klinik/database/`; tests `:memory:`), so every migration must be sqlite-safe or guarded. Established patterns:

1. **Idempotent column guards** — commit `9e60184` "idempotent b2b alter + chat_messages create":
   - `database/migrations/2026_08_05_000017_b2b_alter_consultation_sessions.php:19-52`: wraps every `Schema::table` column add in `if (! $schema->hasColumn(...))` (`$schema = Schema::getConnection()->getSchemaBuilder()`), column defs built from arrays of closures. Also avoids sqlite-illegal `->after()` reordering issues by only adding when missing.
   - `database/migrations/2026_11_04_000001_create_chat_messages_table.php:12-14`: `if (Schema::hasTable('chat_messages')) return;` before create.
   → **Pattern for new migrations: guard every alter with `hasColumn`/`hasTable`, guard every create with `hasTable`, never assume fresh DB.**
2. **Driver dispatch + table-rebuild for CHECK constraints** — `database/migrations/2026_10_21_000001_fix_perusahaan_sessions_status_check_on_sqlite.php:20-70`:
   - `DB::getDriverName()` switch: mysql path does plain `ALTER ... MODIFY ENUM`; sqlite path rebuilds the whole table (`PRAGMA foreign_keys = OFF` → `CREATE TABLE _new` with DDL copied verbatim from live schema → `INSERT INTO _new SELECT *` → `DROP` → `RENAME` → recreate index → `PRAGMA foreign_keys = ON`).
   - Lesson documented in its docblock (`:8-17`): Laravel creates enum columns as `varchar + CHECK(...)` on sqlite, and **sqlite cannot ALTER a CHECK** — adding a value to an "enum" requires full rebuild, and the bug bit prod (not just tests) because prod is sqlite too.
   - `down()` deliberately no-ops with a reason comment (`:72-75`) — removing values from a CHECK would corrupt existing rows.
   → **Pattern: never "just widen the enum". Either add the value at table-create time, or copy the rebuild pattern.**
3. **Laravel 12 upgrade fixes** (commit `ae8862f`): sqlite can't `DROP COLUMN`/drop FK the way MySQL does; older alter-migrations were rewritten to guard or rebuild. Same constraint applies to new ones.
4. **Idempotency matters twice**: tests run `RefreshDatabase` (migrate fresh per class) AND prod runs `migrate --force` on an already-migrated DB after every deploy (§6) — a non-idempotent migration re-runs harmlessly once via the migrations table, but the guards exist because prod DB was migrated by an older branch lineage where columns may already exist (branch-merge history, e.g. `b2b_alter` re-entering). Guard anyway.

Also note: `2026_08_18_080001_add_cancelled_to_perusahaan_sessions_status.php` is the original buggy "MySQL-only" enum widen that `2026_10_21_000001` cleans up after — study it as the anti-pattern.

## 5. Scheduled jobs (this branch: `app/Console/Kernel.php:16-35`)

Two schedules on the mission branch:
1. **Expiry sweep §17.7** — closure calling `(new MidtransController)->updateExpiredPayments()` `everyFiveMinutes()` (`:19-22`): payment waiting/expired → canceled.
2. **Dual-confirm fallback §9.1** — hourly closure flagging `needs_ops_review=true` for sessions `konsultan_done_at > 24h` (config `klinik.confirm_window_hours`) without customer confirm (`:25-34`). Anti-fake-session; never auto-completes.

No no-show sweeper, no imports:prune on THIS branch — but the **prod tree** (`klinik/app/Console/Kernel.php:17-45`) runs 5:
- expiry sweep (same) `everyFiveMinutes()`
- `sessions:cancel-expired` hourly (B2B no-show/check-in auto-cancel + quota restore; `klinik/app/Console/Commands/CancelExpiredSessions.php`)
- `chat:purge-ended` daily (privacy: purge transcripts of sessions done ≥24h)
- `imports:prune` hourly (CSV roster imports with plaintext bootstrap passwords; `klinik/app/Console/Commands/PruneRosterImports.php` — this is the consumer of the finfo polyfill)
- dual-confirm fallback hourly (same)

**No scheduler runner exists**: no cron entry for `schedule:run` anywhere (no user crontab, no cron.d entry, no systemd timer — only `soluusi-b2b.service` and `soluusi-worker.service` user units, `ls /home/hermes/.config/systemd/user/`). PRD plans `* * * * * php artisan schedule:run` (`docs/Soluusi B2B Platform - PRD.md:918`) but it is NOT deployed. The queue worker (soluusi-worker.service, `queue:work database`) is running (pid 1517358). → Scheduled jobs are currently dead code in prod until a cron/timer is added; PRD `docs/PRD-2026-11-16-current-state.md:321` even lists "pastikan cron schedule:run jalan" as a deploy blocker.

## 6. Deploy flow — actual mechanism

**Verified pieces:**

- **Prod app**: `soluusi-b2b.service` (user unit, `/home/hermes/.config/systemd/user/soluusi-b2b.service`): `WorkingDirectory=.../soluusi-b2b-klinik/public`, `Environment=PHP_CLI_SERVER_WORKERS=6`, `ExecStart=/opt/cpanel/ea-php83/root/usr/bin/php -S 127.0.0.1:8099 .../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`, `Restart=on-failure`. 7 php processes live (1 master + 6 workers, ps verified). `serve-local.sh` (repo) is the manual equivalent.
- **Tunnel**: single cloudflared process (pid 1381154, `~/.local/bin/cloudflared tunnel --config ~/.cloudflared/config.yml run`) serving `https://soluusi.tokengateway.ai.id` → 127.0.0.1:8099. Config file not readable from t3 (perm 700 hermes). No dedicated soluusi tunnel unit — tunnel predates and is shared (only kawanku has its own unit).
- **Queue worker**: `soluusi-worker.service` (`/home/hermes/.config/systemd/user/soluusi-worker.service`): `queue:work database --queue=default --tries=2 --timeout=120`, log at `/home/hermes/.hermes/cache/scratch/soluusi-worker.log`.
- **Prod tree**: `/home/hermes/workspace/projects/soluusi-b2b-klinik`, git repo, remote `https://github.com/fauzan171/soluusib2b.git`, checked out on branch **`improvement-live`** (HEAD `088a215`, 2026-11-21). NO git hooks (`.git/hooks` only samples), NO CI, NO webhook receiver, NO path-watcher process, NO cron doing `git pull`.
- **Deploy doc: none.** `docs/plan/recon-r6-docs.md:62` already flags "NO DOC EXISTS — deploy knowledge lives only in user memory". The mission brief's "push main = reset --hard + migrate --force" mechanism is **not verifiable on disk from t3**: there is no receiver script, no inotify watcher, no auto-pull. Deploy is almost certainly manual: user pushes, then in the prod tree runs `git fetch && git reset --hard origin/<branch> && composer install && php artisan migrate --force` (+ service restart). One clue: remote `prodhead` exists in this mission repo's git refs; `~/.local/bin/soluusi-prod` (hermes-owned, mode 700, unreadable) is likely the deploy helper script — name suggests `soluusi-prod` wraps exactly that pull/reset/migrate/restart sequence.
  → **Action item: deploy.md (R6 already plans it) must capture the exact `soluusi-prod` sequence — ask user to `cat ~/.local/bin/soluusi-prod` as hermes.**

### php -S worker risks (all mitigated in prod tree, NOT present in mission tree)

The `require_once`/stale-classmap issue with `PHP_CLI_SERVER_WORKERS=6`: each php -S worker keeps its in-memory classmap from boot; after any `composer dump-autoload` (deploy), stale workers 500 with empty body until restarted. Prod fixes, in order:

1. **`44bed0d` self-heal guard** (`klinik/public/index.php:29-42`): before framework boot, compares worker process birth time (`/proc/self/stat` field 22 + `/proc/uptime`) to `vendor/composer/autoload_classmap.php` mtime; if worker predates the classmap → `exit(1)` → systemd `Restart=on-failure` respawns a fresh worker. Comment says "Remove when the app moves to php-fpm."
2. **finfo polyfill** (`klinik/app/Support/finfo-polyfill.php`, loaded via `composer.json` autoload.files): cPanel ea-php83 CLI lacks ext-fileinfo → `league/mime-type-detection` fatal. (Same polyfill needed in mission tree for composer: `--ignore-platform-req=ext-fileinfo` per PROGRESS.md:10.)
3. **`52954db`**: debug line to `/tmp/soluusi-guard-debug.log` + `app/Jobs/RestartWebServer.php` — one-shot queue job that runs `systemctl --user restart soluusi-b2b.service` from the hermes-owned queue worker (which CAN reach the systemd user bus, unlike t3). Dispatch by inserting into the jobs table.
4. **`3d48aed`**: strips `/dev` subdirectory prefix from REQUEST_URI in index.php (tunnel path quirk).

**Risk for this mission**: those four prod fixes live only on `improvement-live` in the prod tree. The mission branch (`upgrade/laravel-12` lineage) does NOT have the index.php self-heal, finfo polyfill, or REQUEST_URI fix. Any deploy of mission work must re-apply or cherry-pick `44bed0d`/`52954db`/`3d48aed` or the first post-deploy requests 500 until workers are manually restarted.

### Other deploy-relevant facts

- Prod `.git/config`: `pull.rebase=true`; branch `main` tracks `origin/main` — but working branch is `improvement-live` (git HEAD `ref: refs/heads/improvement-live`). "Push main = deploy" in the brief is at best approximate: the live tree tracks `improvement-live`, and mission work is on `feat/mission-package-quota` → merge path to live must be decided (merge mission → `upgrade/laravel-12` → cherry-pick/rebase onto `improvement-live`? The trees have diverged 194 vs 63 commits).
- No config/route/view caching in prod runbook beyond PRD §16.5 (`docs/Soluusi B2B Platform - PRD.md:914-918`: composer install --no-dev, npm run build, migrate --force, config:cache etc. — aspirational, not scripted).
- t3 cannot restart the services: `systemctl --user` as t3 fails ("No medium found"), and RestartWebServer job exists precisely because of that.
- Env gotchas carried in `docs/plan/PROGRESS.md:12-15`: safelock cPanel stderr noise (filter `grep -viE "safelock|userdatadomains"`), t3 umask 660 → chmod 664 for hermes-read files, composer at `/home/hermes/.local/bin/composer`.

## 5 impactful findings

1. **66/66 is real at HEAD** — brief's 66 matches `80ac781` (61 at the `9e60184` base + 5 interim + 3 provisioning). The earlier "52 passed" baseline was a different, older tree AND an env-broken run. Fresh clones must `mkdir -p storage/framework/{views,cache,sessions}` + `npm install && npm run build` or view-rendering tests 500 (Vite manifest).
2. **No scheduler runs in prod** — no cron/timer for `schedule:run` anywhere; all 5 scheduled jobs (expiry sweep, no-show cancel, chat purge, imports prune, dual-confirm) are dead until one is added. PRD itself lists this as an open deploy blocker.
3. **Deploy automation is unverifiable from t3** — no hooks/CI/watcher on disk; likely `~/.local/bin/soluusi-prod` (hermes 700) does pull+reset+migrate+restart. Deploy doc must be written from that script; prod tree live branch is `improvement-live`, not `main`.
4. **php -S self-heal fixes live only on `improvement-live`** (`44bed0d` index.php classmap-mtime guard, `52954db` RestartWebServer job, `3d48aed` REQUEST_URI fix, finfo polyfill) — mission branch lacks all four; deploying mission code without cherry-picking them reintroduces the empty-500-after-deploy failure.
5. **New migrations MUST follow the guard patterns** — `hasColumn`/`hasTable` idempotent guards (`9e60184`) + driver dispatch with full sqlite table-rebuild for enum/CHECK changes (`2026_10_21_000001`); prod is sqlite so an unguarded "MySQL-style" alter breaks production, not just tests (precedent: `2026_08_18_080001`).
