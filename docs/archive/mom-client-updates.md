# MoM — Client Updates (Buaisya / Owner Product)

> Track setiap sesi update ke client. Satu entry per pertemuan. Link ke doc ini dari chat/email biar traceable.

---

## Template (copy per entry baru)

```markdown
## YYYY-MM-DD — [Judul Update]

**Attendees:** Mekari, Buaisya
**Duration:** X menit
**Format:** Call / Meet / Async doc

### Yang gw presentasiin
- Bullet point fitur/progress
- Demo link (kalau ada)

### Feedback client
| # | Feedback | Priority | Action |
|---|----------|----------|--------|
| 1 | ... | High/Med/Low | ... |

### Revisi / scope change
- ...

### Next steps (gw)
- [ ] Task 1 — ETA ...
- [ ] Task 2 — ETA ...

### Next steps (client)
- [ ] ...

### Follow-up scheduled
YYYY-MM-DD — agenda: ...

---
```

---

## 2026-08-06 — Kickoff / Progress Update (DRAFT — belum dikirim)

**Attendees:** Mekari, Buaisya
**Duration:** —
**Format:** TBD

### Yang mau gw presentasiin
- [ ] Laravel 9 B2B scaffold up & running (SQLite local, match B2C stack)
- [ ] Landing page 3 vertikal (Klinik / Sekolah / Perusahaan) — live di `http://localhost:8000`
- [ ] Lead capture form + Partner self-register flow
- [ ] Model C checkout (guest, white-label, no Soluusi branding)
- [ ] Split payment structure (net → Soluusi, markup → klinik)
- [ ] Dual-confirm anti-fraud (konsultan + customer)
- [ ] HAP 3-stage commission schema
- [ ] Admin dashboard (Super Admin + Klinik Admin)

**Demo URLs (lokal):**
- Landing: `http://localhost:8000`
- Login: `http://localhost:8000/login`
- Admin klinik: `admin@demo.test` / `password`
- Super admin: `admin@klinik-konsultan.local` / `password`

### Pertanyaan buat client (blocking decision)
- [ ] **§15.5 MHCU licensing** — MMCI/MMPI pakai license Soluusi atau psikolog partner? (Phase 2, tapi perlu confirm sekarang)
- [ ] **§15.7 Pricing final** — angka indikatif Playbook boleh dipakai MVP, atau finance mau finalize dulu?
- [ ] **§15.11 gcal service account** — siapa pegang Google Workspace admin? (butuh untuk service-account migration)

### Known gaps (belum implement, jangan kaget pas demo)
- Midtrans webhook signature verify belum aktif (masih sandbox, tapi PRD flag ini SECURITY)
- gcal integration masih OAuth-user test creds (belum service account)
- Email mailables belum di-white-label per brand
- Sekolah & Perusahaan vertikal = UI landing doang, belum ada backend flow
- MHCU module belum ada sama sekali (Phase 2)

### Risks yang harus client tahu
| Risk | Mitigation |
|------|-----------|
| Customer bisa tahu ini Soluusi (domain, receipt Midtrans) | Midtrans receipt masih ada nama Soluusi → perlu custom domain + receipt template (Phase 2) |
| HIMPSI compliance MMCI/MMPI | Disabled di seeder sampai license confirmed |
| Data residency UU PDP | SQLite lokal = dev only; prod harus MySQL ID/SG region |

---

## Notes buat gw sendiri (jangan kirim ke client)

**Yang client mungkin gak suka:**
- Masih jalan di `localhost:8000` — belum staging domain
- Landing page copy indikatif ("Rp15–35rb", "Rp8–25rb") — belum final pricing
- Login page masih pakai brand "Klinik Konsultan" neutral (bukan white-label per klinik)

**Yang client mungkin suka:**
- 3 vertikal visible dari hari 1 (bukan cuma Klinik)
- Lead capture + Partner register = self-serve GTM (walau PRD bilang associate-only)
- Checkout link flow sudah jalan end-to-end (admin generate → customer bayar → split)

**Tech debt yang harus gw omongin (kalau client technical):**
- No git repo yet (belum `git init`) — risky
- Laravel 9.52.21 (match B2C), tapi PHP 8.4 (PRD bilang 8.0+) — minor compat
- Google/apiclient deprecation warnings di PHP 8.4 (noise, non-blocking)
