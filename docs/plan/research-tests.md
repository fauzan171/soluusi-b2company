# Research — dampak fitur katalog paket + pricing band + assign program ke suite test existing

Baseline terverifikasi ulang saat riset ini (2026-11-23): **712 passed, 2553 assertions, 31.9s**, `php artisan test`, sqlite `:memory:` (phpunit.xml:24-25), exit 0.

Sumber requirement: `docs/plan/PRODUCT-DOC-SUMMARY.md` §1 (4 paket: Discovery/Foundation/Growth/Impact; kuota kontrak-level total, unused hangus; Discovery caps 40 peserta / 8 sesi psikolog untuk 8 karyawan terpilih; Growth/Impact Deep Support Option A (MHCU) XOR B (1-on-1)), §2 (harga Rp/bulan per band 50–150 … 801–1000), §10 (superadmin: katalog paket + harga per band + assign/aktifasi program per perusahaan).

---

## 1. Test yang menyentuh Package / EmployeePackage / QuotaService

Model inti: `app/Models/Package.php` (kolom: `name`, `monthly_quota` tinyint, `is_active`), `app/Models/Perusahaan/EmployeePackage.php` (`perusahaan_employee_id`, `package_id`, `perusahaan_id`, `starts_at`, `ends_at`, `status`), `app/Services/QuotaService.php` (ledger `consultation_quota_ledger`, periode bulan `period_ym`).

### 1a. Menyentuh `App\Models\Package` (perusahaan) — TERDAMPAK jika skema Package berubah

| File | Pemakaian (file:line) | Dampak kalau Package berubah (tambah kolom / konsep contract-level) |
|---|---|---|
| `tests/Feature/QuotaServiceTest.php` | `Package::create(['name','monthly_quota','is_active'])` :46; fixture() :40-63; 8 test method | AMAN untuk tambah kolom nullable/default (guarded=['id'], bukan fillable whitelist). BREAK jika `monthly_quota` berubah makna (bulanan → kontrak-level) karena saldo dihitung per `period_ym` bulanan. |
| `tests/Feature/QuotaEntryPointsTest.php` | `Package::create` :47, `EmployeePackage::create` :53, `QuotaService::reserve` :175; 6 test | Idem QuotaServiceTest. Entry-point guard (book/startChat/requestConsultation) — wajib tetap hijau. |
| `tests/Feature/QuotaLifecycleRegressionTest.php` | `Package::create` :44, `EmployeePackage::create` :45, reserve/consume/forfeit/refund :68-81 | Idem. Terminal-state used event — sensitif kalau event ledger bertambah. |
| `tests/Feature/W8QuotaEdgeFinalTest.php` | `Package::create` :34, `EmployeePackage::create` :46, reserve/release/forfeit :64-90 | Idem (3 test edge). |
| `tests/Feature/NoShowSweepAndRefundTest.php` | `Package::create` :44, `EmployeePackage::create` :50; `employee_package_id` di ledger :75,108,129,156 | Idem; 9 test. |
| `tests/Feature/W1FoundationSchemaTest.php` | `Schema::hasTable` :49-52, `Package::factory()->create(...)` :55,77,246; relasi `employeePackage` :93; **`test_mission_package_seeder_assigns_demo_employees` :280-298** | Test seeder (lihat §4) paling sensitif: hard-assert `name='Wellness Basic'`, `monthly_quota===2`, `is_active`, assignment ke SEMUA demo employee, idempoten re-run. BREAK kalau MissionPackageSeeder diubah ke 4 paket baru. |
| `tests/Feature/W1RollbackCheckTest.php` | daftar tabel :22 (`packages`, `employee_packages`, ...) | AMAN selama rollback migrasi baru tidak drop tabel lama. |
| `tests/Feature/W8CrossTenantIsolationTest.php` | `QuotaService::reserve($empA,$sessionA)` :160 (via helper fixture employee package) | Rendah; hanya lewat fixture. |
| `tests/Feature/PerusahaanWellbeingTest.php` | quota via `sessions_per_employee_month` :24 (fallback legacy, TANPA package) | AMAN — memakai fallback tenant, bukan package. Ini bukti jalur legacy harus tetap hidup. |
| `tests/Feature/EmployeeBookingUiTest.php`, `tests/Feature/Perusahaan/QuotaConsistencyTest.php`, `tests/Feature/Sekolah/SekolahQuotaDisplayTest.php` | tampilan saldo via `QuotaService::balanceFor/packageLabel` (controller `EmployeeController.php:92-93,261-265,461-491`) | BREAK hanya jika `packageLabel()` / `balanceFor()` signature berubah. Signature stabil → aman. |

Catatan: banyak file lain meng-import `ConsultantPackage` (HpaKlinikPortalTest, AdminActionsAuditTest, CheckoutFlowAtomicityTest, KlinikMoneyFlowTest, MidtransWebhookTerminalTest, DummyPaymentGateTest, OpsQueueTest, SessionPolicyTest, TenantSwitcherTest, TenancyIsolationTest, ScaffoldSmokeTest, dll.) — itu model **klinik yang berbeda**, tidak terdampak perubahan `App\Models\Package`.

### 1b. Ringkasan klasifikasi

- **Aman** (tambah kolom baru nullable / tabel baru / relasi baru): semua file §1a — `Package` pakai `$guarded=['id']`, semua create test tetap valid; W1FoundationSchemaTest hanya assert tabel + kolom lama ada (assert tambahan kolom baru → tulis test baru, bukan edit lama).
- **Berpotensi break** (perlu strategi): perubahan SEMANTIK `monthly_quota` bulanan → kuota kontrak-level total. `QuotaService` (`periodOf`, `balanceFor`, `append`) adalah titik sentral; 35+ test method quota bergantung pada periode bulanan.
- **Pasti break** jika seeder diubah tanpa compat: `W1FoundationSchemaTest::test_mission_package_seeder_assigns_demo_employees` (:280).

---

## 2. Pattern house-style test (contoh file:line)

- **Base**: `tests/TestCase.php` — `CreatesApplication` + `finfo-polyfill.php` require. Tidak ada setUp global, tidak ada seeding otomatis.
- **Isolasi DB**: `use RefreshDatabase` di kelas test (sqlite `:memory:` via phpunit.xml). 105/85 file Feature pakai RefreshDatabase (yang sisanya Unit/Screening tanpa DB). Tidak ada `DatabaseTransactions`.
- **Fixture inline, bukan factory berat**: pola dominan = private method `fixture()` / `user()` per test class yang create model langsung (QuotaServiceTest.php:26-64, W8QuotaEdgeFinalTest.php:34-46, NoShowSweepAndRefundTest.php:44-50). Factory hanya untuk `User`, `Package`, `ConsultantPackage` (`database/factories/`). Perusahaan/EmployeePackage hampir selalu `Model::create` eksplisit dengan counter `static $n` untuk uniqueness (QuotaServiceTest.php:40-42).
- **Auth**: `actingAs($user)` (661 pemakaian di 78 file) + helper private `admin()` per class: `Role::firstOrCreate(['name'=>'admin'])` lalu `User::factory()->create(['role_id'=>$role->id])` (AdminPerusahaanProvisionTest.php:21-24, AdminActionsAuditTest.php:32). Role dibuat on-the-fly `firstOrCreate`/insertGetId — TIDAK pakai `DatabaseSeeder` di setUp (QuotaServiceTest.php:29-31).
- **Superadmin route surface**: semua route admin di `routes/web.php:251` — `prefix('admin')` + `middleware(['auth','role:admin'])` + nama `admin.*`. Fitur baru (katalog paket, pricing band, assign program) harus masuk grup ini: `role:admin` = satu-satunya gate, tidak ada konsep role superadmin terpisah (User hanya punya `role_id`).
- **Assert style**: `$this->assertSame/assertEquals` nilai eksak, `assertDatabaseHas`, `artisan('db:seed', ['--class'=>...])->assertExitCode(0)`, assert redirect pada POST form admin, `assertForbidden()` (403) untuk negative-role test (AdminPerusahaanProvisionTest.php:76-77).
- **Idempotensi test**: seeder test selalu re-run dan assert count tidak berubah (W1FoundationSchemaTest.php:294-298).

---

## 3. Test plan fitur baru (scenario tabel)

Semua test baru di `tests/Feature/` + `RefreshDatabase` + pola fixture §2. Grup A = superadmin katalog, B = pricing band, C = assign program, D = kuota kontrak-level, E = gating.

| # | Scenario | Aktor | Assert kunci | File test baru (usulan) |
|---|---|---|---|---|
| A1 | Superadmin create paket (Foundation/Growth/Impact, durasi, fitur) | admin | redirect + `assertDatabaseHas('packages', ...)`; hr_admin 403 | `SuperadminPackageCatalogTest` |
| A2 | Superadmin edit/aktifasi/arsip paket; paket nonaktif tak bisa di-assign | admin | `is_active=false` → assign program ditolak | idem |
| A3 | Non-admin (hr_admin/employee/klinik_admin) akses CRUD katalog | hr_admin, employee | 403 semua verb | idem |
| A4 | Discovery paket ada & gratis; tidak bisa di-custom harga | admin | harga band Discovery tidak berlaku | idem |
| B1 | Band 50–150 s.d. 801–1000: harga per band tersimpan benar (tabel §2 product doc) | admin | nilai Rp eksak per (paket, band) | `PricingBandTest` |
| B2 | Validasi: band overlap ditolak, `min_employees <= max_employees`, harga >= 0 | admin | validation error, DB bersih | idem |
| B3 | Band lock: edit harga band yang sudah dipakai kontrak aktif → ditolak / snapshot harga ke kontrak | admin | harga kontrak tidak berubah walau band master di-edit | idem |
| B4 | Perusahaan di luar semua band (mis. 1200 karyawan) → estimasi harga error jelas | admin | error message | idem |
| C1 | Assign program paket ke perusahaan (program activation) | admin | tabel assignment/pivot + `perusahaan.is_active` program aktif | `ProgramAssignmentTest` |
| C2 | Assign paket yang sudah ada ke perusahaan lain → paralel ok, data tenant terisolasi | admin | tenant A tidak lihat tenant B | idem |
| C3 | Unassign/expire program → karyawan kehilangan akses fitur terkait | admin | gate balik ke 403/tutup | idem |
| C4 | HR melihat program perusahaannya (Program & Subscription menu) tapi read-only; tidak bisa assign | hr_admin | GET 200, POST 403 | idem |
| D1 | Kuota kontrak-level: total sesi kontrak (bukan bulanan); consume mengurangi total; pergantian bulan TIDAK reset | employee | `balanceFor` contract-style: saldo sama di bulan berikut | `ContractQuotaTest` |
| D2 | Unused hangus saat kontrak berakhir (`ends_at`) | employee | saldo 0 setelah end date | idem |
| D3 | Tidak dipindahtuangkan: employee B tidak bisa pakai sisa employee A | employee | reserve employee B gagal | idem |
| D4 | Discovery: 8 sesi psikolog hanya untuk 8 karyawan terpilih + consent; karyawan ke-9 ditolak | employee | reserve gagal untuk non-terpilih | idem |
| D5 | Discovery: max 40 peserta assessment; peserta 41 ditolak | employee/hr | submission ke-41 gagal | idem |
| E1 | Growth/Impact Deep Support: Option A (15/30 MHCU) XOR B (15/30 sesi 1-on-1) — pilih keduanya ditolak | admin | validation error saat assign dengan A+B | `DeepSupportOptionTest` |
| E2 | Expert chat Foundation = 1 expert, Growth = 2, Impact = 2 | employee | paket key `included_experts` men-gate jumlah expert aktif | idem |
| F1 | Regression gate: QuotaServiceTest + QuotaEntryPointsTest + QuotaLifecycleRegressionTest + W8QuotaEdgeFinalTest + NoShowSweepAndRefundTest + W1FoundationSchemaTest tetap 712 hijau tanpa edit | — | `php artisan test` full hijau | (tidak ada file baru — criteria) |

Prioritas urutan tulis: A3 → B2 → C1 → D1 → E1 (gate keamanan & validasi dulu), sisanya menyusul.

---

## 4. Risiko baseline & strategi backward-compat seeder

**Ripple MissionPackageSeeder** (`database/seeders/MissionPackageSeeder.php`):
- Dipanggil HANYA dari: `tests/Feature/W1FoundationSchemaTest.php:282-296` (via `artisan('db:seed --class=')`) dan dirinya sendiri. **TIDAK** dipanggil `DatabaseSeeder.php` (isi: roles + admin + KlinikAdminSeeder + PaymentSeeder + SekolahSeeder saja) — jadi seeder demo lokal/demo dashboard tidak tersentuh.
- Test yang pakai literal `Wellness Basic`: `QuotaServiceTest.php:46` (name unik suffixed `$n`), `NoShowSweepAndRefundTest.php:44`, `W1FoundationSchemaTest.php:285` (hard-assert nama + quota 2). Semua create inline, bukan seeder — aman selama nama kolom `name/monthly_quota/is_active` tidak dihapus.
- Jadi ripple seeder berubah jadi 4 paket baru: **1 test method pasti break** (`test_mission_package_seeder_assigns_demo_employees`). 556 legacy improvement-live + 156 mission lain tidak memanggil MissionPackageSeeder sama sekali → ripple selesai di 1 method.

**Strategi backward-compat (urut risiko naik):**
1. **Kolom aditif, semantik lama dipertahankan**: migrasi baru hanya `ALTER TABLE packages ADD` (nullable/default) + tabel baru (`package_pricing_bands`, `company_program_assignments` dst). `monthly_quota` TIDAK dihapus — jadi "kuota bulanan legacy"; kolom baru `total_sessions`/`duration_months` nullable. Semua 712 test tetap hijau tanpa satu pun edit. Zero ripple.
2. **Seeder: paket baru pakai `firstOrCreate` per paket baru (Discovery/Foundation/Growth/Impact)** di seeder BARU (mis. `WellnessCatalogSeeder`), MissionPackageSeeder dibiarkan apa adanya untuk legacy demo. `W1FoundationSchemaTest` tidak tersentuh.
3. **Alternatif kalau MissionPackageSeeder memang harus berubah**: ubah W1 test sekaligus di commit yang sama (assert 4 paket + assignment per paket), dan pastikan `Wellness Basic` tetap di-firstOrCreate oleh katalog baru sebagai alias Discovery bila demo flow (dashboard demo, `PerusahaanDashboardDemoSeeder`) membacanya. Cek dulu `PerusahaanDashboardDemoSeeder`/`PerusahaanConsultantCatalogSeeder` tidak hardcode `Wellness Basic` — saat riset ini tidak ditemukan referensi.
4. **QuotaService**: jangan ganti `balanceFor()`/`periodOf()` di tempat. Tambah jalur kontrak-level (method baru / mode flag per EmployeePackage) dan default tetap perilaku bulanan untuk assignment lama — regression F1 jadi gate.

**Net**: dengan strategi 1+2, prediksi test yang perlu diedit = **0 file**, test baru = ±5 file, dan `php artisan test` harus tetap 712 + N baru semua hijau.
