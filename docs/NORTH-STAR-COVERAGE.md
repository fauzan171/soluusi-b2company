# North Star → Implementation Coverage

Tanggal: 2026-11-14. Status: gap analysis.

## Legend
- ✅ Sudah ada task spec
- ⚠️ Sebagian tercakup
- ❌ Belum ada task

## North Star Requirements

### 1. Superadmin
| Requirement | Status | Task |
|-------------|--------|------|
| Provisioning akun | ⚠️ | T3.1 (admin controllers) — tapi belum lengkap untuk semua tenant |
| Kelola perusahaan/klinik | ⚠️ | T3.1 — approve/activate ada, tapi belum full CRUD |
| Assignment consultant | ❌ | **BELUM ADA** — perlu task untuk assign consultant ke perusahaan/klinik |
| View-as dengan audit log | ⚠️ | T3.1 — audit log untuk admin actions, tapi view-as seam belum eksplisit |

**Gap baru:**
- **T8.1** — Consultant assignment management (superadmin assign/remove consultant dari perusahaan/klinik pool)
- **T8.2** — View-as middleware + audit (superadmin impersonasi tenant admin dengan logging)

### 2. HR per perusahaan
| Requirement | Status | Task |
|-------------|--------|------|
| Review request karyawan | ⚠️ | T5.3 (pending ACC card) — tapi HR review sendiri belum |
| Assign consultant | ❌ | **BELUM ADA** — HR assign consultant ke employee |
| Approval consultant yang dialokasikan | ❌ | **BELUM ADA** — HR approve consultant assignment |

**Gap baru:**
- **T8.3** — HR review request queue (list pending, approve/reject, assign consultant)
- **T8.4** — HR consultant allocation approval (review consultant yang di-assign system, approve/override)

### 3. Consultant
| Requirement | Status | Task |
|-------------|--------|------|
| Availability | ⚠️ | Sudah ada di `schedules` table + `BookingService::available` |
| Proposal slot | ❌ | **BELUM ADA** — consultant propose multiple slot options |
| Sesi | ✅ | T4.3 (state machine) |
| Catatan | ❌ | **BELUM ADA** — session notes/records |

**Gap baru:**
- **T8.5** — Consultant slot proposal (propose 2-3 slot options, employee/HR pilih satu)
- **T8.6** — Session notes (consultant add notes post-session, private atau shared)

### 4. Employee
| Requirement | Status | Task |
|-------------|--------|------|
| Request | ✅ | T5.3 (pending card) |
| Konfirmasi slot | ❌ | **BELUM ADA** — employee confirm proposed slot |
| Sesi/chat | ✅ | ChatController + T2.2 (policies) |
| Riwayat | ✅ | Sudah ada di employee dashboard |

**Gap baru:**
- **T8.7** — Employee slot confirmation (view proposed slots, confirm one, auto-book)

### 5. Klinik admin
| Requirement | Status | Task |
|-------------|--------|------|
| Kelola klinik | ⚠️ | Sudah ada dashboard, tapi edit profile klinik belum |
| Kelola tenaga sendiri | ❌ | **BELUM ADA** — klinik admin manage own consultants/staff |

**Gap baru:**
- **T8.8** — Klinik profile management (edit info, branding, contact)
- **T8.9** — Klinik staff management (add/remove consultant dari klinik pool)

### 6. Relasi perusahaan–klinik–consultant fleksibel
| Requirement | Status | Task |
|-------------|--------|------|
| Flexible assignment | ❌ | **BELUM ADA** — consultant bisa di banyak klinik/perusahaan |

**Gap baru:**
- **T8.10** — Consultant pool flexibility (consultant bisa multi-tenant, assignment via pool junction)

### 7. Flow lengkap
| Requirement | Status | Task |
|-------------|--------|------|
| request → HR review → assignment → slot proposed → confirmed → in-session → completed | ⚠️ | Sebagian ada, tapi slot proposal + confirmation belum |
| rejected/cancelled/expired/rescheduled | ⚠️ | cancel/reschedule ada, expired belum |

**Gap baru:**
- **T8.11** — Session expiry sweep (auto-cancel scheduled sessions that passed without check-in)
- **T8.12** — Full flow integration test (request → ... → completed)

### 8. Tenant isolation
| Requirement | Status | Task |
|-------------|--------|------|
| Data layer | ⚠️ | Sudah ada `perusahaan_id`, `klinik_id` di tables |
| Policy layer | ✅ | T2.2 (policies) |
| Test negatif | ✅ | T2.3 (tenancy tests) |

### 9. Waktu
| Requirement | Status | Task |
|-------------|--------|------|
| UTC storage | ✅ | Laravel default |
| Asia/Jakarta display | ❌ | **BELUM ADA** — timezone helper |

**Gap baru:**
- **T8.13** — Timezone display helper (UTC → Asia/Jakarta di views)

### 10. AI seam
| Requirement | Status | Task |
|-------------|--------|------|
| Interface AIService | ✅ | T6.1 |
| Provider via env | ✅ | T6.1 |
| Feature flag tenant | ✅ | T6.1 |
| Modul ai | ✅ | T6.1 |

### 11. Bootstrap workflow mandor/kuli
| Requirement | Status | Task |
|-------------|--------|------|
| CLAUDE.md/AGENTS.md | ❌ | **BELUM ADA** — dokumentasi workflow |
| Subagents definition | ❌ | **BELUM ADA** — auditor/backend/frontend/test/docs/reviewer/server-ops |
| Commands | ❌ | **BELUM ADA** — mandor/dispatch/review/ship/docs-sync |
| scripts/gate.sh | ❌ | **BELUM ADA** — pre-merge gate |
| scripts/kuli.sh | ❌ | **BELUM ADA** — kuli launcher |

**Gap baru:**
- **T9.1** — CLAUDE.md + AGENTS.md untuk workflow mandor/kuli
- **T9.2** — Subagent definitions (auditor, backend, frontend, test, docs, reviewer, server-ops)
- **T9.3** — Commands: mandor, dispatch, review, ship, docs-sync
- **T9.4** — scripts/gate.sh (pre-merge checks: lint, test, no secret)
- **T9.5** — scripts/kuli.sh (launcher dengan model routing tg/glm-5.3)

## Summary

| Category | Total | Covered | Gap |
|----------|-------|---------|-----|
| Superadmin | 4 | 2 | 2 |
| HR | 3 | 1 | 2 |
| Consultant | 4 | 2 | 2 |
| Employee | 4 | 3 | 1 |
| Klinik admin | 2 | 0 | 2 |
| Relasi fleksibel | 1 | 0 | 1 |
| Flow lengkap | 2 | 0 | 2 |
| Tenant isolation | 3 | 3 | 0 |
| Waktu | 2 | 1 | 1 |
| AI seam | 4 | 4 | 0 |
| Bootstrap workflow | 5 | 0 | 5 |
| **TOTAL** | **34** | **16** | **18** |

## Priority untuk gap baru

**P0 (blocker flow inti):**
- T8.3 HR review request queue
- T8.5 Consultant slot proposal
- T8.7 Employee slot confirmation
- T8.11 Session expiry sweep

**P1 (penting untuk operasional):**
- T8.1 Consultant assignment management
- T8.2 View-as middleware + audit
- T8.4 HR consultant allocation approval
- T8.12 Full flow integration test

**P2 (nice to have):**
- T8.6 Session notes
- T8.8 Klinik profile
- T8.9 Klinik staff
- T8.10 Consultant pool flexibility
- T8.13 Timezone helper

**Bootstrap (infrastruktur):**
- T9.1–T9.5 — workflow mandor/kuli
