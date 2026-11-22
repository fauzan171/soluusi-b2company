# Recon R6 — Docs Inventory & Staleness Classification

Branch audited: `upgrade/laravel-12` (docs tree byte-identical to working branch `feat/mission-package-quota`; verified `git diff --stat` = empty). All dates = last commit touching the file. Current date 2026-11-22.

Reality baseline for "current" checks:
- Code: **Laravel 12 / PHP 8.2** (`composer.json`: `laravel/framework ^12.0`), upgraded via `ae8862f` (Laravel 9→12, Sanctum 4, PHPUnit 11).
- Main line includes Halodoc chat Phase 1 (`75eba98`, `ConsultantPickerTest`) + improvement round 1 fixes + screening tables (`2026_10_21_000002_create_screening_tables.php`, `ScoringService`, `ScreeningQuestionSeeder`).
- No `tasks.yaml`/`task.md` in repo (checked tracked tree + filesystem, maxdepth 2). Mission docs live in untracked `docs/plan/` (PROGRESS.md + tasks/).

## 1. Inventory & Classification

### Markdown — docs/

| File | Last commit | Classification | Reason | Action |
|---|---|---|---|---|
| `Soluusi B2B Master Playbook.md` | 2026-10-20 6496d3f | CURRENT | GTM/commission/90-day business doc; business model unchanged; consolidated superset of the 4 HAP PDFs | keep |
| `Soluusi B2B Platform - PRD.md` | 2026-10-20 6496d3f | STALE (partially) | Business scope/§15.0 decisions still valid; §12 stack ("Laravel 9 locked"), §16 running tech, Appendices C–F (copas manifest from `/Users/mekari/soluusi-main`) contradict Laravel 12 reality; references macOS path that doesn't exist here | update (strip/annotate stack + copas appendices; keep decisions) |
| `Soluusi B2B - Klinik Vertical Spec.md` | 2026-10-20 6496d3f | CURRENT (business) / STALE (tech) | v1.1 commercial model (markup, white-label, split) is authoritative and matches code per audits; tech notes assume pre-upgrade stack | keep; annotate tech sections |
| `Soluusi B2B - Perusahaan Vertical Spec.md` | 2026-10-20 6496d3f | CURRENT | Business spec; PEPM/EAP model — matches implemented perusahaan portal direction | keep |
| `Soluusi B2B - Sekolah Vertical Spec.md` | 2026-10-20 6496d3f | CURRENT | Business spec; sekolah portal exists (SekolahWellbeingTest) | keep |
| `Soluusi B2B - Dev Preparation.md` | 2026-10-20 6496d3f | STALE | Entire doc is "persiapan sebelum ngoding": locks Laravel 9 / PHP 8.0 / `soluusi-main` copas manifest — all superseded by 3 months of builds + L12 upgrade | archive (docs/archive/) |
| `Soluusi B2B - Competitor Analysis.md` | 2026-10-20 6496d3f | UNCLEAR | Aug 2026 web-research snapshot; doc itself flags "verify sebelum final positioning" (KALM parked etc.); no re-verification since | keep; re-verify before GTM decisions |
| `PRD-Soluusi-B2B-Reframed.md` | 2026-10-20 6496d3f | STALE (executed) | Landing reframe v2.0 "implementation-ready" — implemented (4 landing views exist per Landing-Copy-Inventory); value now historical | archive |
| `Landing-Copy-Inventory.md` | 2026-10-20 6496d3f | UNCLEAR | Extract dated 14 Aug 2026; views may have drifted since; still the only copy reference for marketing | keep; refresh extraction if copy changes |
| `TASK-halodoc-chat-flow.md` | 2026-11-07 3aeedb0 | STALE (mostly done) | Phase 1 shipped (`75eba98` + ConsultantPickerTest); "chat end session + rating" (`0cbca5b`) covers lifecycle; unchecked-box task doc now misleading | archive with completion notes |
| `audit/KLINIK-FLOW-AUDIT.md` | 2026-10-21 cc6ab0d | UNCLEAR | Baseline `6496d3f`; controls verified with file:line refs, but 6+ weeks of commits (incl. L12 migration) shifted lines/behavior — assertions unverified on current tree | keep; re-run audit vs `upgrade/laravel-12` if cited |
| `dev-login.md` | 2026-10-20 6496d3f | CURRENT | Seed-based demo accounts; mission worktree re-seeded 2026-11-22 with same seeders; pattern unchanged | keep; verify emails after re-seed |
| `memory.md` | 2026-10-20 6496d3f | STALE (dangerously) | "Status: Pre-development — all docs written, **no code yet**"; stack "Laravel 9 locked" — both flat wrong 3 months later | archive or rewrite as 1-page project snapshot |
| `mom-client-updates.md` | 2026-10-20 6496d3f | STALE | Kickoff entry (Aug 6) claims "Laravel 9 scaffold up & running" only; research-flow-audit itself notes this doc "sudah tidak sesuai source"; only one draft entry, never sent | archive |
| `research-flow-audit-2026-10-05.md` | 2026-10-20 6496d3f | STALE (partially fixed) | P1 findings (booking runtime broken, chat dead-end, no nav) largely addressed by improvement round 1 + halodoc phase 1; remaining findings untracked | keep as source-truth baseline; annotate fixed items |
| `screening-parity-plan.md` | 2026-10-21 2db1e8a | STALE (executed) | Plan doc "ditulis sebelum perubahan kode" — screening tables, ScoringService, question seeder all exist (migration `2026_10_21_000002`) | archive with "implemented" stamp |
| `improvement/analysis-round-1.md` | 2026-11-05 8e3dcd9 | STALE (executed) | 23 findings → fix commits landed on main (`919f6b2`, `daa1300`, `1441e43`, …) | archive |
| `improvement/pm-plan-round-1.md` | 2026-11-05 8e3dcd9 | STALE (executed) | 10 tasks; round 1 QA OK per state.md; later commits went beyond | archive |
| `improvement/state.md` | 2026-11-05 229fc6f | STALE | "Round 1 selesai, siap push origin/laravel-clinic" — superseded: main already at `178d7de` (session history, chat rating) and pushed; loop never continued | archive |
| `archive/session-transcript-2026-10.md` | 2026-10-24 fcbe210 | STALE (archived) | Raw terminal transcript of founding session; historical only | keep in archive (already there) |

### Root level

| File | Last commit | Classification | Reason | Action |
|---|---|---|---|---|
| `README.md` | 2026-10-20 6496d3f | STALE | Stock Laravel 9-era README ("About Laravel", Taylor Otwell) — zero project info; worst first impression for a 3-vertical B2B product | update (replace with 10-line project README: stack, verticals, setup, link to docs) |
| `docs/plan/` (untracked) | — | CURRENT | Active mission workspace: PROGRESS.md + tasks/ | keep (mission docs; decide tracking policy) |

### PDFs (docs/) — business source material, not code-adjacent

| File | Classification | Action |
|---|---|---|
| `01 - Soluusi HAP Handbook.pdf` … `04 - Partnership Playbook HPA.pdf` | CURRENT (sources for Master Playbook) | keep |
| `1. Holistic Employee Wellbeing Program - For Company.pdf`, `2. Employee Asessment Program (×2 versions, duplicate)` | CURRENT; duplicate pair `(1)`/no-suffix | keep; delete one duplicate `2. Employee Asessment Program.pdf` |
| `School - Mental Health Program (Eng/Ind)` | CURRENT | keep |
| `Landing-Copy-Inventory.pdf`, `Landing-Pages-Review.pdf` | STALE (Aug landing review, pre/post reframe) | archive |

## 2. Authoritative doc per topic

| Topic | Authoritative | Caveat |
|---|---|---|
| **Booking (klinik, business rules)** | `Soluusi B2B - Klinik Vertical Spec.md` v1.1 §7 | dual-confirm + checkout controls verified once in `audit/KLINIK-FLOW-AUDIT.md` (old baseline) |
| **Booking (perusahaan/chat, current behavior)** | none current — code + `tests/Feature/ConsultantPickerTest.php` is ground truth | TASK-halodoc doc is closest but unchecked-box form |
| **Assessment/screening** | `screening-parity-plan.md` (design) + `app/Services/Screening/ScoringService.php` (implementation) | doc executed; code is truth for scoring bands |
| **PRD / product scope** | `Soluusi B2B Platform - PRD.md` (scope, §15.0 decisions) | ignore §12/§16/Appendix C–F (Laravel 9 era) |
| **GTM / pricing / commission** | `Soluusi B2B Master Playbook.md` | — |
| **Deploy** | **NO DOC EXISTS** — deploy knowledge lives only in user memory (prod tree = php -S :8099 + cloudflared; push main = deploy) | GAP: write `docs/deploy.md` during mission |
| **Mission plan** | `docs/plan/PROGRESS.md` (untracked) | current |

## 3. Contradictions between docs

- **Stack:** PRD §12 "Tech Stack (Locked)" + Dev Prep §2 + memory.md all say Laravel 9 / PHP 8.0 / PHPUnit 9.5; `composer.json` says Laravel 12 / PHP 8.2 / Sanctum 4 / PHPUnit 11. Three docs wrong, one file right.
- **Project status:** memory.md "no code yet" (Aug) vs reality: 3 vertical portals, chat, screening, 52 tests passing.
- **Vertical priority #2 claimed by BOTH `Sekolah Vertical Spec.md` and `Perusahaan Vertical Spec.md` ("dibangun setelah Klinik").** In practice perusahaan came first (improvement rounds + halodoc all target perusahaan).
- **Landing status:** mom-client-updates kickoff ("scaffold up, SQLite") vs PRD-Reframed "implementation-ready" vs Landing-Copy-Inventory extraction of the 4 already-built pages — three snapshots, only the last matches today.
- **Test baseline:** TASK-halodoc says "52/52" (Nov 7); mission brief says 66/66; actual run 2026-11-22 = 52 passed (per PROGRESS.md; R5 investigating).
- **Ground-truth path:** PRD/Dev Prep reference `/Users/mekari/soluusi-main` (macOS) — nonexistent on this host; actual sibling trees are `/home/hermes/workspace/projects/*`.
- **Repo identity:** improvement/state.md points at `soluusi-b2b-klinik` repo pushing to `origin/laravel-clinic`; this mission repo is `soluusi-b2b-mission` off `upgrade/laravel-12` — same lineage, different tree; state.md reads as if it governs this repo.

## 4. Recommended minimal docs structure post-mission

```
README.md                    # replace stock: 10 lines (stack L12/PHP8.2, 3 verticals, setup, docs map)
docs/
  plan/                      # mission workspace (NEW, already seeded)
    recon-r*.md              #   phase-0 recon (this file: r6)
    research-*.md            #   phase-1 external research
    MASTER-PLAN.md           #   phase-2 plan
    tasks/Wn.md              #   weekly task slices
    PROGRESS.md              #   running log
  business/                  # GTM truth
    Master-Playbook.md
    Competitor-Analysis.md
    Vertical-Klinik.md       # (renamed, tech sections annotated)
    Vertical-Perusahaan.md
    Vertical-Sekolah.md
    PRD.md                   # stack sections stripped, decisions kept
  dev/
    deploy.md                # NEW — capture prod runbook (:8099, cloudflared, push=deploy)
    dev-login.md
  reference/                 # evidence, read-only history
    audit-klinik-flow.md
    research-flow-audit-2026-10-05.md
    landing-copy-inventory.md
  archive/                   # superseded: memory.md, mom-client-updates.md, Dev-Prep,
                             # PRD-Reframed, TASK-halodoc, screening-parity (stamped "implemented"),
                             # improvement/* (round 1, stamped), session-transcript, old PDFs
  pdfs/                      # HAP/Company/School source PDFs (dedupe Asessment pair)
```

Rule: `docs/plan/` = living mission docs; `docs/business/` = decisions; `docs/reference/` + `archive/` = never edited, stamped with status. Moves can happen post-mission; nothing deleted except the duplicate PDF.

## 5. Top-5 docs that could mislead the team

1. **`memory.md`** — "Pre-development, no code yet" + Laravel 9 lock. Anyone onboarding from this believes the product doesn't exist. Worst offender because its title implies it's the memory/source of truth.
2. **`Soluusi B2B - Dev Preparation.md`** — full stack-lock (Laravel 9/PHP 8.0), copas manifest from a macOS path that doesn't exist; an engineer following it would try to downgrade or clone the wrong ground truth.
3. **`Soluusi B2B Platform - PRD.md` §12/§16 + Appendices C–F** — "Stack locked — jangan nebak stack, jangan add Redis/…" is actively wrong post-upgrade; §16 running-tech commands assume PHPUnit 9/Laravel 9 flow. High risk because the doc's own §0 says "read this FIRST, self-contained".
4. **`improvement/state.md`** — describes a different repo (`soluusi-b2b-klinik`), says round 1 "siap push" while the work is already merged and pushed further; implies the improvement loop is mid-flight when it's dormant.
5. **`README.md` (root)** — stock Laravel 9 boilerplate. Combined with `TASK-halodoc-chat-flow.md`'s unchecked boxes (phases actually shipped), both make the repo look unmaintained/earlier-stage than it is; README is the entry point so it's the visible one.

Honorable mention: `mom-client-updates.md` (single unsent draft, wrong status) and the duplicate `2. Employee Asessment Program.pdf` pair.
