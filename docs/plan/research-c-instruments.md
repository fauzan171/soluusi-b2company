# Research C — Psychometric Instruments for Employee Wellbeing Screening (B2B, Indonesia)

Goal: pick validated, legally usable instruments beyond the already-implemented WHO-5, and define a data-driven scoring approach.

## 1. Comparison table

| Instrument | Purpose | Items | Scale/item | Score range | Interpretation bands | License | Bahasa Indonesia |
|---|---|---|---|---|---|---|---|
| WHO-5 | Wellbeing (2 weeks) | 5 | 0–5 (6-point) | 0–100 (raw×4) | ≤50 reduced; ≤28 very low / depression screen | **FREE** (no copyright, free translation policy) | WHO official translation (100+ languages) |
| PHQ-9 | Depression screen (2 weeks) | 9 | 0–3 | 0–27 | 0–4 none; 5–9 mild; 10–14 moderate; 15–19 mod-severe; 20–27 severe | **FREE** ("no permission required to reproduce, translate, display or distribute") | Validated (Sucitra et al. 2026, *Assessment*; PMCID 40926676) |
| GAD-7 | Anxiety screen (2 weeks) | 7 | 0–3 | 0–21 | 0–4 minimal; 5–9 mild; 10–14 moderate; ≥15 severe; screen+ ≥8 | **FREE** (same Pfizer/Spitzer terms as PHQ) | Validated (Budikayanti et al. 2019; α=0.867, cutoff >6, sens 100%, spec 84.4%) |
| PHQ-2 / GAD-2 | Ultra-short screeners | 2 each | 0–3 | 0–6 | ≥3 = positive screen | **FREE** (subset of PHQ/GAD) | Inherits parent validation |
| DASS-21 | Depression + Anxiety + Stress | 21 (3×7) | 0–3 | 0–63 (×2 vs DASS-42) | Bands per subscale (below) | **FREE / PUBLIC DOMAIN** ("permission is not needed", may charge for assessment itself; no open public posting) | Widely used; official policy requires public-domain translations |
| PSS-10 | Perceived stress (1 month) | 10 | 0–4 | 0–40 | No official cutoffs (not diagnostic); higher = more stress | **PERMISSION-REQUIRED** (via MAPI Trust/ePROVIDE; free for academic nonprofit, commercial use needs license) | Translations exist but are translator IP — clearable |
| SRQ-20 | Common mental disorders (30 days) | 20 | Yes/No (0–1) | 0–20 | Classic cutoff 7/8; country studies range 4–6 | **FREE** (WHO instrument, freely reproducible) | Used in Indonesian community studies (Aceh, etc.) |
| MBI | Burnout | 16–22 | 0–6 (frequency) | per subscale | Low/moderate/high per subscale | **PAID — NO-GO** (Mind Garden: pay per administration, license to administer, no translation rights) | Available with MBI license purchase only |
| CBI (Copenhagen) | Burnout | 19 (6+7+6) | 0–4 (100/75/50/25/0%) | 0–100 per subscale | subscale mean ×25; higher = more burnout | **FREE / ATTRIBUTION** (public domain; cite Kristensen et al. 2005) | Back-translated in Indonesian studies (e.g., nurses, PLoS One 2023) |
| OLBI | Burnout | 16 (2×8) | 1–5 Likert | per subscale | Higher = more burnout (exhaustion, disengagement) | **FREE / ATTRIBUTION** ("freely available"; cite Demerouti/Bakker) | No prominent Indonesian validation found |

## 2. Per-instrument detail

### WHO-5 (already implemented — reference only)
- 5 positively-worded items, 6-point scale (0 "at no time" … 5 "all the time"), past 2 weeks.
- Raw sum 0–25 → ×4 = 0–100 percentage. Higher = better. Screening/monitoring only, not diagnostic.
- Cutoffs: ≤50 reduced wellbeing (follow-up), ≤28 very low (depression screen), ~10 points = clinically meaningful change.
- Psychometrics: α ≈ 0.91; validated cross-culturally (35-country IRT study, Sischka et al. 2020). 30+ official translations, no licensing restriction.
- Source: https://pmc.ncbi.nlm.nih.gov/articles/PMC12748132/

### PHQ-9 — depression
- 9 items, 0–3 each ("Not at all" … "Nearly every day"), past 2 weeks. Total 0–27.
- Bands: 0–4 none-minimal, 5–9 mild, 10–14 moderate, 15–19 moderately severe, 20–27 severe. ≥10 → treatment plan. Item 9 (suicidal ideation) positive → mandatory escalation to a competent assessor.
- License: free — Pfizer funded development; phqscreeners.com states no permission required to reproduce/translate/display/distribute.
- Indonesian: formal validation + measurement invariance (ID/DE/US): Sucitra et al., *Assessment* 2026, PMID 40926676.
- Sources: https://www.hiv.uw.edu/page/mental-health-screening/phq-9 ; https://www.phqscreeners.com/

### GAD-7 — anxiety
- 7 items, 0–3 each, past 2 weeks. Total 0–21 (+ unscored impairment question).
- Bands: 0–4 minimal, 5–9 mild, 10–14 moderate, ≥15 severe. ≥8 = reasonable GAD screen (sens 92%, spec 76%); ≥10 also screens panic/social anxiety/PTSD.
- License: same terms as PHQ — "No permission required to reproduce, translate, display or distribute." (Spitzer, Kroenke et al., Pfizer educational grant.)
- Indonesian: validated (epilepsy outpatients): α 0.867, best cutoff >6 (sens 100%, spec 84.4%, AUC 0.98). Budikayanti et al., *Neurol Res Int* 2019, PMC6582805.
- Sources: https://www.hiv.uw.edu/page/mental-health-screening/gad-7 ; https://pmc.ncbi.nlm.nih.gov/articles/PMC6582805/

### PHQ-2 / GAD-2 — ultra-short
- 2 items each (first two items of PHQ-9 / GAD-7), 0–3, total 0–6. ≥3 = positive screen.
- PHQ-2 at cutoff 3: sens 82.9%, spec 90.0% for MDD. GAD-2 at ≥3: sens 86%, spec 83% for GAD.
- Use as funnel step: positive PHQ-2 → full PHQ-9; positive GAD-2 → full GAD-7.
- Sources: https://www.hiv.uw.edu/page/mental-health-screening/phq-2 ; https://www.hiv.uw.edu/page/mental-health-screening/gad-2

### DASS-21
- 21 items, 3 subscales × 7 (Depression, Anxiety, Stress), 0–3 scale ("Did not apply to me at all" … "Applied very much"), past week.
- Scoring: sum each subscale, ×2 to compare with DASS-42 norms. Missing data: tolerate 1 missing item per subscale (impute subscale mean).
- Severity bands (per Lovibond manual, subscale ×2 values):
  - Depression: normal 0–9, mild 10–13, moderate 14–18, severe 19–21, extremely severe 22+
  - Anxiety: normal 0–7, mild 8–9, moderate 10–14, severe 15–17, extremely severe 18+
  - Stress: normal 0–14, mild 15–18, moderate 19–25, severe 26–33, extremely severe 34+
- License: public domain. "Permission is not needed to use it"; scales may be downloaded/copied without restriction. MAY charge for an assessment that includes DASS. MUST NOT post on a public open website — access must be restricted to enrolled participants (fits a logged-in B2B platform). Translations must also be public domain.
- Citation: Lovibond & Lovibond (1995), *Manual for the DASS*, 2nd ed.
- Caveat: manual (with cutoff table) is sold (AUS$55), but the cutoffs above are the standard published values. Cutoffs are conventions on a dimensional construct, not diagnoses.
- Source: http://www2.psy.unsw.edu.au/dass/DASSFAQ.htm

### PSS-10
- 10 items, 0–4 ("never" … "very often"), past month. 4 positively-worded items reverse-scored → total 0–40, higher = more perceived stress.
- NOT diagnostic; no official cutoffs — compare within sample or against published norms (Cohen & Janicki-Deverts 2012).
- License: distributed by Mind Garden; permission requests via MAPI Research Trust (ePROVIDE) — free of charge to request, academic/nonprofit typically granted free, commercial use requires a paid license. Translations are translator IP.
- Verdict: **skip for v1** — weak fit (no bands → weak product UX; commercial clearance needed).
- Sources: https://www.cmu.edu/dietrich/psychology/stress-immunity-disease-lab/scales/ ; https://eprovide.mapi-trust.org

### SRQ-20
- 20 yes/no items on symptoms over past 30 days; 1 point per "yes", max 20. Designed by WHO for primary-care CMD screening in LMICs, lay-administered, ~5 min.
- Classic cutoff 7/8 widely used; country-specific optima vary (5–6 in several LMIC studies; 7 in Zambia, sens 0.85/spec 0.94). Screen only — not a substitute for clinical diagnosis.
- License: WHO instrument, freely reproducible.
- Indonesian: used in Indonesian community studies (Aceh post-disaster surveys, primary care), though a single flagship validation paper was not confirmed in indexed literature.
- Verdict: **skip for v1** — PHQ-9/GAD-7 are more precise, have better Indonesian validation, and give richer severity bands.

### MBI — PAID, NO-GO
- Copyright Maslach & Jackson, published by Mind Garden. "License to Administer" is pay-per-administration (every survey open = 1 administration, billed), PDF licenses expire in 3 years, no translation rights (translations only with license purchase), no instrument modification, no sample-item publication without a $100+ publication agreement.
- Do not use in a commercial B2B SaaS.

### CBI (Copenhagen Burnout Inventory) — free alternative
- 19 items, 3 subscales: Personal (6), Work-related (7), Client-related (6).
- Response: 5 frequency options scored 100/75/50/25/0 (never→always). Subscale score = mean of items → 0–100, higher = more burnout. One reverse item in work subscale ("energy for family/friends") — must be reverse-keyed.
- Public domain (criterion: fatigue/exhaustion, no commercial publisher). Cite Kristensen et al. 2005, *Work & Stress* 19:192–207.
- Indonesian: back-translated and used in Indonesian nurse studies (α ≈ 0.89; PLoS One 2023, PMC10564161) — usable as workplace burnout screen.

### OLBI
- 16 items, 2 subscales × 8: Exhaustion, Disengagement. 1–5 Likert, half items reverse-keyed (Disengagement R: 1,7,13,15; Exhaustion R: 5,10,14,16; higher = more burnout after reversal). "Freely available" instrument (Demerouti/Bakker).
- No prominent Indonesian validation found → **second choice after CBI**.

## 3. Scoring engine — instrument-as-data pattern

Pragmatic pattern (REDCap-style): the instrument is configuration, not code.

```
instrument:  { code, title, license_note, time_window, citation }
item:        { instrument_code, order, key, text, scale_type,
               options: [{label, value}] }        // value = weight
scoring:     { type: sum | sum_then_multiply | subscale_sum,
               subscale: [item_keys...], multiplier, reverse_items: [keys],
               range: {min, max} }
band:        { instrument_code, subscale?, min, max, label, severity,
               action }   // e.g. "flag HR", "show help resources", "escalate"
```

- One generic `score()` engine: collect item→value, apply reversals, aggregate per scoring config, resolve band by range lookup, return {score, band, action}.
- Adding PHQ-9/GAD-7/CBI becomes data insertion (seed file), zero new code — matches how WHO-5 was done.
- REDCap reference: https://projectredcap.org/ (survey logic + scoring computed from field metadata — same idea).
- Guardrails: never store raw PII item text in logs; item 9 (PHQ-9) positive must always route to an escalation action regardless of total score.

## 4. Recommended starter set (screening funnel)

WHO-5 (exists) → add 4:

1. **PHQ-9** — core depression screen, free, validated Indonesian, clean bands + critical item-9 escalation path.
2. **GAD-7** — core anxiety screen, free, validated Indonesian (cutoff 6 local), clean bands.
3. **CBI** — the only credible free burnout instrument; workplace-specific subscales (work/client) directly serve a B2B narrative.
4. **PHQ-2 + GAD-2** — not separate instruments, but a 4-item "pulse check" mode between full screenings: positive → trigger full PHQ-9/GAD-7.

Skip: MBI (paid, NO-GO), PSS-10 (permission + no bands), SRQ-21/OLBI (redundant or weaker Indonesian evidence).

Funnel: monthly WHO-5 pulse → quarterly PHQ-2/GAD-2 → full PHQ-9/GAD-7 on positive screen or low WHO-5 → CBI semi-annually. All four are free/attribution-only — no legal blocker for a commercial B2B platform.

## 5. Key URLs

- WHO-5 (validation review): https://pmc.ncbi.nlm.nih.gov/articles/PMC12748132/
- PHQ-9: https://www.hiv.uw.edu/page/mental-health-screening/phq-9 ; https://www.phqscreeners.com/
- PHQ-9 Indonesian (PMID 40926676): https://pubmed.ncbi.nlm.nih.gov/40926676/
- GAD-7: https://www.hiv.uw.edu/page/mental-health-screening/gad-7
- GAD-7 Indonesian: https://pmc.ncbi.nlm.nih.gov/articles/PMC6582805/
- PHQ-2 / GAD-2: https://www.hiv.uw.edu/page/mental-health-screening/phq-2 ; https://www.hiv.uw.edu/page/mental-health-screening/gad-2
- DASS (public domain + FAQ): http://www2.psy.unsw.edu.au/dass/DASSFAQ.htm
- CBI structure & psychometrics: https://pmc.ncbi.nlm.nih.gov/articles/PMC6121611/
- CBI in Indonesian nurses: https://pmc.ncbi.nlm.nih.gov/articles/PMC10564161/
- SRQ-20 (example validation): https://pmc.ncbi.nlm.nih.gov/articles/PMC10112468/
- PSS (Cohen, permission via MAPI): https://www.cmu.edu/dietrich/psychology/stress-immunity-disease-lab/scales/ ; https://eprovide.mapi-trust.org
- MBI (Mind Garden, paid): https://www.mindgarden.com/117-maslach-burnout-inventory-mbi ; https://www.mindgarden.com/content/23-faq
- OLBI (transcultural adaptation, "freely available"): https://pmc.ncbi.nlm.nih.gov/articles/PMC6422925/
