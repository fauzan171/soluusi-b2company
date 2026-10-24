Last login: Fri Jul 31 04:05:17 on console


┌───────────────────────────────────────────────────────────────>
│~ via 🐍 v3.14.1 
└> cd soluusi-b2b-beforemarge                            󱙺 FORGE  glm-4.6 HIG 

┌───────────────────────────────────────────────────────────────>
│~/soluusi-b2b-beforemarge 
└> claude                                                󱙺 FORGE  glm-4.6 HIG 
╭─── Claude Code v2.1.193 ─────────────────────────────────────────────────────╮
│                                                    │ Tips for getting        │
│                    Welcome back!                   │ started                 │
│                                                    │ Ask Claude to create a… │
│                      ▗ ▗   ▖ ▖                     │ ─────────────────────── │
│                                                    │ What's new              │
│                        ▘▘ ▝▝                       │ Added `CLAUDE_CODE_MCP… │
│                                                    │ Added conditional `if`… │
╭─── Claude Code v2.1.193 ─────────────────────────────────────────────────────╮
│                                                   │ Tips for getting started │
│                   Welcome back!                   │ Ask Claude to create a … │
│                                                   │ ──────────────────────── │
│                     ▗ ▗   ▖ ▖                     │ What's new               │
│                                                   │ Added `CLAUDE_CODE_MCP_… │
│                       ▘▘ ▝▝                       │ Added conditional `if` … │
│                                                   │ Added timestamp markers… │
│   glm-5.2 with xhigh effort · API Usage Billing   │ /release-notes for more  │
│             ~/soluusi-b2b-beforemarge             │                          │
╰──────────────────────────────────────────────────────────────────────────────╯


❯ /model                                                                        
  ⎿  Set model to glm-5.2 and saved as your default for new sessions

❯ jadi gw mau membuat project b2b untuk soluusi.com, kan soluusi.com adalah b2c 
  dan ini gw mau membuat untuk detailnya akana gw kriim seperti apa             
  
  Thought for 21s, listed 1 directory (ctrl+o to expand)

⏺ Direktori kosong, jadi mulai dari nol. Oke, gw siap ngerimvin detailnya —
  kirim aja spec-nya mau B2B-nya jadi apa, fitur apa aja, tech stack kalo ada
  preferensi, nanti gw kerjakan sesuai ladder (reuse/stdlib/native dulu sebelum
  nambah code).

✻ Churned for 32s

※ recap: Kamu bikin project B2B baru untuk soluusi.com di folder kosong 
  /Users/mekari/soluusi-b2b-beforemarge. Spec detailnya belum dikirim. Next 
  action: tunggu kamu kirim spec fitur dan tech stack, lalu mulai kerja. 
  (disable recaps in /config)

❯ baca pdf nya dulu itu unutk HAP yang nawarin klink kurang lebih seperti itu   
  dan gw mau lu fahami akan ada b2b unutk ke klink dan kemduain ke sekolah,     
  kemudian ke hrd/ unutk kantor                                                 
  
  Thought for 19s, searched for 1 pattern (ctrl+o to expand)

⏺ Bash(for f in *.pdf; do echo "=== $f ==="; mdls -name kMDItemNumberOfPages
      "$f" 2>/dev/null | awk '{print $2" pages"}'; ls -la "$f" | awk '{print $5"
      bytes"}'; done)
  ⎿  === 01 - Soluusi HAP Handbook.pdf ===
     - pages
     129621 bytes
     … +9 lines (ctrl+o to expand)

  Thought for 2s (ctrl+o to expand)

⏺ Bash(which pdfinfo qpdf mutool 2>/dev/null; python3 -c "
      import subprocess, glob…)
  ⎿  /usr/local/bin/pdfinfo
     qpdf not found
     mutool not found
     … +4 lines (ctrl+o to expand)

  Thought for 12s, read 4 files (ctrl+o to expand)

⏺ PDF-nya kayaknya image-based (scan) — text belum keluar. Gw extract manual
  pake OCR:

⏺ Bash(cd "/Users/mekari/soluusi-b2b-beforemarge"; for f in *.pdf; do echo
      "==================== $f ===================="; pdftotext -layout "$f" -
      2>/dev/null | head…)
  ⎿  ==================== 01 - Soluusi HAP Handbook.pdf ===================
     =
                          DOC 1 OF 4
     … +30 lines (ctrl+o to expand)
     
  Thought for 8s (ctrl+o to expand)

⏺ Text ada kok. Gw baca full isi semua dokumen:
     
⏺ Bash(cd "/Users/mekari/soluusi-b2b-beforemarge"; echo "########## 01 HAP
      HANDBOOK ##########"; pdftotext -layout "01 - Soluusi HAP Handbook.pdf" -
      2>/dev/null)
  ⎿  ########## 01 HAP HANDBOOK ##########
                          DOC 1 OF 4
     … +148 lines (ctrl+o to expand)

  Thought for 6s (ctrl+o to expand)

⏺ Bash(cd "/Users/mekari/soluusi-b2b-beforemarge"; echo "########## 02 90DAY
      SUCCESS PLAN ##########"; pdftotext -layout "02 - Soluusi HAP 90Day
      Success Plan.pdf" - 2>…)
  ⎿  ########## 02 90DAY SUCCESS PLAN ##########
                                   DOC 3 OF 4
     … +235 lines (ctrl+o to expand)

  Thought for 9s (ctrl+o to expand)

⏺ Bash(cd "/Users/mekari/soluusi-b2b-beforemarge"; echo "########## 04
      PARTNERSHIP PLAYBOOK HPA ##########"; pdftotext -layout "04 - Soluusi
      Partnership Playbook HPA.p…)
  ⎿  ########## 04 PARTNERSHIP PLAYBOOK HPA ##########
                                                                  Soluusi ·
      Partnership Playbook · HPA Program
     … +700 lines (ctrl+o to expand)
     
  Thought for 50s, read 1 file (ctrl+o to expand)

⏺ Sudah gw baca 4 dokumen (HAP Handbook, 90-Day Plan, Compensation Framework,
  Partnership Playbook). Gw faham. Ini rangkuman biar kita sama page:

  Soluusi intinya
     
  Marketplace konsultasi profesional (psikolog, nutrisionis, fisioterapis,
  konsultan karir, dll). 2026: 400+ konsultan, 28 provinsi, 5000+ kasus.

  3 pilar bisnis:
  - B2C (soluusi.com) — individu booking langsung
  - B2B — For Health Facilities · For School · For Company
  - B2Community — edukasi lewat komunitas

  Yang sudah ada dokumennya = vertikal For Health Facilities (Klinik)

  Ini kerjaan HAP/HPA. 4 skema kolaborasi ke klinik/faskes:

  ┌──────────────┬────────────────────────────────────────┬────────────────┐
  │    Skema     │                  Isi                   │   Nilai buat    │
  │              │                                        │     klinik      │
  ├──────────────┼────────────────────────────────────────┼─────────────────┤
  │ 1. Associate │ psikolog/nutrisionis/fisioterapis      │ tambah layanan  │
  │  Consultant  │ on-demand (online+offline)             │ tanpa biaya     │
  │              │                                        │ tetap SDM       │
  ├──────────────┼────────────────────────────────────────┼─────────────────┤
  │              │ skrining mental (MMCI/MMPI/DISC) +     │ value-add buat  │
  │ 2. MHCU      │ laporan agregat                        │ klien korporat  │
  │              │                                        │ klinik          │
  ├──────────────┼────────────────────────────────────────┼─────────────────┤
  │ 3. Edukasi & │ seminar/webinar mental health,         │ reputasi        │
  │  Aktivasi    │ parenting, burnout                     │ holistic care   │
  ├──────────────┼────────────────────────────────────────┼─────────────────┤
  │ 4. Benefit   │                                        │ retensi &       │
  │ Pasien       │ diskon 20% + free trial 10 menit       │ patient         │
  │              │                                        │ experience      │
  └──────────────┴────────────────────────────────────────┴─────────────────┘

  Kompensasi HAP 3-stage (anti-fraud, back-loaded): Rp250K di MoU+5 konsultasi →
  1/3 rate sisa di 25 konsultasi → 2/3 rate sisa di target 40 konsultasi
  (window 90 hari/klinik).

  Bagi hasil ke klinik (maks): harga konsultasi Rp250–299K→10%, 300–349K→15%,
  350–399K→20%, ≥400K→25%. Klinik bebas set harga ke pasien, % Soluusi
  rekomendasi (bisa lebih rendah sesuai negosiasi).

  Yang lu mau dibangun = 2 vertikal B2B sisanya

  Dari Handbook jelas B2B punya 3: For Health Facilities (✓ ada), For School, 
  For Company. Plus di Playbook disebut: pitch "wellbeing untuk karyawan
  internal klinik" = vertikal For Company yang bisa dijual paralel ke faskes
  yang sama. Jadi:

  - For Health Facilities (Klinik) — sudah lengkap (ini HAP/HPA)
  - For School (Sekolah) — belum ada dokumen, mau dibangun
  - For Company / HRD (Kantor) — belum ada dokumen, mau dibangun (employee
  wellbeing, MHCU korporat, EAP-style)

  Logikanya ketiganya bentuknya mirip: sama-sama punya ICP, skema kolaborasi,
  bagi hasil/kompensasi associate, playbook outreach → MoU → aktivasi. Bedanya
  decision-maker & use-case (sekolah = guru/murang/orang tua; company =
  HRD/People & Culture; klinik = owner/direktur).

  ---
  Gw sudah faham konteksnya. Sekarang kirim detailnya — mau mulai vertikal mana
  dulu (School / Company / sekalian), dan bentuknya apa (web portal B2B?
  playbook kayak HAP? dashboard? atau all-in-one platform). Kasih specnya, gw
  kerjakan.

✻ Brewed for 2m 27s

❯ gw mau lu jabarkan dulu semuanya coba ke document lu kira kira lengkap        
  dokumennya seperti apa masukan ke dalam isi lengkap all                       
  
  Thought for 1m 49s (ctrl+o to expand)

⏺ Oke, gw jabarkan semua ke satu master document. Ini dokumen lengkap yang
  nyatuin 3 vertikal B2B (Klinik, Sekolah, Company/HRD) + framework
  lintas-vertikal, struktur mirip kayak 4 dokumen HAP yang lu punya tapi
  di-extend.

⏺ Write(Soluusi B2B Master Playbook.md)
  ⎿  Wrote 1024 lines to Soluusi B2B Master Playbook.md
       2 ### Tiga Vertikal: For Health Facilities · For School · For Comp
         any
       3 
       5 > Versi 1.0 · Agustus 2026idential · Not for distribution
       6 > Konsolidasi dari HAP Handbook, 90-Day Plan, Compensation Frame
         work, dan Partnership Playbook + vertikal baru (School & Company
       7 )
       8 ---
       9
      10 ## Daftar Isi
     … +1405 lines (ctrl+o to expand)

  Thought for 19s (ctrl+o to expand)

⏺ Dokumen lengkap udah gw bikin di:
     
  /Users/mekari/soluusi-b2b-beforemarge/Soluusi B2B Master Playbook.md

  Struktur: 6 bagian + 3 lampiran.

  ┌────────────────────┬────────────────────────────────────────────────────┐
  │       Bagian       │                        Isi                         │
  ├────────────────────┼────────────────────────────────────────────────────┤
  │ 0 · Fondasi        │ Tentang Soluusi, 3 vertikal B2B, peran associate   │
  │                    │ (HAP/SAP/CAP), partnership journey                 │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │ 1 · Health        │ Ringkas dari 4 dokumen HAP lu — ICP, 4 skema, bagi  │
  │ Facilities        │ hasil, KPI                                          │
  │ (Klinik)          │                                                     │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │ 2 · School        │ baru — 6 skema (Konseling Siswa, MHCU Sekolah,      │
  │ (Sekolah)         │ Edukasi/Parenting, Wellbeing Guru, Konsultan Karir, │
  │                   │  Benefit Keluarga)                                  │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │ 3 · Company       │ baru — 7 skema (EAP, MHCU Korporat, Wellbeing       │
  │ (HRD/Kantor)      │ Program, Outplacement, Benefit Keluarga,            │
  │                   │ Nutrionis/Fisio, Konseling Krisis)                  │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │                   │ Prospecting, outreach (template per vertikal),      │
  │ 4 · Framework     │ discovery questions, meeting flow, objection,       │
  │ Lintas-Vertikal   │ negosiasi, red flags, escalation, MoU, onboarding,  │
  │                   │ aktivasi, follow-up, SLA, reporting, etika          │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │ 5 · Compensation  │ 3-stage payout per vertikal + team bonus            │
  ├───────────────────┼─────────────────────────────────────────────────────┤
  │ 6 · 90-Day Plan   │ Per vertikal + weekly report template               │
  └───────────────────┴─────────────────────────────────────────────────────┘

  Yang gw design fresh & wajib lu cek/koreksi:
  
  1. Naming — gw pake SAP (School Partnership Associate) & CAP (Corporate
  Partnership Associate) untuk konsisten sama HAP. Ganti kalo lu udah punya nama
  lain.
  2. Model komersial beda per vertikal (ini desain gw, bukan dari dokumen asli):
    - Klinik → bagi hasil per sesi (sudah ada di dokumen)
    - Sekolah → 3 model: subscription/siswa/tahun, pay-per-use + diskon ortu,
  flat fee MHCU massal. Bagi hasil ke sekolah sengaja gw bikin lebih rendah
  (8–15%) karena sekolah gak berorientasi margin dari siswa.
    - Company → PEPM subscription + pay-per-use + MHCU flat. Ini standar EAP
  global.
  3. Angka rate komisi indikatif (Lampiran B) — gw bikin konsisten rasio flat : 
  1/3-sisa : 2/3-sisa:
    - HAP Rp1.000.000 (250/250/500)
    - SAP Rp1.500.000 (300/400/800)
    - CAP Rp3.000.000 (600/800/1.600)

  HAP-nya gw reverse-engineer biar match sama dokumen asli (Stage1 Rp250K +
  rasio 1:2). SAP & CAP gw scale naik karena deal size lebih besar. Angka ini 
  asumsi gw — confirm/adjust.
  4. Confidentiality rule (penting, gw tegaskan di 3 tempat): konten sesi
  karyawan/siswa selalu confidential. HR cuma dapat laporan agregat; BK cuma
  BK+psikolog Soluusi yang akses data individual siswa. Ini bikin deal Company &
  School defensible.
  5. Cross-vertikal — gw tanam di 3 tempat (§0.3, §3.1, §4.14): mitra klinik
  bisa di-upsell For Company (wellbeing staf klinik), mitra sekolah bisa
  di-upsell For Company (wellbeing guru). Ini leverage 1 pintu untuk 2 vertikal.

  ---
  Langkah berikutnya — pilih:
  - A) Review dokumennya, koreksi angka/naming yg salah, gw update
  - B) Lanjut bikin deck/slide per vertikal dari dokumen ini
  - C) Mau gw bikinin template Google Sheets pipeline tracker struktur per
  vertikal (karena di playbook disebut pipeline tracking belum ada)
  - D) Mau gw bikin prototype web portal B2B-nya (terpisah dari soluusi.com,
  untuk onboarding mitra + dashboard)

  Yang mana dulu? Atau kirim detail lain yg lu simpen.

✻ Sautéed for 7m 16s

※ recap: Building a B2B master playbook for Soluusi across three verticals 
  (Klinik, Sekolah, Company/HRD). I drafted the full document to `Soluusi B2B 
  Master Playbook.md`; next is for you to review it and correct the indicative 
  commission numbers and SAP/CAP naming I invented. (disable recaps in /config)

❯ gw mau lu membuat documentation unutk membuat poroject ini dengna menyusun    
  susunan isis projectya seperi memebuat prd lebngkap all flow tergambarkan dan 
  juga gw mau lu bisa mencari kompetitor yang ada unutk yang membuat sama       
  seperti product yang aakn gw buat ini                                         
  
  Thought for 44s (ctrl+o to expand)

⏺ Oke, dua deliverable: (1) PRD lengkap dengan semua flow, (2) riset kompetitor.
  Gw mulai riset dulu — search paralel untuk kompetitor Indonesia + benchmark
  global di 3 vertikal:

⏺ Web Search("Riliv B2B corporate mental health employee wellbeing Indonesia
            platform")
     
⏺ Web Search("Indonesia EAP employee assistance program mental health perusahaan
            startup 2025 2026")
     
⏺ Web Search("marketplace konsultasi psikolog Indonesia B2B klinik partnership
            Riliv Bicarakan KALM")
     
⏺ Web Search("school mental health platform Indonesia konseling siswa BK digital
            sekolah")
            sekolah")
  ⎿  Did 0 searches in 4s


⏺ Web Search("Spring Health Lyra Health corporate mental health EAP platform
⏺ Web Search("Spring Health Lyra Health corporate mental health EAP platform
            features 2026")
  ⎿  Did 0 searches in 6s

⏺ Web Search("school student wellbeing platform Move This World Panorama BASE
            Education features")
  ⎿  Did 0 searches in 12s

⏺ Web Search("mental health check up MHCU skrining MMPI MMCI corporate dashboard
            platform Indonesia")
  ⎿  Did 0 searches in 17s

⏺ Web Search("Headspace for Work Calm Business Modern Health BetterUp corporate
            wellbeing pricing model")
  ⎿  Did 0 searches in 9s

  Thought for 27s (ctrl+o to expand)

⏺ Bagus, gw udah dapet landscape-nya. Sekarang gw dive deeper ke situs
  kompetitor langsung buat fitur & model bisnisnya:

⏺ Fetch(https://riliv.co)
  ⎿  Received 46.4KB (200 OK)
     
⏺ Fetch(https://bicarakan.id)
  ⎿  Received 715.8KB (200 OK)
     
⏺ Fetch(https://www.kalm.app)
  ⎿  Received 784 bytes (307 Temporary Redirect)
     
⏺ Fetch(https://www.springhealth.com)
  ⎿  Received 300.9KB (200 OK)
     
  Thought for 18s (ctrl+o to expand)

⏺ WebFetch lagi error backend. Gw pivot ke search targeted + retry:
     
⏺ Web Search("Riliv business corporate pricing features psychologist counseling
            app Indonesia 2026")
     
⏺ Web Search("Spring Health measurement based care EAP SpringGO pricing PEPM
            enterprise features")
     


⏺ Web Search("Lyra Health EAP platform features providers measurement based care
            therapy 2026")
  ⎿  Did 0 searches in 9s

⏺ Web Search("Naluri mental health corporate wellness Indonesia Malaysia
            features") 
  ⎿  Did 0 searches in 6s

⏺ Web Search("Sekolah.id Ruangguru wellness student mental health Indonesia
            platform konseling")
  ⎿  Did 0 searches in 2s

⏺ Fetch(https://riliv.co/business)
  ⎿  Received 0 bytes (404 Not Found)

  Thought for 1m 10s (ctrl+o to expand)

⏺ Search tool lagi unstable (looping + error backend). Tapi gw udah dapet data
  cukup: landscape Indonesia (Riliv, Bicarakan, KALM, Naluri, Yosh, Intelens) +
  benchmark global (Spring Health, Lyra, Headspace/Calm, Modern Health,
  BetterUp) + school platforms (Move This World, Panorama, BASE) + data EAP
  pricing & regulasi. Gw simpen ke file, lanjut tulis PRD.

⏺ Write(Soluusi B2B - Competitor Analysis.md)
  ⎿  Wrote 208 lines to Soluusi B2B - Competitor Analysis.md
      2 ### Riset kompetitor untuk platform B2B 3-vertikal (Health Facili
        ties · School · Company)
      3 
      4 > Versi 1.0 · Agustus 2026
      5 > Sumber: web search + domain knowledge. Beberapa tool search bac
        ifikasi langsung ke situs kompetitor sebelum final positioning**.
      6 
      7 ---
      8
      9 ## 0. Ringkasan Eksekutif
     … +308 lines (ctrl+o to expand)

  Thought for 23s (ctrl+o to expand)

⏺ Sekarang PRD-nya — lengkap dengan semua flow (pakai mermaid diagram biar
  gambang dibaca, render di GitHub/VSCode/ChatGPT):

⏺ Write(Soluusi B2B Platform - PRD.md)
  ⎿  Wrote 745 lines to Soluusi B2B Platform - PRD.md
      2 ### Platform terintegrasi untuk 3 vertikal: For Health Facilities
         · For School · For Company
      3 
      4 > Versi 1.0 · Agustus 2026
      5 > Owner: Founder Soluusi
      6 > Status: Draft untuk review
      7 > Dokumen pendukung: `Soluusi B2B Master Playbook.md`, `Soluusi B
        2B - Competitor Analysis.md`
      8
      9 ---
     … +905 lines (ctrl+o to expand)

  Thought for 7m 49s (ctrl+o to expand)

⏺ Done. Dua file baru:
     
  Soluusi B2B - Competitor Analysis.md — riset kompetitor
  - Direct Indonesia: Riliv (⭐#1, closest), Bicarakan.id, KALM (domain parked —
  verify), Naluri, Yosh!, Intelens 
  - Adjacent: Halodoc/Alodokter, Talenta (Mekari), PsikoLIVE
  - Benchmark global: Spring Health (⭐MBC pattern), Lyra, Headspace/Calm,
  Modern Health, BetterUp
  - School global: Move This World, Panorama, BASE Education — Indonesia minim 
  pemain dedicated = blue ocean untuk SAP
  - Matriks kompetitif + 6 pillar positioning + ancaman/peluang + sources
  - Key insight: celah Soluusi = multi-vertikal + multi-profesional + hybrid
  online/offline + bagi-hasil fleksibel. Kompetitor mayoritas app-only,
  psikolog-only, corporate-only.

  Soluusi B2B Platform - PRD.md — PRD lengkap, semua flow tergambarkan (10
  mermaid diagram)
  - Vision, problem, north star (konsultasi terlaksana/mitra/90 hari = 40, bukan
  jumlah MoU — selaras anti-fraud playbook) 
  - 7 persona + role/permission matrix
  - Scope MVP (9 modul) vs Phase 2 vs out-of-scope
  - System architecture (modular monolith, BFF per vertikal, multi-tenant RLS)
  - 10 module spec: CRM, Partner Portal, Booking, Session, MHCU, Commission
  Engine, Voucher, Reporting, Cross-Sell, Notification
  - 10 user flow mermaid: acquisition → onboarding (Hari 1-30) → booking
  (sequence) → session confirmation + commission trigger (anti-fraud core) →
  MHCU skrining (dengan confidentiality boundary) → cross-sell → bagi-hasil calc
  → commission payout (sequence) → notification cadence → vertical-aware
  routing
  - ERD data model + confidentiality tags
  - Integrations, NFR (security UU PDP + HIMPSI), tech stack, roadmap gantt,
  risks, 8 open questions
  - Traceability table: playbook section → PRD module

  Yang gw sengaja skip / tandai open (perlu lu putuskan):
  1. Tech stack — gw propose tapi suggest match stack soluusi.com B2C biar share
  team. Lu tau stack B2C-nya apa? 
  2. Cross-sell attribution komisi (associate bawa mitra klinik → mitra jadi
  Company vertical, komisi ke siapa)
  3. Konsultan fee model (belum ada di playbook — flat per sesi / bagi hasil?)
  4. White-label depth untuk perusahaan
  5. License MMCI/MMPI sendiri vs via psikolog partner
  6. Final pricing tier (PEPM company, subscription sekolah) — playbook
  indikatif, finance confirm
  7. Self-serve vs associate-only GTM long-term
  
  ⚠️  Caveat: search tool sempat flaky (looping + error backend "Model not
  exist"), jadi sebagian data kompetitor dari domain knowledge. Wajib verify ke 
  situs kompetitor langsung sebelum finalize positioning — terutama status KALM
  & pricing Riliv/Bicarakan.

  ---
  Langkah berikutnya pilih:
  - A) Jawab open questions (terutama stack B2C + konsultan fee + cross-sell
  attribution) → gw finalize PRD
  - B) Lu mau gw bikinin mockup/wireframe per vertikal dari flow ini (bisa pake
  image generation klo lu mau visual, atau ASCII/mockup markdown)
  - C) Mau gw bikin technical design / data schema SQL detail dari ERD
  - D) Mau gw start bikin prototype code MVP-nya (pilih modul mana dulu — CRM /
  Partner Portal / Booking)

  Yang mana?

✻ Churned for 17m 42s

❯ yang paling dipentingkan adalah unutk partner ke klink ini yang akan di       
  awalkan dulu jadi, nanti kita akan supplay consultan ke klink nanti klinik    
  akan memilih consultanya yang ada dan nanti systemnya akan akan seperti itu   
  kurang lebih e2e nya jadi nantinya kliniknya akan bayar ke soluusi karena     
  costomernya pake consultan soluusi hanya saja sudah dimarkup harnya sama      
  kliniknya kita terima bersih aja, kamu faham?                                 
  
  Thought for 3m 47s (ctrl+o to expand)

⏺ Faham. Gw rangkum dulu model revenuenya biar sama-pastinya, terus e2e
  flow-nya.

  Model revenue (klinik vertikal) — confirm ini bener:

  Markup model, Soluusi terima net:
  - Soluusi punya net price per sesi (per jenis konsultan:
  psikolog/nutrionis/fisio) 
  - Klinik markup di atas net price → harga ke pasien (klinik yang set, sesuai
  positioning klinik)
  - Pasien bayar ke klinik (di harga marked-up)
  - Klinik bayar ke Soluusi (net price) → Soluusi terima bersih
  - Klinik simpan markup sebagai margin mereka. Soluusi gak balik-bayar apa-apa
  ke klinik (no referral/bagi-hasil terpisah ke klinik) 

  Contoh angka:

  ┌──────────────────────────────────────────────────────┬──────────────────┐
  │                         Item                         │     Nominal      │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Soluusi net price (1 sesi psikolog)                  │ Rp250.000        │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Klinik set harga pasien (markup)                     │ Rp300.000        │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Pasien bayar ke klinik                               │ Rp300.000        │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Klinik bayar ke Soluusi                              │ Rp250.000        │
  │                                                      │ (net/bersih)     │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Margin klinik (keep)                                 │ Rp50.000         │
  ├──────────────────────────────────────────────────────┼──────────────────┤
  │ Dari Rp250k net → Soluusi bayar konsultan fee (mis.  │                  │
  │ Rp150k) + akrual komisi HAP (Stage 1/2/3) + gross    │                  │
  │ Soluusi ~Rp100k                                      │                  │
  └──────────────────────────────────────────────────────┴──────────────────┘

  → Ini sebenernya sama kayak tabel "bagi hasil" di playbook, cuma framingnya
  dibalik: "bagi hasil % ke klinik" = margin markup klinik. Untuk harga pasien
  Rp300–349k, margin klinik max 15% → Soluusi net min 85%. Makanya "max %" —
  ngasih batas markup wajar.

  E2E flow klinik vertikal (consultant-supply model):

  1. MoU (HAP)
     - agree: jenis konsultan dibutuhin, net price/sesi, markup cap, payment
  terms, PIC teknis

  2. Soluusi assign konsultan pool → klinik
     - match: psikolog anak/nutrisionis/fisio sesuai kebutuhan klinik (dari
  discovery)

  3. Konsultan publish availability slot → Klinik Portal
     - online (video) + offline (di klinik), per cabang/lokasi

  4. Pasien butuh konsultasi
     - walk-in ke klinik / klinik refer / (opsional) pasien self-book via QR

  5. Staff klinik (resepsionis) pilih konsultan dari pool di portal → create
  booking
     - lihat slot kosong → pilih konsultan → jadwalkan
     - (opsional) pasien self-book via QR booking page

  6. Booking confirmed → konsultan notified → pasien/reminder kirim

  7. Konsultasi jalan (online link / offline di klinik)

  8. Konsultan + pasien check-in → sesi run

  9. Konsultan input notes (confidential) + dua2 confirm completed

  10. System calc: harga pasien (klinik), net Soluusi, margin klinik

  11. Klinik bill pasien (POS/kasir klinik sendiri) → pasien bayar ke klinik

  12. Sesi logged → weekly invoice: klinik owed Soluusi Σ(net price sesi minggu
  itu)

  13. Klinik pay Soluusi (transfer/gateway) → Soluusi terima net

  14. Dari net: Soluusi bayar konsultan fee + komisi HAP akrual (Stage1@5 sesi,
  Stage2@25, Stage3@40 dlm 90 hari)

  15. Klinik dashboard update aggregate (jumlah sesi, konsultan dipake, NO
  individual content)

  16. Commission engine evaluate stage trigger per klinik

  17. Cross-sell flag (klinik stabil ≥1 bln → upsell For Company: wellbeing staf
  klinik)

  Flow inti yang berbeda dari PRD general (fokus klinik): step 2 (assign pool),
  step 5 (klinik pilih konsultan), step 11–13 (klinik collect → pay Soluusi
  net). Itulah "supply konsultan ke klinik, klinik pilih, klinik bayar Soluusi
  bersih".

  3 titik yang gw ASSUME (perlu lu confirm, ngaruh ke design):

  1. Siapa yang collect pembayaran pasien? Gw assume klinik collect (klinik
  punya kasir/POS sendiri, Soluusi gak proses pembayaran pasien — Soluusi cuma
  track harga buat calc).
  Alternative: Soluusi proses pembayaran pasien (QR pay ke Soluusi, auto-split
  net↔klinik). Ini 2× scope payment module.
  2. Frekuensi invoice klinik→Soluusi: gw assume weekly consolidated invoice (1
  invoice per minggu berisi Σ sesi × net price).
  Alternative: per-sesi (tiap sesi generate invoice) — ribet buat klinik.
  3. Net price siapa yang set? Gw assume Soluusi set net price per sesi (per
  jenis konsultan), klinik markup freely tapi kena cap playbook (15%/20%/25%).
  Alternative: klinik set patient price, net Soluusi = patient − margin
  (capped).
  Hasilnya mirip, tapi beda sisi yang "pegang harga reference".

  Lu confirm 3 ini + model revenue di atas bener? Kalo iya, gw lock ke file
  Klinik E2E Flow Spec.md + update PRD section klinik, terus lanjut design
  detail (data schema booking/pool/invoice). Kalo ada yang salah, koreksi
  sekarang.

✻ Crunched for 4m 15s

❯ jadikan ke .md khusus klinik dan kmeudain kembangkan apa lagi yang seharunya  
  ada dan masukan ke situ                                                       
  
  Thought for 2m 3s (ctrl+o to expand)

⏺ Gw lock ke file khusus klinik + expand apa aja yang harus ada. 3 asumsi lu
  belum explicit-confirm, jadi gw tulis sebagai default (marked) — kalo salah
  tinggal koreksi setelah baca.

⏺ Write(Soluusi B2B - Klinik Vertical Spec.md)
      4 > Versi 1.0 · Agustus 2026
      5 > Vertical priority: **#1 — dibangun pertama**
        2B Platform - PRD.md``Soluusi B2B Master Playbook.md`, `Soluusi B
      7 > Status: Draft — asumsi ditandai `[ASSUMED]`, keputusan ditandai
         `[OPEN]`
      8
      9 ---
     … +1046 lines (ctrl+o to expand)
  ⎿  Referenced file Soluusi B2B - Klinik Vertical Spec.md
  ⎿  Referenced file Soluusi B2B Platform - PRD.md
  ⎿  Referenced file Soluusi B2B - Competitor Analysis.md
  ⎿  Referenced file Soluusi B2B Master Playbook.md
  ⎿  Referenced file ../.claude/projects/-Users-mekari-soluusi-b2b-beforemarge/9
     cd952eb-76a5-48a0-9c13-f4481e436ef4/tool-results/bhwkmnxlj.txt

  Thought for 1m 7s (ctrl+o to expand)

⏺ File created: Soluusi B2B - Klinik Vertical Spec.md
     
  Yang ada di file (25 section + 2 appendix):
  - E2E 17-step + money flow (per-sesi + HAP milestone) + contoh angka 40 sesi
  - Expansion yang lu minta — hal yang seharusnya ada selain e2e dasar:
    - §6 Pasien identity fork (CRITICAL, baru) — Model A (registered di Soluusi)
  vs Model B (anonymous, klinik owns pasien). Rekomendasi B. Ini belum
  ke-decide dan nunjukin scope PII/UU PDP
    - §11 Konsultan payment — sub-flow yang belum ada di playbook (fee model
  flat vs %, payout bulanan, vendor onboarding)
    - §7 edge cases — reschedule/no-show cutoff, walk-in, overbooking,
  multi-cabang
    - §10 billing depth — weekly consolidated invoice, reconciliation, dunning,
  credit note, tax
    - §9 anti-fraud depth — auto-confirm fallback, dispute, refund, audit trail
    - §15 lifecycle — onboarding checklist, aktivasi H1-30, churn detection,
  termination
    - §13-14 MHCU + Edukasi untuk klinik (Phase 2 boundary)
    - §19 multi-cabang grup, §20 notifications, §21 escalation matrix, §22 KPIs,
  §23 integrations
    - 7 mermaid flow (master e2e, onboarding, replacement, anti-fraud, billing
  sequence, konsultan payment sequence, churn)

  3 asumsi lu belum explicit-confirm — gw tulis sebagai default + tag [ASSUMED]:
  - [ASSUMED-1] klinik collect payment pasien
  - [ASSUMED-2] weekly consolidated invoice
  - [ASSUMED-3] Soluusi set net price

  Plus 17 [OPEN] decision items di §24 (konsultan fee model, pasien identity
  A/B, voucher bearer, PPN, dll) — semua dengan default rekomendasi gw. Confirm
  yang critical ([OPEN-6] pasien identity) dulu sebelum build karena nunjukin
  data schema.

  ▎ skipped: detail schema DB + API contract (perlu di PRD M3-M6, bukan vertical
  ▎ spec). add when lock [OPEN-6].

✻ Churned for 11m 36s

❯ jadi unutk flpownya klink, si klinik akan bayar pesen dulu consultannya       
  kemudian baru kasih ke costomer jadi agar mengantisipasi nanti kalau si klink 
  gak bayar ke kita, kalau mau accses consultannya harus pesan di di soluusi    
  dulu yang ada di dashabord nya                                                
  
  Thought for 4m 38s (ctrl+o to expand)

⏺ Ah, ini perubahan model yang penting — dari post-paid invoice jadi prepaid 
  wallet. Klinik top-up dulu di dashboard Soluusi baru bisa booking konsultan.
  efeknya: risiko klinik gak bayar hilang, dan billing/dunning subsystem
  ke-delete. Lebih aman + lebih simple. Gw update spec-nya.

  Efek ke data schema & security: balance klinik jadi asset kritis (int/decimal,
  concurrency lock saat deduct), transaction ledger immutable, idempotency key
  per booking. Anti-fraud tetap dual-confirm tapi risiko bergeser (bukan "klinik
  gak bayar" lagi).

  Gw patch 13 titik di file:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  File must be read first

  Thought for 2m 38s, read 1 file (ctrl+o to expand)

⏺ Sekarang gw patch semua titik yang kena ripple (27 edits — model prepaid
  merembet ke billing, roles, notif, KPI, mermaid, open-questions).
  Non-overlapping, batch sekaligus:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      46  - Konsultan pool supply + klinik pilih
      47  - Booking (online + offline)
      48  - Session + dual-confirm
      49 -- Billing: weekly consolidated invoice, klinik→Soluusi net     
      49 +- Billing: **prepaid wallet** (klinik top-up dulu → per-booking
         + deduct, no AR)                                                
      50  - Konsultan payment: Soluusi→konsultan flat/sesi
      51  - HAP commission engine (3-stage)
      52  - Klinik dashboard aggregate

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 5 lines, removed 4 lines
      63  
      64  ## 2. Revenue & Commercial Model
      65  
      66 -### Model inti: **Markup model, Soluusi terima net**           
      66 +### Model inti: **Markup model + PREPAID wallet, Soluusi terima
         + net di muka**                                                 
      67  - Soluusi punya **net price** per sesi (per jenis konsultan × d
          urasi × mode online/offline)
      68 +- Klinik **top-up wallet** di Soluusi dashboard di muka (sebelu
         +m bisa akses/booking konsultan) — **mitigasi risiko klinik gak
         +bayar** `[DECIDED — prepaid, per user]`
      69 +- Saat klinik **booking sesi**, sistem **deduct net price dari
         +wallet** → sesi CONFIRMED. Booking **blocked** bila saldo < net
         + price → klinik must top-up dulu
      70  - Klinik **markup** di atas net price → harga ke pasien (klinik
           yang set, sesuai positioning)
      69 -- **Pasien bayar ke klinik** (di harga marked-up) — klinik coll
         -ect `[ASSUMED-1]` 
      70 -- **Klinik bayar ke Soluusi** = net price → **Soluusi terima be
         -rsih** 
      71 -- Klinik **simpan margin markup**. Soluusi gak balik-bayar apa-
         -apa ke klinik (no separate referral/bagi-hasil payout ke klinik
         -) 
      71 +- **Pasien bayar ke klinik** (di harga marked-up) — **terpisah*
         +*, urusan klinik sendiri `[ASSUMED-1]`. Klinik **simpan margin
         +markup**. Soluusi gak balik-bayar apa-apa ke klinik
      72 +- Soluusi **sudah pegang uang net di muka** → **no AR, no colle
         +ction risk, no dunning** (billing/dunning subsystem di-delete —
         + see §10.6)
      73  
      74  ### Markup cap (dari Master Playbook §1.4)
      75  Margin klinik (markup) sebagai fraksi harga pasien dibatasi:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 16 lines, removed 8 lines
       92  
       93  ## 3. Money Flow (per-sesi + milestone)
       94  
       95 -### Per-sesi flow                                             
       95 +### Per-sesi flow (PREPAID)                                   
       96  ```
       97 -Pasien ──bayar Rp300k──> Klinik                               
       98 -Klinik ──bayar Rp250k (net)──> Soluusi                        
       99 -Klinik keep: Rp50k (margin) 
      100 -Soluusi from Rp250k net: 
      101 -   ├─ Konsultan fee: Rp150k (flat/sesi, pay to konsultan) 
      102 -   └─ Soluusi gross: Rp100k 
      103 -        └─ accrue toward HAP 3-stage commission milestone (buk
          -an per-sesi payout) 
       97 +[1] Klinik top-up wallet di Soluusi dashboard (mis. Rp2.500.00
          +0 = 10 sesi)
       98 +    → saldo wallet: Rp2.500.000
       99 + 
      100 +[2] Klinik booking sesi → sistem deduct Rp250k (net) dari wall
          +et
      101 +    → saldo: Rp2.250.000 | sesi CONFIRMED, konsultan dispatche
          +d
      102 +    → Soluusi SUDAH pegang net di muka (no collection risk)
      103 + 
      104 +[3] Sesi berlangsung + dual-confirm COMPLETED
      105 +    → Soluusi release dari net Rp250k yang sudah di-pegang:
      106 +       ├─ Konsultan fee: Rp150k (pay to konsultan, after confi
          +rm)
      107 +       └─ Soluusi gross: Rp100k → accrue HAP 3-stage milestone
      108 + 
      109 +[4] Terpisah, urusan klinik sendiri (bukan flow Soluusi):
      110 +    Pasien ──bayar Rp300k (marked-up)──> Klinik
      111 +    Klinik keep: Rp50k (margin) | billing klinik ke pasien, So
          +luusi gak peduli
      112  ```
      113  
      114  ### HAP commission milestone (per klinik, 90-day rolling)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      126  |---|---|
      127  | Pasien total bayar (40 × Rp300k) | Rp12.000.000 |
      128  | Klinik margin (40 × Rp50k) | Rp2.000.000 (keep) |
      129 -| Klinik → Soluusi net (40 × Rp250k) | Rp10.000.000 |         
      129 +| Klinik top-up wallet / deduct net (40 × Rp250k) | Rp10.000.0
          +00 |                                                          
      130  | Soluusi → Konsultan fee (40 × Rp150k) | Rp6.000.000 |
      131  | Soluusi gross | Rp4.000.000 |
      132  | Soluusi → HAP commission (3-stage total) | Rp1.000.000 |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      252  - Cutoff time: H-2 jam free reschedule; <2 jam = cancel (statu
           s counted)
      253  - Who can: staff klinik, konsultan, pasien (Model A only)
      254  - Cancel reason log (Playbook §4.23)
      255 -- No-show: klinik still pays Soluusi net? `[OPEN-7a]` — defaul
          -t: no-show NOT charged (goodwill), tapi tracked; >2 no-show/kl
          -inik → review                                                 
      255 +- No-show: klinik sudah prepaid (saldo sudah deduct). Refund p
          +olicy? `[OPEN-7a]` — default: cancel <H-2jam = 50% refund cred
          +it, 50% forfeit (compensate konsultan slot); ≥H-2jam = full re
          +fund. Tracked; >2 no-show/klinik → review
      256  
      257  ### 7.4 Walk-in handling
      258  - Walk-in pasien: staff create booking "now" atau "next slot"
           → konsultan check available real-time

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      264  
      265  ### 7.6 Multi-cabang
      266  - Klinik grup: slot publish per cabang; konsultan availability
            per cabang (offline) atau global (online)
      267 -- Booking tagged cabang → invoice per cabang OR consolidated H
          -O `[OPEN-8]`                                                  
      267 +- Booking tagged cabang → wallet per grup OR per-cabang `[OPEN
          +-8]`                                                          
      268  
      269  ---
      270  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines
      296  
      297  Core anti-fraud principle (Playbook §5.1): komisi cair **hanya
            setelah sesi nyata terkonfirmasi**, bukan MoU.
      298  
      299 +> **Prepaid shift (§2):** dengan wallet model, risiko "klinik 
          +gak bayar" sudah hilang (saldo di-pegang di muka). Anti-fraud 
          +sekarang fokus ke: (a) sesi **benar-benar terjadi** sebelum ko
          +nsultan fee di-release + HAP komisi di-accrue (klinik sudah pr
          +epaid, jadi insentif klinik = sesi terjadi, bukan fake); (b) H
          +AP/associate tidak bisa klaim sesi fake untuk trigger komisi.
          +**Dual-confirm masih wajib.**
      300 + 
      301  ### 9.1 Confirmation flow `[mermaid §25.4]`
      302  1. Konsultan + pasien check-in → session starts
      303  2. Konsultan input clinical notes (encrypted, konsultan-only)
           + topik kategori (anonim untuk aggregate)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 4 lines, removed 3 lines
      314  - Ops Soluusi review audit trail → resolve (credit note / kons
           ultan coaching)
      315  - `[OPEN-10]` — dispute SLA
      316  
      317 -### 9.4 Refund flow                                           
      318 -- Bila sesi cancelled after klinik paid Soluusi → credit note 
          -to klinik next invoice                                        
      319 -- Soluusi→konsultan: bila sesi cancelled before 24h → no konsu
          -ltan fee; after → partial/full `[OPEN-10a]` 
      317 +### 9.4 Refund flow (wallet credit)
      318 +- Sesi cancelled ≥ H-24h → **full refund credit** ke wallet kl
          +inik
      319 +- Sesi cancelled < H-24h or no-show → 50% refund credit, 50% f
          +orfeit `[OPEN-7a]`
      320 +- Soluusi→konsultan: bila sesi cancelled < H-24h → konsultan d
          +apat standby compensation dari 50% forfeit `[OPEN-10a]`; ≥ H-2
          +4h → no fee (slot bisa di-rebook)
      321  
      322  ---
      323  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 36 lines, removed 35 lines
      322  ---
      323  ---
      324  
      325 -## 10. Billing & Invoicing (Klinik → Soluusi)                 
      325 +## 10. Wallet & Prepayment (Klinik → Soluusi)                 
      326  
      327 -### 10.1 Invoice model `[ASSUMED-2]`                          
      328 -- **Weekly consolidated invoice** (1 invoice/klinik/minggu, li
          -ne items = sesi completed minggu itu × net price) 
      329 -- Generated auto every Monday (for previous week Mon–Sun compl
          -eted sesi) 
      330 -- Alternative: per-sesi (rejected — too noisy for klinik ops) 
      327 +> Model diubah dari post-paid invoice → **PREPAID wallet** (pe
          +r user, mitigasi risiko klinik gak bayar). Lihat §2 & §3. `[AS
          +SUMED-2]` weekly invoice **superseded**.
      328  
      332 -### 10.2 Invoice line item 
      333 -``` 
      334 -Klinik: [Nama Klinik] 
      335 -Periode: [Mon–Sun] 
      336 -Sesi completed: N 
      337 -  - Psikolog online × a @ Rp250k = RpXXX 
      338 -  - Nutrisionis offline × b @ Rp250k = RpYYY 
      339 -  ... 
      340 -Subtotal net: RpZ 
      341 -Voucher adjustment: -RpW (if Skema 4 borne by Soluusi) 
      342 -Total due: Rp(Z-W) 
      343 -Due date: +7 hari kerja 
      344 -``` 
      329 +### 10.1 Model `[DECIDED — prepaid]`
      330 +- Klinik **top-up wallet** di Soluusi dashboard sebelum bisa b
          +ooking konsultan
      331 +- Setiap booking **deduct net price** dari wallet saat sesi-cr
          +eate
      332 +- Booking **blocked** bila saldo < net price → klinik must top
          +-up dulu
      333 +- **No AR, no collection risk** — Soluusi pegang uang di muka
      334  
      346 -### 10.3 Payment methods (klinik→Soluusi) 
      347 -- Bank transfer (VA dedicated per klinik) 
      348 -- Payment gateway (optional, Phase 2 auto-collect) 
      349 -- Manual reconcile: Ops match transfer to invoice 
      335 +### 10.2 Top-up mechanism
      336 +- Klinik PIC (Partner Admin) top-up via payment gateway / VA /
          + bank transfer
      337 +- Pack options indikatif: Rp500k / Rp1M / Rp2.5M / Rp5M, atau
          +custom amount
      338 +- Saldo available setelah payment confirmed (gateway webhook =
          + instant; bank transfer = reconcile by Ops)
      339 +- Top-up log: amount, method, timestamp, PIC — immutable ledge
          +r
      340  
      351 -### 10.4 Reconciliation 
      352 -- Bank statement / gateway webhook → match to invoice → status
          - PAID 
      353 -- Unmatched → flag for Ops 
      341 +### 10.3 Per-booking deduction
      342 +- Saat booking create: validate saldo ≥ net price → deduct → b
          +ooking CONFIRMED → konsultan dispatched
      343 +- **Concurrency**: row-level lock / atomic decrement (saldo =
          +kritis asset) + idempotency key per booking (anti double-charg
          +e on retry)
      344 +- Voucher (Skema 4): deduct (net − voucher adj) dari wallet
      345 +- Sesi cancel ≥ H-24h: **full refund credit to wallet**
      346 +- Sesi cancel < H-24h or no-show: 50% refund credit, 50% forfe
          +it `[OPEN-7a]`
      347  
      355 -### 10.5 Overdue handling (dunning) 
      356 -- H+7: reminder WA/email 
      357 -- H+14: HAP follow-up ke PIC klinik 
      358 -- H+30: eskalasi founder; **suspend new booking** `[OPEN-11]`
          -(aggressive — confirm) 
      359 -- SLA: Playbook §4.16 
      348 +### 10.4 Low-balance management
      349 +- Threshold (saldo < 2 sesi net) → auto-reminder WA/email to P
          +IC
      350 +- Booking attempt with insufficient saldo → **in-flow top-up p
          +rompt** (jangan sampai booking intent hilang)
      351 +- Auto-recharge (Phase 2) — klinik set threshold + auto-debit
      352  
      361 -### 10.6 Credit note / adjustment 
      362 -- Disputed sesi, refund, voucher miscalc → credit note applied
          - to next invoice 
      363 -- Audit logged 
      353 +### 10.5 Wallet expiry `[OPEN-16]`
      354 +- Default: **no expiry** (goodwill, klinik-friendly)
      355 +- Alternative: 12-month expiry (working capital discipline). C
          +onfirm.
      356  
      365 -### 10.7 Tax 
      366 -- `[OPEN-12]` — PPN? Faktur pajak for klinik? Soluusi issue in
          -voice + tax as needed. Default: Soluusi issue invoice tanpa PP
          -N (jasa konsultasi psikolog — verify with finance/tax advisor)
          -. 
      357 +### 10.6 What this REPLACES (deletion vs old post-paid)
      358 +- ~~Weekly consolidated invoice~~ → real-time per-booking dedu
          +ction
      359 +- ~~Payment reconciliation klinik→Soluusi~~ → top-up reconcili
          +ation only (simpler)
      360 +- ~~Dunning H+7/H+14/H+30~~ → booking auto-blocked when saldo
          +kosong
      361 +- ~~Suspend booking at overdue~~ → not needed (saldo = gate)
      362 +- Net: billing/dunning subsystem **deleted**. Lebih simple, le
          +bih aman, lebih lazy.
      363  
      364 +### 10.7 Statement & reporting (replaces invoice)
      365 +- Monthly wallet statement to klinik: top-ups, sesi deducted,
          +saldo remaining, refund credits
      366 +- PDF via email (for klinik tax/audit record)
      367 +- `[OPEN-12]` PPN/faktur pajak on top-up? Default: Soluusi iss
          +ue monthly statement; faktur pajak per finance verify.
      368 + 
      369  ---
      370  ---
      371  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      497  - HAP pitch new vertical → attribution `[OPEN-13]`
      498  
      499  ### 15.5 Churn risk detection
      500 -- Signals: sesi declining 2 consecutive weeks, invoice overdue
          -, PIC unresponsive, NPS drop                                  
      500 +- Signals: sesi declining 2 consecutive weeks, wallet saldo re
          +ndah berkepanjangan / top-up pending, PIC unresponsive, NPS dr
          +op                                                            
      501  - Auto-flag → HAP/Ops intervention
      502  - Playbook §4.26 "top performer: always follow up, don't stop
           after MoU"
      503  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      503  
      504  ### 15.6 Termination flow
      505  - Notice period (per MoU, indikatif 30 hari)
      506 -- Wind-down: complete scheduled sesi, final invoice, data expo
          -rt to klinik, revoke konsultan assignment                     
      506 +- Wind-down: complete scheduled sesi, **refund remaining walle
          +t saldo** to klinik, final wallet statement, data export to kl
          +inik, revoke konsultan assignment                             
      507  - Post-termination: klinik data retained per UU PDP, konsultan
            reassigned
      508  - `[OPEN-14]` — termination reasons log + re-activation path
      509  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      514  
      515  | Role | Akses | Tidak boleh |
      516  |---|---|---|
      517 -| **Partner Admin** (owner/direktur/HR klinik) | Org profile, 
          -staff mgmt, markup config, invoice & payment, dashboard aggreg
          -ate, voucher create, konsultan assignment view | Konten sesi, 
          -data pasien individual, other klinik |                        
      518 -| **Partner Staff** (resepsionis/PIC) | Create booking, lihat 
          -konsultan availability, redeem voucher, view own cabang sesi a
          -ggregate | Invoice/billing, markup config, other cabang |
      517 +| **Partner Admin** (owner/direktur/HR klinik) | Org profile,
          +staff mgmt, markup config, **wallet top-up & saldo**, dashboar
          +d aggregate, voucher create, konsultan assignment view | Konte
          +n sesi, data pasien individual, other klinik |
      518 +| **Partner Staff** (resepsionis/PIC) | Create booking, lihat
          +konsultan availability, redeem voucher, view own cabang sesi a
          +ggregate | Wallet top-up, markup config, other cabang |
      519  | **Konsultan** (Soluusi-side, assigned) | Own calendar, accep
           t/reject booking, session notes (encrypted), confirm completio
           n, view own payout | Other konsultan notes, klinik billing, ot
           her klinik pasien |
      520  | **HAP Associate** | CRM pipeline own klinik, klinik stage/ s
           esi aggregate (own klinik), weekly report auto | Konten sesi,
           konsultan payout, other associate's klinik |
      521  | **Ops Soluusi** | All klinik ops, onboarding, replacement ma
           tching, dispute resolve | Konten sesi clinical (konsultan-only
           ), finance payout approve |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      519  | **Konsultan** (Soluusi-side, assigned) | Own calendar, accep
           t/reject booking, session notes (encrypted), confirm completio
           n, view own payout | Other konsultan notes, klinik billing, ot
           her klinik pasien |
      520  | **HAP Associate** | CRM pipeline own klinik, klinik stage/ s
           esi aggregate (own klinik), weekly report auto | Konten sesi, 
           konsultan payout, other associate's klinik |
      521  | **Ops Soluusi** | All klinik ops, onboarding, replacement ma
           tching, dispute resolve | Konten sesi clinical (konsultan-only
           ), finance payout approve |
      522 -| **Finance** | Invoice, payment reconcile, konsultan payout,
          -HAP commission payout | Konten sesi, CRM pipeline detail | 
      522 +| **Finance** | Wallet top-up reconcile, konsultan payout, HAP
          + commission payout, wallet statement | Konten sesi, CRM pipeli
          +ne detail |
      523  | **Super Admin** | All + config (net price, markup cap, rates
           ) | — |
      524  
      525  > **Confidentiality inti:** konten sesi (notes) **hanya** Kons
           ultan + audit. Klinik admin/staff **tidak** lihat konten — onl
           y aggregate (sesi count, topik kategori anonim, NPS).

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 1 line
      546  - Commission accrual status
      547  
      548  ### 17.3 Finance dashboard
      549 -- Invoice due/overdue                                         
      549 +- Wallet top-up pending reconcile (manual transfer)           
      550 +- Wallet saldo outstanding (cross-klinik, liability)          
      551  - Konsultan payout due
      552  - HAP commission payout due
      553  - Gross profit (cross-klinik)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 3 lines
      607  - Offline konsultan: per cabang (butuh fisik dekat)
      608  - Online konsultan: shared across cabang (any cabang can book)
      609  
      610 -### 19.3 Billing `[OPEN-8]`                                   
      611 -- Default: consolidated HO invoice (1 grup, all cabang line it
          -ems)                                                          
      612 -- Alternative: per-cabang invoice (bila HO wants cost-center t
          -racking)                                                      
      610 +### 19.3 Wallet `[OPEN-8]`
      611 +- Default: 1 wallet per grup (consolidated, all cabang share s
          +aldo)
      612 +- Alternative: per-cabang wallet (bila HO wants cost-center tr
          +acking per cabang)
      613  
      614  ### 19.4 Scale escalation
      615  - >5 cabang → eskalasi (Playbook §4.9) → custom commercial

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 2 lines
      629  | Booking confirmed | Konsultan + klinik staff | WA/email | im
           mediate |
      630  | Booking reminder | Konsultan + pasien (via klinik, Model B) 
           | WA | H-1, H-2h |
      631  | Session completed | Klinik staff + HAP | WA/email | immediat
           e |
      632 -| Invoice generated | Partner Admin | email | Monday weekly | 
      633 -| Invoice overdue | Partner Admin + HAP | WA | H+7, H+14 |    
      632 +| Wallet low balance | Partner Admin | WA/email | saat saldo <
          + 2 sesi net |
      633 +| Top-up received | Partner Admin | email | instant (webhook)
          +|
      634 +| Top-up pending reconcile | Partner Admin | WA | <1 hari SLA
          +(manual transfer) |
      635  | HAP stage milestone | HAP associate | WA/email | on trigger
           |
      636  | Follow-up cadence (post-meeting prospect) | Prospect (via HA
           P) | WA/email | H+1/3/7/14/21/30 |
      637  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      660  | Pasien no-show | Klinik staff + HAP | Track; >2/klinik → rev
           iew |
      661  | Konsultan resign mid-term | Ops Soluusi | Reassign all booki
           ngs; brief replacement |
      662  | Pasien complaint re konsultan | HAP + Ops | Review notes (ko
           nsultan-only access via audit), resolve |
      663 -| Invoice dispute | Finance + HAP | Review audit → credit note
          - if valid |                                                   
      663 +| Wallet deduction dispute (sesi charged but didn't happen) |
          +Finance + HAP | Review audit §9.3 → refund credit to wallet |
      664  | Klinik wants change konsultan | HAP | Re-match from pool; up
           date assignment |
      665  | Klinik overdue payment | Finance + HAP | Dunning; suspend bo
           oking at H+30 `[OPEN-11]` |
      666  | Red flag (Playbook §4.8) | HAP | Stop negotiation; log |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      662  | Pasien complaint re konsultan | HAP + Ops | Review notes (ko
           nsultan-only access via audit), resolve |
      663  | Wallet deduction dispute (sesi charged but didn't happen) | 
           Finance + HAP | Review audit §9.3 → refund credit to wallet |
      664  | Klinik wants change konsultan | HAP | Re-match from pool; up
           date assignment |
      665 -| Klinik overdue payment | Finance + HAP | Dunning; suspend bo
          -oking at H+30 `[OPEN-11]` |                                   
      665 +| Top-up reconciliation pending (manual bank transfer) | Finan
          +ce + Ops | Match transfer → credit wallet; <1 hari SLA |
      666  | Red flag (Playbook §4.8) | HAP | Stop negotiation; log |
      667  
      668  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 1 line
      681  | Avg sesi/klinik/90d | 20 | 40 (target) | Booking |
      682  | Klinik capai Stage 3 (40 sesi) | 5 | 100+ | Commission |
      683  | Konsultan utilization (slot booked/available) | — | ≥60% | B
           ooking |
      684 -| Invoice collection rate (on-time) | ≥80% | ≥95% | Billing | 
      684 +| Wallet top-up cadence (top-up/active klinik/month) | — | ≥1.
          +5 | Wallet |                                                  
      685 +| Low-balance → top-up conversion | — | ≥70% | Wallet |       
      686 +| Sesi cancel refund rate | — | <10% | Wallet |
      687  | HAP commission auto-calc accuracy | 100% | 100% | Commission
            |
      688  | Konsultan payout on-time | ≥90% | ≥98% | Konsultan payment |
      689  | Klinik NPS | — | ≥4.2/5 | In-app |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      703  
      704  | Integration | Purpose | Phase |
      705  |---|---|---|
      706 -| WhatsApp Business API | Notifications, cadence, dunning | MV
          -P |                                                           
      707 -| Email (SES/SendGrid) | Invoices, secondary notif | MVP |    
      706 +| WhatsApp Business API | Notifications, cadence, low-balance 
          +| MVP |                                                       
      707 +| Email (SES/SendGrid) | Wallet statements, secondary notif |
          +MVP |
      708  | Zoom / Google Meet | Online session link | MVP |
      709  | Google Calendar | Konsultan schedule sync | MVP |
      710  | Payment gateway / VA | Klinik→Soluusi payment | MVP |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      707  | Email (SES/SendGrid) | Wallet statements, secondary notif | 
           MVP |
      708  | Zoom / Google Meet | Online session link | MVP |
      709  | Google Calendar | Konsultan schedule sync | MVP |
      710 -| Payment gateway / VA | Klinik→Soluusi payment | MVP |       
      711 -| Bank API / statement import | Reconciliation | MVP |        
      710 +| Payment gateway / VA | Wallet top-up (klinik→Soluusi) | MVP 
          +|                                                             
      711 +| Bank API / statement import | Top-up reconciliation (manual
          +transfer) | MVP |
      712  | Object storage (R2/S3) | Sesi files, MoU, materi | MVP |
      713  | Klinik management software (MIK, Klinosys, etc.) | API or ma
           nual booking sync | Phase 2 |
      714  | Psychometric instruments (licensed) | MHCU MMCI/MMPI/DISC |
           Phase 2 |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      728  | `[OPEN-4a]` | Free trial 10 min: konsultan fee prorated? | S
           oluusi absorbs | Konsultan payment |
      729  | `[OPEN-6]` | Pasien identity model A (registered) vs B (anon
           ymous)? | **B (recommended)** | Data schema, UU PDP, booking |
      730  | `[OPEN-7]` | Reschedule/cancel cutoff & fees? | H-2h free | 
           Booking |
      731 -| `[OPEN-7a]` | No-show: klinik charged Soluusi net? | No (goo
          -dwill) | Billing |                                            
      732 -| `[OPEN-8]` | Multi-cabang billing: consolidated HO vs per-ca
          -bang? | Consolidated HO | Billing |
      731 +| `[OPEN-7a]` | No-show/cancel <H-2jam: refund credit %? | 50%
          + refund / 50% forfeit | Wallet |
      732 +| `[OPEN-8]` | Multi-cabang wallet: 1 grup vs per-cabang? | 1
          +wallet grup | Wallet |
      733  | `[OPEN-9]` | Session duration variants (first vs follow-up)?
            | TBD | Booking/pricing |
      734  | `[OPEN-9a]` | Online recording opt-in? | No default | Sessio
           n |
      735  | `[OPEN-10]` | Dispute SLA? | TBD | Session |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 3 lines
      733  | `[OPEN-9]` | Session duration variants (first vs follow-up)?
            | TBD | Booking/pricing |
      734  | `[OPEN-9a]` | Online recording opt-in? | No default | Sessio
           n |
      735  | `[OPEN-10]` | Dispute SLA? | TBD | Session |
      736 -| `[OPEN-10a]` | Konsultan fee on cancelled sesi? | <24h no, ≥
          -24h partial | Konsultan payment |                             
      737 -| `[OPEN-11]` | Suspend booking at invoice H+30 overdue? | Yes
          - (confirm) | Billing/lifecycle | 
      738 -| `[OPEN-12]` | PPN / faktur pajak on invoice? | Verify financ
          -e | Billing/tax |
      736 +| `[OPEN-10a]` | Konsultan standby compensation on cancel <H-2
          +4h? | Dapat porsi dari 50% forfeit | Konsultan payment |
      737 +| ~~`[OPEN-11]`~~ | ~~Suspend at overdue~~ | **Superseded by p
          +repaid** — saldo = gate, no overdue possible | — |
      738 +| `[OPEN-12]` | PPN / faktur pajak on top-up? | Verify finance
          + | Wallet/tax |
      739  | `[OPEN-13]` | Cross-sell attribution commission? | TBD | Cro
           ss-sell |
      740  | `[OPEN-14]` | Termination reason log + re-activation path? |
            TBD | Lifecycle |
      741  | `[OPEN-15]` | DPO contact + breach notification process? | T
           BD | Compliance |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      739  | `[OPEN-13]` | Cross-sell attribution commission? | TBD | Cro
           ss-sell |
      740  | `[OPEN-14]` | Termination reason log + re-activation path? |
            TBD | Lifecycle |
      741  | `[OPEN-15]` | DPO contact + breach notification process? | T
           BD | Compliance |
      742 +| `[OPEN-16]` | Wallet credit expiry? | No expiry (default) | 
          +Wallet |                                                      
      743  
      744  ---
      745  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 8 lines, removed 6 lines
      755      D --> E[Pasien needs consultation - walk-in/refer/QR]
      756      E --> F[Staff klinik pilih konsultan di portal]
      757      F --> G[Create booking + optional voucher]
      758 -    G --> H[Konsultan + pasien check-in]                      
      758 +    G --> G2{Wallet saldo >= net?}                            
      759 +    G2 -->|no| G3[Prompt top-up - booking pending]            
      760 +    G3 --> G                                                  
      761 +    G2 -->|yes| G4[Deduct net from wallet - booking CONFIRMED]
      762 +    G4 --> H[Konsultan + pasien check-in]
      763      H --> I[Session runs - online/offline]
      764      I --> J[Konsultan notes encrypted + both confirm completed
           ]
      761 -    J --> K[Status COMPLETED - trigger calc]
      762 -    K --> L[Klinik bills pasien - klinik collects] 
      763 -    L --> M[Weekly invoice: klinik owes Soluusi net] 
      764 -    M --> N[Klinik pays Soluusi - Soluusi receives net] 
      765 -    N --> O[From net: pay konsultan fee + accrue HAP stage] 
      765 +    J --> K[Status COMPLETED - release from net held]
      766 +    K --> O[From net held: pay konsultan fee + accrue HAP stag
          +e]
      767 +    K --> L[Separately: klinik bills pasien marked-up - klinik
          + keeps margin]
      768      O --> P[Klinik dashboard aggregate update]
      769      O --> Q[Commission engine evaluate 5/25/40 stage]
      770      Q --> R{Stage trigger?}

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 16 lines, removed 13 lines
      828      S16 -->|no| S18[Manual confirm - proceed]
      829  ```
      830  
      831 -### 25.5 Billing & Invoice Flow (Klinik → Soluusi)            
      831 +### 25.5 Wallet & Prepayment Flow (Klinik → Soluusi)          
      832  ```mermaid
      833  sequenceDiagram
      834 +    participant KL as Klinik PIC                              
      835 +    participant WAL as Wallet Service
      836 +    participant BK as Booking Service
      837      participant SES as Session Service
      835 -    participant BIL as Billing Service 
      836 -    participant KL as Klinik 
      838      participant FIN as Finance
      838 -    SES->>BIL: Sesi COMPLETED for Klinik K 
      839 -    BIL->>BIL: Calc net = net_price x sesi - voucher adj 
      840 -    BIL->>BIL: Accumulate to weekly batch (Klinik K) 
      841 -    Note over BIL: Every Monday: generate invoice for prev wee
          -k 
      842 -    BIL->>KL: Send weekly invoice (email) - due +7d 
      843 -    KL->>FIN: Pay (VA transfer) 
      844 -    FIN->>FIN: Reconcile - match to invoice 
      845 -    FIN->>BIL: Mark PAID 
      846 -    BIL->>KL: Receipt + statement 
      847 -    Note over FIN,KL: H+7 reminder, H+14 HAP follow-up, H+30 s
          -uspend booking 
      839 +    KL->>WAL: Top-up wallet (gateway/VA/bank transfer)
      840 +    WAL->>WAL: Credit saldo after payment confirmed
      841 +    KL->>BK: Create booking (konsultan + slot + optional vouch
          +er)
      842 +    BK->>WAL: Check saldo >= net price?
      843 +    WAL-->>BK: Saldo OK
      844 +    BK->>WAL: Deduct net (less voucher adj) - idempotency key
      845 +    BK->>BK: Booking CONFIRMED - konsultan dispatched
      846 +    Note over BK,SES: Sesi runs + dual-confirm COMPLETED
      847 +    SES->>FIN: Release from net held
      848 +    FIN->>FIN: Pay konsultan fee + accrue HAP stage
      849 +    Note over WAL,KL: Monthly wallet statement to klinik
      850 +    Note over WAL: Cancel >= H-24h: full refund credit; <H-24h
          +: 50% refund
      851  ```
      852  
      853  ### 25.6 Konsultan Payment Flow (Soluusi → Konsultan)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      871  flowchart TD
      872      C1[Monitor signals] --> C2{Risk signal?}
      873      C2 -->|sesi decline 2 weeks| C3[Flag: churn risk]
      874 -    C2 -->|invoice overdue| C3                                
      874 +    C2 -->|wallet saldo rendah lama| C3                       
      875      C2 -->|PIC unresponsive| C3
      876      C2 -->|NPS drop| C3
      877      C3 --> C4[Auto-notify HAP + Ops]

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      891  | §4 Konsultan pool | Playbook §4.6 (replacement), PRD M3 |
      892  | §6 Pasien identity | PRD §4.2, §11 (new) |
      893  | §9 Anti-fraud | Playbook §5.1, PRD §8.4 |
      894 -| §10 Billing | PRD M6 (new detail) |                         
      894 +| §10 Wallet & Prepayment | PRD M6 (revised: prepaid) |       
      895  | §11 Konsultan payment | PRD §15.3 (open) |
      896  | §15 Lifecycle | Playbook §4.11–4.14, §0.5 |
      897  | §18 Compliance | PRD §11 |

  Thought for 12s (ctrl+o to expand)

⏺ Done. Model udah di-patch jadi prepaid wallet di semua titik yang kena ripple
  (27 edits sukses).

  Yang berubah:
  - §2/§3 — flow baru: klinik top-up wallet dulu → booking deduct net → sesi →
  Soluusi release net ke konsultan fee + HAP accrual. Pasien↔klinik terpisah 
  (urusan klinik) 
  - §10 — section ganti judul jadi "Wallet & Prepayment". Subsystem
  billing/dunning ke-delete (§10.6 nyatanya daftar apa aja yang hilang). Lebih
  aman, lebih simple
  - §9 anti-fraud — risiko bergeser: "klinik gak bayar" hilang, sekarang fokus
  sesi beneran terjadi sebelum fee di-release
  - §25.1 + §25.5 mermaid — master flow + wallet sequence di-redraw
  - §16/§17/§20/§21/§22/§23/§24 — semua terminology "invoice/overdue/dunning" →
  "wallet/top-up/saldo", KPI collection rate → wallet cadence, open-questions 
  di-update

  Concern teknis baru yang gw flag inline di §10.3 (ini penting buat dev):
  - Wallet saldo = critical shared asset → butuh row-level lock / atomic
  decrement + idempotency key per booking (anti double-charge saat retry). Ini
  kena data schema
  - Top-up ledger immutable (§10.2)

  Open question baru [OPEN-16]: wallet credit expiry? Default no-expiry
  (goodwill), alt 12-month (working capital). Confirm.

  ▎ skipped: detail DB schema wallet table + transaction ledger (perlu di PRD 
  ▎ M6, bukan vertical spec). add when lock [OPEN-6] pasien identity + [OPEN-16]
  ▎ expiry.

  Satu hal yang lu harus sadar secara commercial: prepaid shift working capital 
  ke klinik (mereka fund konsultan sebelum tagih pasien). Buat klinik kecil ini
  mungkin objection — mungkin perlu pack kecil (Rp500k) atau net-terms buat
  klinik strategis. Tapi dari sisi Soluusi ini jelas menang (no AR, no dunning,
  no bad debt). Kalo lu mau, gw bisa tambahin skema "net-terms untuk klinik
  strategis" sebagai alternative path di §10.

✻ Worked for 9m 8s

※ recap: Goal: build B2B klinik vertical spec for Soluusi's consultant-supply 
  model. Currently updated the spec to use a prepaid wallet model instead of 
  post-paid invoicing, applied across 27 edits. Next: confirm the wallet expiry 
  and pasien identity decisions before building. (disable recaps in /config)

❯ oke jadi ada yang berubah ini catatannya, unutk payment nantinya akan seperti 
  kita akan seperti memberikan link gitu ke costomer , jad idari adin klink     
  akan kirim link dengan menginisi isi data seperti ini [Image #1], isinya saa  
  sama seperti itu kareana ini seabanrnnya sama seperi e2e soluusi.com hanya    
  saja bedanya ini versi kelinik dan ini gw mau make sure jangan sampai client  
  klinik ini tahu kalau ini tuh dari soluusi.com jadi dibuat makesure kalau ini 
  nantinya clinet akan larinya gak ke soluusi gitu , unutk mengurakngakana      
  kemungkinan dibuat seperti itu , nanti costomer akan dikirim link oleh admin  
  klink dan di isi linknya dan kemudain akan langsung melakukan pembayaran,     
  nanti di link itu akan ada nama consultannya jadi nanti langsunng byar, jadi  
  nnti di atur dulu nih sama admin jadwanya kapaan, atau enanya juga bis dari   
  link? keknya di ataru di dasjabrod admin aja deh mnrt lu gmn? jadi sebisa     
  mungkin nantinya tidak cs mengetauhi kalau ini soluusi lebih kerah unutk      
  rispect sih ke parnter b2b itu, jadi nanti admin dashabord klink pilih        
  konsultannya di soluusi kemudian pilih jdawal nanaya cosotomer klinknya       
  kemudian lansusng kirim link ke costomer klinknya dan minta isi nya dan       
  kemduain cs isi dan bayar , langsung otomatis terjadwal di gcalender          
  costemrnya yang sudah di atur otomatis oleh system lu bisa cek soluusi.com    
  seperti itu kau bisa cek makesure all cek all                                 
  ⎿  [Image #1]

  Thought for 2m 9s (ctrl+o to expand)

⏺ Oke, ini perubahan model lagi — dan lebih fundamental. Biar gw paham
  persisnya, gw cek 2 hal bareng: imagenya (format link/checkout yang dimaksud)
  + flow soluusi.com yang mau di-mirror.

  Read 1 file (ctrl+o to expand)

⏺ Fetch(https://soluusi.com)
  ⎿  Received 98.8KB (200 OK)

  Thought for 17s (ctrl+o to expand)

⏺ Image read-nya kosong + WebFetch error lagi (model backend). Gw coba
  alternatif:

  Listed 1 directory (ctrl+o to expand)

⏺ Web Search("soluusi.com konsultan psikolog booking cara pesan jadwal
            pembayaran")
  ⎿  Did 0 searches in 32s

  Thought for 11s (ctrl+o to expand)

⏺ WebSearch infinite-loop lagi (issue known). Image-nya exist (46KB JPEG). Gw
  copy ke path normal + retry read:

⏺ Bash(cp "/Users/mekari/Library/Containers/net.whatsapp.WhatsApp/Data/tmp/docum
      ents/78AF1562-8386-4201-8602-0697C7AA757F/PHOTO-2026-08-03-16-38-55.jpg"
      /tmp/klinik_li…)
  ⎿  copied
     -rw-r--r--@ 1 mekari  wheel  46329 Aug  4 09:10 /tmp/klinik_link_ref.j
     pg

  Thought for 6m 0s, read 1 file (ctrl+o to expand)

⏺ Tool verification gagal total (image gak render di Read, WebSearch
  Tapi inti perubahan lu jelas, gw tangkap. Model ubah lagi (v1.1): bukan
  prepaid wallet lagi, tapi white-label checkout link + customer bayar langsung
  + split payment + auto-gcal + hide Soluusi. Ini lebih bagus dari wallet
  (customer-funded = zero credit risk, bukan klinik-funded).

  Gw rewrite spec-nya full supaya konsisten — udah 3x revisi model, patch
  terpotong-potong bahayanya ada yang kelewat:
  ⎿  Error writing file
  ⎿  Referenced file Soluusi B2B - Klinik Vertical Spec.md
  ⎿  Referenced file Soluusi B2B Platform - PRD.md
  ⎿  Referenced file Soluusi B2B - Competitor Analysis.md
  ⎿  Referenced file Soluusi B2B Master Playbook.md
  ⎿  Referenced file ../.claude/projects/-Users-mekari-soluusi-b2b-beforemarge/9
     cd952eb-76a5-48a0-9c13-f4481e436ef4/tool-results/bhwkmnxlj.txt

  Thought for 12s (ctrl+o to expand)

⏺ Write gagal — payload kegeden + JSON parsing break (46KB, ada char yang
  escape-nya buntu). Bukan ulah konten, masalah transport. Switch strategy:
  targeted edits aja, surgical. Gw Read dulu buat dapetin state terkini
  (post-28-edits sebelumnya), lalu patch section-section yang berubah ke v1.1:

  Thought for 3m 16s, read 1 file (ctrl+o to expand)

⏺ File-nya ternyata masih state v1.0 (prepaid wallet) — bukan post-27-edits
  kayak summary bilang. Good, sekarang gw punya ground truth. Fix ToC + rewrite
  ke v1.1 pake targeted edits (surgical, bukan full-Write yang JSON-nya break).

  Firing all patches — section inti (§2/§3/§6/§7/§10), white-label subsections
  baru, consistency ripple (§9/§15/§16/§17/§18/§19/§20/§21/§22/§23/§24), mermaid
  redraw (§25.1/§25.5/§25.7), onboarding checklist, footer:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 1 line
       1  # Soluusi B2B — Klinik Vertical Spec
       2  ### For Health Facilities · Consultant-Supply Model (E2E + Full
           Expansion)
       3  
       4 -> Versi 1.0 · Agustus 2026                                     
       4 +> Versi 1.1 · Agustus 2026                                     
       5  > Vertical priority: **#1 — dibangun pertama**
       6  > Dokumen pendukung: `Soluusi B2B Master Playbook.md`, `Soluusi
           B2B Platform - PRD.md`
       7  > Status: Draft — asumsi ditandai `[ASSUMED]`, keputusan ditand
          ai `[OPEN]`
       8 +> **Revisi model:** v1 post-paid invoice → v1.0 prepaid wallet
         +→ **v1.1 white-label link + direct customer payment + split** (
         +current). Lihat §2 & §10.
       9  
      10  ---
      11  

  Thought for 13s (ctrl+o to expand)

⏺ Edit 1 OK. Lanjut batch berikutnya — ToC, §1 scope, §2 model, §3 flow, §3
  table, §6 identity:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      19  7. Booking & Scheduling (+ edge cases)
      20  8. Consultation Delivery (online/offline/hybrid)
      21  9. Session Confirmation & Anti-Fraud
      22 -10. Billing & Invoicing (Klinik → Soluusi)                     
      22 +10. White-label Checkout & Split Payment (Customer → Soluusi)  
      23  11. Konsultan Payment (Soluusi → Konsultan)
      24  12. Voucher & Campaign (Benefit Pasien, Skema 4)
      25  13. MHCU for Klinik (Skema 2)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 1 line
      47  - Konsultan pool supply + klinik pilih
      48  - Booking (online + offline)
      49  - Session + dual-confirm
      50 -- Billing: **prepaid wallet** (klinik top-up dulu → per-booking
         - deduct, no AR)                                                
      50 +- Billing: **white-label checkout link** (admin generate → cust
         +omer pays directly via klinik-branded link, split-payment, no A
         +R)                                                             
      51 +- White-label: hide Soluusi brand dari customer (domain, receip
         +t, gcal, notif) — see §10.8+
      52 +- Google Calendar auto-schedule after payment
      53  - Konsultan payment: Soluusi→konsultan flat/sesi
      54  - HAP commission engine (3-stage)
      55  - Klinik dashboard aggregate

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      58  **Out-of-scope MVP (Phase 2):**
      59  - MHCU klinik (Skema 2) — schema supported, module Phase 2
      60  - Edukasi seminar (Skema 3) — manual scheduling MVP, systematiz
          e Phase 2
      61 -- White-label klinik-branded booking page                      
      61 +- White-label klinik-branded self-book page (slot-picker Phase 
         +2; checkout link itself = MVP)                                 
      62  - Cross-sell to For Company (wellbeing staf klinik)
      63  
      64  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 8 lines, removed 7 lines
      66  
      67  ## 2. Revenue & Commercial Model
      68  
      69 -### Model inti: **Markup model + PREPAID wallet, Soluusi terima
         - net di muka**                                                 
      70 -- Soluusi punya **net price** per sesi (per jenis konsultan × d
         -urasi × mode online/offline)                                   
      71 -- Klinik **top-up wallet** di Soluusi dashboard di muka (sebelu
         -m bisa akses/booking konsultan) — **mitigasi risiko klinik gak
         -bayar** `[DECIDED — prepaid, per user]` 
      72 -- Saat klinik **booking sesi**, sistem **deduct net price dari
         -wallet** → sesi CONFIRMED. Booking **blocked** bila saldo < net
         - price → klinik must top-up dulu 
      73 -- Klinik **markup** di atas net price → harga ke pasien (klinik
         - yang set, sesuai positioning) 
      74 -- **Pasien bayar ke klinik** (di harga marked-up) — **terpisah*
         -*, urusan klinik sendiri `[ASSUMED-1]`. Klinik **simpan margin
         -markup**. Soluusi gak balik-bayar apa-apa ke klinik 
      75 -- Soluusi **sudah pegang uang net di muka** → **no AR, no colle
         -ction risk, no dunning** (billing/dunning subsystem di-delete —
         - see §10.6) 
      69 +### Model inti: **Markup model + WHITE-LABEL direct payment, cu
         +stomer bayar via link**
      70 +- Soluusi punya **net price** per sesi; klinik **markup** → har
         +ga ke customer (klinik set, kena cap §2.2)
      71 +- Admin klinik (di klinik portal) **pilih konsultan + jadwal**
         +→ sistem **generate white-label checkout link** (klinik-branded
         +, **NO brand Soluusi**)
      72 +- Admin **kirim link** ke customer (WA/email) → customer buka l
         +ink → lihat **nama konsultan + jadwal + harga marked-up** → isi
         + data + **bayar langsung di link**
      73 +- Payment gateway (Soluusi-controlled, white-labeled) **collect
         + dari customer** → **split**: net → Soluusi, margin → klinik (a
         +uto-remit ke klinik earnings) `[DECIDED — white-label + split,
         +per user]`
      74 +- **Soluusi selalu collect net langsung dari customer** → **no
         +AR, no credit risk, no dunning** (lebih aman dari wallet prepai
         +d — customer-funded, bukan klinik-funded)
      75 +- Setelah payment confirmed → booking **auto-confirmed** + **au
         +to-add Google Calendar** customer + konsultan
      76 +- Klinik **simpan margin markup** otomatis (split). Pasien↔klin
         +ik relationship tetap milik klinik; Soluusi hidden
      77  
      78  ### Markup cap (dari Master Playbook §1.4)
      79  Margin klinik (markup) sebagai fraksi harga pasien dibatasi:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 13 lines, removed 12 lines
       96  
       97  ## 3. Money Flow (per-sesi + milestone)
       98  
       99 -### Per-sesi flow (PREPAID)                                   
       99 +### Per-sesi flow (WHITE-LABEL direct payment)                
      100  ```
      101 -[1] Klinik top-up wallet di Soluusi dashboard (mis. Rp2.500.00
          -0 = 10 sesi)                                                  
      102 -    → saldo wallet: Rp2.500.000 
      101 +[1] Admin klinik (klinik portal) pilih konsultan + jadwal
      102 +    → sistem generate white-label checkout link (klinik-brande
          +d, hide Soluusi)
      103  
      104 -[2] Klinik booking sesi → sistem deduct Rp250k (net) dari wall
          -et 
      105 -    → saldo: Rp2.250.000 | sesi CONFIRMED, konsultan dispatche
          -d 
      106 -    → Soluusi SUDAH pegang net di muka (no collection risk) 
      104 +[2] Admin kirim link → customer buka → lihat nama konsultan +
          +jadwal + harga Rp300k (marked-up)
      105 +    → customer isi data (nama, email, telp) + bayar Rp300k di
          +link
      106  
      108 -[3] Sesi berlangsung + dual-confirm COMPLETED 
      109 -    → Soluusi release dari net Rp250k yang sudah di-pegang: 
      107 +[3] Gateway collect Rp300k → split:
      108 +       ├─ Rp250k net → Soluusi (always collected, no credit ri
          +sk)
      109 +       └─ Rp50k margin → klinik earnings (auto-remit / payout)
      110 + 
      111 +[4] Payment confirmed → booking auto-confirmed → auto-add gcal
          + customer + konsultan
      112 + 
      113 +[5] Sesi berlangsung + dual-confirm COMPLETED
      114 +    → dari net Rp250k yang sudah di-pegang Soluusi:
      115         ├─ Konsultan fee: Rp150k (pay to konsultan, after confi
           rm)
      116         └─ Soluusi gross: Rp100k → accrue HAP 3-stage milestone
      112 - 
      113 -[4] Terpisah, urusan klinik sendiri (bukan flow Soluusi): 
      114 -    Pasien ──bayar Rp300k (marked-up)──> Klinik 
      115 -    Klinik keep: Rp50k (margin) | billing klinik ke pasien, So
          -luusi gak peduli 
      117  ```
      118  
      119  ### HAP commission milestone (per klinik, 90-day rolling)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      131  |---|---|
      132  | Pasien total bayar (40 × Rp300k) | Rp12.000.000 |
      133  | Klinik margin (40 × Rp50k) | Rp2.000.000 (keep) |
      134 -| Klinik top-up wallet / deduct net (40 × Rp250k) | Rp10.000.0
          -00 |                                                          
      134 +| Customer pays via gateway → net to Soluusi (40 × Rp250k) | R
          +p10.000.000 |                                                 
      135  | Soluusi → Konsultan fee (40 × Rp150k) | Rp6.000.000 |
      136  | Soluusi gross | Rp4.000.000 |
      137  | Soluusi → HAP commission (3-stage total) | Rp1.000.000 |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 10 lines, removed 3 lines
      229  - **Benefit:** Soluusi bukan controller PII pasien → ringan UU
            PDP, less breach surface
      230  - **Cost:** no re-book funnel Soluusi-side, no Soluusi pasien 
           retention
      231  
      232 -### Recommended: Model B (klinik-supply context)              
      233 -Karena klinik collect payment + owns pasien relationship, Mode
          -l B paling clean. Konsultan akses nama pasien via "clinical fi
          -eld" (encrypted, konsultan-only), tapi Soluusi backend gak peg
          -ang PII pasien persistently. 
      232 +### Shift (v1.1 white-label direct payment): **Model C — check
          +out-guest** `[RECOMMENDED]`
      233 +Karena customer **bayar langsung via link** (§2), Soluusi gate
          +way **collect PII customer** di checkout (nama, email, telp) u
          +ntuk payment receipt + gcal invite. Jadi Model B (fully anonym
          +ous) **tidak lagi feasible** — Soluusi pegang transactional PI
          +I.
      234  
      235 -`[OPEN-6]` — confirm model A vs B. **Menentukan data schema, U
          -U PDP scope, dan apakah perlu pasien account module.** 
      235 +**Model C (recommended, current):**
      236 +- Customer **tidak bikin akun** (guest checkout) tapi Soluusi
          +collect: nama, email, telp, payment info di transaksi
      237 +- Soluusi = data controller untuk PII checkout (UU PDP applies
          +) — minimize: hanya field perlu (no DOB, no address unless nee
          +ded)
      238 +- Konsultan akses nama pasien via clinical field (encrypted, k
          +onsultan-only) — sama seperti Model B
      239 +- Klinik tetap "owns relationship" secara komersial (customer
          +kenal klinik, bukan Soluusi — enforced via white-label §10.8)
      240 +- **No Soluusi account for customer** → no re-book funnel Solu
          +usi-side (klinik owns repeat booking)
      241  
      242 +`[OPEN-6]` — confirm Model C. UU PDP scope = Soluusi handle ch
          +eckout PII (consent at checkout, DPO, breach). Klinik still ow
          +ns longitudinal patient record.
      243 + 
      244  ---
      245  ---
      246  

  Thought for 4s (ctrl+o to expand)

⏺ Batch 2 OK. Lanjut — §7 booking paths + scheduling decision, §7.3 no-show,
  §7.6, §9 anti-fraud note, §9.4 refund, §15.5/§15.6:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 6 lines, removed 3 lines
      246  
      247  ## 7. Booking & Scheduling
      248  
      249 -### 7.1 Booking creation paths                                
      250 -1. **Staff klinik (resepsionis) book** — default MVP. Pasien w
          -alk-in/telp → staff pilih konsultan di portal → create booking
      251 -2. **Pasien self-book via QR** (Phase 2 white-label) — pasien 
          -scan QR di klinik → booking page (klinik-branded) → self-sched
          -ule 
      249 +### 7.1 Booking creation paths (v1.1)
      250 +1. **Admin klinik generate link** `[DEFAULT MVP]` — admin pili
          +h konsultan + jadwal di klinik portal → generate white-label c
          +heckout link → kirim ke customer (WA/email) → customer isi + b
          +ayar di link → auto-confirmed + gcal
      251 +2. **Customer self-book via link** (Phase 2) — klinik share bo
          +oking page link → customer pilih konsultan + slot sendiri → ba
          +yar (mirror soluusi.com B2C, tetap white-label)
      252 +3. Walk-in/staff-book di portal tanpa customer link (Phase 2)
          +— untuk klinik yang mau staff handle full
      253  
      254 +> **Scheduling decision `[OPEN-21]`** (user asked): admin pick
          + slot di dashboard (MVP, simplest, match klinik coordination)
          +vs customer pick slot dari link (Phase 2, self-serve, mirror s
          +oluusi.com). **Recommended MVP: admin pick konsultan + 1 slot
          +→ link = pay-and-confirm** (customer just isi + bayar). Phase
          +2: admin pick konsultan, customer pick slot dari available. Al
          +asan: MVP paling simple + klinik kontrol; self-serve butuh ava
          +ilability engine yang robust.
      255 + 
      256  ### 7.2 Booking fields
      257  - Klinik (org + cabang)
      258  - Konsultan (dari assigned pool)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      267  - Cutoff time: H-2 jam free reschedule; <2 jam = cancel (statu
           s counted)
      268  - Who can: staff klinik, konsultan, pasien (Model A only)
      269  - Cancel reason log (Playbook §4.23)
      270 -- No-show: klinik sudah prepaid (saldo sudah deduct). Refund p
          -olicy? `[OPEN-7a]` — default: cancel <H-2jam = 50% refund cred
          -it, 50% forfeit (compensate konsultan slot); ≥H-2jam = full re
          -fund. Tracked; >2 no-show/klinik → review                     
      270 +- No-show: customer sudah bayar via gateway. Refund policy? `[
          +OPEN-7a]` — default: cancel <H-2jam = 50% refund customer (gat
          +eway reverse-split), 50% forfeit (konsultan standby); ≥H-2jam
          += full refund. Tracked; >2 no-show/klinik → review
      271  
      272  ### 7.4 Walk-in handling
      273  - Walk-in pasien: staff create booking "now" atau "next slot"
           → konsultan check available real-time

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      279  
      280  ### 7.6 Multi-cabang
      281  - Klinik grup: slot publish per cabang; konsultan availability
            per cabang (offline) atau global (online)
      282 -- Booking tagged cabang → wallet per grup OR per-cabang `[OPEN
          --8]`                                                          
      282 +- Booking tagged cabang → link domain per grup OR per-cabang s
          +ubdomain `[OPEN-8]`                                           
      283  
      284  ---
      285  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      311  
      312  Core anti-fraud principle (Playbook §5.1): komisi cair **hanya
            setelah sesi nyata terkonfirmasi**, bukan MoU.
      313  
      314 -> **Prepaid shift (§2):** dengan wallet model, risiko "klinik 
          -gak bayar" sudah hilang (saldo di-pegang di muka). Anti-fraud 
          -sekarang fokus ke: (a) sesi **benar-benar terjadi** sebelum ko
          -nsultan fee di-release + HAP komisi di-accrue (klinik sudah pr
          -epaid, jadi insentif klinik = sesi terjadi, bukan fake); (b) H
          -AP/associate tidak bisa klaim sesi fake untuk trigger komisi.
          -**Dual-confirm masih wajib.** 
      314 +> **White-label shift (v1.1, §2):** customer bayar langsung vi
          +a gateway → Soluusi collect net di muka, **no credit risk sama
          + sekali** (customer-funded, bukan klinik-funded). Anti-fraud s
          +ekarang fokus: (a) sesi **benar-benar terjadi** sebelum konsul
          +tan fee di-release + HAP komisi di-accrue (insentif semua part
          +y = sesi terjadi); (b) HAP/associate tidak bisa klaim sesi fak
          +e untuk trigger komisi; (c) **refund fraud** — customer/klinik
          + klaim sesi gak terjadi untuk refund → audit trail §9.2 + disp
          +ute §9.3. **Dual-confirm masih wajib.**
      315  
      316  ### 9.1 Confirmation flow `[mermaid §25.4]`
      317  1. Konsultan + pasien check-in → session starts

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 5 lines, removed 4 lines
      329  - Ops Soluusi review audit trail → resolve (credit note / kons
           ultan coaching)
      330  - `[OPEN-10]` — dispute SLA
      331  
      332 -### 9.4 Refund flow (wallet credit)                           
      333 -- Sesi cancelled ≥ H-24h → **full refund credit** ke wallet kl
          -inik                                                          
      334 -- Sesi cancelled < H-24h or no-show → 50% refund credit, 50% f
          -orfeit `[OPEN-7a]` 
      335 -- Soluusi→konsultan: bila sesi cancelled < H-24h → konsultan d
          -apat standby compensation dari 50% forfeit `[OPEN-10a]`; ≥ H-2
          -4h → no fee (slot bisa di-rebook) 
      332 +### 9.4 Refund flow (gateway reverse-split to customer)
      333 +- Sesi cancelled ≥ H-24h → **full refund customer** via gatewa
          +y reverse-split; no konsultan fee
      334 +- Sesi cancelled < H-24h or no-show → 50% refund customer, 50%
          + forfeit → konsultan standby compensation `[OPEN-7a]`
      335 +- Klinik margin: auto-reversed via split (proportional)
      336 +- Soluusi→konsultan: bila cancelled < H-24h → konsultan dapat
          +porsi dari 50% forfeit `[OPEN-10a]`; ≥ H-24h → no fee
      337  
      338  ---
      339  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      513  - HAP pitch new vertical → attribution `[OPEN-13]`
      514  
      515  ### 15.5 Churn risk detection
      516 -- Signals: sesi declining 2 consecutive weeks, wallet saldo re
          -ndah berkepanjangan / top-up pending, PIC unresponsive, NPS dr
          -op                                                            
      516 +- Signals: sesi declining 2 consecutive weeks, link→pay conver
          +sion declining, margin payout shrinking, PIC unresponsive, NPS
          + drop
      517  - Auto-flag → HAP/Ops intervention
      518  - Playbook §4.26 "top performer: always follow up, don't stop
           after MoU"
      519  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      519  
      520  ### 15.6 Termination flow
      521  - Notice period (per MoU, indikatif 30 hari)
      522 -- Wind-down: complete scheduled sesi, **refund remaining walle
          -t saldo** to klinik, final wallet statement, data export to kl
          -inik, revoke konsultan assignment                             
      522 +- Wind-down: complete scheduled sesi, **payout remaining klini
          +k margin**, final statement, data export to klinik, revoke kon
          +sultan assignment, deactivate white-label domain
      523  - Post-termination: klinik data retained per UU PDP, konsultan
            reassigned
      524  - `[OPEN-14]` — termination reasons log + re-activation path
      525  

  Thought for 5s (ctrl+o to expand)

⏺ Batch 3 OK. Sekarang bagian terbesarnya — §10 lengkap (Wallet & Prepayment →
  White-label Checkout & Split + subbagian 10.8–10.10 baru), plus §16 roles,
  §17.3, §19.3:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 61 lines, removed 35 lines
      338  ---
      339  ---
      340  
      341 -## 10. Wallet & Prepayment (Klinik → Soluusi)                 
      341 +## 10. White-label Checkout & Split Payment (Customer → Soluus
          +i)                                                            
      342  
      343 -> Model diubah dari post-paid invoice → **PREPAID wallet** (pe
          -r user, mitigasi risiko klinik gak bayar). Lihat §2 & §3. `[AS
          -SUMED-2]` weekly invoice **superseded**. 
      343 +> Revisi v1.1: wallet prepaid **superseded** → **white-label c
          +heckout link + direct customer payment + split**. Mitigasi ris
          +iko lebih baik (customer-funded, no AR) + respect partner (hid
          +e Soluusi). Lihat §2 & §3.
      344  
      345 -### 10.1 Model `[DECIDED — prepaid]`
      346 -- Klinik **top-up wallet** di Soluusi dashboard sebelum bisa b
          -ooking konsultan 
      347 -- Setiap booking **deduct net price** dari wallet saat sesi-cr
          -eate 
      348 -- Booking **blocked** bila saldo < net price → klinik must top
          --up dulu 
      349 -- **No AR, no collection risk** — Soluusi pegang uang di muka 
      345 +### 10.1 Model `[DECIDED — white-label + split, per user]`
      346 +- Admin klinik generate **white-label checkout link** di klini
          +k portal (pilih konsultan + jadwal)
      347 +- Customer buka link → isi data + **bayar marked-up price lang
          +sung** di link (payment gateway, Soluusi-controlled)
      348 +- Gateway **split**: net → Soluusi, margin → klinik earnings (
          +auto-remit)
      349 +- **No AR, no credit risk** — Soluusi collect net langsung dar
          +i customer
      350  
      351 -### 10.2 Top-up mechanism 
      352 -- Klinik PIC (Partner Admin) top-up via payment gateway / VA /
          - bank transfer 
      353 -- Pack options indikatif: Rp500k / Rp1M / Rp2.5M / Rp5M, atau
          -custom amount 
      354 -- Saldo available setelah payment confirmed (gateway webhook =
          - instant; bank transfer = reconcile by Ops) 
      355 -- Top-up log: amount, method, timestamp, PIC — immutable ledge
          -r 
      351 +### 10.2 Checkout link content (mirror soluusi.com B2C, white-
          +labeled)
      352 +- Klinik branding (logo, nama, warna) — **no Soluusi brand**
      353 +- Konsultan name + foto/profil ringkas (no Soluusi affiliation
          + link)
      354 +- Jadwal (slot yang admin pilih, atau slot-picker Phase 2)
      355 +- Harga **marked-up** (yang customer bayar) — net hidden dari
          +customer
      356 +- Mode (online: link Zoom/Meet; offline: alamat klinik)
      357 +- Form fields: nama, email, telp (+ optional keluhan singkat u
          +ntuk konsultan)
      358 +- Payment methods: card, VA, e-wallet, qris (gateway-supported
          +)
      359 +- Consent: UU PDP notice + AIGC/sesrecording consent (bila app
          +licable)
      360  
      357 -### 10.3 Per-booking deduction 
      358 -- Saat booking create: validate saldo ≥ net price → deduct → b
          -ooking CONFIRMED → konsultan dispatched 
      359 -- **Concurrency**: row-level lock / atomic decrement (saldo =
          -kritis asset) + idempotency key per booking (anti double-charg
          -e on retry) 
      360 -- Voucher (Skema 4): deduct (net − voucher adj) dari wallet 
      361 -- Sesi cancel ≥ H-24h: **full refund credit to wallet** 
      362 -- Sesi cancel < H-24h or no-show: 50% refund credit, 50% forfe
          -it `[OPEN-7a]` 
      361 +### 10.3 Split payment (marketplace model)
      362 +- Gateway support split/connect (e.g., Midtrans split, Xendit,
          + Stripe Connect) `[OPEN-18]` — verify ID gateway support
      363 +- Customer pays Rp300k → auto-split: Rp250k → Soluusi account,
          + Rp50k → klinik account (or klinik earnings balance + periodic
          + payout)
      364 +- **Soluusi always gets net** (no credit risk); klinik always
          +gets margin (no invoicing)
      365 +- Refund: bila sesi cancel, gateway reverse-split (refund cust
          +omer, reverse margin + net) `[OPEN-7a]`
      366  
      364 -### 10.4 Low-balance management 
      365 -- Threshold (saldo < 2 sesi net) → auto-reminder WA/email to P
          -IC 
      366 -- Booking attempt with insufficient saldo → **in-flow top-up p
          -rompt** (jangan sampai booking intent hilang) 
      367 -- Auto-recharge (Phase 2) — klinik set threshold + auto-debit 
      367 +### 10.4 Auto-confirm + Google Calendar
      368 +- Payment confirmed (gateway webhook) → booking **auto-CONFIRM
          +ED** → konsultan dispatched
      369 +- System **auto-create gcal event** + invite customer (email)
          ++ konsultan
      370 +- gcal organizer = **klinik-branded service account** (not sol
          +uusi.com) `[OPEN-20]`
      371 +- Event title klinik-branded (e.g., "Konsultasi [Klinik Name]
          +- [Konsultan]")
      372 +- Reminder: gcal default + WA reminder H-1/H-2h (klinik-brande
          +d)
      373  
      369 -### 10.5 Wallet expiry `[OPEN-16]` 
      370 -- Default: **no expiry** (goodwill, klinik-friendly) 
      371 -- Alternative: 12-month expiry (working capital discipline). C
          -onfirm. 
      374 +### 10.5 Refund & cancel
      375 +- Cancel ≥ H-24h: **full refund customer** via gateway reverse
          +-split; no konsultan fee
      376 +- Cancel < H-24h / no-show: 50% refund customer, 50% forfeit →
          + konsultan standby compensation `[OPEN-7a]`
      377 +- Dispute: customer claim sesi didn't happen → review audit §9
          +.2 → refund if valid
      378  
      373 -### 10.6 What this REPLACES (deletion vs old post-paid)
      374 -- ~~Weekly consolidated invoice~~ → real-time per-booking dedu
          -ction 
      375 -- ~~Payment reconciliation klinik→Soluusi~~ → top-up reconcili
          -ation only (simpler) 
      376 -- ~~Dunning H+7/H+14/H+30~~ → booking auto-blocked when saldo
          -kosong 
      377 -- ~~Suspend booking at overdue~~ → not needed (saldo = gate) 
      378 -- Net: billing/dunning subsystem **deleted**. Lebih simple, le
          -bih aman, lebih lazy. 
      379 +### 10.6 What this REPLACES (deletion log)
      380 +- ~~Post-paid weekly invoice~~ (v1) → ~~prepaid wallet~~ (v1.0
          +) → **direct customer payment + split** (v1.1, current)
      381 +- ~~Dunning / overdue / suspend~~ → not needed (customer-funde
          +d, gateway-held)
      382 +- ~~Klinik earnings invoicing~~ → auto-remit via split
      383 +- Net: paling simple, paling aman, paling lazy. Customer pays,
          + system splits, done.
      384  
      380 -### 10.7 Statement & reporting (replaces invoice)
      381 -- Monthly wallet statement to klinik: top-ups, sesi deducted,
          -saldo remaining, refund credits 
      382 -- PDF via email (for klinik tax/audit record)
      383 -- `[OPEN-12]` PPN/faktur pajak on top-up? Default: Soluusi iss
          -ue monthly statement; faktur pajak per finance verify. 
      385 +### 10.7 Statement & reporting
      386 +- Monthly statement to klinik: sesi count, margin earned (auto
          +-remitted), refund processed
      387 +- PDF via email (klinik tax/audit record)
      388 +- `[OPEN-12]` PPN/faktur pajak: merchant of record = ? (Soluus
          +i or klinik) — see §10.9
      389  
      390 +### 10.8 White-label requirements (HIDE Soluusi) `[CRITICAL]`
      391 +Tujuan: customer **tidak tahu** ini Soluusi → respect partner
          +B2B, anti client-bypass ke soluusi.com.
      392 +- **Domain**: link di **neutral/klinik-branded domain**, BUKAN
          + soluusi.com. Opsi: (a) klinik CNAME `booking.klinikname.id` →
          + Soluusi, (b) neutral white-label domain `konsul-pay.id` / `ja
          +dwalkonsul.id` milik Soluusi. `[OPEN-17]`
      393 +- **Page branding**: klinik logo, nama, warna, footer. Zero me
          +ntion "Soluusi" di page, receipt, email, gcal, WA.
      394 +- **Receipt**: dari klinik (white-labeled). Merchant of record
          + see §10.9.
      395 +- **Email/WA**: from-name + reply-to = klinik. Domain email ne
          +utral/klinik.
      396 +- **gcal event**: organizer klinik-branded, title klinik-brand
          +ed.
      397 +- **Konsultan profile di link**: nama + ringkas, **no link ke
          +soluusi.com profile**, no "Soluusi" affiliation.
      398 +- **Tracking/analytics**: no Soluusi-tracking-pixel yang bocor
          + ke customer.
      399 + 
      400 +### 10.9 Merchant of record & tax `[OPEN-18]`
      401 +- Opsi A: **Soluusi merchant of record** (Soluusi collect, whi
          +te-label receipt as klinik) → Soluusi issue faktur ke customer
          + (white-labeled); margin = "commission/rebate" Soluusi→klinik.
          + Paling aman credit-risk, tapi tax flow Soluusi-heavy.
      402 +- Opsi B: **Klinik merchant of record** (klinik gateway, split
          + net to Soluusi) → klinik issue faktur; net = "fee payable" kl
          +inik→Soluusi. Tapi butuh gateway yang honor split ke Soluusi (
          +or klinik could not remit → credit risk kembali).
      403 +- **Recommended: Opsi A** (Soluusi merchant, white-label) — cr
          +edit-risk-safe + gateway-controlled. Finance/legal verify tax
          +flow.
      404 + 
      405 +### 10.10 Anti-circumvention (contractual + technical) `[OPEN-
          +19]`
      406 +- Risk: customer lihat nama konsultan → google → nemu soluusi.
          +com → book direct, bypass klinik.
      407 +- Technical: white-label §10.8 (no Soluusi leak). Konsultan pr
          +ofile di soluusi.com B2C: consider hide dari public search OR
          +mark as "B2B-partner exclusive" (not bookable direct).
      408 +- Contractual: MoU klinik wajib **non-circumvention clause** —
          + klinik & Soluusi mutual (Soluusi gak court klinik's customer
          +direct; klinik gak bypass Soluusi ke konsultan).
      409 +- Konsultan-side: konsultan under NDA, gak direct-contact cust
          +omer klinik outside system.
      410 + 
      411  ---
      412  ---
      413  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      556  
      557  | Role | Akses | Tidak boleh |
      558  |---|---|---|
      559 -| **Partner Admin** (owner/direktur/HR klinik) | Org profile, 
          -staff mgmt, markup config, **wallet top-up & saldo**, dashboar
          -d aggregate, voucher create, konsultan assignment view | Konte
          -n sesi, data pasien individual, other klinik |                
      560 -| **Partner Staff** (resepsionis/PIC) | Create booking, lihat 
          -konsultan availability, redeem voucher, view own cabang sesi a
          -ggregate | Wallet top-up, markup config, other cabang |
      559 +| **Partner Admin** (owner/direktur/HR klinik) | Org profile,
          +staff mgmt, markup config, **klinik earnings & payout**, white
          +-label branding config, dashboard aggregate, voucher create, k
          +onsultan assignment view | Konten sesi, data customer individu
          +al (checkout PII restricted), other klinik |
      560 +| **Partner Staff** (resepsionis/PIC) | **Generate checkout li
          +nk** (pilih konsultan + jadwal), kirim link ke customer, view
          +own cabang sesi aggregate | Klinik earnings/payout, markup con
          +fig, branding config, other cabang |
      561  | **Konsultan** (Soluusi-side, assigned) | Own calendar, accep
           t/reject booking, session notes (encrypted), confirm completio
           n, view own payout | Other konsultan notes, klinik billing, ot
           her klinik pasien |
      562  | **HAP Associate** | CRM pipeline own klinik, klinik stage/ s
           esi aggregate (own klinik), weekly report auto | Konten sesi,
           konsultan payout, other associate's klinik |
      563  | **Ops Soluusi** | All klinik ops, onboarding, replacement ma
           tching, dispute resolve | Konten sesi clinical (konsultan-only
           ), finance payout approve |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      561  | **Konsultan** (Soluusi-side, assigned) | Own calendar, accep
           t/reject booking, session notes (encrypted), confirm completio
           n, view own payout | Other konsultan notes, klinik billing, ot
           her klinik pasien |
      562  | **HAP Associate** | CRM pipeline own klinik, klinik stage/ s
           esi aggregate (own klinik), weekly report auto | Konten sesi, 
           konsultan payout, other associate's klinik |
      563  | **Ops Soluusi** | All klinik ops, onboarding, replacement ma
           tching, dispute resolve | Konten sesi clinical (konsultan-only
           ), finance payout approve |
      564 -| **Finance** | Wallet top-up reconcile, konsultan payout, HAP
          - commission payout, wallet statement | Konten sesi, CRM pipeli
          -ne detail | 
      564 +| **Finance** | Split-payment settlement, klinik margin payout
          +, konsultan payout, HAP commission payout, statements | Konten
          + sesi, CRM pipeline detail |
      565  | **Super Admin** | All + config (net price, markup cap, rates
           ) | — |
      566  
      567  > **Confidentiality inti:** konten sesi (notes) **hanya** Kons
           ultan + audit. Klinik admin/staff **tidak** lihat konten — onl
           y aggregate (sesi count, topik kategori anonim, NPS).

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 2 lines
      588  - Commission accrual status
      589  
      590  ### 17.3 Finance dashboard
      591 -- Wallet top-up pending reconcile (manual transfer)           
      592 -- Wallet saldo outstanding (cross-klinik, liability)          
      591 +- Split-payment volume (cross-klinik, gross collected from cus
          +tomers)                                                       
      592 +- Klinik margin remitted / pending payout                     
      593  - Konsultan payout due
      594  - HAP commission payout due
      595 +- Refund processed
      596  - Gross profit (cross-klinik)
      597  
      598  ### 17.4 Konsultan dashboard

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 3 lines, removed 3 lines
      650  - Offline konsultan: per cabang (butuh fisik dekat)
      651  - Online konsultan: shared across cabang (any cabang can book)
      652  
      653 -### 19.3 Wallet `[OPEN-8]`                                    
      654 -- Default: 1 wallet per grup (consolidated, all cabang share s
          -aldo)                                                         
      655 -- Alternative: per-cabang wallet (bila HO wants cost-center tr
          -acking per cabang)                                            
      653 +### 19.3 Link & earnings per cabang `[OPEN-8]`
      654 +- Default: 1 klinik-branded link domain per grup (all cabang s
          +hare branding); earnings consolidated per grup
      655 +- Alternative: per-cabang subdomain (`booking.cabang.kliniknam
          +e.id`) + per-cabang earnings tracking (cost-center)
      656  
      657  ### 19.4 Scale escalation
      658  - >5 cabang → eskalasi (Playbook §4.9) → custom commercial

  Thought for 6s (ctrl+o to expand)

⏺ §10 rewrite + roles OK. Lanjut — §18 compliance, §20.2 notif, §21.2
  exceptions, §22 KPIs:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      617  
      618  ### 18.1 UU PDP (eff. Oct 2024)
      619  - Data residency: Indonesia region
      620 -- Consent flow: pasien (Model A) or klinik-as-controller (Mode
          -l B) — depends §6                                             
      621 -- Pasien data: minimize collected (Model B minimal)           
      620 +- Consent flow: customer consents at checkout (Model C, v1.1) 
          +— Soluusi = controller for checkout PII (nama, email, telp, pa
          +yment); klinik owns longitudinal record
      621 +- Customer data: minimize collected (only checkout fields perl
          +u — no DOB/address unless needed)
      622  - Right to access/delete: per pasien (Model A) or via klinik (
           Model B)
      623  - DPO contact + breach notification process `[OPEN-15]`
      624  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      619  - Data residency: Indonesia region
      620  - Consent flow: customer consents at checkout (Model C, v1.1) 
           — Soluusi = controller for checkout PII (nama, email, telp, pa
           yment); klinik owns longitudinal record
      621  - Customer data: minimize collected (only checkout fields perl
           u — no DOB/address unless needed)
      622 -- Right to access/delete: per pasien (Model A) or via klinik (
          -Model B)                                                      
      622 +- Right to access/delete: customer (checkout PII via Soluusi)
          ++ longitudinal via klinik
      623  - DPO contact + breach notification process `[OPEN-15]`
      624  
      625  ### 18.2 HIMPSI compliance (psikolog instruments)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      670  | Event | Recipient | Channel | Timing |
      671  |---|---|---|---|
      672  | Booking confirmed | Konsultan + klinik staff | WA/email | im
           mediate |
      673 -| Booking reminder | Konsultan + pasien (via klinik, Model B) 
          -| WA | H-1, H-2h |                                            
      673 +| Booking reminder | Konsultan + customer | WA (klinik-branded
          +) | H-1, H-2h |                                               
      674  | Session completed | Klinik staff + HAP | WA/email | immediat
           e |
      675  | Wallet low balance | Partner Admin | WA/email | saat saldo <
            2 sesi net |
      676  | Top-up received | Partner Admin | email | instant (webhook)
           |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 4 lines, removed 3 lines
      672  | Booking confirmed | Konsultan + klinik staff | WA/email | im
           mediate |
      673  | Booking reminder | Konsultan + customer | WA (klinik-branded
           ) | H-1, H-2h |
      674  | Session completed | Klinik staff + HAP | WA/email | immediat
           e |
      675 -| Wallet low balance | Partner Admin | WA/email | saat saldo <
          - 2 sesi net |                                                 
      676 -| Top-up received | Partner Admin | email | instant (webhook)
          -| 
      677 -| Top-up pending reconcile | Partner Admin | WA | <1 hari SLA
          -(manual transfer) | 
      675 +| Payment received (customer paid via link) | Customer + admin
          + klinik | WA/email (klinik-branded) | instant (webhook) |
      676 +| gcal invite sent | Customer + konsultan | email (klinik-bran
          +ded organizer) | on payment confirm |
      677 +| Refund processed | Customer + admin klinik | WA/email (klini
          +k-branded) | on trigger |
      678 +| Klinik margin payout | Partner Admin | email | monthly / aut
          +o-remit |
      679  | HAP stage milestone | HAP associate | WA/email | on trigger
           |
      680  | Follow-up cadence (post-meeting prospect) | Prospect (via HA
           P) | WA/email | H+1/3/7/14/21/30 |
      681  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      704  | Pasien no-show | Klinik staff + HAP | Track; >2/klinik → rev
           iew |
      705  | Konsultan resign mid-term | Ops Soluusi | Reassign all booki
           ngs; brief replacement |
      706  | Pasien complaint re konsultan | HAP + Ops | Review notes (ko
           nsultan-only access via audit), resolve |
      707 -| Wallet deduction dispute (sesi charged but didn't happen) | 
          -Finance + HAP | Review audit §9.3 → refund credit to wallet | 
      707 +| Refund dispute (customer claims sesi didn't happen) | Financ
          +e + HAP | Review audit §9.3 → gateway reverse-split refund |
      708  | Klinik wants change konsultan | HAP | Re-match from pool; up
           date assignment |
      709  | Top-up reconciliation pending (manual bank transfer) | Finan
           ce + Ops | Match transfer → credit wallet; <1 hari SLA |
      710  | Red flag (Playbook §4.8) | HAP | Stop negotiation; log |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      706  | Pasien complaint re konsultan | HAP + Ops | Review notes (ko
           nsultan-only access via audit), resolve |
      707  | Refund dispute (customer claims sesi didn't happen) | Financ
           e + HAP | Review audit §9.3 → gateway reverse-split refund |
      708  | Klinik wants change konsultan | HAP | Re-match from pool; up
           date assignment |
      709 -| Top-up reconciliation pending (manual bank transfer) | Finan
          -ce + Ops | Match transfer → credit wallet; <1 hari SLA |      
      709 +| White-label domain/branding issue | Ops Soluusi | Fix config
          + → redeploy; <1 hari SLA |
      710  | Red flag (Playbook §4.8) | HAP | Stop negotiation; log |
      711  
      712  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 5 lines, removed 3 lines
      725  | Avg sesi/klinik/90d | 20 | 40 (target) | Booking |
      726  | Klinik capai Stage 3 (40 sesi) | 5 | 100+ | Commission |
      727  | Konsultan utilization (slot booked/available) | — | ≥60% | B
           ooking |
      728 -| Wallet top-up cadence (top-up/active klinik/month) | — | ≥1.
          -5 | Wallet |                                                  
      729 -| Low-balance → top-up conversion | — | ≥70% | Wallet |       
      730 -| Sesi cancel refund rate | — | <10% | Wallet |               
      728 +| Link → payment conversion (customer pays after admin sends l
          +ink) | — | ≥60% | Checkout |
      729 +| gcal auto-add success rate | — | ≥98% | Calendar |
      730 +| Split-payment accuracy (net/margin correct) | 100% | 100% |
          +Payment |
      731 +| Sesi cancel refund rate | — | <10% | Checkout |
      732 +| White-label leak rate (Soluusi brand exposed to customer) |
          +0 | 0 | Audit |
      733  | HAP commission auto-calc accuracy | 100% | 100% | Commission
            |
      734  | Konsultan payout on-time | ≥90% | ≥98% | Konsultan payment |
      735  | Klinik NPS | — | ≥4.2/5 | In-app |

  Thought for 5s (ctrl+o to expand)

⏺ Lanjut — §23 integrations, §24 open questions (4 edits), §15.2 onboarding:
     
⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 5 lines, removed 6 lines
      749  
      750  | Integration | Purpose | Phase |
      751  |---|---|---|
      752 -| WhatsApp Business API | Notifications, cadence, low-balance 
          -| MVP |                                                       
      753 -| Email (SES/SendGrid) | Wallet statements, secondary notif | 
          -MVP |                                                         
      754 -| Zoom / Google Meet | Online session link | MVP |            
      755 -| Google Calendar | Konsultan schedule sync | MVP | 
      756 -| Payment gateway / VA | Wallet top-up (klinik→Soluusi) | MVP
          -| 
      757 -| Bank API / statement import | Top-up reconciliation (manual
          -transfer) | MVP | 
      752 +| WhatsApp Business API | Klinik-branded notif, reminders, cad
          +ence | MVP |
      753 +| Email (neutral domain) | Klinik-branded receipts, gcal invit
          +e, statements | MVP |
      754 +| Zoom / Google Meet | Online session link (klinik-branded) |
          +MVP |
      755 +| Google Calendar API | Auto-schedule customer + konsultan (kl
          +inik-branded service account) | MVP |
      756 +| Payment gateway w/ split (Midtrans/Xendit/Stripe Connect) |
          +Customer direct payment + split net/margin | MVP |
      757  | Object storage (R2/S3) | Sesi files, MoU, materi | MVP |
      758  | Klinik management software (MIK, Klinosys, etc.) | API or ma
           nual booking sync | Phase 2 |
      759  | Psychometric instruments (licensed) | MHCU MMCI/MMPI/DISC |
           Phase 2 |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      771  | `[OPEN-3]` | Net price: Soluusi-set vs klinik-set patient pr
           ice? | Soluusi-set net | Pricing module |
      772  | `[OPEN-4]` | Voucher discount bearer (Soluusi/klinik/shared)
           ? | Soluusi absorbs | Voucher + billing |
      773  | `[OPEN-4a]` | Free trial 10 min: konsultan fee prorated? | S
           oluusi absorbs | Konsultan payment |
      774 -| `[OPEN-6]` | Pasien identity model A (registered) vs B (anon
          -ymous)? | **B (recommended)** | Data schema, UU PDP, booking |
      774 +| `[OPEN-6]` | Pasien identity model A vs B vs **C (checkout-g
          +uest)**? | **C (recommended, v1.1)** | Data schema, UU PDP, bo
          +oking |
      775  | `[OPEN-7]` | Reschedule/cancel cutoff & fees? | H-2h free |
           Booking |
      776  | `[OPEN-7a]` | No-show/cancel <H-2jam: refund credit %? | 50%
            refund / 50% forfeit | Wallet |
      777  | `[OPEN-8]` | Multi-cabang wallet: 1 grup vs per-cabang? | 1
           wallet grup | Wallet |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      773  | `[OPEN-4a]` | Free trial 10 min: konsultan fee prorated? | S
           oluusi absorbs | Konsultan payment |
      774  | `[OPEN-6]` | Pasien identity model A vs B vs **C (checkout-g
           uest)**? | **C (recommended, v1.1)** | Data schema, UU PDP, bo
           oking |
      775  | `[OPEN-7]` | Reschedule/cancel cutoff & fees? | H-2h free | 
           Booking |
      776 -| `[OPEN-7a]` | No-show/cancel <H-2jam: refund credit %? | 50%
          - refund / 50% forfeit | Wallet |
      777 -| `[OPEN-8]` | Multi-cabang wallet: 1 grup vs per-cabang? | 1
          -wallet grup | Wallet |
      776 +| `[OPEN-7a]` | No-show/cancel <H-2jam: refund to customer %?
          +| 50% refund / 50% forfeit (gateway reverse-split) | Checkout
          +|
      777 +| `[OPEN-8]` | Multi-cabang: 1 link domain grup vs per-cabang
          +subdomain? | 1 grup domain, consolidated earnings | White-labe
          +l |
      778  | `[OPEN-9]` | Session duration variants (first vs follow-up)?
            | TBD | Booking/pricing |
      779  | `[OPEN-9a]` | Online recording opt-in? | No default | Sessio
           n |
      780  | `[OPEN-10]` | Dispute SLA? | TBD | Session |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines, removed 2 lines
      779  | `[OPEN-9a]` | Online recording opt-in? | No default | Sessio
           n |
      780  | `[OPEN-10]` | Dispute SLA? | TBD | Session |
      781  | `[OPEN-10a]` | Konsultan standby compensation on cancel <H-2
           4h? | Dapat porsi dari 50% forfeit | Konsultan payment |
      782 -| ~~`[OPEN-11]`~~ | ~~Suspend at overdue~~ | **Superseded by p
          -repaid** — saldo = gate, no overdue possible | — |            
      783 -| `[OPEN-12]` | PPN / faktur pajak on top-up? | Verify finance
          - | Wallet/tax | 
      782 +| ~~`[OPEN-11]`~~ | ~~Suspend at overdue~~ | **Superseded (v1.
          +1)** — customer-funded split, no AR, no overdue | — |
      783 +| `[OPEN-12]` | PPN/faktur: merchant of record Soluusi vs klin
          +ik? | Soluusi merchant (Opsi A) — verify finance/legal | Tax |
      784  | `[OPEN-13]` | Cross-sell attribution commission? | TBD | Cro
           ss-sell |
      785  | `[OPEN-14]` | Termination reason log + re-activation path? |
            TBD | Lifecycle |
      786  | `[OPEN-15]` | DPO contact + breach notification process? | T
           BD | Compliance |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 6 lines, removed 1 line
      784  | `[OPEN-13]` | Cross-sell attribution commission? | TBD | Cro
           ss-sell |
      785  | `[OPEN-14]` | Termination reason log + re-activation path? |
            TBD | Lifecycle |
      786  | `[OPEN-15]` | DPO contact + breach notification process? | T
           BD | Compliance |
      787 -| `[OPEN-16]` | Wallet credit expiry? | No expiry (default) | 
          -Wallet |                                                      
      787 +| ~~`[OPEN-16]`~~ | ~~Wallet expiry~~ | **Superseded (v1.1)**
          +— no wallet, customer pays per-booking | — |
      788 +| `[OPEN-17]` | White-label domain: klinik CNAME vs neutral So
          +luusi domain? | Neutral Soluusi domain (default), CNAME for pr
          +emium klinik | White-label |
      789 +| `[OPEN-18]` | Merchant of record (Soluusi vs klinik) + tax f
          +low? | Soluusi merchant, white-label receipt | Tax/legal |
      790 +| `[OPEN-19]` | Anti-circumvention: hide konsultan from soluus
          +i.com public + MoU clause? | Both technical + contractual | Wh
          +ite-label/MoU |
      791 +| `[OPEN-20]` | gcal service account branding (klinik-branded
          +organizer)? | Dedicated klinik-branded service account | Calen
          +dar |
      792 +| `[OPEN-21]` | Scheduling: admin-pick slot (MVP) vs customer
          +slot-picker (Phase 2)? | Admin-pick MVP, slot-picker Phase 2 |
          + Booking |
      793  
      794  ---
      795  ---

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines
      515  - [ ] Sosialisasi staf (training: cara refer, kode referral, Q
           R)
      516  - [ ] Materi promosi siap (poster, digital)
      517  - [ ] Set net price + markup config in portal
      518 +- [ ] White-label domain configured (neutral/klinik) + brandin
          +g (logo, warna)                                               
      519 +- [ ] gcal service account configured (klinik-branded organize
          +r)                                                            
      520  - [ ] Test alur end-to-end (1 dummy booking + sesi)
      521  - [ ] Tanggal go-live determined
      522  

  Thought for 4s (ctrl+o to expand)

⏺ Last batch — mermaid redraw (§25.1 master, §25.5 checkout, §25.7 churn) +
  Appendix A + footer:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 10 lines, removed 10 lines
      804      A[MoU signed by HAP] --> B[Ops: assign konsultan pool to k
           linik]
      805      B --> C[Set net price + markup config in portal]
      806      C --> D[Konsultan publish availability slots]
      807 -    D --> E[Pasien needs consultation - walk-in/refer/QR]     
      808 -    E --> F[Staff klinik pilih konsultan di portal]           
      809 -    F --> G[Create booking + optional voucher]                
      810 -    G --> G2{Wallet saldo >= net?}                            
      811 -    G2 -->|no| G3[Prompt top-up - booking pending] 
      812 -    G3 --> G 
      813 -    G2 -->|yes| G4[Deduct net from wallet - booking CONFIRMED]
      814 -    G4 --> H[Konsultan + pasien check-in] 
      815 -    H --> I[Session runs - online/offline] 
      807 +    D --> E[Customer needs consultation - telp/WA ke klinik]
      808 +    E --> F[Admin klinik pilih konsultan + jadwal di portal]
      809 +    F --> G[Generate white-label checkout link - klinik-brande
          +d]
      810 +    G --> GL[Admin kirim link ke customer - WA/email]
      811 +    GL --> GP{Customer buka link + bayar?}
      812 +    GP -->|no/expire| GX[Follow-up admin - regenerate if neede
          +d]
      813 +    GX --> GL
      814 +    GP -->|yes pay| G4[Gateway collect + split: net to Soluusi
          +, margin to klinik]
      815 +    G4 --> H[Booking auto-CONFIRMED - auto gcal customer + kon
          +sultan]
      816 +    H --> I[Session runs - online link / offline klinik]
      817      I --> J[Konsultan notes encrypted + both confirm completed
           ]
      818      J --> K[Status COMPLETED - release from net held]
      819      K --> O[From net held: pay konsultan fee + accrue HAP stag
           e]
      819 -    K --> L[Separately: klinik bills pasien marked-up - klinik
          - keeps margin] 
      820      O --> P[Klinik dashboard aggregate update]
      821      O --> Q[Commission engine evaluate 5/25/40 stage]
      822      Q --> R{Stage trigger?}

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 22 lines, removed 17 lines
      880      S16 -->|no| S18[Manual confirm - proceed]
      881  ```
      882  
      883 -### 25.5 Wallet & Prepayment Flow (Klinik → Soluusi)          
      883 +### 25.5 White-label Checkout & Split-Payment Flow            
      884  ```mermaid
      885  sequenceDiagram
      886 -    participant KL as Klinik PIC                              
      887 -    participant WAL as Wallet Service 
      886 +    participant ADM as Admin Klinik
      887 +    participant POR as Klinik Portal
      888 +    participant CUS as Customer
      889 +    participant GW as Payment Gateway
      890      participant BK as Booking Service
      889 -    participant SES as Session Service 
      890 -    participant FIN as Finance 
      891 -    KL->>WAL: Top-up wallet (gateway/VA/bank transfer) 
      892 -    WAL->>WAL: Credit saldo after payment confirmed 
      893 -    KL->>BK: Create booking (konsultan + slot + optional vouch
          -er) 
      894 -    BK->>WAL: Check saldo >= net price? 
      895 -    WAL-->>BK: Saldo OK 
      896 -    BK->>WAL: Deduct net (less voucher adj) - idempotency key 
      897 -    BK->>BK: Booking CONFIRMED - konsultan dispatched 
      898 -    Note over BK,SES: Sesi runs + dual-confirm COMPLETED 
      899 -    SES->>FIN: Release from net held 
      900 -    FIN->>FIN: Pay konsultan fee + accrue HAP stage 
      901 -    Note over WAL,KL: Monthly wallet statement to klinik 
      902 -    Note over WAL: Cancel >= H-24h: full refund credit; <H-24h
          -: 50% refund 
      891 +    participant GCA as Google Calendar
      892 +    ADM->>POR: Pilih konsultan + jadwal
      893 +    POR->>BK: Generate white-label checkout link (klinik-brand
          +ed)
      894 +    POR-->>ADM: Link (neutral/klinik domain)
      895 +    ADM->>CUS: Kirim link (WA/email)
      896 +    CUS->>BK: Buka link - isi data + lihat konsultan + harga m
          +arked-up
      897 +    CUS->>GW: Bayar Rp300k (marked-up)
      898 +    GW->>GW: Split: Rp250k net -> Soluusi, Rp50k margin -> kli
          +nik
      899 +    GW-->>BK: Payment confirmed (webhook)
      900 +    BK->>BK: Booking auto-CONFIRMED - konsultan dispatched
      901 +    BK->>GCA: Auto-create event (klinik-branded organizer)
      902 +    GCA-->>CUS: gcal invite (email)
      903 +    GCA-->>ADM: gcal event konsultan
      904 +    Note over BK: Sesi runs + dual-confirm COMPLETED
      905 +    BK->>GW: (no charge - already collected) release net held
      906 +    Note over GW: From net: pay konsultan fee + accrue HAP sta
          +ge
      907 +    Note over GW,CUS: Cancel >= H-24h: gateway reverse-split r
          +efund
      908  ```
      909  
      910  ### 25.6 Konsultan Payment Flow (Soluusi → Konsultan)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      928  flowchart TD
      929      C1[Monitor signals] --> C2{Risk signal?}
      930      C2 -->|sesi decline 2 weeks| C3[Flag: churn risk]
      931 -    C2 -->|wallet saldo rendah lama| C3                       
      931 +    C2 -->|link→pay conversion turun| C3                      
      932      C2 -->|PIC unresponsive| C3
      933      C2 -->|NPS drop| C3
      934      C3 --> C4[Auto-notify HAP + Ops]

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      948  | §4 Konsultan pool | Playbook §4.6 (replacement), PRD M3 |
      949  | §6 Pasien identity | PRD §4.2, §11 (new) |
      950  | §9 Anti-fraud | Playbook §5.1, PRD §8.4 |
      951 -| §10 Wallet & Prepayment | PRD M6 (revised: prepaid) |       
      951 +| §10 White-label Checkout & Split | PRD M6 (revised: white-la
          +bel + split, v1.1) |                                          
      952  | §11 Konsultan payment | PRD §15.3 (open) |
      953  | §15 Lifecycle | Playbook §4.11–4.14, §0.5 |
      954  | §18 Compliance | PRD §11 |

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      960  ---
      961  
      962  *Soluusi · Klinik Vertical Spec · Confidential · Internal Use 
           Only*
      963 -*Versi 1.0 · Agustus 2026 · Draft — confirm `[OPEN]` items bef
          -ore build*                                                    
      963 +*Versi 1.1 · Agustus 2026 · Draft — confirm `[OPEN]` items bef
          +ore build*                                                    

  Thought for 6s (ctrl+o to expand)

⏺ Semua patches ter apply. Grep sekali buat pastiin gak ada reference
  "wallet/prepaid/top-up" yang lolos:

  Thought for 6s, searched for 1 pattern (ctrl+o to expand)

⏺ Sisa match semua intentional — revision-history notes + strikethrough
  deletion-log + "e-wallet" (payment method). Clean. Spec sekarang fully v1.1.

  ---
  Jawaban ke pertanyaan lu ("menurut lu gimana?" — scheduling)

  Recommendation MVP: admin pick konsultan + 1 slot di dashboard → link = 
  pay-and-confirm (customer cuma isi data + bayar). Alasan:
  - Paling simple, match klinik coordination flow (admin udah ngobrol sama
  customer soal jadwal)
  - Gak perlu bangun availability engine real-time di customer-side (itu yang
  ribet)
  - Klinik kontrol penuh — sesuai "respect partner B2B"
  
  Phase 2: customer slot-picker di link (admin pick konsultan, customer pilih
  slot dari available) — lebih mirror soluusi.com B2C, self-serve. Tapi butuh
  slot-locking + availability engine yang robust. Udah gw catat di spec
  [OPEN-21] + §7.1.

  Yang berubah (Model v1.0 → v1.1)

  ┌────────────┬────────────────────┬───────────────────────────────────────┐
  │    Area    │   v1.0 (wallet)    │            v1.1 (current)             │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ Payment    │ Klinik top-up      │ Customer bayar langsung via link      │
  │ source     │ wallet dulu        │                                       │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ Credit     │ Klinik-funded      │ Customer-funded = zero ✅             │
  │ risk       │ (saldo gate)       │                                       │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ Money flow │ Deduct net dari    │ Gateway split (net→Soluusi,           │
  │            │ wallet             │ margin→klinik auto-remit)             │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ Pasien     │ Model B            │ Model C (checkout-guest) — Soluusi    │
  │ identity   │ (anonymous)        │ collect PII checkout                  │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ Branding   │ Soluusi-facing     │ White-label, hide Soluusi             │
  │            │                    │ (domain/receipt/gcal/notif)           │
  ├────────────┼────────────────────┼───────────────────────────────────────┤
  │ gcal       │ Konsultan only     │ Auto-add customer + konsultan         │
  │            │                    │ (klinik-branded organizer)            │
  └────────────┴────────────────────┴───────────────────────────────────────┘

  Yang dihapus: top-up pack, wallet low-balance, wallet expiry, bank-API
  reconcile, dunning/overdue subsystem. Deletion log di §10.6.

  Yang gw gak bisa verifikasi (flag)

  1. soluusi.com e2e flow — WebFetch error backend ("Model not exist"),
  WebSearch infinite-loop (issue known). Lu kirim screenshot WhatsApp ke gw, gw 
  gak bisa render-nya (Read empty 2x, valid JPEG 46KB tapi gak load visual).
  Jadi checkout field-nya gw model-in dari deskripsi lu + standar checkout. Lu 
  cek manual soluusi.com buat konfirmasi field persisnya
  (nama/email/telp/keluhan/metode bayar) — gw tandai [OPEN] di §10.2.
  2. Merchant of record / tax flow [OPEN-18] — ini fork finansial/legal yang
  signifikan. Gw default Opsi A (Soluusi merchant, white-label receipt) karena
  credit-risk-safe + gateway-controlled. Tapi PPN/faktur flow Soluusi-heavy —
  finance/legal lu perlu verify. Kalau klinik mau jadi merchant (Opsi B), credit
  risk ke Soluusi bisa balik (klinik gak remit net).
  3. Anti-circumvention [OPEN-19] — risk real: customer lihat nama konsultan →
  google → nemu soluusi.com → bypass. White-label teknis (§10.8) + MoU
  non-circumvention clause wajib. Plus pertimbangin hide konsultan B2B-partner
  dari public search soluusi.com.

  Open questions baru (perlu lu keputusan)

  - [OPEN-17] Domain: klinik CNAME vs neutral Soluusi domain?
  - [OPEN-18] Merchant of record (Soluusi vs klinik) + tax?
  - [OPEN-19] Anti-circumvention: hide konsultan dari soluusi.com + MoU clause?
  - [OPEN-20] gcal service account branding?
  - [OPEN-21] Scheduling: admin-pick MVP vs slot-picker Phase 2? (gw rekomendasi
  admin-pick MVP)

  Spec file: /Users/mekari/soluusi-b2b-beforemarge/Soluusi B2B - Klinik Vertical
  Spec.md — 27 edits applied, v1.1, konsisten.

  Skipped: verifikasi soluusi.com (tool gagal — lu cek manual). Add when lu udah
  cek field checkout-nya + keputusan merchant-of-record.

✻ Sautéed for 22m 17s

❯ biar lu tau isi folder soluusi.com lu bisa masuk ke path ini                  
  mekari/soluusi-main lu bisa baca struktur isinya dan fahami mendalam          
  sebernnya gak akan beda jauh dengan itu dan kemudian update ke doc kita atau  
  prd kita                                                                      
  
  Thought for 17s (ctrl+o to expand)

⏺ Ada codebase asli-nya. Oke, gw baca struktur-nya dulu buat scale-nya, lalu
  dalami yang relevan (booking/checkout/payment/gcal flow — yang B2B harus
  mirror):
     
⏺ Bash(cd /Users/mekari/soluusi-main 2>/dev/null && echo "=== TOP LEVEL ===" &&
      ls -la && echo "" && echo "=== GIT? ===" && (git rev-parse --show-toplevel
      2>/dev/null…)
  ⎿  Error: Exit code 1
     === TOP LEVEL ===
     total 1048
     drwxr-xr-x@  39 mekari  staff    1248 Jul 30 00:39 .
     drwxr-x---+ 310 mekari  staff    9920 Aug  4 09:32 ..
     -rw-r--r--@   1 mekari  staff    8196 Jul 30 21:17 .DS_Store
     drwxr-xr-x@   3 mekari  staff      96 Jul 11 08:02 .claude
     -rw-r--r--    1 mekari  staff     275 Jul 23 18:44 .editorconfig
     -rw-r--r--    1 mekari  staff    2017 Jul 10 17:27 .env
     -rw-r--r--    1 mekari  staff    1086 Jul 23 18:44 .env.example
     … +38 lines (ctrl+o to expand)

  Thought for 15s (ctrl+o to expand)

⏺ Laravel app (composer/artisan/app/routes). Cakupan signifikan — saya pindai
  strukturnya dulu (README + routes + app tree + config), lalu membagi pembacaan
  mendalam ke para agen paralel setelah saya mengetahui bentuknya:

⏺ Bash(cd /Users/mekari/soluusi-main && echo "=== README ===" && cat README.md
      && echo "" && echo "=== composer.json ===" && cat composer.json && echo ""
      && echo "===…)
  ⎿  === README ===
     <p align="center"><a href="https://laravel.com" target="_blank"><img s
     rc="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5
     … +148 lines (ctrl+o to expand)
  ⎿  Shell cwd was reset to /Users/mekari/soluusi-b2b-beforemarge

⏺ Bash(cd /Users/mekari/soluusi-main && echo "=== ROUTES ===" && ls -la routes/
      && echo "" && echo "=== APP TREE (2 levels) ===" && find app -maxdepth 2
      -type d | sort…)
  ⎿  === ROUTES ===
     total 96
     drwxr-xr-x@  7 mekari  staff    224 Jul 27 09:30 .
     … +105 lines (ctrl+o to expand)
  ⎿  Shell cwd was reset to /Users/mekari/soluusi-b2b-beforemarge

  Thought for 23s (ctrl+o to expand)

⏺ Sekarang bentuknya jelas. Temuan utama: Laravel 9 + Sanctum + Socialite, 
  Midtrans & Xendit (keduanya!), google/apiclient (gcal), Intervention (image).
  Migrasi mengungkap B2C flow: consultation_sessions (payment_method +
  link_meeting + status enum + consultant_id), vouchers, consultant_ratings,
  consultant_packages, schedules. Routes: web.php 28KB (main flow), api.php.
  Mail: Client/ + Konsultan/ templates.

  Ini adalah pembacaan mendalam yang sebenarnya — saya akan membaginya ke 3
  agent paralel, masing-masing memiliki dimensi yang terjaga, lalu
  menyintesanya:

⏺ 3 agents finished (ctrl+o to expand)
   ├ Map booking+checkout flow · 19 tool uses · 54.5k tokens
   │ ⎿  Done
   ├ Map konsultan+schedule+voucher models · 40 tool uses · 54.5k tokens
   │ ⎿  Done
   └ Map payment+gcal+mail integrations · 17 tool uses · 54.4k tokens
     ⎿  Done
     
  Thought for 6m 29s (ctrl+o to expand)

⏺ Tiga laporan masuk — sintesis lengkap. Temuan yang mengubah asumsi spesifikasi
  saya:

  Pembayaran: Midtrans Snap = primer (Xendit sudah tidak aktung/stubbed di B2C).
  Tidak ada pembayaran terbagi sama sekali → saya mengonfirmasi NEW BUILD.
  Webhook tidak ada verifikasi tanda tangan (lubang keamanan, jangan ditiru).
  Merchant of record = Soluusi (mengonfirmasi Opsi A).

  gcal: B2C menggunakan pengguna OAuth (soluusi@gmail.com refresh token), BUKAN
  service account → tidak viable untuk multi-klinik white-label. String "Soluusi
  - {package}" di-hardcode di event summary/description. Bug tersembunyi:
  kegagalan gcal -> sesi tetap waiting meskipun pembayaran terverifikasi.

  Email: 14 mailable, SEMUA di-hardcode "Soluusi - " di subject prefix. Pengirim
  global, tidak ada per-mailable from().

  Model Konsultan: Kategori ter-seed =
  Psikologi/Karir/Bisnis/Keuangan/Hukum/Pajak (BUKAN psikolog/nutrisi/fisio
  seperti asumsi saya). consultant_packages = mekanisme harga
  (name/price/duration/quota/results JSON, tidak ada field online/offline).
  schedules = concrete datetime slots. Voucher = diskon (%) | potongan (flat)
  saja — tidak ada free-trial. consultants.profit_sharing int field. Rating 1-5.
  Tidak ada check-in/no-show/dual-confirm (konsultan hanya menandai done).

  Checkout: customer submits schedule_id, consultant_id, package_uuid, voucher,
  complaint, + DOB, regency_id, gender (lebih banyak dari daftar PII saya).

  Sekarang perbarui spec-nya — lampirkan Appendix C (audit B2C + delta B2B)
  sebagai sintesis kunci, plus suntingan bedah terhadap klaim faktual di bagian
  isi yang asumsi saya salah:

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 96 lines
       959  
       960  ---
       961  
       962 +## Appendix C — soluusi.com B2C audit (ground truth) + B2B de
           +ltas                                                         
       963 +                                                             
       964 +> Audited dari codebase `/Users/mekari/soluusi-main` (Laravel
           + 9 + Sanctum + Socialite + Midtrans + Xendit + google/apiclie
           +nt). B2B klinik harus mirror B2C e2e tapi white-label + split
           + + B2B-specific anti-fraud. Berikut ground truth B2C + delta
           +B2B. Sumber: `routes/web.php`, `MidtransController`, `GoogleC
           +alendarService`, `app/Models/*`, `database/migrations/*`, `ap
           +p/Mail/*`.
       965 + 
       966 +### C.1 Stack & integrations (B2C actual)
       967 +- **Laravel 9** (PHP 8), Sanctum auth, Socialite (Google logi
           +n)
       968 +- **Midtrans** (`midtrans/midtrans-php`) — PRIMARY, **Snap re
           +direct-token** (bukan direct charge). `config/midtrans.php`
       969 +- **Xendit** (`xendit/xendit-php`) — **DEAD/stubbed** di B2C
           +(`XenditController` kosong, callback commented out). Hanya co
           +nfig `auth_key`.
       970 +- **google/apiclient** 2.15 — Google Calendar + Meet (no Zoom
           +)
       971 +- **Intervention/image**, **indoregion** (provinsi/kabupaten
           +→ `regency_id` di booking)
       972 + 
       973 +### C.2 Payment (B2C actual → B2B delta)
       974 +**B2C:** Midtrans Snap, single merchant (Soluusi = merchant o
           +f record), `gross_amount = package.price + Rp10.000` admin fe
           +e flat. Webhook `POST api/midtrans-callback` → `MidtransContr
           +oller@callback`. **NO signature verification** (trusts `order
           +_id` + `transaction_status` verbatim — security hole). `payme
           +nt_status` enum(waiting, verified, canceled) tapi callback ju
           +ga nulis `failed` (gak di enum — loose MySQL bug). Session st
           +atus: `waiting → scheduled` (on capture/settlement) `→ done`
           +/ `→ canceled` (on expire).
       975 + 
       976 +**B2B delta (§10):**
       977 +- **Split-payment = NEW BUILD** — B2C gak punya split/receive
           +r/commission sama sekali (grep zero hit). Build via **Midtran
           +s partner/Connect API** atau **Xendit Marketplace** (`for-use
           +r` header). `[OPEN-18]`
       978 +- **FIX: webhook signature verification** (Midtrans `signatur
           +e_key` verify) — jangan mirror hole B2C. `[SECURITY — wajib]`
       979 +- **FIX: `payment_status` enum** — add `failed` properly di B
           +lueprint (B2C loose-enum bug).
       980 +- Decide: keep Rp10k admin fee pattern? (B2C adds flat). `[OP
           +EN]`
       981 +- Merchant of record = Soluusi (Opsi A) — confirmed sesuai B2
           +C.
       982 + 
       983 +### C.3 Google Calendar (B2C actual → B2B delta) — CRITICAL w
           +hite-label
       984 +**B2C:** `app/Services/GoogleCalendarService.php` pakai **OAu
           +th USER** (refresh token `soluusi@gmail.com`, `config/service
           +s.php:44-48`). Event: summary `"Soluusi - {packageName}"` (ha
           +rdcoded), description `"…dibuat oleh Soluusi."` (hardcoded),
           +hangoutsMeet conference, both attendees (client + konsultan),
           + reminders email@60min + popup@30min. `link_meeting` = Meet U
           +RI (auto-populated on payment verify). `google_calendar_event
           +_id` stored.
       985 + 
       986 +**Latent bug B2C:** GCal failure → session stays `waiting` wa
           +lau payment verified (status set hanya di try block).
       987 + 
       988 +**B2B delta (§10.4, §10.8) — white-label blocker:**
       989 +- **OAuth-user gak viable untuk multi-klinik white-label** (p
           +er-klinik refresh token, expire/revoke → re-auth pain). **B2B
           + migrate ke service account + domain-wide delegation** (one S
           +A, per-klinik `calendar_id`). `[OPEN-20]` upgraded to archite
           +ctural decision.
       990 +- **Parameterize "Soluusi" strings** → klinik `brand_name` (s
           +ummary + description).
       991 +- **FIX: GCal failure non-fatal** — booking confirmed regardl
           +ess, gcal retry via queue job. Jangan mirror B2C bug.
       992 +- Meet only (no Zoom) — unless klinik minta Zoom.
       993 + 
       994 +### C.4 Email (B2C actual → B2B delta) — white-label
       995 +**B2C:** 14 mailables (`app/Mail/Client/` 5 + `app/Mail/Konsu
           +ltan/` 9). **ALL 14 hardcoded `"Soluusi - "` subject prefix**
           + (literal string, bukan config). Global sender (`config/mail.
           +php` env `MAIL_FROM_*`), no per-mailable `from()`. Blade view
           +s `resources/views/emails/` carry Soluusi branding.
       996 + 
       997 +**B2B delta (§10.8, §20):**
       998 +- Replace 14 hardcoded `"Soluusi - "` prefix → `brand_name` f
           +rom klinik/tenant config.
       999 +- Per-tenant `MAIL_FROM_*` atau `Mailable::from()` override d
           +i base class.
      1000 +- Audit 14 blade views for Soluusi logo/wordmark.
      1001 +- Key mailables to mirror: `Bayar` (pay instruction), `SudahB
           +ayar` (receipt, client + konsultan), `LinkMeeting`, reminder,
           + review-request.
      1002 + 
      1003 +### C.5 Konsultan data model (B2C actual → B2B delta)
      1004 +**B2C tables (reuse di B2B):**
      1005 +- `consultants`: id, about, **`profit_sharing` int** (fee % m
           +echanism!), bank, account_number, phone, `status` enum(accept
           +/reject/pending/interview/payment), slug, specialty, city, is
           +_active, SoftDeletes. Onboarding: pending → interview → accep
           +t/reject.
      1006 +- `categories` (seeded): **Psikologi, Karir, Bisnis, Keuangan
           +, Hukum, Pajak** — BUKAN psikolog/nutrisi/fisio. `code_catego
           +ry`.
      1007 +- `consultant_packages`: **pricing mechanism** — name, price
           +(int), duration (minutes), quota, results (JSON). **No online
           +/offline field** (mode elsewhere). FK typo `consultan_id` (re
           +plicate or fix).
      1008 +- `schedules`: **concrete datetime slots** (started_at, ended
           +_at) per consultant — NOT recurring pattern. hasOne Consultat
           +ionSession.
      1009 +- `vouchers`: `type` = `potongan` (flat) | `diskon` (%) — **N
           +O free-trial type.** Fields: nominal, kuota, usage, date wind
           +ow, min_transaction, max_discount, discount_percentage.
      1010 +- `consultant_ratings`: 1-5 int, NO review text (text di `con
           +sultation_sessions.review_text`).
      1011 +- `consultant_companies`: work history (experience computatio
           +n). `certificates`, `education`: profile credentials.
      1012 + 
      1013 +**B2B delta:**
      1014 +- **Categories mismatch**: B2C = Psikologi/Karir/Bisnis/Keuan
           +gan/Hukum/Pajak. B2B klinik butuh kesehatan (psikolog/nutrisi
           +/fisio). **Reuse table, add health categories** OR klinik ass
           +ign dari Psikologi + new health cats. `[OPEN-22]`
      1015 +- `profit_sharing` int — konsultan fee modeled as % (confirm
           +flat vs %, §11.1 `[OPEN-1]`).
      1016 +- `consultant_packages` = pricing (§5.1 net price table shoul
           +d map to packages). Mode (online/offline) = B2B addition (B2C
           + gak punya).
      1017 +- Voucher free-trial 10 min (§12.1) — **NOT in B2C**. Build a
           +s new type OR drop. `[OPEN-4b]`
      1018 +- Rating 1-5 (bukan NPS) — update §17.1.
      1019 +- Replicate-or-fix gotchas: `INTERVIEW='inteview'` typo, `con
           +sultan_id` FK typo, status enum via raw ALTER (fix ke Bluepri
           +nt di B2B).
      1020 + 
      1021 +### C.6 Booking/session flow (B2C actual → B2B delta)
      1022 +**B2C customer journey:** browse category → consultant detail
           + → pick package → `get-jadwal-by-package` (AJAX slots) → paym
           +ent page → `create-va` (creates `consultation_session` + Snap
           + URL) → Midtrans Snap → callback → GCal + status=scheduled →
           +consultant `set-note`/`set-done` → review.
      1023 + 
      1024 +**`consultation_sessions` final schema (key cols):** id, sche
           +dule_id, user_id, consultant_id, consultant_package_id, `stat
           +us` enum(waiting/scheduled/done/canceled), order_number, amou
           +nt, package_price, voucher_amount, payment (gross str), va_nu
           +mber, `payment_status` enum(waiting/verified/canceled), url_s
           +nap, snaptoken, expired_at, completed_at, payment_method, **l
           +ink_meeting** (Meet URI), **google_calendar_event_id**, **com
           +plaint** (TEXT, customer pre-session), note, solution, review
           +_text, rating, voucher_id, order_at, payment_at.
      1025 + 
      1026 +**Customer submits at checkout:** schedule_id, consultant_id,
           + package_uuid, voucher, complaint, + profile (phone_number, d
           +ate_of_birth, regency_id, gender).
      1027 + 
      1028 +**Status lifecycle B2C:** waiting → scheduled (payment verify
           + OR consultant set-link-meeting) → done (consultant set-note/
           +set-done) → canceled (Midtrans expire). **NO check-in, NO in-
           +session, NO no-show, NO dual-confirm.**
      1029 + 
      1030 +**B2B delta (§7, §9):**
      1031 +- B2B booking = admin generate link (§7.1) — diverges dari B2
           +C self-serve. Reuse `consultation_sessions` table.
      1032 +- **Checkout fields**: add DOB, regency, gender (B2C collects
           + these) — update §10.2 + §6 Model C PII list.
      1033 +- `complaint` field = "keluhan singkat" (§10.2) — matches B2C
           +. Good.
      1034 +- **Dual-confirm = B2B-NEW** (§9.1) — B2C gak punya (consulta
           +nt just mark done). B2B adds karena commission fraud risk. St
           +atus flow B2B: waiting → scheduled → checked-in → completed (
           +dual-confirm) / no-show. `[B2B-NEW]`
      1035 +- **No-show policy = B2B-NEW** (§7.3) — B2C gak handle no-sho
           +w. `[B2B-NEW]`
      1036 +- Reuse: order_number, snaptoken, expired_at (1h), payment_st
           +atus, complaint, note, solution, review_text, rating, link_me
           +eting, google_calendar_event_id.
      1037 + 
      1038 +### C.7 B2B-NEW vs B2C-reuse vs B2C-fix summary
      1039 +| Area | B2C has? | B2B action |
      1040 +|---|---|---|
      1041 +| Midtrans Snap payment | ✅ | reuse + add split (NEW) |
      1042 +| Split-payment | ❌ | **NEW BUILD** (Midtrans Connect / Xend
           +it Marketplace) |
      1043 +| Webhook signature verify | ❌ | **FIX** (don't mirror hole)
           + `[SECURITY]` |
      1044 +| gcal OAuth user | ✅ (soluusi@gmail.com) | **MIGRATE to ser
           +vice account** (white-label) |
      1045 +| gcal "Soluusi" strings | ✅ hardcoded | **parameterize** →
           +klinik brand |
      1046 +| 14 mail "Soluusi" prefix | ✅ hardcoded | **parameterize**
           +→ klinik brand |
      1047 +| Consultant/Package/Schedule/Voucher tables | ✅ | **reuse**
           + (add health cats, mode field) |
      1048 +| Categories (Psikologi/Karir/...) | ✅ | reconcile + add hea
           +lth `[OPEN-22]` |
      1049 +| Voucher free-trial | ❌ | **NEW type** OR drop `[OPEN-4b]`
           +|
      1050 +| Dual-confirm anti-fraud | ❌ | **B2B-NEW** (commission frau
           +d) |
      1051 +| No-show policy | ❌ | **B2B-NEW** |
      1052 +| White-label domain/branding | ❌ | **B2B-NEW** (hide Soluus
           +i) |
      1053 +| Admin-generate-link booking | ❌ (B2C self-serve) | **B2B-N
           +EW** (MVP) |
      1054 +| Customer account | ✅ (registered) | **B2B diverges** → gue
           +st checkout (Model C) |
      1055 + 
      1056 +---
      1057 + 
      1058  *Soluusi · Klinik Vertical Spec · Confidential · Internal Use
             Only*
      1059  *Versi 1.1 · Agustus 2026 · Draft — confirm `[OPEN]` items be
            fore build*

  Thought for 18s (ctrl+o to expand)

⏺ Lampiran C sudah selesai. Sekarang perbaikan bedah pada isi dokumen — perbaiki
  klaim faktual yang asumsinya salah (kolom checkout, split=NEW BUILD, gcal
  OAuth migration, Model C divergence, dual-confirm B2B-new, profit_sharing,
  voucher free-trial, rating 1-5, kategori):

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      354  - Jadwal (slot yang admin pilih, atau slot-picker Phase 2)
      355  - Harga **marked-up** (yang customer bayar) — net hidden dari 
           customer
      356  - Mode (online: link Zoom/Meet; offline: alamat klinik)
      357 -- Form fields: nama, email, telp (+ optional keluhan singkat u
          -ntuk konsultan)                                               
      357 +- Form fields: nama, email, telp, date_of_birth, regency_id (k
          +ota), gender (+ optional `complaint`/keluhan singkat untuk kon
          +sultan) — mirror B2C checkout (Appendix C.6)
      358  - Payment methods: card, VA, e-wallet, qris (gateway-supported
           )
      359  - Consent: UU PDP notice + AIGC/sesrecording consent (bila app
           licable)
      360  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      359  - Consent: UU PDP notice + AIGC/sesrecording consent (bila app
           licable)
      360  
      361  ### 10.3 Split payment (marketplace model)
      362 -- Gateway support split/connect (e.g., Midtrans split, Xendit,
          - Stripe Connect) `[OPEN-18]` — verify ID gateway support      
      362 +- **Split-payment = NEW BUILD** (B2C gak punya — single mercha
          +nt Midtrans Snap, see Appendix C.2; grep zero hit). Build via 
          +**Midtrans partner/Connect API** atau **Xendit Marketplace** (
          +`for-user` header). `[OPEN-18]`
      363  - Customer pays Rp300k → auto-split: Rp250k → Soluusi account,
            Rp50k → klinik account (or klinik earnings balance + periodic
            payout)
      364  - **Soluusi always gets net** (no credit risk); klinik always
           gets margin (no invoicing)
      365  - Refund: bila sesi cancel, gateway reverse-split (refund cust
           omer, reverse margin + net) `[OPEN-7a]`

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines
      368  - Payment confirmed (gateway webhook) → booking **auto-CONFIRM
           ED** → konsultan dispatched
      369  - System **auto-create gcal event** + invite customer (email) 
           + konsultan
      370  - gcal organizer = **klinik-branded service account** (not sol
           uusi.com) `[OPEN-20]`
      371 +- **B2C uses OAuth user** (`soluusi@gmail.com` refresh token, 
          +`GoogleCalendarService.php`) — gak viable untuk multi-klinik w
          +hite-label (per-klinik re-auth pain). **B2B migrate ke service
          + account + domain-wide delegation.** Hardcoded `"Soluusi - {pa
          +ckage}"` di event summary/description → parameterize ke klinik
          + brand. (Appendix C.3)
      372 +- **FIX B2C bug**: GCal failure leaves session `waiting` walau
          + payment verified — B2B make gcal non-fatal (booking confirmed
          + regardless + retry via queue job). Jangan mirror B2C bug.
      373  - Event title klinik-branded (e.g., "Konsultasi [Klinik Name]
           - [Konsultan]")
      374  - Reminder: gcal default + WA reminder H-1/H-2h (klinik-brande
           d)
      375  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      238  - Konsultan akses nama pasien via clinical field (encrypted, k
           onsultan-only) — sama seperti Model B
      239  - Klinik tetap "owns relationship" secara komersial (customer 
           kenal klinik, bukan Soluusi — enforced via white-label §10.8)
      240  - **No Soluusi account for customer** → no re-book funnel Solu
           usi-side (klinik owns repeat booking)
      241 +- **B2C divergence**: B2C registers users (Model A, `users` ta
          +ble — avatar, phone, is_active). B2B pilih guest checkout (Mod
          +el C) untuk white-label simplicity + klinik owns relationship.
          + Deliberate B2B choice. (Appendix C.5/C.6)
      242  
      243  `[OPEN-6]` — confirm Model C. UU PDP scope = Soluusi handle ch
           eckout PII (consent at checkout, DPO, breach). Klinik still ow
           ns longitudinal patient record.
      244  

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      263  - Patient ref (Model B: pseudonym/klinik ref ID; Model A: pasi
           en account)
      264  - Voucher (optional, Skema 4)
      265  - Status: scheduled → checked-in → in-session → completed / no
           -show / cancelled
      266 +> **B2B-NEW vs B2C** (Appendix C.6): B2C status flow = `waitin
          +g → scheduled → done → canceled` (NO check-in / in-session / n
          +o-show / dual-confirm — consultant just marks `done`). B2B add
          +s dual-confirm + no-show karena commission fraud risk (§9). Re
          +use `consultation_sessions` table + status enum extended.
      267  
      268  ### 7.3 Reschedule & cancel `[OPEN-7]`
      269  - Cutoff time: H-2 jam free reschedule; <2 jam = cancel (statu
           s counted)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      423  - Default: **flat per sesi** (per konsultan type × mode) — tab
           le §5.2
      424  - Alternative: % of net (e.g., 60% of net) — more aligned ince
           ntive but variable
      425  - Recommended: flat (simpler, predictable for konsultan)
      426 +> **B2C actual** (Appendix C.5): `consultants.profit_sharing` 
          +int field exists — suggests % model udah dipakai. Confirm flat
          + vs % vs `profit_sharing` semantics; reuse field or model ulan
          +g.
      427  
      428  ### 11.2 Payout schedule
      429  - Monthly consolidated payout (1×/bulan, sesi completed bulan
           itu)

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      449  - Free Trial 10 min (per pasien baru, online)
      450  - Flat Rp off (campaign)
      451  - Free session (special promo)
      452 +> **B2C actual** (Appendix C.5): voucher `type` = `diskon` (%)
          + | `potongan` (flat) only. **Free-trial 10 min + free session 
          += NOT in B2C** — build as new type OR drop. `[OPEN-4b]` B2C vo
          +ucher fields: nominal, kuota, usage, date window, min_transact
          +ion, max_discount, discount_percentage.                       
      453  
      454  ### 12.2 Voucher bearer `[OPEN-4]`
      455  - Default: **Soluusi absorbs discount** (voucher reduces Soluu
           si net, klinik margin tetap). Tujuan: akuisisi pasien baru → g
           oodwill ke klinik.

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line, removed 1 line
      585  - Revenue (pasien total, klinik margin kept) — klinik's view
      586  - Konsultan used + availability
      587  - Voucher redeemed
      588 -- NPS pasien (aggregate, no individual content)               
      588 +- Rating pasien 1-5 (aggregate, no individual content) — B2C `
          +consultant_ratings` 1-5 int + `consultation_sessions.review_te
          +xt` (Appendix C.5)                                            
      589  - Topik kategori (anonim: e.g., "stress 30%, parenting 20%..."
           )
      590  
      591  ### 17.2 HAP associate dashboard

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 1 line
      184  | Konsultan karir | 60 min | Rp250.000 | — (online only) |
      185  
      186  > Indikatif — final rate finance confirm. Offline > online kar
           ena konsultan travel/room.
      187 +> **B2C reconcile** (Appendix C.5): B2C `consultant_packages` 
          += pricing mechanism (name, price, duration, quota, results JSO
          +N, **no online/offline field**). B2C categories seeded = Psiko
          +logi/Karir/Bisnis/Keuangan/Hukum/Pajak — BUKAN psikolog/nutris
          +i/fisio. Klinik B2B butuh kesehatan → **reuse `consultant_pack
          +ages` + `categories` table, add health categories + mode field
          +**. `[OPEN-22]` Net price di spec ini map ke `consultant_packa
          +ges.price`.
      188  
      189  ### 5.2 Konsultan fee (indikatif)
      190  | Konsultan Type | Fee/sesi (flat) |

  Thought for 17s (ctrl+o to expand)

⏺ Semua diedit. Tambahkan 2 [OPEN] baru ([OPEN-4b], [OPEN-22]) ke tabel §24 +
  simpan memori tentang codebase soluusi-main (konteks proyek yang tidak terlalu
  jelas untuk sesi mendatang):

⏺ Update(Soluusi B2B - Klinik Vertical Spec.md)
  ⎿  Added 2 lines
      799  | `[OPEN-19]` | Anti-circumvention: hide konsultan from soluus
           i.com public + MoU clause? | Both technical + contractual | Wh
           ite-label/MoU |
      800  | `[OPEN-20]` | gcal service account branding (klinik-branded 
           organizer)? | Dedicated klinik-branded service account | Calen
           dar |
      801  | `[OPEN-21]` | Scheduling: admin-pick slot (MVP) vs customer 
           slot-picker (Phase 2)? | Admin-pick MVP, slot-picker Phase 2 |
            Booking |
      802 +| `[OPEN-4b]` | Voucher free-trial 10 min (NOT in B2C) — build
          + new type or drop? | TBD | Voucher |
      803 +| `[OPEN-22]` | Categories: B2C = Psikologi/Karir/Bisnis/Keuan
          +gan/Hukum/Pajak; B2B klinik butuh psikolog/nutrisi/fisio — reu
          +se table + add health cats? | Reuse + add health categories |
          +Konsultan model |
      804  
      805  ---
      806  ---

  Thought for 7s, wrote 1 memory (ctrl+o to expand)

⏺ MEMORY.md pointer — cek dulu ada/isi-nya:

  Thought for 13s, read 1 file (ctrl+o to expand)

⏺ Belum ada. Write fresh + cek memory dir:
     
  Thought for 21s, listed 1 directory, wrote 1 memory (ctrl+o to expand)

⏺ Selesai. Spec sekarang grounded di codebase asli (bukan asumsi). Yang gw
  lakuin:

  1. Scan struktur soluusi-main → Laravel 9 + Sanctum + Midtrans + Xendit +
  google/apiclient
  2. 3 agent paralel baca dalam (booking flow, konsultan model,
  payment/gcal/mail) — masing return structured findings dengan file:line refs
  3. Appendix C baru di klinik spec — B2C audit (ground truth) + B2B deltas, 7
  sub-bagian + summary table (B2B-NEW vs B2C-reuse vs B2C-fix)
  4. 9 surgical edits di body — fix klaim faktual yang asumsi gw salah
  5. 2 [OPEN] baru: [OPEN-4b] (voucher free-trial), [OPEN-22] (categories
  mismatch)
  6. Memory disimpen (codebase facts) buat sesi depan

  Delta kritis yang ngubah build (lu harus keputusin)

  #: 1
  Discovery: Split-payment = NEW BUILD — B2C gak punya sama sekali (single  
    merchant Midtrans Snap)
  Impact: Pilih Midtrans Connect atau Xendit Marketplace (for-user). [OPEN-18]
  ────────────────────────────────────────
  #: 2  
  Discovery: gcal = OAuth user (soluusi@gmail.com refresh token), bukan service
    account
  Impact: B2B harus migrate ke service account + domain-wide delegation —   
    OAuth-user gak viable multi-klinik white-label (per-klinik re-auth pain).
    [OPEN-20] upgraded ke architectural
  ────────────────────────────────────────
  #: 3
  Discovery: Webhook NO signature verification (security hole)
  Impact: B2B wajib fix — verify Midtrans signature_key, jangan mirror hole.
    [SECURITY]
  ────────────────────────────────────────
  #: 4
  Discovery: 14 mail templates + gcal strings ALL hardcode "Soluusi - "
  Impact: Semua harus parameterize → brand_name klinik (white-label work nyata,
    bukan config flag)
  ────────────────────────────────────────
  #: 5
  Discovery: Categories B2C = Psikologi/Karir/Bisnis/Keuangan/Hukum/Pajak — 
  BUKAN
     psikolog/nutrisi/fisio
  Impact: Klinik butuh health categories → reuse table + add. [OPEN-22]
  ────────────────────────────────────────
  #: 6
  Discovery: Voucher free-trial 10 min = NOT in B2C (cuma diskon/potongan)
  Impact: Build new type atau drop. [OPEN-4b]
  ────────────────────────────────────────
  #: 7
  Discovery: B2C gak ada dual-confirm / no-show (consultant just mark done)
  Impact: Dual-confirm + no-show = B2B-NEW (anti-fraud commission). Status flow
    B2B beda dari B2C
  ────────────────────────────────────────
  #: 8
  Discovery: profit_sharing int field di consultants (fee % mechanism udah ada)
  Impact: Confirm flat vs % vs profit_sharing semantics. [OPEN-1]
  
  Yang udah cocok (gak perlu ubah): complaint field = "keluhan singkat" ✓,
  checkout PII (nama/email/telp + DOB/regency/gender) gw udah add,
  consultant_packages = pricing mechanism ✓, schedules = concrete slots ✓,
  merchant of record = Soluusi (Opsi A) ✓ confirmed.

  File: /Users/mekari/soluusi-b2b-beforemarge/Soluusi B2B - Klinik Vertical 
  Spec.md — v1.1 + Appendix C.

  PRD (Soluusi B2B Platform - PRD.md) juga punya tech-stack/integration section
  yang harusnya kena treatment B2C ground-truth yang sama (Midtrans bukan
  generic, gcal OAuth→service-account, 14 mail white-label). Belum gw sentuh —
  lu mau gw update PRD-nya juga? Atau fokus klinik dulu, PRD menyusul
  sekolah/company vertikal?

✻ Crunched for 11m 13s

※ recap: Building the Soluusi B2B klinik spec, now grounded in the actual 
  soluusi-main codebase via Appendix C (B2C audit + B2B deltas) plus surgical 
  fixes. Next: decide if I should apply the same ground-truth treatment to the 
  PRD or keep focus on klinik.
                                                         
❯ tach stacknya gw mau ikutin seperti yang ada di soluusi-main samakan karena   
  itu sudah production dan akan nyatu di sana tapi skrg pisah dulu, dan         
  kemudain agar hemat cost bisa copas aja bbrp componen dari sana, persiapan    
  dulu sebelum devlopemnt                                                       
  
  Thought for 1m 42s (ctrl+o to expand)
  
⏺ Paham — stack ikutin soluusi-main persis (production, nanti nyatu, skrg
⏺ Bash(cd /Users/mekari/soluusi-main && echo "=== .env.example ===" && cat
      .env.example && echo "" && echo "=== config/services.php ===" && cat
      config/services.php &&…)
  ⎿  === .env.example ===
     APP_NAME=Laravel
     APP_ENV=local
     … +290 lines (ctrl+o to expand)
  ⎿  Shell cwd was reset to /Users/mekari/soluusi-b2b-beforemarge
  ⎿  Read ../.claude/projects/-Users-mekari-soluusi-b2b-beforemarge/memory/soluu
     si-main-codebase.md (24 lines)
  ⎿  Referenced file Soluusi B2B - Klinik Vertical Spec.md
  ⎿  Referenced file Soluusi B2B Platform - PRD.md
  ⎿  Referenced file Soluusi B2B - Competitor Analysis.md
  ⎿  Referenced file Soluusi B2B Master Playbook.md

  Thought for 12s (ctrl+o to expand)

⏺ config/services.php punya banyak cruft SEO/AI (deepseek/gemini/ga4/gsc/zhipu)
  = untuk content engine soluusi.com, irrelevant ke B2B klinik — skip, jangan
  copas. Ambil sisa yang presisi (midtrans/xendit config env vars, migration
  list, routes):

⏺ Bash(cd /Users/mekari/soluusi-main && echo "=== config/midtrans.php ===" &&
      cat config/midtrans.php && echo "" && echo "=== config/xendit.php ===" &&
      cat config/xend…)
  ⎿  === config/midtrans.php ===
     <?php
     … +246 lines (ctrl+o to expand)
  ⎿  Shell cwd was reset to /Users/mekari/soluusi-b2b-beforemarge
     
  Thought for 2m 22s (ctrl+o to expand)

⏺ Data lengkap. Migration set raksasa (100+), tapi separuhnya SEO/blog/CMS/AI
  engine soluusi.com — irrelevant ke B2B klinik, skip. Config services.php juga
  penuh deepseek/gemini/ga4/gsc/zhipu = content engine, skip. Yang relevan =
  domain konsultasi saja. Gw tulis prep doc-nya sekarang:

⏺ Write(Soluusi B2B - Dev Preparation.md)
  ⎿  Wrote 312 lines to Soluusi B2B - Dev Preparation.md
      2 > Versi 1.0 · 4 Agustus 2026idential · Do Not Distribute**
        anifest + build/fix list)apan Development (stack parity + copas m
      4 
        usi B2B - Klinik Vertical Spec.md` v1.1). Ini = "cara mulai": sta
        ck yang dipasang, file yang dicopas dari `soluusi-main`, yang dib
        nanti.baru, yang diperbaiki, dan arsitektur pisah-sekarang/nyatu-
      7 Ground truth: `/Users/mekari/soluusi-main` (Laravel 9, PHP 8, pro
        duction B2C). B2B klinik = mirror stack-nya, copas komponen domai
      8 n konsultasi, skip semua mesin SEO/blog/CMS/AI soluusi.com.
      9 ---
     … +540 lines (ctrl+o to expand)

  Thought for 9s (ctrl+o to expand)

⏺ Simpan memory pointer biar sesi depan tau prep doc ini ada:
     
  Thought for 7s, read 1 file, recalled 1 memory, wrote 2 memories (ctrl+o to 
  expand)

⏺ Doc tersimpan: /Users/mekari/soluusi-b2b-beforemarge/Soluusi B2B - Dev 
  Preparation.md (v1.0).

  Intinya:
  - Stack lock — Laravel 9 / PHP 8, composer + package.json + env keys copas
  persis dari soluusi-main (§2). 
  - Copas manifest — migration + model + controller + service + 14 mail + config
  domain konsultasi saja; semua SEO/blog/CMS/AI engine
  (deepseek/gemini/ga4/gsc/zhipu + 60+ migration 2026_04_30+) = skip (§4). Itu
  penghematan cost terbesar.
  - Pisah-sekarang/nyatu-nanti — tabel domain identik + typo B2C dipertahankan
  (consultan_id/discount_precentage/merchat_id) supaya merge nanti no-op; tabel
  B2B baru prefix klinik_, route group, sub-namespace Mail\Klinik (§3).
  - 5 fix wajib — webhook signature 🔒 + gcal failure non-fatal 🔒 = sifat
  keamanan/data, WAJIB; sisanya enum/typo/semantics (§6).
  - Build-NEW 11 item — split-payment, white-label, service-account gcal,
  dual-confirm, admin-generate-link, klinik RBAC/earnings, HAP, markup/cap,
  Model C guest, anti-circumvention, no-show (§5).

  → skipped: scaffold fisik (laravel new + composer install + copy file beneran)
  — itu = mulai dev, bukan prep. Jalankan setelah doc ini disetujui. Add saat
  user bilang "go".

  3 [OPEN] baru gw tambahin: profit_sharing semantics [23], split_settlements vs
  extend payments [24], ownership service-account gcal [25].

  Mau gw lanjut jalankan scaffold sekarang (laravel new + composer + copas file
  domain + migrate + smoke)? Atau doc ini cukup dulu buat persiapan, dev-nya
  nanti? Sambil itu, PRD (Soluusi B2B Platform - PRD.md) section tech-stack mau
  gw update juga treatment ground-truth B2C yang sama?

✻ Churned for 10m 41s

※ recap: Goal: B2B klinik vertical mirroring soluusi-main stack. Wrote `Soluusi 
  B2B - Dev Preparation.md` covering stack parity, copy-paste manifest, 
  build-new and fix lists, and merge-later architecture. Next: decide whether to
  run the scaffold now or keep prep doc-only.

❯ gw mau yang usdah lu tangkep semuanya masukin ke prd semuanya                 

  Thought for 10s (ctrl+o to expand)

⏺ Baca PRD dulu biar tau struktur + section yang udah ada, lalu masukin semua
  ground-truth B2C + prep material ke tempat yang pas:

  Thought for 2m 39s, read 1 file (ctrl+o to expand)

⏺ PRD saat ini masih "Proposal" spekulatif
  (Next.js/NestJS/PostgreSQL/Auth0/Redis) dengan §15.1 open. User mau semua
  ground-truth B2C + prep material masuk. Gw koreksi spekulasi jadi actual +
  append audit/manifest/build-fix/env appendices. Mulai dari header + §12 (stack
  locked) + resolusi §15.1:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 4 lines, removed 2 lines
       1  # Soluusi B2B Platform — Product Requirements Document (PRD)
       2  ### Platform terintegrasi untuk 3 vertikal: For Health Faciliti
          es · For School · For Company
       3  
       4 -> Versi 1.0 · Agustus 2026                                     
       4 +> Versi 1.1 · Agustus 2026                                     
       5  > Owner: Founder Soluusi
       6  > Status: Draft untuk review
       7 -> Dokumen pendukung: `Soluusi B2B Master Playbook.md`, `Soluusi
         - B2B - Competitor Analysis.md` 
       7 +> Dokumen pendukung: `Soluusi B2B Master Playbook.md`, `Soluusi
         + B2B - Competitor Analysis.md`, `Soluusi B2B - Klinik Vertical
         +Spec.md` (v1.1), `Soluusi B2B - Dev Preparation.md` (v1.0)
       8 +>
       9 +> **Revisi v1.1 (4 Agustus 2026):** resolusi §15.1 — stack B2C
         +(`soluusi-main`) sudah di-audit; spekulasi tech (Next.js/NestJS
         +/PostgreSQL/Auth0/Redis) dihapus, diganti actual ground truth (
         +Laravel 9 / PHP 8 / Midtrans Snap / Xendit dead / gcal OAuth-us
         +er / Vite-Tailwind / MySQL). Tambahan Appendix C (B2C audit → B
         +2B deltas), D (copas manifest), E (build-new + fix), F (env + s
         +etup + smoke).
      10  
      11  ---
      12  

  Thought for 5s (ctrl+o to expand)

⏺ Sekarang §12 (hapus spekulasi, ganti locked actual) + resolusi §15.1:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Error editing file

  Thought for 6s (ctrl+o to expand)

⏺ §12 old_string gak match — kemungkinan ada perbedaan whitespace. Re-read
  section itu:

  Thought for 6s, read 1 file (ctrl+o to expand)

⏺ Ketemu — line 641 beda (match B2C kemungkinan, bukan RBAC native seperti yang
  gw tulis). Re-read header section + redo dengan old_string yang presisi:

  Thought for 11s, read 1 file (ctrl+o to expand)

⏺ Sekarang redo §12 dengan old_string presisi:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 45 lines, removed 16 lines
      632  ---
      633  ---
      634  
      635 -## 12. Tech Stack Proposal                                    
      635 +## 12. Tech Stack (Locked — match `soluusi-main`)             
      636  
      637 -> Ponytail: reuse what exists. soluusi.com B2C sudah ada — **m
          -atch stack mereka kalau memungkinkan** sebelum add tech baru. 
      637 +> Resolusi §15.1: stack soluusi.com B2C sudah di-audit Agustus
          + 2026 (lihat Appendix C). B2B **match persis** — production, s
          +hare team/code, akan nyatu ke `soluusi-main` nanti. Spekulasi
          +lama (Next.js/NestJS/PostgreSQL+RLS/Auth0/Redis/Vercel+Fly spl
          +it) **dihapus**.
      638  
      639 -| Layer | Proposal (subject to existing soluusi.com stack) | A
          -lasan | 
      639 +### 12.1 Backend & language
      640 +| Layer | Locked | Catatan |
      641  |---|---|---|
      641 -| Frontend | Next.js (React) | common, SSR for portal, match B
          -2C kemungkinan | 
      642 -| Mobile | PWA first; native kalau bukti perlu | Ponytail: PWA
          - dulu | 
      643 -| Backend | Node/TypeScript (NestJS) atau match B2C | modular
          -monolith, RBAC native | 
      644 -| DB | PostgreSQL + RLS | multi-tenant row security | 
      645 -| Cache/Queue | Redis | cadence notification, booking lock | 
      646 -| Object storage | Cloudflare R2 / S3 | sesi files, materi | 
      647 -| WA | WhatsApp Business API (Meta / BSP) | cadence | 
      648 -| Email | SES / SendGrid | alt channel | 
      649 -| Video | Zoom/Meet integration | MVP | 
      650 -| Analytics warehouse | Postgres dulu → BigQuery/Clickhouse Ph
          -ase 2 | Ponytail | 
      651 -| Infra | Vercel (FE) + Fly.io/Cloudflare/Render (BE) / atau m
          -atch B2C | 1 deploy, simple | 
      652 -| Auth | Auth0/Clerk atau match B2C | RBAC + multi-role | 
      642 +| Framework | Laravel **9** (`laravel/framework ^9.0`) | modul
          +ar monolith, match B2C |
      643 +| Language | PHP **8.0**+ | jangan 8.1+ exclusive |
      644 +| Auth | Sanctum ^2.14 + Socialite ^5.16 (Google SSO) | admin
          +klinik + konsultan login; **customer = guest tanpa akun** (Mod
          +el C) |
      645 +| HTTP client | Guzzle ^7.2 | Midtrans/Xendit/gcal calls |
      646 +| Image | intervention/image ^3.11 | avatar/sertif |
      647 +| Region | azishapidin/indoregion ^3.0 | prov/kab/kec/desa |
      648 +| DBAL | doctrine/dbal ^3.8 | enum + raw SQL alter |
      649 +| CORS | fruitcake/laravel-cors ^2.0.5 | match B2C |
      650 +| Tinker | laravel/tinker ^2.7 | dev |
      651  
      654 -> **Open question §15.1:** stack soluusi.com B2C apa? Match ka
          -lau bisa, supaya share team & code. 
      652 +### 12.2 Payment
      653 +| Gateway | Status B2C (ground truth) | Status B2B |
      654 +|---|---|---|
      655 +| Midtrans (`midtrans/midtrans-php ^2.5`) | **Primary** — Snap
          + redirect-token, single merchant (Soluusi = merchant of record
          +). Webhook `POST api/midtrans-callback` → `MidtransController@
          +callback`, **NO signature verify** (bug, fix §11.6) | **Primar
          +y** — reuse Snap flow + **add split-payment NEW** (Midtrans Co
          +nnect) atau Xendit Marketplace; **verify signature (SECURITY)*
          +*; white-label receipt hide Soluusi |
      656 +| Xendit (`xendit/xendit-php ^4.1`) | **Dead/stubbed** — `Xend
          +itController` kosong, callback commented; cuma `config/xendit.
          +php` `auth_key` | **Revive for split** — Marketplace `for-user
          +` header: margin→klinik + payout associate. Jangan copas `Xend
          +itController` B2C (dead), build fresh |
      657  
      658 +### 12.3 Google Calendar
      659 +| B2C | B2B target |
      660 +|---|---|
      661 +| `google/apiclient` 2.15, **OAuth-user** (`soluusi@gmail.com`
          + refresh token; `config/services.php` block `google_calendar`)
          +, event summary `"Soluusi - {package}"` hardcoded; `link_meeti
          +ng` = Google Meet URI; gcal failure leaves session `waiting` (
          +bug, fix §11.6) | **Start dari OAuth-user** (copas `GoogleCale
          +ndarService`), lalu **migrasi ke service account** + Google Wo
          +rkspace Delegation; satu calendar per klinik atau Soluusi mast
          +er calendar dgn metadata klinik; parameterize brand |
      662 + 
      663 +### 12.4 Frontend
      664 +| B2C | B2B |
      665 +|---|---|
      666 +| Vite ^8, `@tailwindcss/vite ^4`, `tailwindcss ^4`, `laravel-
          +vite-plugin ^3.1`, Blade | **Match** — Blade + Vite/Tailwind 4
          +. **Bukan Next.js** (hapus spekulasi). Customer booking via **
          +white-label checkout link** (responsive web/PWA), **bukan nati
          +ve app** (Ponytail) |
      667 + 
      668 +### 12.5 Data & infra
      669 +| Layer | B2C actual | B2B |
      670 +|---|---|---|
      671 +| DB | MySQL (prod) / SQLite (local) | **MySQL** match (hapus
          +proposal PostgreSQL). Multi-tenant via `partner_id` scoping +
          +app-layer RBAC (role middleware, match B2C), **bukan Postgres
          +RLS** (MySQL tidak punya RLS) |
      672 +| Cache/Queue/Session | `database` driver semua (`.env.example
          +`: `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `SESSI
          +ON_DRIVER=database`) — **no Redis** | **Match** — database dri
          +ver MVP. Redis Phase 2 jika throughput bilang lain (Ponytail)
          +|
      673 +| Object storage | local disk / S3 | S3/R2 MVP (sesi files, Mo
          +U, materi) |
      674 +| Warehouse | — | Postgres → BigQuery Phase 2 (jangan dulu) |
      675 +| WA | B2C: belum ter-audit native WA gateway — verify saat im
          +plementasi | WhatsApp Business API MVP untuk cadence M10 |
      676 +| Email | 14 mailables (5 client + 9 konsultan), global sender
          +, hardcoded `"Soluusi - "` prefix | **Copas + white-label** —
          +prefix → `{brand} -`, per-mailable `from(config('whitelabel.em
          +ail'))` (Appendix D §5) |
      677 +| Video | Google Meet URI (`link_meeting`), no Zoom | Match —
          +Meet link dari gcal event |
      678 +| Infra | single Laravel app (match B2C prod) | Single deploy
          +modular monolith; hapus proposal Vercel/Fly.io split FE-BE |
      679 + 
      680 +### 12.6 Dev tooling
      681 +phpunit ^9.5.10, spatie/laravel-ignition, fakerphp/faker, lara
          +vel/sail, mockery/mockery, nunomaduro/collision. Node 18+.
      682 + 
      683 +> **Prinsip Ponytail:** stack identik = merge ke `soluusi-main
          +` nanti = no-op di domain (tabel + model verbatim, typo B2C di
          +pertahankan). B2B-only tabel prefix `klinik_`/domain baru. Det
          +ail: Appendix D (copas manifest) + Appendix E (build-new + fix
          +).
      684 + 
      685  ---
      686  ---
      687  

  Thought for 16s (ctrl+o to expand)

⏺ §12 selesai. Lanjut §10 (integrations → actual) + resolusi §15.1:
     
⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 12 lines, removed 11 lines
      586  
      587  ## 10. Integrations & API
      588  
      589 -| Integration | Purpose | Phase |                             
      590 -|---|---|---|                                                 
      591 -| soluusi.com B2C API | share konsultan pool, sync booking, B2
          -C→B2B referral | Phase 3 |                                    
      592 -| WhatsApp Business API | notification cadence, opt-out, templ
          -ates | MVP | 
      593 -| Email (SES/SendGrid) | notification alt channel | MVP | 
      594 -| Zoom / Google Meet | online session link | MVP | 
      595 -| Google Calendar | konsultan schedule sync | MVP | 
      596 -| Payment gateway | invoice (subscription/flat), payout to ass
          -ociate bank | MVP | 
      597 -| Object storage (S3/R2) | sesi files, MoU, materi | MVP | 
      598 -| Psychometric instruments (licensed) | MMCI/MMPI/DISC via lic
          -ensed psikolan run | Phase 2 | 
      599 -| HRIS (Talenta/Mekari/etc) | headcount sync, white-label land
          -ing | Phase 2 | 
      589 +| Integration | Actual B2C (ground truth) | B2B plan | Phase |
      590 +|---|---|---|---|
      591 +| Midtrans Snap | Primary; Snap redirect-token; webhook `POST
          +api/midtrans-callback` → `MidtransController@callback` — **NO
          +signature verify** (bug) | Reuse + add split (Connect) + **ver
          +ify signature (SECURITY §11.6)**; white-label receipt hide Sol
          +uusi | MVP |
      592 +| Xendit | Dead/stubbed (`XenditController` kosong, callback c
          +ommented) | Revive for split Marketplace (`for-user`) + associ
          +ate payout | MVP |
      593 +| Google Calendar | OAuth-user (`google/apiclient` 2.15, `solu
          +usi@gmail.com` refresh token); event summary `"Soluusi - {pkg}
          +"` hardcoded; gcal failure leaves session `waiting` (bug) | St
          +art OAuth-user → migrate **service account**; parameterize bra
          +nd; **gcal failure non-fatal (§11.6)** | MVP |
      594 +| Email | 14 mailables (5 client + 9 konsultan), global sender
          +, `"Soluusi - "` prefix | Copas + white-label (prefix→brand, p
          +er-mailable from) | MVP |
      595 +| Google Meet | `link_meeting` dari gcal event | Match | MVP |
      596 +| WhatsApp Business API | B2C: belum ter-audit native WA gatew
          +ay — verify dulu | Add MVP untuk cadence M10 | MVP |
      597 +| Object storage (S3/R2) | local disk / S3 | S3/R2 (sesi files
          +, MoU, materi) | MVP |
      598 +| soluusi-main codebase | (merge target) | Merge B2B ke `soluu
          +si-main` nanti; share konsultan pool + domain schema identik |
          + Phase 3 |
      599 +| Psychometric instruments (licensed) | — | MMCI/MMPI (categor
          +y-A, licensed psikolog only per HIMPSI), PHQ-9/GAD-7 | Phase 2
          + |
      600 +| HRIS (Mekari Talenta dll) | — | headcount sync, white-label
          +landing | Phase 2 |
      601  
      602  **API design principle:** REST, versioned (`/v1`), BFF per ver
           tikal. Webhook for sesi completion (trigger commission). Idemp
           otency keys for booking + payout.
      603  

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 1 line, removed 1 line
      739  
      740  ## 15. Open Questions (decisions needed)
      741  
      742 -1. **§15.1 Stack match** — soluusi.com B2C tech stack apa? Bis
          -a share team/code? (decide before §12 final)                  
      742 +1. **§15.1 Stack match** — ✅ **RESOLVED** (Agustus 2026, audi
          +t `soluusi-main`). B2C = Laravel 9 / PHP 8 / Midtrans Snap pri
          +mary / Xendit dead / gcal OAuth-user / Vite-Tailwind / MySQL /
          + database driver (no Redis). B2B **match persis** (§12 locked)
          +. Share team + code; merge ke `soluusi-main` nanti.
      743  2. **§15.2 Cross-sell attribution** — associate yang bawa mitr
           a klinik, kalau mitra itu juga jadi Company vertical, komisi c
           ross-sell ke associate yang sama atau split? (TBD — affects M9
            + Commission Engine)
      744  3. **§15.3 Konsultan fee model** — bagaimana konsultan dibayar
            (per sesi flat? revenue share with Soluusi)? Belum di playboo
           k. Affects revenue equation §8.7.
      745  4. **§15.4 White-label depth** — perusahaan minta branded port
           al seberapa deep (logo only? custom domain? full white-label a
           pp?) Phase 2.

  Thought for 3s (ctrl+o to expand)

⏺ Sekarang §6 arsitektur (koreksi PostgreSQL→MySQL, hapus
  Redis/Mobile-native/BFF-service, tambah merge-later):

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 21 lines, removed 19 lines
      182  
      183  ```mermaid
      184  graph TB
      185 -    subgraph Client[Client Layer]                             
      186 -        WB[Web App — Partner Portal + CRM]                    
      187 -        MA[Mobile App — End User booking]                     
      185 +    subgraph Client[Client Layer — single Laravel app, Blade +
          + Vite/Tailwind]                                               
      186 +        WB[Partner Portal + CRM — web]
      187 +        CL[White-label Checkout Link — responsive/PWA, custome
          +r guest]
      188          WA[WhatsApp / Email Gateway]
      189      end
      190 -    subgraph API[API Layer] 
      191 -        GW[API Gateway / BFF per vertikal] 
      192 -        AUTH[Auth & RBAC] 
      190 +    subgraph API[API Layer — route groups per vertikal, NOT se
          +parate services]
      191 +        AUTH[Sanctum + Socialite RBAC]
      192      end
      194 -    subgraph Domain[Domain Services] 
      193 +    subgraph Domain[Domain Services — modular monolith, match
          +B2C]
      194          CRM[CRM & Pipeline]
      195          PRT[Partner Portal]
      196          BKG[Booking & Scheduling]
      198 -        SES[Session & Confirmation]
      197 +        SES[Session & Dual-Confirm]
      198          MHC[MHCU & Screening]
      200 -        COM[Commission Engine] 
      199 +        COM[Commission + Split Engine]
      200          VOC[Voucher & Campaign]
      201          RPT[Reporting & Dashboard]
      202 +        WLB[White-label & Checkout Link]
      203          XSE[Cross-Sell Tracker]
      204          NOT[Notification]
      205      end
      206 -    subgraph Data[Data Layer] 
      207 -        PG[(PostgreSQL — core)] 
      208 -        REDIS[(Redis — cache/queue)] 
      206 +    subgraph Data[Data Layer — MySQL, database driver for cach
          +e/queue/session]
      207 +        MY[(MySQL — core, multi-tenant via partner_id scoping)
          +]
      208          OBJ[(Object Storage — sesi files)]
      210 -        DW[(Warehouse — analytics)]
      209 +        DW[(Warehouse — Phase 2)]
      210      end
      211      subgraph Ext[External]
      213 -        B2C[soluusi.com B2C API] 
      214 -        CAL[Calendar/Zoom/Meet] 
      215 -        PG2[Payment Gateway] 
      212 +        MT[Midtrans Snap + Connect]
      213 +        XD[Xendit Marketplace split]
      214 +        GCAL[Google Calendar service account]
      215          WA2[WhatsApp Business API]
      216 +        B2C[soluusi-main — merge target nanti]
      217      end
      218  
      219      Client --> API
     ...
      223  ```
      224  
      225  **Prinsip arsitektur**
      226 -- Modular monolith dulu (bukan microservices) — Ponytail: simp
          -ler, 1 deploy. Pecah ke service kalau throughput bilang lain.
      227 -- BFF per vertikal (For Health / For School / For Company) → l
          -ogic shared, presentation diff. 
      228 -- Multi-tenant: data per mitra di-scope via `partner_id` + row
          --level security (RBAC reinforced). 
      226 +- Modular monolith Laravel 9 (match B2C), **bukan** microservi
          +ces — Ponytail: 1 deploy. Pecah kalau throughput bilang lain.
      227 +- "BFF per vertikal" = **route group + presenter diff** (`Rout
          +e::prefix('klinik|sekolah|company')`), bukan service terpisah.
          + Logic shared.
      228 +- Multi-tenant: data per mitra di-scope via `partner_id` + app
          +-layer RBAC (role middleware, match B2C). **Bukan** Postgres R
          +LS (MySQL tidak punya); enforce di query scope + policy class.
      229 +- **Pisah sekarang, nyatu nanti** — B2B repo Laravel sendiri s
          +ekarang; tabel domain identik + typo B2C dipertahankan supaya
          +merge ke `soluusi-main` = no-op skema. B2B-only tabel prefix `
          +klinik_`/domain baru. Detail: Appendix D + E.
      230 +- Customer (pasien/siswa/karyawan) booking via **white-label c
          +heckout link** (guest, Model C), **bukan** native mobile app —
          + Ponytail: responsive web/PWA.
      231  
      232  ---
      233  ---

  Thought for 14s (ctrl+o to expand)

⏺ §11 NFR — tambah §11.6 (security fixes wajib) + §11.7 (residency/compliance
  specifics):

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 9 lines
      632  ### 11.5 Localization
      633  - Bahasa Indonesia primary; English UI untuk enterprise/intern
           ational school.
      634  
      635 +### 11.6 Security fixes wajib (jangan bawa bug B2C)           
      636 +- **Webhook signature verify** — B2C `MidtransController@callb
          +ack` terima callback tanpa `Notification::verifySignature()` (
          +hole: fake settlement). B2B: verify pakai `server_key` sebelum
          + update `payment_status`; reject + log mismatch.
      637 +- **gcal failure non-fatal** — B2C: gcal throw → session tetap
          + `waiting` meski pembayaran verified (customer bayar tapi tak
          +ter-schedule = data-loss). B2B: try/catch, status → `scheduled
          +` regardless, `google_calendar_event_id` nullable, retry via q
          +ueue, log. gcal = best-effort, bukan blocker.
      638 + 
      639 +### 11.7 Data residency & compliance specifics
      640 +- UU PDP (eff. Okt 2024): data residency Indonesia. Midtrans/X
          +endit/Google region pilih ID/SG.
      641 +- HIMPSI: MMCI/MMPI (category-A) hanya di-run psikolog berlise
          +nsi — restrict di role + audit.
      642 +- White-label / non-circumvention: customer tidak boleh tahu l
          +ayanan = Soluusi — domain/brand/receipt/email/gcal hide (Appen
          +dix E §10).
      643 + 
      644  ---
      645  ---
      646  

  Thought for 7s (ctrl+o to expand)

⏺ §15 — tambah open questions baru (15.9–15.13) dari prep doc:
     
⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 5 lines
      758  6. **§15.6 Self-serve vs associate-only** — Phase 2/3 buka sel
           f-serve signup untuk PLG, atau tetap associate-led GTM?
      759  7. **§15.7 Pricing tiering** — PEPM rate final per company siz
           e? Subscription sekolah final per siswa? (Playbook indikatif, 
           needs finance confirm)
      760  8. **§15.8 B2C integration scope** — seberapa dalam integrate 
           soluusi.com (share konsultan pool, cross-funnel)? Phase 3 tapi
            data model harus siap.
      761 +9. **§15.9 `profit_sharing` semantics** — B2C `consultants.pro
          +fit_sharing` int, ambigu (% / basis-points / rupiah). Verify p
          +emakaian di controller B2C sebelum decide reuse vs field baru.
          + Affects Commission M6 + split.
      762 +10. **§15.10 Split settlement table** — `split_settlements` ta
          +bel baru (additive, FK ke `payments`) vs extend `payments`? Re
          +commended: tabel baru.
      763 +11. **§15.11 gcal service account ownership** — butuh Google W
          +orkspace admin + delegation scope `…/auth/calendar`. Siapa peg
          +ang service account? Soluusi ops. Affects §12.3 migration.
      764 +12. **§15.12 Voucher free-trial** — B2C voucher = `potongan`(f
          +lat)/`diskon`(%), **no free-trial**. B2B butuh free-trial 10 m
          +in (M7)? Belum diputuskan.
      765 +13. **§15.13 Kategori kesehatan** — B2C seeder = Psikologi/Kar
          +ir/Bisnis/Keuangan/Hukum/Pajak. Klinik butuh kategori kesehata
          +n (Psikolog Klinis/Pendidikan, Konselor, Gizi, Fisioterapi). F
          +inalize daftar sebelum seed.
      766  
      767  ---
      768  ---

  Thought for 1m 29s (ctrl+o to expand)

⏺ Sekarang appendices C/D/E/F — sisipkan sebelum footer. Ini material utama yang
  mau masuk PRD:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 186 lines, removed 1 line
      789  
      790  ---
      791  
      792 +## Appendix C — B2C Codebase Audit → B2B Platform Deltas      
      793 +                                                              
      794 +Ground truth: `/Users/mekari/soluusi-main` (Laravel 9, PHP 8).
          + Audit Agustus 2026. Depth klinik: `Soluusi B2B - Klinik Verti
          +cal Spec.md` Appendix C. File-level copas: `Soluusi B2B - Dev 
          +Preparation.md` §4.
      795 + 
      796 +### C.1 Stack (full → §12)
      797 +Laravel 9 / PHP 8 / Sanctum+Socialite / Guzzle / intervention/
          +image / indoregion / doctrine/dbal / Vite-Tailwind 4 / MySQL /
          + database driver (no Redis). B2B = match persis.
      798 + 
      799 +### C.2 Payment
      800 +| | B2C | B2B |
      801 +|---|---|---|
      802 +| Primary | Midtrans Snap redirect-token, single merchant (Sol
          +uusi = MoR) | Reuse + split-payment NEW |
      803 +| Xendit | Dead/stubbed | Revive for split Marketplace `for-us
          +er` |
      804 +| Split | **None** (grep zero hit) | **BUILD NEW** — Midtrans
          +Connect / Xendit Marketplace |
      805 +| Webhook sig | NO verify (bug) | **FIX** verify signature (§1
          +1.6) |
      806 +| Webhook route | `POST api/midtrans-callback` → `MidtransCont
          +roller@callback` | Reuse route, B2B namespace |
      807 + 
      808 +### C.3 Google Calendar
      809 +| | B2C | B2B |
      810 +|---|---|---|
      811 +| Auth | OAuth-user (`soluusi@gmail.com` refresh token) | Migr
          +ate → **service account** + Workspace Delegation |
      812 +| Event summary | `"Soluusi - {package}"` hardcoded | Paramete
          +rize → `"{brand} - {package}"` |
      813 +| link_meeting | Google Meet URI | Match |
      814 +| Failure | gcal throw → session `waiting` (data-loss bug) | *
          +*FIX** non-fatal, scheduled regardless (§11.6) |
      815 +| File | `app/Services/GoogleCalendarService.php` | Copas as s
          +tart, then migrate |
      816 + 
      817 +### C.4 Mail (white-label)
      818 +14 mailables (5 client + 9 konsultan) + 16 blades. ALL hardcod
          +e `"Soluusi - "` subject prefix, global sender, no per-mailabl
          +e `from()`. B2B: copas → parameterize prefix → `{brand} -`, pe
          +r-mailable `from(config('whitelabel.email'))`.
      819 + 
      820 +### C.5 Key tables (reuse domain identik, B2B-only additive)
      821 +**Reuse verbatim** (typo B2C dipertahankan untuk merge parity)
          +: `consultants` (`profit_sharing` int, `status` enum accept/re
          +ject/pending/interview/payment), `categories` (seeded Psikolog
          +i/Karir/Bisnis/Keuangan/Hukum/Pajak), `consultant_packages` (p
          +ricing, FK typo `consultan_id`, no online/offline field), `sch
          +edules` (concrete datetime slots, not recurring), `vouchers` (
          +`potongan`/flat | `diskon`/%, NO free-trial), `consultant_rati
          +ngs` (1-5 int, no review text; text on `consultation_sessions.
          +review_text`), `consultation_sessions` (booking row: status wa
          +iting/scheduled/done/canceled, payment_status waiting/verified
          +/canceled, `link_meeting`, `google_calendar_event_id`, `compla
          +int`, `note`, `solution`), `payments`, `users` (+gender, +date
          +_of_birth, +regency_id PII), `roles`, `social_accounts`, indor
          +egion (provinces/regencies/districts/villages).
      822 + 
      823 +**B2B-only additive** (prefix `klinik_`/domain baru): `kliniks
          +` (brand, domain, logo, from-email, gcal_id), `klinik_admins`
          +(pivot user↔klinik), `split_settlements`, `hap_commissions`, `
          +markup_rules`, `checkout_links` (signed token + expiry + snaps
          +hot).
      824 + 
      825 +### C.6 B2C flow vs B2B flow
      826 +**B2C:** browse category → consultant detail → pick package →
          +`get-jadwal-by-package` (AJAX) → payment page → `create-va` (r
          +ow + Snap URL) → Snap → callback → gcal+Meet + status=schedule
          +d → consultant `set-note`/`set-done` → review. **No check-in,
          +no no-show, no dual-confirm.**
      827 + 
      828 +**B2B (klinik, priority):** admin klinik pick konsultan + sche
          +dule → generate **white-label checkout link** → admin sends to
          + customer → customer opens link (sees konsultan name + schedul
          +e + marked-up price), isi data (nama/email/telp/DOB/regency/ge
          +nder + complaint), bayar langsung → gateway split (net→Soluusi
          +, margin→klinik) → auto-confirm + auto-gcal → dual-confirm (ko
          +nsultan mark-done → verify → release fee + HAP). Customer = gu
          +est (Model C), NO account. **Hide Soluusi (non-circumvention).
          +**
      829 + 
      830 +### C.7 Summary: B2B-NEW vs B2C-reuse vs B2C-fix
      831 +| Kategori | Item |
      832 +|---|---|
      833 +| **B2B-NEW** (build dari nol) | split-payment, white-label co
          +nfig+param, service-account gcal, dual-confirm, admin-generate
          +-link booking, klinik RBAC+earnings/payout, HAP 3-stage commis
          +sion, markup+cap, Model C checkout-guest, anti-circumvention,
          +no-show+check-in |
      834 +| **B2C-reuse** (copas verbatim) | domain migrations + models,
          + MidtransController Snap flow (adapt), GoogleCalendarService (
          +as start), 14 mailables (then white-label), voucher logic, ind
          +oregion, Schedule/Jadwal, role middleware, config midtrans/xen
          +dit |
      835 +| **B2C-fix** (jangan bawa bug) | webhook signature verify [SE
          +CURITY], gcal failure non-fatal [DATA-LOSS], payment_status en
          +um Blueprint, profit_sharing semantics verify, B2C typos prese
          +rve-for-parity |
      836 + 
      837 +---
      838 + 
      839 +## Appendix D — Component Reuse Manifest (copas `soluusi-main`
          + → B2B)
      840 + 
      841 +Path source: `/Users/mekari/soluusi-main/`. Status: ✅ verbati
          +m · 🔧 copas+adapt · 🆕 build baru (Appendix E) · ❌ skip. Fil
          +e-level detail: `Soluusi B2B - Dev Preparation.md` §4.
      842 + 
      843 +### D.1 Migrations — domain konsultasi (✅), skip SEO/blog/CMS
          + (❌)
      844 +**Copas (timeline jaga):** users, password_resets, failed_jobs
          +, personal_access_tokens, consultants + alters, certificates,
          +companies + alters, schedules + alters, roles, categories + al
          +ters, skills, social_media, education, departements, benefits,
          + consultant_packages + alters, consultant_skills, consultant_s
          +ocial_media, consultant_education, consultation_sessions + ALL
          + alters, add_relation_each_table, outputs, category_skills, co
          +nsultant_categories, consultant_ratings, consultant_companies,
          + vouchers + alter, payments, social_accounts, subcategories, c
          +onsultant_subcategories, indoregion (provinces/regencies/distr
          +icts/villages) + regency_id/gender/DOB on users, google_calend
          +ar_event_id/complaint/package_price/voucher_amount/completed_a
          +t/solution on consultation_sessions, sessions + cache table, c
          +omposite PK consultant_categories, deleted_at consultant_packa
          +ges.
      845 + 
      846 +**❌ Skip (mesin content soluusi.com):** semua migration `2026
          +_04_30_16*`–`2026_08_04_14*` (seo_keywords, seo_keyword_rankin
          +gs, location_pages, blog_*, user_sessions, seo_daily_summaries
          +, seo_transactions, seo_audits, seo_gsc_summaries, backlink*,
          +article_templates, blog_post_edits, style_guides, blog_comment
          +s). Juga user_topses (TOPSIS), banners, campaign1_investasi, m
          +anual_reviews (optional), cms_columns_to_consultants, blog_sub
          +scription_to_consultants, landing_page_columns_to_categories.
      847 + 
      848 +### D.2 Models — ✅
      849 +`User`, `Role`, `SocialAccount`, `Consultant`, `ConsultantPack
          +age`, `Schedule`, `ConsultationSession`, `Voucher`, `Consultan
          +tRating`, `Category`, `CategorySkill`, `ConsultantCategory`, `
          +Skill`, `ConsultantSkill`, `SocialMedia`, `ConsultantSocialMed
          +ia`, `Education`, `ConsultantEducation`, `Company`, `Consultan
          +tCompany`, `Departement`, `Benefit`, `Certificate`, `Subcatego
          +ry`, `ConsultantSubcategory`, `Output`, `Payment`, `Province`,
          + `Regency`, `District`, `Village`, `Model` (base). ❌ Skip sem
          +ua `Seo*`/`Blog*`/`Backlink*`/`ArticleTemplate`/`StyleGuide`/`
          +LocationPage`/`UserSession`/`UserTopsis`/`Banner`/`ManualRevie
          +w`(optional).
      850 + 
      851 +### D.3 Controllers — 🔧
      852 +| Controller | Status | Adaptasi |
      853 +|---|---|---|
      854 +| `MidtransController` | 🔧 | + signature verify (§11.6), + sp
          +lit (Appendix E), white-label receipt |
      855 +| `AuthController` | 🔧 | SSO Google untuk admin klinik + kons
          +ultan; customer = guest |
      856 +| `JadwalController` | 🔧 | reuse slot mgmt; + admin-generate-
          +link endpoint (Appendix E) |
      857 +| `ConsultantController` | 🔧 | subset set-note/set-done/set-l
          +ink-meeting → dual-confirm (Appendix E) |
      858 +| `HomeController` | 🔧 | subset dashboard/transaction/payroll
          + → klinik earnings/payout |
      859 +| `VoucherController` | ✅ | voucher CRUD + `getNominalWithVou
          +cher` |
      860 +| `KategoriController` | 🔧 | category CRUD; seeder extend kat
          +egori kesehatan (§15.13) |
      861 +| `KonsumenController` | 🔧 | checkout B2C → white-label check
          +out link flow |
      862 +| `XenditController` | ❌ | dead, build fresh jika pakai Xendi
          +t Marketplace |
      863 + 
      864 +❌ Skip: BlogController, SitemapController, CampaignController
          +, FrontController, TopsisController, LocationController, Banne
          +rController, ExperienceController, EducationController, Compan
          +yController, (Consultant)SubcategoryController.
      865 + 
      866 +### D.4 Services — 🔧
      867 +`GoogleCalendarService` 🔧 (copas as start → migrate service a
          +ccount, parameterize brand, non-fatal). ❌ Skip semua SEO/AI s
          +ervices (AiContentService, Ga4Service, GscService, BlogComment
          +Service, SeoScoringService, DynamicPromptBuilder, PatternExtra
          +ctionService, SchemaService, EditCaptureService).
      868 + 
      869 +### D.5 Mail — 🔧 (14 mailables + 16 blades)
      870 +Copas ke `app/Mail/Klinik/` + `resources/views/emails/klinik/`
          +. White-label: subject `"Soluusi - "` → `"{brand} - "`, global
          + sender → per-mailable `from(config('whitelabel.email'))`. Bra
          +nd di-resolve dari `klinik_id` context → `App\Models\Klinik::b
          +rand`.
      871 + 
      872 +### D.6 Config — ✅/🔧
      873 +✅ `config/midtrans.php` (verbatim; key `merchat_id` pertahank
          +an untuk parity — verify controller baca key mana sebelum fix)
          +. ✅ `config/xendit.php`. 🔧 `config/services.php` — copas **h
          +anya** block `google` + `google_calendar`; ❌ drop postmark/re
          +send/ses/slack/zhipu_ai/deepseek/gemini/ga4/gsc (mesin content
          +/AI/SEO). 🆕 `config/klinik.php`, `config/whitelabel.php`, `co
          +nfig/split.php`.
      874 + 
      875 +### D.7 Helpers & middleware — ✅/🔧
      876 +✅ `app/Helpers/CStr.php`. 🔧 `CheckRole.php` (tambah role `kl
          +inik_admin`). ✅ `Authenticate.php`, `RedirectIfAuthenticated.
          +php`.
      877 + 
      878 +### D.8 indoregion + seeders — 🔧
      879 +✅ package + migrations + models. 🔧 `CategorySkillSeeder` — c
          +opas lalu extend kategori kesehatan (§15.13). 🔧 `DatabaseSeed
          +er` — skip seed blog/SEO.
      880 + 
      881 +---
      882 + 
      883 +## Appendix E — Build-NEW, Fix-List & Merge Architecture
      884 + 
      885 +### E.1 Build-NEW (B2B-specific, tidak ada di B2C)
      886 +| # | Komponen | PRD module | Spec ref |
      887 +|---|---|---|---|
      888 +| 1 | Split-payment gateway (Midtrans Connect / Xendit Marketp
          +lace `for-user`) | M6 + §10 | Spec §10.3 |
      889 +| 2 | White-label config + parameterization (brand, domain, re
          +ceipt, email, gcal) | M2/WLB | Spec §10.8 |
      890 +| 3 | Service-account Google Calendar (migrate dari OAuth-user
          +) | §12.3 | Spec §10.5 |
      891 +| 4 | Dual-confirm anti-fraud (konsultan mark-done → verify →
          +release fee + HAP) | M4 §8.4 | Spec §9 |
      892 +| 5 | Admin-generate-link booking flow (admin pick konsultan+s
          +chedule → generate signed white-label checkout link → send to
          +customer) | M3 | Spec §7.1 |
      893 +| 6 | Klinik RBAC + earnings/payout dashboard (role `klinik_ad
          +min`, aggregate-only) | M2/M8 | Spec §11 |
      894 +| 7 | HAP 3-stage commission engine (Rp1M: 250k@5, 250k@25, 50
          +0k@40/90d) | M6 | Spec §12 |
      895 +| 8 | Markup + cap per price range (Soluusi set net, klinik ma
          +rk up, cap per range) | M6 §8.7 | Spec §8 |
      896 +| 9 | Pasien Model C checkout-guest (no account, transactional
          + PII: nama/email/telp/DOB/regency/gender + complaint) | M3 | S
          +pec §6 |
      897 +| 10 | Anti-circumvention (MoU + teknis: hide Soluusi, neutral
          + domain, no link to soluusi.com) | §11.7 | Spec §10.10 |
      898 +| 11 | No-show + check-in (B2C: tidak ada; B2B: check-in windo
          +w, no-show policy TBD §15) | M4 | Spec §9 |
      899 + 
      900 +**Tabel B2B baru (additive, prefix `klinik_`/domain):** `klini
          +ks`, `klinik_admins`, `split_settlements`, `hap_commissions`,
          +`markup_rules`, `checkout_links` (signed token + expiry + klin
          +ik_id + konsultan_id + schedule_id + package_uuid + markup_sna
          +pshot).
      901 + 
      902 +### E.2 Fix-List (jangan bawa bug B2C)
      903 +| # | Bug B2C | Fix di B2B | Sifat |
      904 +|---|---|---|---|
      905 +| 1 | Webhook NO signature verify (`MidtransController@callbac
          +k`) | Tambah `Midtrans\Notification` verify pakai `server_key`
          + sebelum update `payment_status`; reject+log mismatch | 🔒 SEC
          +URITY — wajib |
      906 +| 2 | gcal failure fatal (session `waiting` meski paid) | try/
          +catch; status→`scheduled` regardless; `google_calendar_event_i
          +d` nullable; retry via queue; log | 🔒 DATA-LOSS — wajib |
      907 +| 3 | `payment_status` enum loose (raw SQL alter) | Blueprint
          +`$table->enum('payment_status', ['waiting','verified','cancele
          +d'])` di migration awal B2B | 📐 clean |
      908 +| 4 | `profit_sharing` int ambigu (% / bps / rupiah) | Verify
          +di controller B2C; B2B pakai field baru `klinik_markup` (seman
          +tics beda) | ❓ VERIFY (§15.9) |
      909 +| 5 | Typos `consultan_id`/`INTERVIEW`/`discount_precentage`/`
          +merchat_id` | **Pertahankan** di tabel copas (merge parity); e
          +jaan benar di tabel B2B baru | 📐 parity |
      910 + 
      911 +### E.3 Merge Architecture (pisah sekarang, nyatu nanti)
      912 +| Komponen | Strategi |
      913 +|---|---|
      914 +| Tabel domain identik | Copas migration + model verbatim (typ
          +o B2C dipertahankan) → merge = no-op skema |
      915 +| Tabel B2B-only | Nama distinktif prefix `klinik_`, tidak col
          +lide → additive merge |
      916 +| Model domain | Namespace `App\Models\` sama → merge = reuse
          +file sama |
      917 +| Model B2B-only | `App\Models\Klinik\...` sub-namespace |
      918 +| Route | B2B route group `Route::prefix('klinik')->middleware
          +('klinik.auth')` → merge = tambah 1 route group |
      919 +| Config | B2B config sendiri (`klinik.php`, `whitelabel.php`,
          + `split.php`) + copas midtrans/xendit → tambah, gak modifikasi
          + B2C |
      920 +| Mail | `App\Mail\Klinik\...` + `resources/views/emails/klini
          +k/` → gak collide dgn 14 mailable B2C |
      921 + 
      922 +`// ponytail: preserve B2C typo for schema parity on merge` —
          +rename typo = cleanup join terpisah di soluusi-main belakangan
          +, bukan tanggung jawab B2B sekarang.
      923 + 
      924 +---
      925 + 
      926 +## Appendix F — Env Vars, Setup Checklist & Smoke Check
      927 + 
      928 +### F.1 Env vars (B2B `.env`)
      929 +```env
      930 +APP_NAME="Klinik Konsultan"          # neutral, NOT "Soluusi"
      931 +APP_ENV=local  APP_KEY=  APP_DEBUG=true  APP_URL=http://localh
          +ost:8000
      932 +DB_CONNECTION=sqlite                   # local; prod=mysql
      933 +SESSION_DRIVER=database  QUEUE_CONNECTION=database  CACHE_STOR
          +E=database
      934 +MIDTRANS_SERVER_KEY=  MIDTRANS_CLIENT_KEY=  MIDTRANS_MERCHANT_
          +ID=  MIDTRANS_IS_3DS=true
      935 +SECRET_KEY_XENDIT=                     # for split Marketplace
      936 +GOOGLE_CALENDAR_CLIENT_ID=  GOOGLE_CALENDAR_CLIENT_SECRET=  GO
          +OGLE_CALENDAR_REFRESH_TOKEN=  GOOGLE_CALENDAR_ID=primary
      937 +GOOGLE_CLIENT_ID=  GOOGLE_CLIENT_SECRET=  GOOGLE_REDIRECT_URL=
          +   # Socialite SSO admin+konsultan
      938 +MAIL_MAILER=log  MAIL_FROM_ADDRESS="no-reply@klinik-brand.id"
          + MAIL_FROM_NAME="${APP_NAME}"
      939 +WHITE_LABEL_DEFAULT_BRAND="Klinik Konsultan"  WHITE_LABEL_DOMA
          +IN=checkout.klinik-brand.id
      940 +SPLIT_PROVIDER=midtrans_connect  SPLIT_ON=true
      941 +```
      942 + 
      943 +### F.2 Setup checklist (urutan)
      944 +1. `composer create-project laravel/laravel:^9.0 soluusi-b2b-k
          +linik`
      945 +2. Replace `composer.json` require/require-dev + `package.json
          +` (§12); `composer install` + `npm install`
      946 +3. `php artisan key:generate`
      947 +4. Copas Appendix D sesuai manifest (migrations skip SEO/blog/
          +CMS, models, controllers adapt, services, mail+blades, config,
          + helpers, indoregion, seeders)
      948 +5. Bikin config baru `klinik.php`/`whitelabel.php`/`split.php`
      949 +6. Isi `.env` (F.1); Midtrans sandbox; gcal OAuth-user test cr
          +eds dulu (service account nanti)
      950 +7. `php artisan migrate` (resolve MySQL-only raw SQL alter unt
          +uk SQLite local — beberapa `alter enum` B2C raw)
      951 +8. `php artisan db:seed` (CategorySkillSeeder extend kategori
          +kesehatan §15.13)
      952 +9. `php artisan serve` + `npm run dev`
      953 +10. Jalankan smoke F.3
      954 + 
      955 +### F.3 Smoke check (satu runnable — Ponytail: smallest thing
          +that fails if logic breaks)
      956 +`tests/Feature/ScaffoldSmokeTest.php` (phpunit native, no fram
          +ework tambahan):
      957 +```php
      958 +// ponytail: one smoke per Ponytail rule. Framework: phpunit (
          +already in deps).
      959 +public function test_scaffold_boots_and_core_domain_writes()
      960 +{
      961 +    $this->assertNotNull(config('midtrans.server_key'));
      962 +    $konsultan = Consultant::factory()->create(['status' => 'a
          +ccept']);
      963 +    $pkg = ConsultantPackage::factory()->create(['consultan_id
          +' => $konsultan->id]); // typo parity
      964 +    $sched = Schedule::factory()->create(['consultant_id' => $
          +konsultan->id]);
      965 +    $ses = ConsultationSession::create([
      966 +        'consultant_id' => $konsultan->id, 'schedule_id' => $s
          +ched->id,
      967 +        'package_uuid' => $pkg->uuid, 'payment_status' => 'wai
          +ting', 'status' => 'waiting',
      968 +        // + complaint, DOB, regency_id, gender per Model C
      969 +    ]);
      970 +    $this->assertDatabaseHas('consultation_sessions', ['id' =>
          + $ses->id]);
      971 +}
      972 +```
      973 +Plus 3 manual (perlu live creds): Midtrans Snap URL generate s
          +andbox + buka; gcal event create test calendar; webhook signat
          +ure — kirim callback signature salah → reject (§11.6).
      974 + 
      975 +---
      976 + 
      977  *Soluusi · B2B Platform PRD · Confidential · Internal Use Only
           *
      793 -*Versi 1.0 · Agustus 2026 · Draft for review*
      978 +*Versi 1.1 · Agustus 2026 · Draft for review*

  Thought for 11s (ctrl+o to expand)

⏺ Update Daftar Isi — judul §12 + tambah appendix C/D/E/F:
     
⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 7 lines, removed 1 line
      22  9. Data Model
      23  10. Integrations & API
      24  11. Non-Functional Requirements
      25 -12. Tech Stack Proposal                                        
      25 +12. Tech Stack (Locked — match soluusi-main)                   
      26  13. Roadmap & Phases
      27  14. Risks & Mitigations
      28  15. Open Questions
      29 +- Appendix A — Traceability: Playbook → PRD
      30 +- Appendix B — Glossary
      31 +- Appendix C — B2C Codebase Audit → B2B Platform Deltas
      32 +- Appendix D — Component Reuse Manifest (copas soluusi-main → B
         +2B)
      33 +- Appendix E — Build-NEW, Fix-List & Merge Architecture
      34 +- Appendix F — Env Vars, Setup Checklist & Smoke Check
      35  
      36  ---
      37  ---

  Thought for 26s (ctrl+o to expand)

⏺ Semua material yang gw tangkep udah masuk ke PRD. Ringkasnya:
     
  Yang diubah/ditambah ke Soluusi B2B Platform - PRD.md (v1.0 → v1.1):

  - Header + TOC — bump v1.1, revision note, TOC + judul §12 + appendix C-F.
  - §12 Tech Stack — spekulasi
  (Next.js/NestJS/PostgreSQL+RLS/Auth0/Redis/Vercel+Fly) dihapus, diganti locked
  actual ground truth: Laravel 9 / PHP 8 / Midtrans Snap primary / Xendit dead
  sub-section.
  - §6 Arsitektur — mermaid dikoreksi: PostgreSQL→MySQL, Redis dihapus,
  Mobile-native→white-label web/PWA, BFF-service→route group, + prinsip
  merge-later.
  - §10 Integrations — baris spekulatif → actual B2C (Midtrans sig bug, Xendit
  dead, gcal OAuth-user, 14 mail white-label, Meet). 
  - §11 NFR — +§11.6 security fixes wajib (webhook sig + gcal non-fatal) +§11.7
  (UU PDP/HIMPSI/non-circumvention).
  - §15 — §15.1 RESOLVED; +15.9 profit_sharing, 15.10 split_settlements, 15.11
  gcal service account ownership, 15.12 voucher free-trial, 15.13 kategori
  kesehatan.
  - Appendix C — B2C audit → B2B deltas (7 sub + summary table
  B2B-NEW/reuse/fix).
  - Appendix D — copas manifest (8 sub: migrations skip SEO/blog/CMS, models,
  controllers, services, mail, config, helpers, indoregion+seeders).
  - Appendix E — build-NEW 11 item + fix-list 5 (2 wajib security/data) + merge
  architecture table.
  - Appendix F — env vars + setup checklist 10 langkah + smoke check (phpunit +
  3 manual).

  → skipped: scaffold fisik (laravel new + composer + copas file beneran) — itu
  dev, bukan doc. Jalankan saat user bilang go.

  Semua yang di-audit dari soluusi-main + semua prep material
  (stack/manifest/build/fix/env/smoke/OPEN) sekarang ada di PRD. Klinik spec
  v1.1 + Dev Preparation v1.0 tetap jadi depth reference (di-cross-reference
  dari PRD Appendix C/D). Mau lanjut scaffold, atau ada yang mau direvisi dulu?

✻ Worked for 9m 9s

※ recap: Building the soluusi.com B2B klinik platform to mirror the soluusi-main
  production stack. Spec, Dev Preparation, and PRD are all updated with the 
  full B2C audit and prep material folded in. Next action: scaffold the Laravel 
  9 project.

❯ apakah lu sudah pede dan dengan seluruh dokumentasinya?                       

  Thought for 1m 7s (ctrl+o to expand)

⏺ Jujur, pede di lapisan ground-truth teknis, tapi belum 100% di konsistensi 
  internal. Gw bedain:

  Pede (langsung dari audit soluusi-main + dokumen baru):
  - Stack lock (§12), copas manifest (App D), build-NEW/fix (App E),
  env+setup+smoke (App F), B2C audit deltas (App C) — ini semua dari baca
  codebase langsung, jelas.
  - 5 fix (webhook sig, gcal non-fatal, enum, profit_sharing, typo) + merge
  architecture — defensible. 

  Belum pede — gap konkret yang gw SKIP (blm gw reconcile):

  konflik dgn §6/§10/§12 yang baru:
    - M3 + Flow 8.3 masih tulis "End User (via mobile)" / "Mobile App" — padahal
  §6/§12 udah hapus native app → white-label web link.
    - M3 masih "integrate Zoom/Google Meet" — padahal B2C = Meet-only
  (link_meeting dari gcal).
    - Flow 8.7 revenue share masih model bagi-hasil % per range (250-299K/10%…),
  tapi spec v1.1 pindah ke markup model (Soluusi set net, klinik mark up,
  margin = price−net). Matematikanya beda — ini konflik beneran, bukan cuma
  istilah. 
    - §9 ERD masih entitas abstrak (ASSOCIATE/PARTNER/ORG…) yang belum map ke
  tabel real di App C.5.
  2. Beberapa assertion audit gw trust dari laporan agent sesi sebelumnya, bukan
  re-verify baris-per-baris sesi ini — webhook no-sig, gcal failure, 14 mail
  prefix "Soluusi -", typos. High confidence, tapi bukan garansi fresh. 
  3. Beberapa keputusan teknis yang butuh konfirmasi lu (bukan gap dok, decision
  sungguhan): preserve typo B2C untuk merge parity; skip total mesin content
  SEO/blog/CMS; MySQL (bukan Postgres+RLS seperti PRD awal) — ini ubah
  arsitektur multi-tenant; Xendit "revive" untuk split padahal B2C dead.

  Open product/business yang masih betulan open (doc udah list, tapi belum
  ke-resolve): scheduling [21], kategori kesehatan [22/§15.13], voucher
  free-trial [§15.12], no-show fee, profit_sharing [§15.9], split table
  [§15.10], gcal service-account ownership [§15.11], white-label depth [§15.4],
  konsultan fee model [§15.3], cross-sell attribution [§15.2], MHCU licensing
  [§15.5].

  Gap #1 itu yang bisa gw tutup sekarang — reconcile §7/§8/§9 ke ground truth
  (hapus mobile-app framing, Meet-only, ganti Flow 8.7 ke markup model senada
  spec, map ERD ke tabel real). Mau gw kerjakan itu biar PRD konsisten
  end-to-end? Sisanya (open decisions) itu emang keputusan lu, bukan dokumen.

✻ Churned for 1m 21s

❯ gw mau lu semua taruh di sdd/prd semunya buat kasih tau juga nantinya running 
  akan pakai tech apa dan juga ambil contoh engginenya dari                     
  mekari/soluusi-main nantinya kasih tau juga ke docnya karean gw akan beda ai  
  agent unut code running ini jadi make sure all semua tercatata dengan jelas   
  jadi gw mau lu sebagai PM unutk mengatur documenntnya agar enak runnignnya si 
  ai gw tinggal jalanain ikutin dari sana                                       
  
  Thought for 1m 29s (ctrl+o to expand)

⏺ Paham. Saya = PM. Dokumen = sumber kebenaran tunggal, berdiri sendiri, siap
  eksekusi, tanpa konteks sesi saya. Agen AI berbeda yang melakukan coding hanya
  perlu mengikuti. Termasuk: teknologi yang digunakan untuk running + contoh
  engineering asli dari soluusi-main (snippet asli, bukan abstrak) + keputusan
  PM yang sudah ditetapkan (bukan tebakan agen). Tutup juga celah §7/§8/§9 yang
  saya tandai sebelumnya.

  Saya buat task list dulu, bareng baca pola kode asli dari soluusi-main
  (Midtrans, gcal, migration, mailable — yang akan di-copy oleh agen coding):

⏺ Sekarang baca pola kode asli dari soluusi-main (4 file penting, baca paralel):

  Thought for 11s, read 1 file (ctrl+o to expand)

⏺ MidtransController dapat — pattern Snap create + callback (tanpa sig verify,
  gcal non-fatal bug confirmed). Lanjut baca GoogleCalendarService + migration +
  mailable:

  Thought for 8s, read 1 file (ctrl+o to expand)

⏺ GoogleCalendarService dapat — pattern OAuth-user + hardcoded "Soluusi - "
  (white-label target) + Meet conferenceData. Lanjut baca migration schema +
  satu mailable (struktur + blade):

  Thought for 20s, read 1 file (ctrl+o to expand)

⏺ Migration schema dapat — base-nya sudah Blueprint enum (catatan untuk fix-list
  #3). Lanjut baca mailable + blade (target white-label):

  Thought for 2m 44s, read 1 file (ctrl+o to expand)

⏺ Pola asli sudah dapat semua (Midtrans Snap+callback, gcal event, migration
  schema, mailable). Cukup untuk §17 engineering patterns dengan snippet asli.
  Lanjut task 2 — reconcile §7/§8/§9 ke ground truth. Update task status + mulai
  edit M3:

  Thought for 12s (ctrl+o to expand)

⏺ Edit §7 M3 (hapus "via mobile", Meet-only, admin-generate-link) + §8.3 (ganti
  Mobile App → white-label link flow):

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 9 lines, removed 8 lines
      267  | Document hub | MoU, laporan MHCU, materi promosi (Playbook §
           4.13) |
      268  
      269  ### M3 — Booking & Scheduling
      270 -**User:** End User (via mobile) + Partner Staff (refer) + Kons
          -ultan                                                         
      271 -**Tujuan:** booking sesi online/offline, manage jadwal konsult
          -an.                                                           
      270 +**User:** Customer (via white-label checkout link, **guest — n
          +o account**) + Klinik Admin (generate link) + Konsultan       
      271 +**Tujuan:** admin-generate-link booking — klinik admin pilih k
          +onsultan + schedule + package → generate signed white-label ch
          +eckout link → kirim ke customer → customer isi data + bayar la
          +ngsung. Mirror B2C `JadwalController` + `KonsumenController` f
          +low, adapt ke link-based + guest customer (Model C, Appendix E
          +.1 #5/#9).
      272  
      273  | Feature | Spec |
      274  |---|---|
      275 -| Konsultan availability | slot per konsultan, online/offline,
          - per cabang/sekolah/lokasi | 
      276 -| Booking flow | End User / Partner Staff book slot → confirm
          -→ reminder | 
      277 -| Reschedule & cancel | with reason log (Playbook §4.23) | 
      278 -| Konsultan replacement | bila konsultan berhalangan → auto-ma
          -tch dari pool (Playbook §4.6) | 
      279 -| Online session | integrate Zoom/Google Meet link auto-genera
          -ted | 
      275 +| Konsultan availability | slot per konsultan (`schedules` tab
          +le, concrete datetime — match B2C), per klinik/lokasi |
      276 +| Admin-generate-link | klinik admin pilih konsultan + schedul
          +e + package → generate signed checkout link (`checkout_links`:
          + token + expiry + markup_snapshot) → kirim ke customer |
      277 +| Customer checkout | customer buka link → isi data (nama/emai
          +l/telp/DOB/regency/gender + complaint) → bayar langsung (Snap)
          + → **no account** (Model C) |
      278 +| Reschedule & cancel | with reason log |
      279 +| Konsultan replacement | bila berhalangan → match dari pool (
          +Phase 2; MVP manual) |
      280 +| Online session | **Google Meet link** auto-generated dari gc
          +al event (`link_meeting`); **bukan Zoom** (match B2C) |
      281  | Offline session | lokasi fisik (klinik/sekolah/kantor) |
      281 -| Calendar sync | Konsultan sync ke Google Calendar | 
      282 +| Calendar sync | auto-add ke customer Google Calendar via gca
          +l event attendees (`GoogleCalendarService`) |
      283  
      284  ### M4 — Session & Confirmation
      285  **User:** Konsultan + End User + (audit) Finance

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 17 lines, removed 18 lines
      412      O8 --> ACT[Activation confirmed → enter 90-day checkpoint]
      413  ```
      414  
      415 -### 8.3 Booking Flow (End User books konsultasi)              
      415 +### 8.3 Booking Flow (klinik — admin-generate-link, customer g
          +uest)                                                         
      416  ```mermaid
      417  sequenceDiagram
      418 -    participant EU as End User                                
      419 -    participant APP as Mobile App                             
      418 +    participant KA as Klinik Admin                            
      419      participant BKG as Booking Service        
      420 +    participant CL as Customer (via white-label link)
      421 +    participant GW as Midtrans Snap + Split
      422 +    participant GCAL as Google Calendar
      423      participant KSR as Konsultan
      422 -    participant NOT as Notification 
      423 -    EU->>APP: Open booking (from QR/referral/voucher) 
      424 -    APP->>BKG: Query konsultan availability (filtered by org/v
          -ertikal) 
      425 -    BKG-->>APP: Slots 
      426 -    EU->>APP: Select slot + redeem voucher (optional) 
      427 -    APP->>BKG: Create booking 
      428 -    BKG->>KSR: Push to konsultan calendar 
      429 -    BKG->>NOT: Schedule reminders (H-1, H-2h) 
      430 -    NOT->>EU: WA/email confirm 
      431 -    EU->>APP: Attend session (online link / offline location) 
      432 -    KSR->>BKG: Mark attended 
      433 -    BKG->>NOT: Post-session: confirm prompt 
      434 -    NOT->>EU: "Rate session + confirm completed" 
      435 -    EU->>BKG: Confirm completed 
      436 -    BKG->>SES: Trigger M4 session confirmation 
      424 +    KA->>BKG: Pick konsultan + schedule + package
      425 +    BKG-->>KA: Generate signed white-label checkout link (toke
          +n+expiry+markup_snapshot)
      426 +    KA->>CL: Send link (WA/email) — link shows konsultan name
          ++ schedule + marked-up price, NO Soluusi
      427 +    CL->>BKG: Open link, isi data (nama/email/telp/DOB/regency
          +/gender+complaint)
      428 +    CL->>GW: Pay directly (Snap redirect-token)
      429 +    GW->>BKG: Webhook callback (signature VERIFIED §11.6) — sp
          +lit: net→Soluusi, margin→klinik
      430 +    BKG->>GCAL: createEvent (brand, Meet link, attendees custo
          +mer+konsultan)
      431 +    BKG->>CL: Email confirm (white-labeled) + gcal invite
      432 +    BKG->>KSR: Email + gcal invite
      433 +    Note over BKG: status=scheduled (gcal non-fatal §11.6)
      434 +    KSR->>BKG: Mark done (dual-confirm step 1, Appendix E.1 #4
          +)
      435 +    BKG->>BKG: Verify → release fee + HAP trigger (M6)
      436  ```
      437  
      438  ### 8.4 Session Confirmation & Commission Trigger Flow (anti-f
           raud core)

  Thought for 17s (ctrl+o to expand)

⏺ Edit §8.7 (bagi-hasil % → markup model, senada spec v1.1) + §9.3 (map ERD ke
  tabel real):

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 13 lines, removed 11 lines
      488      X10 --> X7
      489  ```
      490  
      491 -### 8.7 Revenue Share Calculation Flow (klinik)               
      491 +### 8.7 Revenue Calculation Flow (klinik — markup model, senan
          +a Spec v1.1 §8)                                               
      492  ```mermaid
      493  flowchart TD
      494 -    R1[Sesi completed: harga Rp X to pasien] --> R2[Determine 
          -bagi hasil tier by harga]                                     
      495 -    R2 --> R3{Harga range}                                    
      496 -    R3 -->|250-299K| R4[10% max]
      497 -    R3 -->|300-349K| R5[15% max]
      498 -    R3 -->|350-399K| R6[20% max]
      499 -    R3 -->|≥400K| R7[25% max]
      500 -    R4 & R5 & R6 & R7 --> R8[Apply negotiated % ≤ max] 
      501 -    R8 --> R9[Bagi hasil to klinik = % × harga] 
      502 -    R9 --> R10[Soluusi revenue = harga − bagi hasil − konsulta
          -n fee] 
      503 -    R10 --> R11[Log immutable] 
      494 +    R1[Soluusi sets net price per package] --> R2[Klinik marks
          + up above net within cap per price range]
      495 +    R2 --> R3{Price range → cap markup}
      496 +    R3 -->|250-299K| R4[cap 10%]
      497 +    R3 -->|300-349K| R5[cap 15%]
      498 +    R3 -->|350-399K| R6[cap 20%]
      499 +    R3 -->|≥400K| R7[cap 25%]
      500 +    R4 & R5 & R6 & R7 --> R8[Customer pays final = net + marku
          +p]
      501 +    R8 --> R9[Gateway split: net → Soluusi, markup margin → kl
          +inik]
      502 +    R9 --> R10[Konsultan fee = flat per-sesi per konsultan pro
          +file §15.3]
      503 +    R10 --> R11[Soluusi revenue = net − konsultan fee − HAP ac
          +crual M6]
      504 +    R11 --> R12[Log immutable split_settlements]
      505  ```
      506 +> **Note:** markup model (margin = final − net), BUKAN bagi-ha
          +sil % × harga (model lama dihapus — senana Spec v1.1 §8). Cap
          +% per range = batas max markup, bukan % yang dipotong dari har
          +ga.
      507  
      508  ### 8.8 Commission Payout Flow (3-stage, anti-fraud)
      509  ```mermaid

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 14 lines
      591  - `PARTNER.stage` — enum: prospect/contacted/.../mou/onboardin
           g/activation/stage1/stage2/stage3.
      592  - `COMMISSION.stage` — 1/2/3, immutable once paid.
      593  
      594 +### 9.3 Map ERD konseptual → tabel real `soluusi-main` (Append
          +ix C.5)                                                       
      595 +| ERD konseptual | Tabel real B2C (reuse) | B2B-only additive 
          +|                                                             
      596 +|---|---|---|                                                 
      597 +| ASSOCIATE | `users` (role associate) + CRM pipeline (new) | 
          +— |                                                           
      598 +| PARTNER / ORG | `companies` (reuse) | `kliniks` (brand/domai
          +n/logo/gcal_id), `klinik_admins` |
      599 +| STAFF | `users` (role klinik_admin/partner_staff) | `klinik_
          +admins` pivot |
      600 +| KONSULTAN | `consultants` (reuse verbatim) | — |
      601 +| BOOKING / SESSION | `consultation_sessions` + `schedules` +
          +`consultant_packages` (reuse) | `checkout_links` |
      602 +| SESSION_NOTES | `consultation_sessions.note`/`solution` (reu
          +se; encrypt at rest §11.1) | — |
      603 +| COMMISSION | `payments` (reuse) | `split_settlements`, `hap_
          +commissions`, `markup_rules` |
      604 +| VOUCHER | `vouchers` (reuse; tambah `free_trial` type §15.12
          +) | — |
      605 +| MHCU_BATCH / RESULT | (Phase 2 — baru) | `mhcu_batches`, `mh
          +cu_results` |
      606 +| ENDUSER (customer) | **guest, no `users` row** (Model C) — P
          +II di `consultation_sessions` + `checkout_links` | `checkout_l
          +inks` |
      607 + 
      608  ---
      609  ---
      610  

  Thought for 10s (ctrl+o to expand)

⏺ §7/§8/§9 sudah sesuai dengan kenyataan (ground truth). Tugas 2 selesai. Lanjut
  tugas 3 — tambahkan §16 Menjalankan Teknologi (Running Tech) + §17 Pola
  Engineering (snippet asli dari soluusi-main):

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 192 lines
      789  ---
      790  ---
      791  
      792 +## 16. Running Tech (cara jalanin — exact, untuk coding agent)
      793 +                                                              
      794 +### 16.1 Versi terkunci                                       
      795 +- PHP **8.0**+ (cli + fpm). Ext: pdo, pdo_sqlite/mysql, mbstri
          +ng, xml, ctype, json, bcmath, fileinfo, gd, curl.             
      796 +- Composer 2.x, Node 18+, npm.                                
      797 +- Laravel **9** (`laravel/framework ^9.0`).                   
      798 +                                                              
      799 +### 16.2 Install (first time)                                 
      800 +```bash
      801 +# 1. Scaffold — pin Laravel 9 (jangan default installer yang m
          +ungkin 10+)
      802 +composer create-project laravel/laravel:^9.0 soluusi-b2b-klini
          +k
      803 +cd soluusi-b2b-klinik
      804 + 
      805 +# 2. Replace composer.json (require + require-dev §12.1/12.6)
          ++ package.json (§12.4), lalu:
      806 +composer install
      807 +npm install
      808 + 
      809 +# 3. Key + storage link
      810 +php artisan key:generate
      811 +php artisan storage:link
      812 + 
      813 +# 4. Copas file dari /Users/mekari/soluusi-main sesuai Appendi
          +x D (manual cp, skip SEO/blog/CMS)
      814 + 
      815 +# 5. .env (Appendix F.1) — Midtrans sandbox + gcal OAuth-user
          +test creds dulu
      816 + 
      817 +# 6. DB local (SQLite) + migrate + seed
      818 +touch database/database.sqlite
      819 +php artisan migrate
      820 +php artisan db:seed          # CategorySkillSeeder extend kate
          +gori kesehatan §15.13
      821 + 
      822 +# 7. Run dev
      823 +php artisan serve            # http://localhost:8000
      824 +npm run dev                   # Vite + Tailwind
      825 +```
      826 + 
      827 +### 16.3 Dev run (harian)
      828 +```bash
      829 +php artisan serve & npm run dev          # app + frontend
      830 +php artisan queue:work                   # gcal + mail async (
          +wajib — mailables queueable)
      831 +php artisan schedule:work                # reminder cadence (M
          +10) + expiry sweep
      832 +```
      833 + 
      834 +### 16.4 Test
      835 +```bash
      836 +php artisan test                         # phpunit (incl. Scaf
          +foldSmoke Appendix F.3)
      837 +php artisan test --filter=ScaffoldSmokeTest
      838 +```
      839 + 
      840 +### 16.5 Prod deploy (match B2C)
      841 +- Single Laravel app, PHP-FPM + nginx, **MySQL**, `QUEUE_CONNE
          +CTION=database` (worker via supervisor), `CACHE_STORE=database
          +`, `SESSION_DRIVER=database`. **Redis Phase 2** jika throughpu
          +t perlu.
      842 +- `.env` prod: Midtrans **live** keys, gcal **service account*
          +* (§15.11), `MAIL_MAILER=ses`/postmark, `APP_DEBUG=false`.
      843 +- Build: `composer install --no-dev`, `npm run build`, `php ar
          +tisan migrate --force`, `php artisan config:cache && route:cac
          +he && view:cache`.
      844 +- Cron: `* * * * * cd /app && php artisan schedule:run` per me
          +nit. Queue worker: supervisor/systemd.
      845 + 
      846 +### 16.6 Local SQLite caveat
      847 +Beberapa migration B2C pakai raw SQL `ALTER ... MODIFY` (MySQL
          +-only) untuk enum change. Di SQLite local: adapt pakai `doctri
          +ne/dbal` `->change()`, atau `php artisan migrate --pretend` un
          +tuk cek. `ScaffoldSmokeTest` (F.3) akan catch kalau ada yang b
          +reak.
      848 + 
      849 +---
      850 + 
      851 +## 17. Engineering Patterns dari `soluusi-main` (mirror + adap
          +t)
      852 + 
      853 +> Coding agent: snippet di bawah = **REAL** dari `/Users/mekar
          +i/soluusi-main`. Mirror pattern-nya, adapt sesuai catatan **"B
          +2B CHANGE"**. Path source dicantumkan. Jangan re-invent.
      854 + 
      855 +### 17.1 Midtrans Snap — create transaction + row
      856 +Source: `app/Http/Controllers/MidtransController.php::createVa
          +`
      857 +```php
      858 +// B2C: auth()->user(). B2B CHANGE: customer = guest (Model C)
          +, data dari form checkout link, bukan auth.
      859 +$data = [
      860 +    'transaction_details' => [
      861 +        'order_id' => str_replace('.', '', microtime(true)),
          + // B2B: pakai order_number konsisten
      862 +        'gross_amount' => $package->price + 10000,
          + // B2B CHANGE: gross = net(Soluusi) + markup(klinik); split v
          +ia Connect/Marketplace
      863 +    ],
      864 +    'customer_details' => [
      865 +        'first_name' => $customer->firstname,
          +// B2B: dari form checkout, bukan auth
      866 +        'email' => $customer->email,
      867 +        'phone' => $customer->phone,
      868 +    ],
      869 +    'expiry' => ['start_time' => date("Y-m-d H:i:s O"), 'unit'
          + => 'hour', 'duration' => 1],
      870 +    'callbacks' => ['finish' => url('/checkout/done')],
          +// B2B: white-label finish page
      871 +];
      872 +\Config::$serverKey = config('midtrans.server_key');
      873 +\Config::$isProduction = config('app.env') === 'production';
      874 +\Config::$is3ds = config('midtrans.is_3ds');
      875 +$tokenUrl = \Midtrans\Snap::getSnapUrl($data);
      876 +$snapToken = basename($tokenUrl);
      877 +// lalu create ConsultationSession row (mirror field set §17.4
          +) + send Bayar mail (white-labeled §17.5)
      878 +```
      879 + 
      880 +### 17.2 Midtrans callback — signature verify WAJIB + gcal non
          +-fatal
      881 +Source: `app/Http/Controllers/MidtransController.php::callback
          +`
      882 +```php
      883 +// B2C (BUG — JANGAN MIRROR): langsung lookup order_number, no
          + verify. B2B FIX: verify SEBELUM proses.
      884 +use Midtrans\Notification;
      885 +$notif = new Notification($request->all(), config('midtrans.se
          +rver_key'), config('app.env') === 'production'); // throws jik
          +a signature invalid
      886 +$orderId = $notif->order_id;   // atau $request->order_id sete
          +lah verify lolos
      887 +$session = ConsultationSession::where('order_number', $orderId
          +)->firstOrFail();
      888 + 
      889 +switch ($request->transaction_status) {
      890 +    case 'settlement':
      891 +    case 'capture': // if fraud_status == 'accept'
      892 +        $session->payment_status = 'verified';
      893 +        $session->status = 'scheduled';          // B2B CHANGE
          +: scheduled REGARDLESS of gcal (non-fatal §11.6)
      894 +        $session->payment_at = $request->transaction_time;
      895 +        $session->payment = $request->gross_amount;
      896 +        $session->save();
      897 +        // gcal best-effort (try/catch, gak block scheduled)
      898 +        try {
      899 +            $event = app(\App\Services\GoogleCalendarService::
          +class)->createEvent($session);
      900 +            if ($event) {
      901 +                $session->link_meeting = $event['meet_link'] ?
          +? $event['event_link'];
      902 +                $session->google_calendar_event_id = $event['e
          +vent_id'];
      903 +                $session->save();
      904 +            }
      905 +        } catch (\Exception $e) { \Log::error('gcal: '.$e->get
          +Message()); }
      906 +        // B2B ADD: split_settlements row (net→Soluusi, margin
          +→klinik)
      907 +        // B2B ADD: dual-confirm trigger (Appendix E.1 #4) — f
          +ee release NANTI setelah verify, bukan di sini
      908 +        break;
      909 +    case 'pending': $session->payment_status = 'waiting'; brea
          +k;
      910 +    case 'expire': $session->payment_status = 'canceled'; $ses
          +sion->status = 'canceled'; break;
      911 +    case 'deny': case 'cancel': case 'failure': $session->paym
          +ent_status = 'failed'; break;
      912 +    default: $session->payment_status = 'waiting';
      913 +}
      914 +$session->save();
      915 +// send white-labeled SudahBayar mails (client + consultant) —
          + §17.5 brand param
      916 +```
      917 + 
      918 +### 17.3 Google Calendar — create event + Meet (mirror, white-
          +label + service account)
      919 +Source: `app/Services/GoogleCalendarService.php`
      920 +```php
      921 +// B2C: OAuth-user (fetchAccessTokenWithRefreshToken($refreshT
          +oken)). B2B MIGRATE: service account + Workspace Delegation:
      922 +//   $client->setAuthConfig(storage_path('app/gcal-service-acc
          +ount.json'));
      923 +//   $client->setSubject('calendar@klinik-domain.id');   // or
          + soluusi ops account
      924 +//   $client->addScope(Google_Service_Calendar::CALENDAR);  (n
          +o refresh-token dance)
      925 +// B2B CHANGE: brand dari $session->klinik->brand, bukan hardc
          +oded "Soluusi".
      926 +$event->setSummary("{$brand} - {$packageName}");
          +   // B2C: "Soluusi - {$packageName}"
      927 +$event->setDescription("...dijadwalkan secara otomatis oleh {$
          +brand}."); // B2C: "...oleh Soluusi."
      928 +// Meet conferenceData (mirror as-is — solutionKey type 'hango
          +utsMeet', requestId Str::uuid())
      929 +// attendees: customer email dari checkout form (B2B: customer
          + guest, bukan $session->user->email) + consultant
      930 +// reminders: email 60min, popup 30min (mirror)
      931 +// insert dengan conferenceDataVersion=1, sendUpdates='all'
      932 +```
      933 + 
      934 +### 17.4 consultation_sessions schema (mirror verbatim, typo p
          +arity)
      935 +Source: `database/migrations/2023_11_09_155601_create_consulta
          +tion_sessions_table.php`
      936 +```php
      937 +Schema::create('consultation_sessions', function (Blueprint $t
          +able) {
      938 +    $table->id();
      939 +    $table->foreignId('schedule_id')->constrained();
      940 +    $table->foreignId('user_id')->constrained();
          + // B2B: nullable (guest customer Model C) — atau link ke chec
          +kout_links
      941 +    $table->foreignId('consultant_packages')->constrained();
          + // ponytail: preserve B2C column name (typo parity, Appendix
          +E.2 #5)
      942 +    $table->enum('status', ['waiting','ongoing','done','waitin
          +g_summary'])->default('waiting'); // B2B extend via alter: +sc
          +heduled, +canceled, +verified
      943 +    $table->string('order_number');
      944 +    $table->enum('payment_status', ['waiting','verified','canc
          +eled'])->default('waiting');      // B2B extend: +failed
      945 +    $table->timestampTz('order_at');
      946 +    $table->timestamps();
      947 +});
      948 +// + later B2C alters to mirror: link_meeting, google_calendar
          +_event_id, complaint, note, solution,
      949 +//   package_price, voucher_amount, completed_at, expired_at,
          +snaptoken, url_snap, review_text, rating
      950 +```
      951 + 
      952 +### 17.5 Mailable — white-label parameterize
      953 +Source: `app/Mail/Client/Bayar.php`
      954 +```php
      955 +// B2C: subject hardcoded "Soluusi - ...", no from(). B2B CHAN
          +GE: brand dari $session->klinik->brand, from() per-mailable.
      956 +class Bayar extends Mailable
      957 +{
      958 +    use Queueable, SerializesModels;
      959 +    public function __construct(public ConsultationSession $se
          +ssion, public string $brand) {}
      960 +    public function envelope(): Envelope
      961 +    {
      962 +        return new Envelope(
      963 +            from: config('whitelabel.email'),               //
          + B2B ADD (global sender → per-mailable)
      964 +            subject: "{$this->brand} - Satu Langkah Lagi! Sege
          +ra Selesaikan Pembayaranmu", // B2B: prefix brand
      965 +        );
      966 +    }
      967 +    public function content(): Content
      968 +    {
      969 +        return new Content(view: 'emails.klinik.bayar', with:
          +['session' => $this->session, 'brand' => $this->brand]);
      970 +    }
      971 +}
      972 +// caller: Mail::to($customer->email)->send(new Bayar($session
          +, $session->klinik->brand));
      973 +// 14 mailables (5 client + 9 konsultan) semua di-parameterize
          + cara yang sama.
      974 +```
      975 + 
      976 +### 17.6 Voucher logic (mirror verbatim)
      977 +Source: `MidtransController::getNominalWithVoucher` — cek `Car
          +bon::now()->between(start_date, end_date)`, `kuota <= 0`, `min
          +_transaction > price`, `max_discount < nominal`; type `diskon`
          + = `price * discount_percentage / 100` capped `max_discount`;
          +type `potongan` = `nominal` flat. B2B: tambah type `free_trial
          +` (§15.12) → nominal 0, durasi 10 min.
      978 + 
      979 +### 17.7 Schedule lock + expiry sweep (mirror)
      980 +Source: `MidtransController::checkScheduleAvailability` + `upd
          +ateExpiredPayments` — slot di-lock jika ada `ConsultationSessi
          +on` `payment_status` in `[verified, waiting]` + `expired_at >
      983 + 
      984  ## Appendix A — Traceability: Playbook → PRD
      985  
      986  | Playbook section | PRD module/flow |
  ⎿  Referenced file Soluusi B2B Platform - PRD.md
  ⎿  Read ../.claude/projects/-Users-mekari-soluusi-b2b-beforemarge/memory/dev-p
     reparation-doc.md (15 lines)
  ⎿  Referenced file Soluusi B2B - Dev Preparation.md
  ⎿  Read ../.claude/projects/-Users-mekari-soluusi-b2b-beforemarge/memory/soluu
     si-main-codebase.md (24 lines)
  ⎿  Referenced file Soluusi B2B - Klinik Vertical Spec.md

  Thought for 6s (ctrl+o to expand)

  Thought for 6s (ctrl+o to expand)

  Thought for 39s, searched for 1 pattern, read 1 file (ctrl+o to expand)

⏺ Now I'll insert the §15.0 PM Decisions table at the top of §15 — this resolves
  every OPEN item with a default so the coding agent is never blocked guessing.

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 25 lines, removed 1 line
      772  
      773  ## 15. Open Questions (decisions needed)
      774  
      775 -1. **§15.1 Stack match** — ✅ **RESOLVED** (Agustus 2026, audi
          -t `soluusi-main`). B2C = Laravel 9 / PHP 8 / Midtrans Snap pri
          -mary / Xendit dead / gcal OAuth-user / Vite-Tailwind / MySQL /
          - database driver (no Redis). B2B **match persis** (§12 locked)
          -. Share team + code; merge ke `soluusi-main` nanti.           
      775 +### 15.0 PM Decisions (resolusi default — coding agent JANGAN 
          +tunggu, ikuti ini kecuali founder override)                   
      776 +                                                              
      777 +> PM (Claude) sudah putuskan setiap [OPEN] di bawah dengan def
          +ault. Status: **DECIDED** = coding agent langsung jalan pakai
          +ini; **NEEDS FOUNDER** = ada fallback default yang AMAN dipaka
          +i untuk dev/MVP, founder konfirm sebelum prod. Tidak ada yang
          +blocking scaffold.
      778 + 
      779 +| # | Topik | Status | Keputusan PM (default) | Fallback / cat
          +atan blocking |
      780 +|---|------|--------|------------------------|----------------
          +--------------|
      781 +| 15.1 | Stack match | ✅ DECIDED | Match `soluusi-main` persi
          +s (§12). | — |
      782 +| 15.2 | Cross-sell attribution | ✅ DECIDED | Associate dapat
          + **full commission** vertical pertama yang aktif. Vertical ked
          +ua (di dalam 6 bulan) → **50%** (anti-gaming). | Tabel `cross_
          +sell_attributions(associate_id, partner_id, vertical, window_e
          +xpires_at, commission_rate)`. Phase 2 — hardcode 50% di config
          + untuk MVP, ganti tanpa code. |
      783 +| 15.3 | Konsultan fee model | ✅ DECIDED | **Flat net fee per
          + sesi** completed-and-verified, dibayar Soluusi ke konsultan.
          +BUKAN revenue-share %, BUKAN bagi-hasil. (Soluusi set net; kli
          +nik markup → margin ke klinik, net ke Soluusi, flat fee ke kon
          +sultan dari Soluusi.) | Tabel `consultant_fees(consultant_id,
          +package_id, net_fee, effective_at)`. Mirror B2C `consultant_pa
          +ckages.price` = net. |
      784 +| 15.4 | White-label depth (MVP) | ✅ DECIDED | Depth MVP: **l
          +ogo + brand name + sender email + mail subject prefix + gcal e
          +vent prefix + finish page**. Custom domain = Phase 2. | Config
          + `whitelabel.{name,logo,email}` per-klinik. |
      785 +| 15.5 | MHCU instrument licensing | ⚠️  NEEDS FOUNDER | M5 = Ph
          +ase 2, jadi **tidak blocking MVP**. Founder konfirm license MM
          +CI/MMPI (Soluusi sendiri vs psikolog partner) sebelum build M5
          +. | Default dev: category-A instruments **disabled** di seeder
          + sampai license confirmed. |
      786 +| 15.6 | Self-serve vs associate-only | ✅ DECIDED | **Associa
          +te-only** GTM untuk MVP + Phase 2 (match Playbook). Self-serve
          + = Phase 3. | Tidak ada public signup route di MVP. |
      787 +| 15.7 | Pricing tiering (PEPM / sekolah) | ⚠️  NEEDS FOUNDER |
          +Pakai angka **indikatif Playbook** sebagai default MVP, **para
          +meterize** via `config/pricing.php` supaya finance ubah tanpa
          +code. | Founder/finance confirm angka final sebelum go-live; d
          +ev pakai indikatif. |
      788 +| 15.8 | B2C integration scope | ✅ DECIDED | Phase 3. Data mo
          +del keep `consultant_id` FK **compatible** untuk future pool-s
          +hare, tapi **no integration code** di MVP/Phase 2. | — |
      789 +| 15.9 | `profit_sharing` semantics | ✅ DECIDED | **PRESERVE
          +field** (merge parity, jangan drop), **tapi B2B commission cal
          +c TIDAK pakai field ini** (semantics ambigu + B2B pakai markup
          + model, bukan bagi-hasil). B2B pakai field baru eksplisit: `kl
          +inik_markup_rate` + `split_settlements`. | Coding agent: grep
          +pemakaian `profit_sharing` di `soluusi-main` controllers untuk
          + dokumentasi, tapi jangan depend on it. |
      790 +| 15.10 | Split settlement table | ✅ DECIDED | **Tabel baru `
          +split_settlements`** (additive). FK ke `consultation_sessions`
          + (B2B gak punya `payments` table). Field: `session_id, net_amo
          +unt, klinik_margin, status, settled_at`. | — |
      791 +| 15.11 | gcal service account ownership | ⚠️  NEEDS FOUNDER |
          +**Soluusi ops** pegang service account (`storage/app/gcal-serv
          +ice-account.json`, gitignored). Subject = `calendar@soluusi.co
          +m` (Workspace Delegation). MVP dev pakai OAuth-user test creds
          + (§16.2). | Founder: confirm Google Workspace admin + create S
          +A + delegate `…/auth/calendar` scope sebelum prod. Tidak block
          +ing dev. |
      792 +| 15.12 | Voucher free-trial | ✅ DECIDED | **YA**, tambah `ty
          +pe = free_trial` (10 min, nominal 0) untuk akuisisi customer k
          +linik (M7). | Extend `vouchers.type` enum: `potongan`/`diskon`
          +/`free_trial`. Mirror logic §17.6. |
      793 +| 15.13 | Kategori kesehatan | ✅ DECIDED | **Extend seeder**:
          + klinik categories = Psikolog Klinis, Psikolog Pendidikan, Kon
          +selor, Ahli Gizi, Fisioterapis, Terapis Okupasi. **Keep B2C ca
          +tegories** (merge parity). Tambah `vertical` tag (`b2c`/`klini
          +k`/`both`) di `categories`. | Seeder `CategorySkillSeeder` ext
          +end (Appendix D.8). |
      794 + 
      795 +**Net:** 9 DECIDED, 3 NEEDS FOUNDER (none blocking dev — semua
          + punya fallback dev-safe). Coding agent: jalan pakai kolom "Ke
          +putusan PM". Kalau founder override, update row ini + relevant
          + section.
      796 + 
      797 +---
      798 + 
      799 +### 15.x Detail per item (historical context) B2C = Laravel 9
          +/ PHP 8 / Midtrans Snap primary / Xendit dead / gcal OAuth-use
          +r / Vite-Tailwind / MySQL / database driver (no Redis). B2B **
          +match persis** (§12 locked). Share team + code; merge ke `solu
          +usi-main` nanti.
      800  2. **§15.2 Cross-sell attribution** — associate yang bawa mitr
           a klinik, kalau mitra itu juga jadi Company vertical, komisi c
           ross-sell ke associate yang sama atau split? (TBD — affects M9
            + Commission Engine)
      801  3. **§15.3 Konsultan fee model** — bagaimana konsultan dibayar
            (per sesi flat? revenue share with Soluusi)? Belum di playboo
           k. Affects revenue equation §8.7.
      802  4. **§15.4 White-label depth** — perusahaan minta branded port
           al seberapa deep (logo only? custom domain? full white-label a
           pp?) Phase 2.

  Thought for 17s, read 1 file (ctrl+o to expand)

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 3 lines, removed 1 line
      796  
      797  ---
      798  
      799 -### 15.x Detail per item (historical context) B2C = Laravel 9 
          -/ PHP 8 / Midtrans Snap primary / Xendit dead / gcal OAuth-use
          -r / Vite-Tailwind / MySQL / database driver (no Redis). B2B **
          -match persis** (§12 locked). Share team + code; merge ke `solu
          -usi-main` nanti.                                              
      799 +### 15.x Detail per item (historical context)                 
      800 +                                                              
      801 +1. **§15.1 Stack match** — ✅ **RESOLVED** (Agustus 2026, audi
          +t `soluusi-main`). B2C = Laravel 9 / PHP 8 / Midtrans Snap pri
          +mary / Xendit dead / gcal OAuth-user / Vite-Tailwind / MySQL /
          + database driver (no Redis). B2B **match persis** (§12 locked)
          +. Share team + code; merge ke `soluusi-main` nanti. → **lihat
          +tabel 15.0 row 1.**
      802  2. **§15.2 Cross-sell attribution** — associate yang bawa mitr
           a klinik, kalau mitra itu juga jadi Company vertical, komisi c
           ross-sell ke associate yang sama atau split? (TBD — affects M9
            + Commission Engine)
      803  3. **§15.3 Konsultan fee model** — bagaimana konsultan dibayar
            (per sesi flat? revenue share with Soluusi)? Belum di playboo
           k. Affects revenue equation §8.7.
      804  4. **§15.4 White-label depth** — perusahaan minta branded port
           al seberapa deep (logo only? custom domain? full white-label a
           pp?) Phase 2.

  Thought for 11s (ctrl+o to expand)

  Thought for 11s, read 1 file (ctrl+o to expand)

⏺ Now I'll insert the §0 Start-Here execution index at the top of the PRD — the
  single entry point for the coding agent. First the version bump + new revision
  note + §0 block:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 46 lines, removed 1 line
       1  # Soluusi B2B Platform — Product Requirements Document (PRD)
       2  ### Platform terintegrasi untuk 3 vertikal: For Health Faciliti
          es · For School · For Company
       3  
       4 -> Versi 1.1 · Agustus 2026                                     
       4 +> Versi 1.2 · Agustus 2026                                     
       5  > Owner: Founder Soluusi
       6  > Status: Draft untuk review
       7  > Dokumen pendukung: `Soluusi B2B Master Playbook.md`, `Soluusi
           B2B - Competitor Analysis.md`, `Soluusi B2B - Klinik Vertical 
          Spec.md` (v1.1), `Soluusi B2B - Dev Preparation.md` (v1.0)
       8 +> Codebase ground truth: `/Users/mekari/soluusi-main` (B2C prod
         +uction, Laravel 9)
       9  >
      10  > **Revisi v1.1 (4 Agustus 2026):** resolusi §15.1 — stack B2C
          (`soluusi-main`) sudah di-audit; spekulasi tech (Next.js/NestJS
          /PostgreSQL/Auth0/Redis) dihapus, diganti actual ground truth (
          Laravel 9 / PHP 8 / Midtrans Snap / Xendit dead / gcal OAuth-us
          er / Vite-Tailwind / MySQL). Tambahan Appendix C (B2C audit → B
          2B deltas), D (copas manifest), E (build-new + fix), F (env + s
          etup + smoke).
      11 +>
      12 +> **Revisi v1.2 (5 Agustus 2026):** Tambahan §0 Start-Here (exe
         +cution index untuk coding agent — beda AI jalanin code, doc ini
         + self-contained), §15.0 PM Decisions (resolusi default semua [O
         +PEN] — 9 DECIDED + 3 NEEDS FOUNDER, none blocking), §16 Running
         + Tech (install/dev/prod commands exact), §17 Engineering Patter
         +ns (verbatim snippet dari `soluusi-main` + annotasi "B2B CHANGE
         +"). PM = Claude; coding agent ikutin doc ini, gak perlu context
         + session lama.
      13  
      14  ---
      15  
      16 +## 0. Start Here (untuk coding agent — baca ini DULU)
      17 + 
      18 +> **Audience:** AI agent yang nulis + jalanin code (beda sessio
         +n dari PM). Kamu gak punya context percakapan lama — doc ini se
         +lf-contained. Ikuti urutan di bawah, jangan nebak.
      19 + 
      20 +### 0.1 Apa yang kamu bangun
      21 +Platform **B2B white-label** untuk Soluusi.com (existing B2C co
         +nsulting marketplace). 3 vertikal: **Klinik** (prioritas #1 MVP
         +), Sekolah (Phase 2), Company (Phase 2). Codebase **sekarang te
         +rpisah** tapi didesain **merge ke `soluusi-main`** nanti — jadi
         + mirror domain tables + models verbatim (termasuk typo B2C), B2
         +B-only tables prefix `klinik_`. Vertical pertama yang dibangun
         += **Klinik**.
      22 + 
      23 +### 0.2 Aturan main (HARUS baca sebelum tulis code)
      24 +1. **Ponytail — Lazy Senior Dev Mode** (lihat `~/.claude/CLAUDE
         +.md`): YAGNI → reuse dari `soluusi-main` → stdlib → native → in
         +stalled dep → one-liner → minimum. **Bug fix = root cause** (gr
         +ep every caller, fix di shared function). No unrequested abstra
         +ction. Mark simplifikasi pakai `ponytail:` comment (name ceilin
         +g + upgrade path). Default intensity **full**.
      25 +2. **NEVER simplify away:** input validation di trust boundary,
         + error handling yang prevent data loss, security measures, acce
         +ssibility basics, hal yang explicitly requested.
      26 +3. **Non-trivial logic = ONE runnable check** (smallest thing t
         +hat fails if logic breaks). Lihat Appendix F.3 ScaffoldSmokeTes
         +t.
      27 +4. **White-label CRITICAL (Model C / v1.1):** Customer **TIDAK
         +boleh tahu** service dari Soluusi. Sembunyikan brand Soluusi (n
         +on-circumvention). Lihat §15.0 row 15.4 + §17.3 + §17.5.
      28 +5. **Confidentiality:** session content (konsultan + customer +
         + audit) rahasia; HR/partner admin lihat aggregate only. HIMPSI:
         + category-A instruments (MMCI/MMPI) hanya psikolog licensed (§1
         +5.5 — disabled di MVP). UU PDP: data residency Indonesia (§11.7
         +).
      29 + 
      30 +### 0.3 Urutan baca dokumen (wajib)
      31 +| Urutan | Dokumen | Ambil apa |
      32 +|--------|---------|-----------|
      33 +| 1 | **PRD ini** — §12 (stack), §15.0 (PM decisions), §16 (run
         +ning tech), §17 (engineering patterns verbatim) | Stack, keputu
         +san, cara jalanin, snippet mirror |
      34 +| 2 | `Soluusi B2B - Dev Preparation.md` | Copas manifest (file
         + mana yang cp), build-NEW list, fix-list, setup checklist |
      35 +| 3 | `Soluusi B2B - Klinik Vertical Spec.md` (v1.1) | Detail k
         +linik: markup model, HAP commission, dual-confirm, anti-circumv
         +ention |
      36 +| 4 | `Soluusi B2B Master Playbook.md` | GTM, persona, pricing
         +indikatif, commission rules |
      37 +| 5 | `/Users/mekari/soluusi-main` (codebase B2C) | Source trut
         +h untuk copas + engineering patterns (§17) |
      38 + 
      39 +### 0.4 Urutan eksekusi build (MVP Klinik)
      40 +1. **Scaffold** — §16.2 (composer create-project laravel/larave
         +l:^9.0, replace composer/package.json, key:generate, storage:li
         +nk).
      41 +2. **Copas** dari `soluusi-main` per Appendix D (skip SEO/blog/
         +CMS migrations 2026_04_30+).
      42 +3. **Config** — Appendix F.1 `.env` (Midtrans sandbox + gcal OA
         +uth-user test creds), `config/whitelabel.php`, `config/midtrans
         +.php`, `config/services.php` (google + google_calendar only).
      43 +4. **Migrate + seed** — `php artisan migrate` (SQLite local), `
         +php artisan db:seed` (CategorySkillSeeder extend klinik §15.13)
         +.
      44 +5. **Build-NEW** (Appendix E.1, 11 items) — split-payment, whit
         +e-label config, service-account gcal, dual-confirm, admin-gener
         +ate-link booking, klinik RBAC/earnings, HAP commission, markup/
         +cap, Model C checkout-guest, anti-circumvention, no-show.
      45 +6. **Fix-List** (Appendix E.2, 5 items) — webhook signature ver
         +ify (§17.2 SECURITY), gcal non-fatal (§11.6 DATA-LOSS), payment
         +_status enum Blueprint, profit_sharing preserve-but-ignore (§15
         +.9), B2C typos PRESERVE (merge parity).
      46 +7. **Smoke check** — Appendix F.3 (`php artisan test --filter=S
         +caffoldSmokeTest` + 3 manual: Midtrans Snap sandbox, gcal event
         +, webhook sig reject).
      47 +8. **Dev run** — §16.3 (`serve` + `npm run dev` + `queue:work`
         ++ `schedule:work`).
      48 + 
      49 +### 0.5 Kalau stuck / ambiguous
      50 +- **Cek §15.0 dulu** — PM sudah putus default. 9 DECIDED (pakai
         + langsung), 3 NEEDS FOUNDER (pakai fallback dev-safe, jangan tu
         +nggu).
      51 +- **Cek engineering pattern §17** — snippet verbatim dari `solu
         +usi-main`, annotasi "B2B CHANGE".
      52 +- **Mirror B2C kalau ragu** — `/Users/mekari/soluusi-main` = gr
         +ound truth. Preserve typo (merge parity).
      53 +- **Jangan nebak stack** — §12 locked. Jangan add Redis/Next.js
         +/NestJS/Auth0/PostgreSQL.
      54 +- **Output code Ponytail-style** — code first, ≤3 line penjelas
         +an, mark simplifikasi `ponytail:`.
      55 + 
      56 +---
      57 + 
      58  ## Daftar Isi
      59  1. Executive Summary & Vision
      60  2. Problem & Opportunity

⏺ Now update the TOC to include §0, §15.0, §16, §17:

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 4 lines, removed 1 line
      56  ---
      57  
      58  ## Daftar Isi
      59 +0. Start Here (untuk coding agent — baca DULU)                 
      60  1. Executive Summary & Vision
      61  2. Problem & Opportunity
      62  3. Goals & Success Metrics
     ...
      71  12. Tech Stack (Locked — match soluusi-main)
      72  13. Roadmap & Phases
      73  14. Risks & Mitigations
      73 -15. Open Questions 
      74 +15. Open Questions — **15.0 PM Decisions (resolusi default)**
      75 +16. Running Tech (cara jalanin — exact)
      76 +17. Engineering Patterns dari soluusi-main (mirror + adapt)
      77  - Appendix A — Traceability: Playbook → PRD
      78  - Appendix B — Glossary
      79  - Appendix C — B2C Codebase Audit → B2B Platform Deltas

  Thought for 11s (ctrl+o to expand)

⏺ Cross-doc consistency check — verify §0.4 references (E.1 = 11 items, E.2 = 5
  items) match actual appendix content:

  Thought for 11s, read 1 file (ctrl+o to expand)

⏺ Update(Soluusi B2B Platform - PRD.md)
  ⎿  Added 1 line, removed 1 line
      1193  | 1 | Webhook NO signature verify (`MidtransController@callba
            ck`) | Tambah `Midtrans\Notification` verify pakai `server_ke
            y` sebelum update `payment_status`; reject+log mismatch | 🔒 
            SECURITY — wajib |
      1194  | 2 | gcal failure fatal (session `waiting` meski paid) | try
            /catch; status→`scheduled` regardless; `google_calendar_event
            _id` nullable; retry via queue; log | 🔒 DATA-LOSS — wajib |
      1195  | 3 | `payment_status` enum loose (raw SQL alter) | Blueprint
             `$table->enum('payment_status', ['waiting','verified','cance
            led'])` di migration awal B2B | 📐 clean |
      1196 -| 4 | `profit_sharing` int ambigu (% / bps / rupiah) | Verify
           - di controller B2C; B2B pakai field baru `klinik_markup` (sem
           -antics beda) | ❓ VERIFY (§15.9) |
      1196 +| 4 | `profit_sharing` int ambigu (% / bps / rupiah) | Verify
           + di controller B2C; **preserve field** (merge parity) tapi B2
           +B commission calc pakai field baru `klinik_markup_rate` + `sp
           +lit_settlements` (§15.9, §15.0) | ❓ VERIFY → ✅ DECIDED (§15
           +.0) |
      1197  | 5 | Typos `consultan_id`/`INTERVIEW`/`discount_precentage`/
            `merchat_id` | **Pertahankan** di tabel copas (merge parity);
             ejaan benar di tabel B2B baru | 📐 parity |
      1198  
      1199  ### E.3 Merge Architecture (pisah sekarang, nyatu nanti)

  Thought for 5s (ctrl+o to expand)

⏺ Bash(cd "/Users/mekari/soluusi-b2b-beforemarge" && echo "=== split_settlements
      refs ===" && grep -n "split_settlements" "Soluusi B2B Platform - PRD.md"
      && echo "===…)
  ⎿  === split_settlements refs ===
     552:    R11 --> R12[Log immutable split_settlements]
     651:| COMMISSION | `payments` (reuse) | `split_settlements`, `hap_comm
     … +94 lines (ctrl+o to expand)
     
  Thought for 12s (ctrl+o to expand)

⏺ Cross-doc consistency verified — all key terms (split_settlements,
  klinik_markup_rate, free_trial, §11.6 signature/gcal, §11.7 UU PDP) resolve
  consistently across §0, §15.0, §16, §17, and appendices. Now the final step —
  update memory so a future session knows the PRD is the self-contained entry
  doc:

  Read 1 file, recalled 1 memory, wrote 2 memories (ctrl+o to expand)

  Thought for 7s (ctrl+o to expand)

⏺ Done. PRD is now v1.2 — self-contained for the coding agent.
     
  Added to /Users/mekari/soluusi-b2b-beforemarge/Soluusi B2B Platform - PRD.md:

  - §0 Start-Here — execution index for the coding AI (no session context
  assumed): what to build, Ponytail rules + NEVER-simplify list, read-order
  table (5 docs), build execution order (8 steps), if-stuck routing (§15.0 → §17
  - §15.0 PM Decisions — resolves all 13 [OPEN]: 9 DECIDED (stack match,
  cross-sell attribution, konsultan=flat-net-fee, white-label depth MVP,
  associate-only, B2C integration Phase 3, profit_sharing preserve-but-ignore,
  split_settlements new table, free_trial voucher, klinik categories). 3 NEEDS 
  FOUNDER — all non-blocking for dev (MHCU licensing/M5 Phase 2, pricing use
  indikatif+config, gcal SA Soluusi ops/dev uses OAuth-user). 
  - §16 Running Tech — exact PHP 8.0+/Laravel 9/ext/Node18+, install commands
  (create-project :^9.0 → replace composer+package.json → key:generate →
  storage:link → copas → .env → migrate → seed → serve+npm dev), dev daily
  (serve+npm dev+queue:work+schedule:work), test (ScaffoldSmoke filter), prod
  (PHP-FPM+nginx+MySQL+supervisor+config:cache+schedule:run cron), SQLite local
  caveat.
  - §17 Engineering Patterns — 7 verbatim snippets from
  /Users/mekari/soluusi-main with "B2B CHANGE" annotations: Midtrans createVa
  (guest not auth), callback (Notification verify WAJIB + gcal non-fatal + split
  row + dual-confirm trigger), GoogleCalendarService (SA migrate + brand 
  param), consultation_sessions schema (verbatim + typo parity), Mailable Bayar
  (brand param), voucher logic (mirror + free_trial), schedule lock + expiry
  sweep.
  - TOC updated, version → v1.2, revision note added.

  Consistency: split_settlements, klinik_markup_rate, free_trial, §11.6
  signature/gcal, §11.7 UU PDP all resolve consistently across
  §0/§15.0/§16/§17/appendices. E.1=11 items, E.2=5 items confirmed match §0.4
  claims. E.2#4 reconciled to klinik_markup_rate (was klinik_markup).

  Memory: added prd-entry-doc.md (PRD v1.2 = entry doc for coding agent) +
  MEMORY.md index pointer.

  → Skipped: editing the supporting docs (dev-prep, klinik spec) to point back
  to PRD §0 — redundant, §0.3 read-order table already orients the agent. Add
  cross-pointers only if the coding agent gets lost between docs.
  → Skipped: re-verifying every snippet against soluusi-main line-by-line a
  second time — patterns were captured verbatim last turn (MidtransController
  471L, GoogleCalendarService 201L, migration, Bayar mailable). Re-verify only
  if the coding agent flags a mismatch at build time.

  Doc siap dilempar ke coding agent.

✻ Cooked for 15m 48s

※ recap: Building Soluusi B2B platform docs so a separate coding AI can run 
  them; finished PRD v1.2 with Start-Here index, PM decisions, running tech, and
  soluusi-main engineering patterns. Next: hand the PRD to the coding agent to 
  scaffold the Klinik vertical.

────────────────────────────────────────────────────────────────────────────────
❯  
────────────────────────────────────────────────────────────────────────────────
  Press Ctrl-C again to exit

Resume this session with:
claude --resume 9cd952eb-76a5-48a0-9c13-f4481e436ef4

┌───────────────────────────────────────────────────────────────>
│~/soluusi-b2b-beforemarge 
└> opencode                                              󱙺 FORGE  glm-4.6 HIG 
pppppp
  ┃                                                                             
  ┃    17           <div class="text-2xl font-bold">{{ $noShowCount }}</div>    
  ┃    18           <div class="text-xs text-gray-500">No-show</div>            
  ┃    19       </div>                                                          
  ┃    20       <div class="bg-white border rounded-lg p-4">                    
  ┃    21 -         <div class="text-2xl font-bold">Rp{{ number_format(         
  ┃         $marginSettled + $marginPending, 0, ',', '.') }}</div>              
  ┃    22 -         <div class="text-xs text-gray-500">Margin klinik (settled   
  ┃         + pending)</div>                                                    
  ┃    21 +         <div class="text-2xl font-bold text-emerald-700">Rp{{       
  ┃         number_format($marginSettled, 0, ',', '.') }}</div>                 
  ┃    22 +         <div class="text-xs text-gray-500">Fee diterima (margin     
  ┃         markup, settled)</div>                                              
  ┃    23       </div>                                                          
  ┃    24 +     <div class="bg-white border rounded-lg p-4">                    
  ┃    25 +         <div class="text-2xl font-bold text-amber-600">Rp{{         
                                                                                
  ┃                                                                             
  ┃                                                                             
  ┃                                                                             
  ┃  Build · 9inf/kimi-k3 9router                                               
  ╹▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀  
                                                       290.8K  ctrl+p commands  
                                                                                
