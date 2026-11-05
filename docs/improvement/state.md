# Soluusi B2B Klinik — UX/Flow Improvement Loop

Owner: Ojaaan. Repo: /home/hermes/workspace/projects/soluusi-b2b-klinik (branch local `main`, push ke `origin/laravel-clinic`).
Live: https://soluusi.tokengateway.ai.id via soluusi-b2b.service (:8099).

## Pipeline (max_concurrent_children=1 → serial)
1. ANALYST — audit UI/flow/desain sistem E2E → docs/improvement/analysis-round-N.md
2. PM — task list + acceptance criteria → docs/improvement/pm-plan-round-N.md
3. ENGINEER (worktree) — implement batch prioritas, run tests
4. QA — verifikasi kriteria + full test suite → verdict
5. Loop balik ke ANALYST (cek hasil) sampai "OK"

## State
- Round: 1
- Phase: ENGINEER batch 2 — T10 queued (T1 ✅ T3 ✅ T4 ✅ T6 ✅ T7 ✅ T9 ✅ merged)
- Test baseline sebelum loop: 52/52 PASS
- Terakhir deploy: b5bd6ca (alur request konsultasi)
- Analyst: docs/improvement/analysis-round-1.md — 23 temuan (critical 2, high 6, medium 9, low 6)
- PM: docs/improvement/pm-plan-round-1.md — 10 task (critical 2, high 6, medium 2), 3L+5M+2S, critical path T1→T3/T4/T6→T10

## Constraints
- Bahasa deliverable: Indonesia.
- Ponytail: smallest diff, no slop, no refactor besar tanpa bukti.
- SQLite dev; tests pakai RefreshDatabase.
- Jangan sentuh branch origin/main (project NestJS lain).
