# Soluusi B2B — Memory

> Last updated: 2026-08-05
> Project: Soluusi B2B Platform (pre-marge)
> Status: Pre-development — all docs written, no code yet

---

## Project Overview

Soluusi = marketplace konsultasi personal pertama di Indonesia (B2C soluusi.com sudah live). B2B platform = mesin operasional untuk 3 vertikal partnership: **For Health Facilities (Klinik/HAP)**, **For School (SAP)**, **For Company (CAP)**.

**Core thesis:** organisasi butuh layanan wellbeing/mental health tapi tidak mau rekrut SDM full-time. Soluusi = talent pool on-demand, online+offline, tanpa beban operasional tetap.

**Scale today:** 400+ konsultan aktif, 28 provinsi, 5000+ kasus, 300+ psikolog berlisensi, 200+ komunitas partner.

---

## Documents (5 files, all read)

| File | Content | Status |
|------|---------|--------|
| `Soluusi B2B Master Playbook.md` | GTM, persona, pricing, 3-stage commission, 90-day plan, outreach templates, objection handling, ethics — all 3 vertikal | v1.0 complete |
| `Soluusi B2B - Klinik Vertical Spec.md` | Detail klinik: markup model, white-label checkout link, split payment, dual-confirm, anti-fraud, konsultan pool, HAP commission, Model C (checkout-guest) | v1.1 complete |
| `Soluusi B2B - Competitor Analysis.md` | Riliv (#1), Bicarakan, KALM, Naluri, Yosh!, Intelens, Halodoc/Alodokter, Talenta, Spring Health/Lyra (global ref), school vertical (blue ocean) | v1.0 complete |
| `Soluusi B2B Platform - PRD.md` | Full PRD: 10 modules, data model, user flows, tech stack locked (Laravel 9/PHP 8/Midtrans/MySQL), PM decisions (9 DECIDED + 3 NEEDS FOUNDER), B2C audit appendix, dev setup | v1.2 complete |
| `Soluusi B2B - Dev Preparation.md` | Stack lock, copas manifest (dari soluusi-main), build-new list (11 items), fix-list (5 B2C bugs), env vars, setup checklist, smoke test | v1.0 complete |

**4 PDFs also present:** HAP Handbook, 90-Day Plan, Compensation Framework, Partnership Playbook HPA (source docs for Master Playbook).

---

## Key Decisions (Locked)

1. **Stack:** Laravel 9 / PHP 8 / Midtrans Snap / MySQL / Vite-Tailwind / database driver (no Redis) — match `soluusi-main` B2C persis
2. **Revenue model (klinik):** Markup model — Soluusi set net price, klinik markup (capped per price range), customer bayar langsung via white-label checkout link, gateway split net→Soluusi + margin→klinik
3. **Pasien identity:** Model C (checkout-guest) — customer tidak bikin akun, Soluusi collect PII transaksional di checkout, klinik owns relationship
4. **White-label CRITICAL:** Customer TIDAK boleh tahu layanan dari Soluusi — hide brand di domain/page/receipt/email/gcal/konsultan profile
5. **Commission:** 3-stage anti-fraud — Stage 1 (MoU+5 sesi = Rp250k), Stage 2 (25 sesi = Rp250k), Stage 3 (40 sesi/90d = Rp500k). Total indikatif Rp1M/klinik
6. **Confidentiality:** Session content = konsultan+customer+audit only. Partner admin/staff = aggregate only. HIMPSI category-A = licensed psikolog only
7. **Architecture:** Pisah sekarang (repo B2B sendiri), nyatu nanti (merge ke soluusi-main). Tabel domain verbatim (preserve B2C typo), tabel B2B prefix `klinik_`
8. **MVP priority:** Klinik vertikal #1 (HAP already running, fastest sales cycle)

---

## Build Plan (from Dev Preparation)

### Copas dari soluusi-main (verbatim/adapt):
- Migrations domain konsultasi (skip SEO/blog/CMS 2026_04_30+)
- Models: User, Consultant, ConsultantPackage, Schedule, ConsultationSession, Voucher, dll
- Controllers: MidtransController (+sig verify +split), AuthController, JadwalController, ConsultantController, VoucherController, KategoriController, KonsumenController
- Services: GoogleCalendarService (OAuth→service account migration)
- Mail: 14 mailables + blades → white-label parameterize
- Config: midtrans, xendit, services (google+google_calendar only)

### Build NEW (11 items):
1. Split-payment gateway (Midtrans Connect / Xendit Marketplace)
2. White-label config + parameterization
3. Service-account Google Calendar
4. Dual-confirm anti-fraud
5. Admin-generate-link booking flow
6. Klinik RBAC + earnings/payout dashboard
7. HAP 3-stage commission engine
8. Markup + cap per price range
9. Pasien Model C (checkout-guest)
10. Anti-circumvention
11. No-show + check-in

### Fix B2C bugs (5 items):
1. Webhook NO signature verify → add verify (SECURITY)
2. gcal failure fatal → non-fatal (DATA-LOSS)
3. payment_status enum loose → Blueprint enum
4. profit_sharing int ambigu → field baru klinik_markup
5. B2C typos → PRESERVE (merge parity)

---

## Open Items (NEEDS FOUNDER)

| # | Topic | Fallback |
|---|-------|----------|
| 15.5 | MHCU instrument licensing (MMCI/MMPI) | Disabled di seeder sampai license confirmed |
| 15.7 | Pricing tiering final (PEPM/sekolah) | Pakai angka indikatif Playbook, parameterize |
| 15.11 | gcal service account ownership | Soluusi ops pegang, MVP dev pakai OAuth-user test |

---

## Competitor Landscape (Summary)

- **Riliv** = #1 threat (brand, B2B2C, but psikolog-only, online-only, corporate-centric)
- **School vertical** = blue ocean (no dedicated player in Indonesia)
- **Soluusi moat:** 3 vertikal + multi-profesional + hybrid online/offline + bagi-hasil fleksibel + cross-vertikal upsell

---

## Execution Order (from PRD §0.4)

1. Scaffold (Laravel 9)
2. Copas dari soluusi-main per Appendix D
3. Config (.env, whitelabel, midtrans, services)
4. Migrate + seed
5. Build-NEW (11 items)
6. Fix-List (5 items)
7. Smoke check
8. Dev run

---

## Codebase References

- B2C production: `/Users/mekari/soluusi-main` (Laravel 9, ground truth)
- B2B docs: `/Users/mekari/soluusi-b2b-beforemarge/` (this project)
- Ponytail mode: active (lazy senior dev, YAGNI, shortest diff)