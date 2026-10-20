> **Internal Document — Confidential · Do Not Distribute**
> Versi 1.0 · 4 Agustus 2026
> Klinik Vertical — Persiapan Development (stack parity + copas manifest + build/fix list)

Dokumen ini = persiapan sebelum ngoding. Bukan spec (spec = `Soluusi B2B - Klinik Vertical Spec.md` v1.1). Ini = "cara mulai": stack yang dipasang, file yang dicopas dari `soluusi-main`, yang dibangun baru, yang diperbaiki, dan arsitektur pisah-sekarang/nyatu-nanti.

Ground truth: `/Users/mekari/soluusi-main` (Laravel 9, PHP 8, production B2C). B2B klinik = mirror stack-nya, copas komponen domain konsultasi, skip semua mesin SEO/blog/CMS/AI soluusi.com.

---

## 1. Prinsip

1. **Stack identik** dengan soluusi-main — production, akan nyatu ke sana. Beda stack = pain merge nanti.
2. **Copas komponen domain konsultasi** dari soluusi-main = hemat cost. Jangan re-implement yang udah ada (Ponytail rung 2: reuse).
3. **Pisah dulu, nyatu nanti** — B2B = repo Laravel sendiri sekarang; dirancang supaya port-nya ke soluusi-main jadi no-op di skema domain.
4. **Skip mesin content soluusi.com** — semua SEO/blog/CMS/AI/ga4/gsc/deepseek/gemini = irrelevant ke klinik vertical. Jangan copas.
5. **Jangan bawa bug B2C** — 5 fix wajib (§6).

---

## 2. Stack Lock (samakan persis)

### 2.1 composer.json — `require`

```json
"require": {
    "php": "^8.0",
    "azishapidin/indoregion": "^3.0",
    "doctrine/dbal": "^3.8",
    "fruitcake/laravel-cors": "^2.0.5",
    "google/apiclient": "2.15",
    "guzzlehttp/guzzle": "^7.2",
    "intervention/image": "^3.11",
    "laravel/framework": "^9.0",
    "laravel/sanctum": "^2.14",
    "laravel/socialite": "^5.16",
    "laravel/tinker": "^2.7",
    "midtrans/midtrans-php": "^2.5",
    "xendit/xendit-php": "^4.1"
}
```

### 2.2 composer.json — `require-dev`

```json
"require-dev": {
    "fakerphp/faker": "^1.9.1",
    "laravel/sail": "^1.0.1",
    "mockery/mockery": "^1.4.4",
    "nunomaduro/collision": "^6.1",
    "phpunit/phpunit": "^9.5.10",
    "spatie/laravel-ignition": "^1.0"
}
```

### 2.3 package.json

```json
{
    "private": true,
    "type": "module",
    "scripts": { "build": "vite build", "dev": "vite" },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^3.1",
        "tailwindcss": "^4.0.0",
        "vite": "^8.0.0"
    }
}
```

### 2.4 Runtime

- PHP **8.0+** (samakan; jangan 8.1+ exclusive), ekstensi: pdo, pdo_sqlite/mysql, mbstring, xml, ctype, json, bcmath, fileinfo, gd (intervention/image), curl.
- Node **18+**, npm.
- DB local: SQLite (sesuai `.env.example` B2C) → prod: MySQL (sesuai soluusi-main prod).
- Queue driver: `database` (gcal + mail async). Session: `database`. Cache: `database`.

---

## 3. Arsitektur: Pisah Sekarang, Nyatu Nanti

B2B = **app Laravel sendiri** sekarang (repo `soluusi-b2b` terpisah). Strategi merge ke `soluusi-main` nanti = **zero-conflict**:

| Komponen | Strategi | Alasan |
|---|---|---|
| **Tabel domain identik** (`consultants`, `categories`, `consultant_packages`, `schedules`, `consultation_sessions`, `vouchers`, `consultant_ratings`, `users`, `roles`, dll) | Copas migration + model **verbatim** (pertahankan typo B2C — `consultan_id`, `discount_precentage` — supaya skema = identik, merge = no-op) | Saat merge, tabel udah ada di soluusi-main → tinggal skip migration B2B, port controller/view/route |
| **Tabel B2B-only** (`kliniks`, `klinik_admins`, `klinik_bookings`, `split_settlements`, `hap_commissions`, `markup_rules`) | Buat baru, nama distinktif (prefix `klinik_` / domain baru), **tidak collide** dengan nama B2C | Additive merge: migration B2B jalan di shared DB, gak ganggu tabel B2C |
| **Model domain** (`App\Models\Consultant` dst) | Namespace sama `App\Models\` | Saat merge, model = file yang sama → langsung reuse |
| **Model B2B-only** | `App\Models\Klinik\...` sub-namespace | Rapi, ganggu segmen B2C |
| **Route** | B2B route group `Route::prefix('klinik')->middleware('klinik.auth')` | Saat merge = tambah satu route group di soluusi-main, gak overlap |
| **Config** | B2B config file sendiri (`config/klinik.php`, `config/whitelabel.php`, `config/split.php`) + copas `config/midtrans.php`/`xendit.php` | Tambah config, gak modifikasi config B2C |
| **Mail** | Sub-namespace `App\Mail\Klinik\...` + blade `resources/views/emails/klinik/...` | Gak collide dengan 14 mailable B2C |

**Typos B2C dipertahankan di tabel yang dicopas** (`consultan_id`, `discount_precentage`, `merchat_id` di config) — mending pertahankan daripada bikin skema beda terus merge nanggung. Rename join cleanup belakangan sebagai migration terpisah di soluusi-main, bukan tanggung jawab B2B sekarang. `// ponytail: preserve B2C typo for schema parity on merge`.

---

## 4. Copas Manifest (dari `soluusi-main` → B2B)

Path source = `/Users/mekari/soluusi-main/`. Status: ✅ copas verbatim · 🔧 copas + adaptasi · 🆕 build baru (§5) · ❌ skip.

### 4.1 Migrations — domain konsultasi (✅ verbatim, skip SEO/blog/CMS)

**Copas (urutan timeline jaga):** `2014_10_12_* users`, `password_resets`, `2019_08_19 failed_jobs`, `2019_12_14 personal_access_tokens`, `2023_11_08_155210 consultants`, `2023_11_08_155436 certificates`, `2023_11_09_021339 companies`, `2023_11_09_022656 schedules`, `2023_11_09_032135 roles`, `2023_11_09_032559 categories`, `2023_11_09_153002 skills`, `2023_11_09_153137 social_media`, `2023_11_09_153436 education`, `2023_11_09_153649 departements`, `2023_11_09_153753 benefits`, `2023_11_09_154023 consultant_packages`, `2023_11_09_154512 consultant_skills`, `2023_11_09_154520 consultant_social_media`, `2023_11_09_154536 consultant_education`, `2023_11_09_155601 consultation_sessions`, `2023_11_09_160613 add_relation_each_table`, `2023_11_20 outputs`, `2023_11_26 alter consultation_sessions + consultant_id`, `2023_11_27 alter + payment_method + link_meeting`, `2023_12_27 add description`, `2023_12_28 deleted_at consultants`, `2023_12_29 consultant_education description`, `2024_01_17 consultants is_active`, `2024_01_23 modify consultation_sessions status enum`, `2024_04_28 companies location nullable`, `2024_04_28 consultant_educations degree nullable`, `2024_04_28 consultant_packages result_type json`, `2024_04_29 consultants bank + account_number`, `2024_04_29 users avatar`, `2024_04_29 add uuid to all`, `2024_05_03 category_skills`, `2024_05_03 categories code_category`, `2024_05_03 consultant_categories`, `2024_05_07 consultants drop category_id`, `2024_05_07 category_skill type nullable`, `2024_05_10 consultants status + reject_reason`, `2024_05_10 users is_active`, `2024_05_10 consultant_ratings`, `2024_05_10 users phone_number` *(note: di-drop di 2024_12_02)*, `2024_05_11 consultant_companies + alters`, `2024_05_11 companies drop started_at dst`, `2024_05_11 vouchers`, `2024_05_23 payments`, `2024_06_06 vouchers discount_precentage nullable`, `2024_06_13 alter users`, `2024_06_18 consultation_sessions voucher_id`, `2024_06_19 snaptoken nullable`, `2024_06_27 expired_at`, `2024_06_28 url_snap`, `2024_07_14 drop id many-to-many`, `2024_07_28 consultation_sessions review_text`, `2024_07_28 consultants decision_at`, `2024_11_29 social_accounts`, `2024_12_02 remove phone_from_users`, `2025_02_02 update users and consultants`, `2025_06_07_010354-7 provinces/regencies/districts/villages` *(indoregion)*, `2025_06_07_022002 add regency_id to users`, `2025_06_29 add solution to consultation_sessions`, `2025_07_02 subcategories + consultant_subcategories`, `2025_07_29 alter categories code_category length`, `2025_08_07 composite PK consultant_categories`, `2025_08_17 deleted_at consultant_packages`, `2025_09_20 add gender to users`, `2026_02_15 add google_calendar_event_id to consultation_sessions`, `2026_02_26 add date_of_birth to users`, `2026_03_24 add complaint to consultation_sessions`, `2026_04_12 add created_by to schedules`, `2026_06_04 add package_price + voucher_amount to consultation_sessions`, `2026_06_07 add completed_at to consultation_sessions`, `2026_07_10 sessions table`, `2026_07_11 cache table`.

**❌ Skip (mesin content soluusi.com, irrelevant klinik):** semua migration `2026_04_30_16*` sampai `2026_08_04_14*` = seo_keywords, seo_keyword_rankings, location_pages, blog_categories, blog_posts, blog_tags, user_sessions, seo_daily_summaries, seo_transactions, seo_audits, seo_gsc_summaries, backlink_outreach, backlinks, article_templates, blog_post_edits, style_guides, blog_comments. Juga `2024_06_07 user_topses` + alters (TOPSIS decision-support, bukan klinik), `2024_07_25 banners` + `2025_06_07 add_url_banners` (CMS landing), `2025_06_14 campaign1_investasi` (marketing), `2025_06_15 manual_reviews` + `2025_08_05 add_consultation_at` (testimonial — **optional**, decide later), `2025_07_27 add_landing_page_columns_to_categories` + `2026_06_21 cms_columns_to_consultants` + `2026_07_11 blog_subscription_to_consultants` (CMS).

### 4.2 Models — ✅ verbatim

`User`, `Role`, `SocialAccount`, `Consultant`, `ConsultantPackage`, `Schedule`, `ConsultationSession`, `Voucher`, `ConsultantRating`, `Category`, `CategorySkill`, `ConsultantCategory`, `Skill`, `ConsultantSkill`, `SocialMedia`, `ConsultantSocialMedia`, `Education`, `ConsultantEducation`, `Company`, `ConsultantCompany`, `Departement`, `Benefit`, `Certificate`, `Subcategory`, `ConsultantSubcategory`, `Output`, `Payment`, `Province`, `Regency`, `District`, `Village`, `Model` (base, punya custom boot UUID/cast).

❌ Skip: semua `Seo*`, `Blog*`, `Backlink*`, `ArticleTemplate`, `StyleGuide`, `LocationPage`, `UserSession`, `UserTopsis`, `Banner`, `ManualReview` (optional), `Campaign`/investasi.

### 4.3 Controllers — 🔧 copas + adaptasi

| Controller | Status | Adaptasi |
|---|---|---|
| `MidtransController` | 🔧 | **Tambah signature verify (§6.1)**, **tambah split-payment (§5.1)**, route callback B2B terpisah, hide Soluusi di receipt |
| `AuthController` | 🔧 | SSO Google (Socialite) untuk **admin klinik + konsultan**; **customer = guest tanpa login** (Model C) |
| `JadwalController` | 🔧 | Manajemen slot konsultan — reuse; tambah endpoint "admin-generate-link" (§5.5) |
| `ConsultantController` | 🔧 | Subset: `set-note`, `set-done`, `set-link-meeting`, `set-scheduled-meeting` → adaptasi **dual-confirm (§5.4)**: konsultan mark-done → sistem verify → release fee + HAP |
| `HomeController` | 🔧 | Subset: dashboard + transaction → adaptasi jadi **klinik earnings/payout dashboard (§5.6)**; `consultantPayroll` → `klinikPayroll` |
| `VoucherController` | ✅ | Voucher CRUD + `getNominalWithVoucher` logic — reuse; note: B2C voucher = `potongan`(flat)/`diskon`(%), **no free-trial** (lihat [OPEN-4b]) |
| `KategoriController` | 🔧 | Category + skill CRUD — reuse; seeder extend kategori kesehatan (§5.8, [OPEN-22]) |
| `KonsumenController` | 🔧 | Flow checkout B2C → jadi **white-label checkout link flow** (§5.5). Customer isi: nama/email/telp/DOB/regency/gender + complaint → bayar langsung |
| `XenditController` | ❌ | **Dead/stub di B2C.** Jangan copas. Kalau split pakai Xendit Marketplace, build fresh (§5.1) |

❌ Skip: `BlogController`, `SitemapController`, `CampaignController`, `FrontController`, `TopsisController`, `LocationController`, `BannerController`, `ExperienceController`, `EducationController`, `CompanyController`, `ConsultantSubcategoryController` (optional), `SubkategoriController` (optional).

### 4.4 Services — 🔧

| Service | Status | Adaptasi |
|---|---|---|
| `GoogleCalendarService` | 🔧 | **Start dari sini**, lalu migrasi OAuth-user → service account (§5.3). Parameterize `summary` `"Soluusi - {package}"` → `"{klinik_brand} - {package}"`. Catch gcal failure non-fatal (§6.2) |

❌ Skip semua service SEO/AI: `AiContentService`, `BlogCommentService`, `DynamicPromptBuilder`, `EditCaptureService`, `Ga4Service`, `GscService`, `PatternExtractionService`, `SchemaService`, `SeoScoringService`, `DynamicPromptBuilder`.

### 4.5 Mail — 14 mailables + blades — 🔧 copas lalu parameterize "Soluusi" → brand

**Mailables** (copas ke `app/Mail/Klinik/`): `Client/{Bayar,Chk,LinkMeeting,Review,SudahBayar}`, `Konsultan/{Chk,DataDiterima,Diterima,Ditolak,LinkMeeting,Pendaftaran,Review,SudahBayar,TerimaKasihAtasInterview}`.

**Blades** (copas ke `resources/views/emails/klinik/`): 16 file di `resources/views/emails/{client,consultant}/*.blade.php` (termasuk `reminder.blade` di kedua subfolder — tidak ada mailable `Reminder`, kemungkinan via `Notification`; verify saat adapt).

**Adaptasi (white-label, §5.2):** semua subject prefix `"Soluusi - "` → `"{klinik_brand} - "`; global `from()` jadi per-mailable `from(config('whitelabel.email'))`. Brand di-resolve dari `klinik_id` di context booking → `App\Models\Klinik::brand`.

### 4.6 Config — ✅/🔧

- ✅ `config/midtrans.php` (verbatim; key `merchat_id` pertahankan — §3 typo parity; verify controller baca key mana sebelum "fix").
- ✅ `config/xendit.php` (verbatim, `auth_key`).
- 🔧 `config/services.php` — **copas hanya block `google` + `google_calendar`**. ❌ Drop: `postmark`, `resend`, `ses`, `slack`, `zhipu_ai`, `deepseek`, `gemini`, `ga4`, `gsc` (semua = mesin content/AI/SEO soluusi.com).
- 🆕 `config/klinik.php` (brand per-klinik, default neutral), `config/whitelabel.php` (default brand, from-email, domain), `config/split.php` (provider: midtrans_connect | xendit_marketplace, split rules).

### 4.7 Helpers & lain — ✅

- `app/Helpers/CStr.php` (string helper) — ✅ verbatim.
- `app/Http/Middleware/CheckRole.php` — 🔧 (tambah role `klinik_admin`).
- `app/Http/Middleware/Authenticate.php`, `RedirectIfAuthenticated.php` — ✅.

### 4.8 indoregion + Seeders — 🔧

- ✅ Package `azishapidin/indoregion ^3.0` + migrations prov/regency/district/village + model.
- 🔧 `database/seeders/CategorySkillSeeder.php` — copas lalu **extend kategori kesehatan** (§5.8, [OPEN-22]). B2C: Psikologi/Karir/Bisnis/Keuangan/Hukum/Pajak. Klinik butuh: Psikolog Klinis, Psikolog Pendidikan, Konselor, Gizi, Fisioterapi (finalize di [OPEN-22]).
- ✅ `database/seeders/DatabaseSeeder.php` — adapt (skip seed blog/SEO).

---

## 5. Build-NEW (B2B-specific, tidak ada di B2C)

| # | Komponen | Spec ref | Catatan |
|---|---|---|---|
| 5.1 | **Split-payment gateway** | §10.3 | Midtrans Connect API **atau** Xendit Marketplace (`for-user` header). Net → Soluusi, margin → klinik. Settlement rows di `split_settlements` (tabel baru). B2C punya zero split — build dari nol. |
| 5.2 | **White-label config + parameterization** | §10.8 | `kliniks` table (brand, domain, logo, from-email, gcal_id). 14 mailable + gcal string baca brand dari context. Domain neutral/klinik CNAME, **bukan** soluusi.com. |
| 5.3 | **Service-account Google Calendar** | §10.5 | B2C pakai OAuth-user (`soluusi@gmail.com` refresh token). B2B: service account + Google Workspace Delegation, satu calendar per klinik (atau Soluusi master calendar dgn metadata klinik). Migration path: start dari OAuth-user copas (§4.4), lalu ganti. |
| 5.4 | **Dual-confirm anti-fraud** | §9 | Konsultan mark-done → sistem/Soluusi verify (review deliverable) → release fee konsultan + trigger HAP. B2C: konsultan langsung mark-done, no verify. |
| 5.5 | **Admin-generate-link booking flow** | §7.1 | Admin klinik pilih konsultan + jadwal di dashboard → sistem generate **white-label checkout link** (signed, expiring) → admin kirim ke customer → customer buka link, isi data + bayar langsung. Default MVP. |
| 5.6 | **Klinik RBAC + earnings/payout dashboard** | §11 | Role `klinik_admin` (partner). Dashboard: aggregate bookings, margin earned, payout request. Akses data: aggregate only (customer PII = Soluusi hold, konsultan hold session detail). |
| 5.7 | **HAP 3-stage commission engine** | §12 | Rp1M total: Rp250k @5 sesi, Rp250k @25 sesi, Rp500k @40 sesi/90d. Track per-konsultan, trigger stage, settle. Tabel `hap_commissions`. |
| 5.8 | **Markup + cap per price range** | §8 | Soluusi set net price; klinik mark up; cap per range. Tabel `markup_rules`. Enforce di checkout link generation. |
| 5.9 | **Pasien Model C (checkout-guest)** | §6 | Customer **tidak buat akun**. Soluusi kumpul PII transaksional: nama/email/telp/DOB/regency_id/gender + complaint di checkout. B2C: user register akun. B2B: no account, link-based. |
| 5.10 | **Anti-circumvention** | §10.10 | MoU + teknis: hide Soluusi (domain/brand/receipt/email/gcal/konsultan profile), neutral domain, no link ke soluusi.com. |
| 5.11 | **No-show + check-in** | §9 | B2C: tidak ada. B2B: check-in window, no-show policy (fee split?). [OPEN] no-show fee detail. |

Tabel baru (additive, prefix `klinik_`/domain): `kliniks`, `klinik_admins` (pivot user↔klinik), `klinik_bookings` (extend consultation_sessions atau separate link), `split_settlements`, `hap_commissions`, `markup_rules`, `checkout_links` (signed token + expiry + klinik_id + konsultan_id + schedule_id + package_uuid + markup_snapshot).

---

## 6. Fix-List (jangan bawa bug B2C)

| # | Bug B2C | Fix di B2B | Sifat |
|---|---|---|---|
| 6.1 | **Webhook NO signature verify** — `MidtransController@callback` terima callback tanpa `Notification::verifySignature()`. Hole: fake settlement. | Tambah `Midtrans\Notification` verify pakai `server_key` sebelum update `payment_status`. Reject + log jika mismatch. | 🔒 SECURITY — wajib |
| 6.2 | **gcal failure fatal** — kalau `GoogleCalendarService` throw, session tetap `waiting` meski pembayaran verified. Customer bayar tapi gak ter-schedule. | Wrap gcal di try/catch; status → `scheduled` regardless; `google_calendar_event_id` nullable; retry via queue; log. gcal = best-effort, bukan blocker. | 🔒 DATA-LOSS — wajib |
| 6.3 | **`payment_status` enum loose** — B2C alter jadi enum tapi via raw SQL. | Pakai Blueprint `$table->enum('payment_status', ['waiting','verified','canceled'])` langsung di migration awal B2B (lebih clean, sama value). | 📐 clean |
| 6.4 | **`profit_sharing` int ambigu** — `consultants.profit_sharing` int, tak jelas % / basis-points / rupiah. Verify pemakaian di B2C controller sebelum reuse. | B2B pakai field **baru** `klinik_markup` di `kliniks`/`markup_rules`, bukan reuse `profit_sharing` (semantics beda: B2C = bagi-hasil konsultan, B2B = margin klinik). | ❓ VERIFY |
| 6.5 | **Typo** `consultan_id` (FK consultant_packages), `INTERVIEW` (enum value, vs `interview`), `discount_precentage`, `merchat_id`. | **Pertahankan** di tabel copas (§3 parity). Di tabel B2B baru, pakai ejaan benar. | 📐 parity |

---

## 7. Env Vars (B2B `.env`)

```env
APP_NAME="Klinik Konsultan"          # neutral default, NOT "Soluusi"
APP_ENV=local
APP_KEY=                             # php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite                  # local; prod=mysql

SESSION_DRIVER=database
QUEUE_CONNECTION=database            # gcal + mail async
CACHE_STORE=database

# Midtrans (sandbox dev, prod live)
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_MERCHANT_ID=
MIDTRANS_IS_3DS=true

# Xendit (untuk split Marketplace, meski B2C dead)
SECRET_KEY_XENDIT=

# Google Calendar — START OAuth-user (copy B2C), MIGRATE ke service account (§5.3)
GOOGLE_CALENDAR_CLIENT_ID=
GOOGLE_CALENDAR_CLIENT_SECRET=
GOOGLE_CALENDAR_REFRESH_TOKEN=
GOOGLE_CALENDAR_ID=primary

# Socialite SSO (admin klinik + konsultan login)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=

# Mail (white-label from = klinik brand, bukan Soluusi)
MAIL_MAILER=log                       # dev; prod: ses/postmark
MAIL_FROM_ADDRESS="no-reply@klinik-brand.id"
MAIL_FROM_NAME="${APP_NAME}"

# B2B-new
WHITE_LABEL_DEFAULT_BRAND="Klinik Konsultan"
WHITE_LABEL_DOMAIN=checkout.klinik-brand.id
SPLIT_PROVIDER=midtrans_connect       # | xendit_marketplace
SPLIT_ON=true
```

---

## 8. Setup Checklist (urutan eksekusi)

1. `laravel new soluusi-b2b-klinik` (Laravel 9 installer; kalau installer default 10+, pin: `composer create-project laravel/laravel:^9.0 soluusi-b2b-klinik`).
2. Replace `composer.json` require/require-dev + `package.json` dengan §2.1–2.3. `composer install` + `npm install`.
3. `php artisan key:generate`.
4. Copas §4 sesuai manifest: migrations (skip SEO/blog/CMS), models, controllers adapt, services, mail+blades, config (midtrans/xendit/services), helpers, middleware, indoregion, seeders.
5. Bikin config baru §4.6: `config/klinik.php`, `config/whitelabel.php`, `config/split.php`.
6. Isi `.env` §7. Set Midtrans sandbox keys. Set gcal OAuth-user test creds dulu (service account nanti).
7. `php artisan migrate` (pastikan semua migration domain jalan di SQLite; resolve jika ada MySQL-only raw SQL — beberapa `alter enum` B2C mungkin raw, adapt untuk SQLite local).
8. `php artisan db:seed` (CategorySkillSeeder extend kategori kesehatan).
9. `php artisan serve` + `npm run dev`.
10. Jalankan smoke check §9.

---

## 9. Smoke Check (satu runnable — Ponytail: smallest thing that fails if logic breaks)

Sebelum dev fitur dimulai, scaffold harus lulus ini. Taruh di `tests/Feature/ScaffoldSmokeTest.php` (phpunit native, no framework tambahan):

```php
// ponytail: one smoke per Ponytail rule. Framework: phpunit (already in deps).
public function test_scaffold_boots_and_core_domain_writes()
{
    $this->assertNotNull(config('midtrans.server_key'));
    $konsultan = Consultant::factory()->create(['status' => 'accept']);
    $pkg = ConsultantPackage::factory()->create(['consultan_id' => $konsultan->id]); // typo parity
    $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);
    $ses = ConsultationSession::create([
        'consultant_id' => $konsultan->id,
        'schedule_id' => $sched->id,
        'package_uuid' => $pkg->uuid,
        'payment_status' => 'waiting',
        'status' => 'waiting',
        // + complaint, DOB, regency_id, gender per Model C
    ]);
    $this->assertDatabaseHas('consultation_sessions', ['id' => $ses->id]);
}
```

Plus 3 manual check (gak bisa automate tanpa live creds):
- **Midtrans Snap URL** generate di sandbox (`create-va` flow) → buka URL → selesai.
- **gcal event** create di test calendar → event muncul.
- **Webhook signature** — kirim callback dgn signature salah → reject (§6.1 verify).

---

## 10. [OPEN] items (dibawa dari spec)

- [OPEN-21] Scheduling: admin-pick (recommended MVP) vs konsultan-pick.
- [OPEN-22] Kategori: extend B2C (Psikologi/Karir/Bisnis/Keuangan/Hukum/Pajak) dengan kategori kesehatan klinik — finalize daftar.
- [OPEN-4b] Voucher free-trial: B2C tidak punya; B2B butuh? Belum diputuskan.
- [OPEN] No-show fee detail (§5.11): split kembali atau hangus?
- [OPEN-23] **NEW dari prep ini:** `profit_sharing` B2C semantics — verify di controller sebelum decide reuse vs field baru (§6.4).
- [OPEN-24] **NEW:** `payments` table B2C — per-row payment attempt; B2B split butuh `split_settlements` baru atau extend `payments`? (additive recommended: `split_settlements` baru, FK ke `payments`).
- [OPEN-25] **NEW:** gcal migration OAuth-user → service account — butuh Google Workspace admin (delegation scope `https://www.googleapis.com/auth/calendar`). Siapa pegang service account? Soluusi ops.

---

## 11. Tidak dikerjakan sekarang

- Dev fitur (§5) — setelah scaffold §8 selesai + smoke §9 lulus.
- Update PRD (`Soluusi B2B Platform - PRD.md`) tech-stack section dengan treatment ground-truth B2C yang sama — opsional, konfirmasi user.
- Rename typo B2C (§6.5) — cleanup join di soluusi-main belakangan, bukan tanggung jawab B2B.
