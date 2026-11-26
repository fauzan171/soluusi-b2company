# Research & Architecture — Wellbeing Product (katalog paket + band pricing + assign program)

Status: DRAFT for /kickoff
Branch: `feat/wellbeing-product` (base 712 test hijau)
Source of truth produk: `docs/plan/PRODUCT-DOC-SUMMARY.md` (Google Doc owner, tidak di-fetch ulang)

---

## 1. Survey admin Soluusi SAAT INI

### 1.1 Auth, role, middleware

- Role = tabel `roles` + `users.role_id` (satu role per user, **bukan** banyak role).
  `app/Models/Role.php`, `app/Models/User.php:75` (`role()` hasOne).
- Gate role: `app/Http/Middleware/CheckRole.php` — middleware alias `role:...`
  (dipakai di `routes/web.php:10` comment, `role:admin` = superadmin).
  Tidak ada package spatie; semua role string di DB (`role->name`).
- Superadmin routes: `routes/web.php:251-353` — prefix `admin.`, middleware
  `['auth', 'role:admin']`. Contoh controller: `AdminDashboardController`,
  `AdminPerusahaanController` (provisioning akun HR + perusahaan),
  `Admin\Tenant\*` (list/search/edit 3 vertikal), `Admin\PerusahaanActivationController`
  (aktivasi program CAP = flip `is_active` + `mou_at` + PervormaService),
  `AdminAssignmentController` (assignment konsultan + workhours), `Klinik\KlinikPackageController`
  (paket klinik B2B — CRUD superadmin, pola terdekat untuk katalog wellbeing).
- Audit trail superadmin: `app/Models/AdminAction.php` (`AdminAction::record($adminId,
  $action, $target, $payload)`, append-only) + `admin_actions` migration
  `2026_11_16_000001`. Ada juga `audit_logs` (dipakai QuotaService refund).
- Layout admin: `resources/views/admin/dashboard.blade.php` meng-extend
  `layouts.app`; nav per-route sudah ada di `resources/views/layouts/app.blade.php:59`
  (`request()->routeIs('admin.*')`). Views admin flat di `resources/views/admin/`
  + subdir `tenants/{klinik,perusahaan,sekolah}`. Blade + Tailwind utility,
  TANPA Filament/Livewire (meski ada comment "Filament" lama di CheckRole —
  Filament tidak terpasang).
- Template pola assign/aktifasi: `PerusahaanActivationController::store`
  (`app/Http/Controllers/Admin/PerusahaanActivationController.php`) —
  flip flag + `AdminAction::record` + flash + `PervormaService::evaluate`.
  Wizard 1-pintu ada: `Admin\ProvisioningController` (3 vertikal + akun admin
  tenant dalam 1 transaksi).

### 1.2 Skema mission existing (M1–M8)

- `packages` + `employee_packages`: migration
  `database/migrations/2026_11_20_000001_create_mission_m1_packages_tables.php`.
  `packages.monthly_quota` = sesi/karyawan/**bulan** (`app/Models/Package.php`).
  `employee_packages` = assignment per **karyawan** (bukan kontrak perusahaan).
- `consultation_quota_ledger`: migration
  `2026_11_20_000002_create_mission_m2_quota_ledger_table.php`, append-only,
  `period_ym "YYYY-MM"`, UNIQUE(`perusahaan_session_id`,`event`),
  events `reserved|used|forfeited|refunded|released`
  (`app/Models/Perusahaan/ConsultationQuotaLedger.php`).
- Kuota = bulanan per karyawan; saldo = `monthly_quota - reserved/used/forfeited
  (distinct session) + released/refunded` per (`employee_package_id`,`period_ym`)
  — `app/Services/QuotaService.php:250-280` (`quota/outflow/inflow`).
- Semua entrypoint sesi (`BookingService::reserve` line 200,
  `EmployeeController::startChat` line 511, consume di `ChatController:73,94`,
  sweep `SweepNoShowSessions:35`) sudah lewat QuotaService — single gate.
- Instrumen assessment = DATA seeder (`assessment_instruments`, migration
  `2026_11_22_000001`, model `AssessmentInstrument.php`) — preseden kuat untuk
  "definitions as data".
- `perusahaans.employee_count` (int) sudah ada
  (`database/migrations/2026_08_13_000001_create_perusahaans_table.php:22`)
  → band karyawan bisa dihitung dari kolom ini (atau `employees()->count()`).
- `perusahaan_sessions.consultation_type` = `chat|online_meeting|one_on_one`
  (migration M4 `2026_11_20_000004`) — 1-on-1 vs chat SUDAH terpisah di level
  sesi; `mode` = `online|offline`.

### 1.3 Tes

- Test quota/mission: `tests/Feature/QuotaServiceTest.php`,
  `QuotaEntryPointsTest.php`, `QuotaLifecycleRegressionTest.php`,
  `W8QuotaEdgeFinalTest.php`, `W8CrossTenantIsolationTest.php` +
  `PerusahaanWellbeingTest.php` (66 mission test).
  Baseline 712 test hijau — perubahan TIDAK boleh break `QuotaService`
  public API (`assignmentFor`, `periodOf`, `balanceFor`, `packageLabel`,
  `reserve`, `consume`, `forfeit`, `refund`, `release`).

---

## 2. Gap analysis: model sekarang vs kebutuhan doc

| Kebutuhan doc (PRODUCT-DOC-SUMMARY) | Skema saat ini | Gap |
|---|---|---|
| 4 paket: Discovery 14d / Foundation 3m / Growth 6m / Impact 12m | `packages` generik, tanpa durasi | Tidak ada kolom durasi/`code` |
| Kuota **kontrak-level** total (unused hangus di akhir kontrak, bukan bulanan) | `monthly_quota` per karyawan per bulan, `period_ym` bulanan | Model periode beda total |
| Option A (MHCU) vs B (1-on-1) **pilihan per kontrak**, mutually exclusive | `consultation_type` per sesi, bebas | Tidak ada kontrak/program entity |
| Expert Chat (terbatas 1–2 expert) — kuota chat **terpisah** dari deep support | Satu kuota campur untuk semua `consultation_type` | Perlu bucket kuota terpisah |
| Harga per band karyawan (5 band: 50–150 … 801–1000), Rp/bulan | Tanpa pricing | Tidak ada tabel harga |
| MHCU kuota per band (5/10/15/20/25 Growth; 10/20/30/40/50 Impact) | — | Tidak ada |
| Webinar count (0/1/3/6), quarterly review flag (Impact) | — | Tidak ada |
| Assessment count + max peserta (Discovery 40) | `perusahaan_assessments` + instrumen ada, tanpa batas paket | Perlu kolom limits |
| Superadmin assign paket+band+option+tanggal ke perusahaan | `employee_packages` per karyawan, tanpa kontrak perusahaan | Perlu entity program/kontrak |
| HR/employee dashboard penuh (8–10 menu) | Portal HR/employee sudah ada (dashboard, wellbeing, assessment, booking) | Out-of-scope phase ini (hanya admin side + data model) |

**Inti gap: tidak ada entity KONTRAK perusahaan.** Semuam model existing
(employee_package + ledger) mengasumsikan kuota bulanan per karyawan.

---

## 3. Desain skema (baru, minimal tapi lengkap)

Prinsip: **jangan sentuh `packages`/`employee_packages`/ledger existing**
(kompatibilitas 712 test). Tambah 3 tabel baru + 2 kolom ke tabel baru saja.

### 3.1 `wellbeing_packages` (katalog 4 paket — DATA, seeder)

```php
Schema::create('wellbeing_packages', function (Blueprint $t) {
    $t->id();
    $t->string('code', 20)->unique();          // discovery|foundation|growth|impact
    $t->string('name');                        // "Discovery", "Foundation"...
    $t->unsignedSmallInteger('duration_days'); // 14 | 90 | 180 | 365
    $t->boolean('is_free')->default(false);    // discovery = true
    // fitur matrix (nullable = tidak termasuk)
    $t->unsignedTinyInteger('assessment_count')->default(0);   // 0|1|1|2|2
    $t->unsignedInteger('assessment_max_participants')->nullable(); // 40 (discovery), null = semua
    $t->json('assessment_domains')->nullable(); // ["mh"] / ["mh","nutrition"]
    $t->unsignedTinyInteger('expert_chat_experts')->default(0); // 0|1|2|2 (jumlah expert chat)
    $t->unsignedInteger('expert_chat_quota')->default(0);   // kuota CHAT expert per kontrak (bucket terpisah)
    $t->unsignedInteger('deep_support_quota')->default(0);  // 8 (discovery) / default band MHCU-1on1 fallback
    $t->string('deep_support_mode', 10)->nullable(); // null|'mhcu'|'one_on_one' — DEFAULT kontrak, dipilih per kontrak
    $t->unsignedTinyInteger('webinar_count')->default(0);  // 0|1|3|6
    $t->boolean('quarterly_review')->default(false);       // impact only
    $t->boolean('is_active')->default(true);
    $t->timestamps();
});
```

`expert_chat_quota` dan `deep_support_quota` = **dua bucket terpisah**
(menjawab requirement "kuota chat expert vs deep support harus terpisah").

### 3.2 `wellbeing_pricing_bands` (harga per band — DATA, seeder)

```php
Schema::create('wellbeing_pricing_bands', function (Blueprint $t) {
    $t->id();
    $t->foreignId('wellbeing_package_id')->constrained()->cascadeOnDelete();
    $t->unsignedInteger('band_min');           // 50|151|401|601|801
    $t->unsignedInteger('band_max');           // 150|400|600|800|1000
    $t->decimal('monthly_price', 14, 2);       // Rp/bulan, exclude pajak; discovery: 0
    $t->unsignedInteger('mhcu_quota')->nullable(); // 5|10|15|20|25 (growth) / 10..50 (impact); null = pakai default
    $t->timestamps();
    $t->unique(['wellbeing_package_id', 'band_min']); // 1 band min per paket
});
```

Band MHCU **menang** atas `deep_support_quota` di katalog bila kontrak option A
(jawaban inkonsistensi doc #2/#3: "gunakan band table").

### 3.3 `company_wellbeing_programs` (assign paket ke perusahaan oleh superadmin — KONTRAK)

```php
Schema::create('company_wellbeing_programs', function (Blueprint $t) {
    $t->id();
    $t->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
    $t->foreignId('wellbeing_package_id')->constrained()->cascadeOnDelete();
    $t->foreignId('pricing_band_id')->nullable()->constrained('wellbeing_pricing_bands')->nullOnDelete(); // snapshot band; null utk discovery
    $t->unsignedInteger('employee_band_size');  // snapshot jumlah karyawan saat assign (basis kuota MHCU band)
    $t->string('deep_support_mode', 10);        // 'mhcu' | 'one_on_one' — keputusan Option A/B per kontrak (wajib utk growth/impact)
    $t->date('starts_at');
    $t->date('ends_at');                        // starts_at + duration_days - 1 (dihitung saat create)
    // KUOTA SNAPSHOT (dari katalog/band saat assign — kebal perubahan katalog):
    $t->unsignedInteger('expert_chat_quota');   // bucket 1
    $t->unsignedInteger('deep_support_quota');  // bucket 2 (sudah final: band MHCU jika option A)
    $t->unsignedTinyInteger('webinar_quota')->default(0);
    $t->unsignedTinyInteger('assessment_count')->default(0);
    $t->unsignedInteger('assessment_max_participants')->nullable();
    $t->string('status', 20)->default('active'); // active|ended|cancelled (30-day notice)
    $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // superadmin
    $t->timestamps();
    $t->index(['perusahaan_id', 'status']);
});
```

Kenapa snapshot kuota di kontrak: kuota kontrak-level harus stabil walau
katalog/harga diubah superadmin kemudian (audit + kepastian kontrak S&K).

### 3.4 `wellbeing_quota_ledger` (bucket kontrak, mirror pola M2)

Alternatif yang dievaluasi: reuse `consultation_quota_ledger` + kolom baru.
**Ditolak** — `period_ym` bulanan + FK `employee_package_id` NOT NULL di sana
adalah asumsi struktural; menambah nullable bucket = merusak semua query
`outflow/inflow` existing. Tabel terpisah lebih murah dan zero-risk:

```php
Schema::create('wellbeing_quota_ledger', function (Blueprint $t) {
    $t->id();
    $t->foreignId('program_id')->constrained('company_wellbeing_programs')->cascadeOnDelete();
    $t->string('bucket', 20);                  // expert_chat|deep_support (TEXT, sqlite-safe)
    $t->foreignId('perusahaan_session_id')->nullable()->constrained('perusahaan_sessions')->cascadeOnDelete();
    $t->foreignId('perusahaan_employee_id')->nullable()->constrained('perusahaan_employees')->cascadeOnDelete(); // siapa pakai (monitoring HR)
    $t->string('event', 20);                   // reserved|used|forfeited|refunded|released (mirror M2)
    $t->text('reason')->nullable();
    $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $t->timestamps();
    $t->unique(['perusahaan_session_id', 'event']);
    $t->index(['program_id', 'bucket']);
});
```

Catatan: TIDAK ada `period_ym` — periode = umur kontrak (`starts_at..ends_at`),
saldo dihitung sepanjang kontrak, hangus otomatis saat `ends_at` lewat /
`status != active` (cukup filter `program aktif` saat balance check — no sweeper
baru; `SweepNoShowSessions` existing tetap jalan untuk event `forfeited`).

### 3.5 Relasi (ringkas)

- `WellbeingPackage` hasMany `WellbeingPricingBand`; hasMany `CompanyWellbeingProgram`.
- `CompanyWellbeingProgram` belongsTo `Perusahaan`, `WellbeingPricingBand` (nullable), `User` (created_by);
  hasMany `WellbeingQuotaLedger`.
- `Perusahaan` hasMany `CompanyWellbeingProgram` (`programs()`).
- Bridge ke existing: `PerusahaanSession` tetap satu-satunya sumber sesi;
  `WellbeingQuotaLedger.perusahaan_session_id` menghubungkan kuota kontrak ke
  sesi yang sama (BookingService/ChatController flow tidak berubah strukturnya).

### 3.6 Migrasi baru (2 file, urutan)

1. `2026_11_23_000001_create_wellbeing_catalog_tables.php`
   — `wellbeing_packages` + `wellbeing_pricing_bands` (3.1 + 3.2).
2. `2026_11_23_000002_create_company_wellbeing_programs_table.php`
   — `company_wellbeing_programs` + `wellbeing_quota_ledger` (3.3 + 3.4).
   (Gabung 3+4 agar FK program→ledger satu file; tetap 2 file total, gaya
   mission M1 yang juga membuat 2 tabel per migration.)

Semua TEXT enum + `Schema::hasColumn` guard, mengikuti konvensi sqlite-safe
repo (lihat M1/M2/M4). Tidak ada perubahan kolom pada tabel existing →
zero migration risk untuk 712 test.

---

## 4. Integrasi QuotaService (kontrak-level tanpa break 712 test)

**Keputusan: adapter terpisah `WellbeingQuotaService`, QuotaService TIDAK diubah.**

Alasan:
- `QuotaService` dipanggil di 6 call-site (BookingService, EmployeeController,
  ChatController, QuotaAdminController, SweepNoShowSessions, RosterController)
  dengan kontrak "monthly per employee". Mengubah signature/perilaku =
  memodifikasi 5 file + 66 mission test + regression test.
- Kuota kontrak-level = scope berbeda (per company program, bucket, kontrak
  window). Beda dimension — bukan extension natural, tapi model paralel.

Desain:

```php
class WellbeingQuotaService
{
    // Mirror public API QuotaService supaya call-site baru terasa sama:
    public static function activeProgram(Perusahaan $company): ?CompanyWellbeingProgram;
    public static function bucketBalance(CompanyWellbeingProgram $p, string $bucket): int;
    public static function reserve(PerusahaanEmployee $emp, PerusahaanSession $session, string $bucket): WellbeingQuotaLedger;
    public static function consume(PerusahaanSession $session, string $bucket): ?WellbeingQuotaLedger;
    public static function forfeit(PerusahaanSession $session, string $reason): ?WellbeingQuotaLedger;
    public static function release(PerusahaanSession $session, string $reason): ?WellbeingQuotaLedger;
    public static function refund(WellbeingQuotaLedger $reserved, User $actor, string $reason): WellbeingQuotaLedger;
}
```

- Pola internal sama persis dengan `QuotaService`: append-only ledger,
  UNIQUE(session,event) idempotent, `DB::transaction`, saldo =
  `snapshot quota - distinct(reserved|used|forfeited) + released/refunded`
  per (`program_id`,`bucket`) — TANPA filter `period_ym` (kontrak window
  di-check via `program->status === 'active' && starts_at <= today <= ends_at`).
- **Routing kuota di entrypoint** (satu-satunya tempur yang menyentuh kode
  existing): di `BookingService` + `EmployeeController::startChat`, sebelum
  `QuotaService::reserve`, cek `WellbeingQuotaService::activeProgram($company)`:
  - Ada program aktif → route ke bucket sesuai `consultation_type`:
    `chat` → bucket `expert_chat`; `one_on_one` → bucket `deep_support`
    (dan tolak `chat` bila `expert_chat_quota = 0` dsb.).
  - Tidak ada program → fallback `QuotaService` bulanan existing (behavior
    saat ini, semua test tetap hijau karena program aktif = 0 di fixture
    existing).
- `SweepNoShowSessions` dan `ChatController::consume` perlu branch yang sama
  (cek ledger wellbeing ada → append ke wellbeing; else QuotaService existing).
  Implementasi termudah: append-method kedua service sudah idempotent; jalankan
  keduanya secara kondisional berdasarkan keberadaan row `reserved` di masing2
  tabel.

> Detail routing bucket per `consultation_type` + `mode` final di tasks.yaml
> (W-P1); prinsip di atas sudah cukup untuk desain.

### Kompatibilitas test

- Tidak ada test existing yang membuat `company_wellbeing_programs` → routing
  selalu fallback ke QuotaService → 712 test tak tersentuh.
- Test baru (W-P2): program aktif → reserve masuk wellbeing ledger, saldo
  kontrak berkurang, tidak reset bulan depan; cross-bucket terpisah; hangus
  setelah `ends_at`; option A vs B mengubah bucket target.

---

## 5. Keputusan desain

| # | Keputusan | Alasan |
|---|---|---|
| 1 | Katalog paket + band harga = **DATA (seeder)**, bukan config/hardcode | Preseden repo: `assessment_instruments` sudah DATA (`2026_11_22_000001` migration comment: "definisi ... sebagai DATA. Instrumen baru = seeder, tanpa kode"). Harga akan diedit owner via admin UI nanti (Program Management menu) — config/hardcode tidak bisa diedit runtime. Seeder `WellbeingPackageSeeder` (4 paket + 15 band rows + discovery). |
| 2 | Tabel baru terpisah, **bukan** extend `packages` | `packages.monthly_quota` adalah kontrak bulanan yang dipegang 66 mission test + 5 file call-site. Entity kontrak = dimensi berbeda. Zero migration risk. |
| 3 | `WellbeingQuotaService` adapter terpisah, **bukan** modif QuotaService | Lihat §4. QuotaService doc-comment sendiri menyebut dirinya "satu-satunya pintu reservasi kuota" untuk model bulanan — model kontrak = pintu kedua, terpisah. |
| 4 | Snapshot kuota di `company_wellbeing_programs` | Stabilitas kontrak (S&K: kuota di-lock saat order); katalog bisa berubah tanpa merusak kontrak jalan; sederhana (tanpa join historis). |
| 5 | Band diambil dari `perusahaans.employee_count` saat assign, disimpan sebagai `employee_band_size` snapshot | Band = keputusan saat kontrak; perusahaan tambah karyawan tidak menggeser harga kontrak jalan. |
| 6 | Inkonsistensi doc: pakai resolusi §8 PRODUCT-DOC-SUMMARY (8 sesi 1-on-1 discovery; MHCU per band table) | Owner sudah menulis resolusi default di summary. |
| 7 | `wellbeing_quota_ledger` tanpa `period_ym` | Periode kontrak-level: window aktif = kondisi program, bukan kolom. Hangus = program ended/expired. |
| 8 | MVP admin UI = 3 route CRUD/assign di `admin.*` group + nav di layouts/app.blade.php | Ikut pola `KlinikPackageController` + `PerusahaanActivationController` (form + AdminAction::record + flash). Role 6-dashboard-main (Account Manager dll) = phase lanjut; sekarang `role:admin` cukup. |

---

## 6. Risiko + mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Double-reserve (sesi masuk ledger lama DAN baru) | Kuota kebakar dobel | Routing single-branch di entrypoint (if/else, bukan keduanya); UNIQUE(session,event) di kedua tabel; test W-P2 assert "program aktif → NOL row baru di consultation_quota_ledger". |
| 66 mission test + regression break karena sentuh BookingService/ChatController | Baseline merah | Routing = tambahan if guard murni; fixture existing tanpa program → path lama identik; jalankan full suite tiap task (verify command). |
| Katalog di-seed ulang menimpa kontrak snapshot | Kuota kontrak berubah | Snapshot di program; seeder idempotent `firstOrCreate` per `code` (pola `MissionPackageSeeder`). |
| sqlite partial unique tidak portable (1 program aktif per perusahaan) | Duplikasi program aktif | Dijaga app-level di controller/service (pola sama seperti M1 comment di migration `2026_11_20_000001` — konsisten repo). |
| `deep_support_mode` kontrak vs `consultation_type` sesi mismatch (karyawan booking chat di kontrak option B tanpa expert chat) | UX error | Validasi service: bucket `expert_chat` kuota 0 → abort 403 pesan jelas; UI employee menyembunyikan opsi. |
| Doc inkonsisten (MHCU "15" vs band 5–25) | Salah angka default | Resolusi §8 summary: band table menang; `deep_support_quota` snapshot selalu final (band A / default B). |
| Scope creep dashboard HR/employee 8–10 menu | Misi meledak | Fase ini = admin + data model + QuotaService routing saja; dashboard = misi berikutnya (dicatat di tasks.yaml backlog). |
| Klinik/MHCU bagi-hasil (§7 doc) tertarik masuk scope | Out-of-scope | Doc bilang separate vertical; tidak ada tabel di desain ini. |

---

## 7. Deliverable berikutnya (untuk /kickoff)

1. Migration 2 file (§3.6) + model 4 (`WellbeingPackage`, `WellbeingPricingBand`,
   `CompanyWellbeingProgram`, `WellbeingQuotaLedger`) + seeder katalog.
2. `WellbeingQuotaService` + routing di 4 call-site (BookingService,
   EmployeeController::startChat, ChatController consume, SweepNoShowSessions).
3. Admin UI: `admin/wellbeing-packages` (CRUD katalog read-heavy + edit harga
   band) + `admin/perusahaans/{p}/programs` (assign: pilih paket, band
   auto-detect dari employee_count, option A/B, tanggal) + `AdminAction` record.
4. Test suite baru (W-P2) + full regression 712.
