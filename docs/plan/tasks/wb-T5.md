# wb-T5 — HR "Program & Subscription" page + AggregatePrivacy threshold 10

Status: READY
Depends: wb-T2. PARALLEL GROUP dengan wb-T4 (file disjoint — lihat CONSTRAINTS).

## GOAL
Halaman HR read-only "Program & Subscription" (hero paket aktif, 3 kartu kuota sisa program, ringkasan komponen, empty state, near-expiry alert) + parameterize `AggregatePrivacy::safe()` min-threshold (wellbeing report pakai 10).

## CONTEXT
- UX EKSAK: `research-ux.md` §4 (wireframe hero band gradient pine pola `perusahaan/booking.blade.php:6-23`; 3 stat card Chat Expert/Deep Support/Webinar pola `perusahaan/dashboard.blade.php:10-22`; progress bar reuse persis `dashboard.blade.php:244-259` + label terra ≥80%; dash-card + panel-surface copy style block `dashboard.blade.php:27-40`; empty state pola `perusahaan/pending.blade.php:4-27` "Program belum aktif... Hubungi tim Soluusi"; near-expiry ≤30 hari alert strip pola `dashboard.blade.php:92-100` tone netral "kuota tersisa tidak dipindahtuangkan") + copy heading §2 ("Program & Subscription", "Paket aktif perusahaan {{brand}}, sisa kuota layanan, dan masa berlaku program.").
- Kuota read via `WellbeingQuotaService::bucketBalance` (T2) — label "sisa kuota program" BUKAN "bulan ini" (ux §5.8).
- Tone-of-voice `research-ux.md` §5: no istilah klinis, no "diagnosis", no label risiko baru, admin netral/HR "Anda", harga `Rp number_format` + "+ pajak".
- Route: perusahaan HR group `routes/web.php` (`prefix('perusahaan')`, `middleware(['role:hr_admin,admin'])` section ~:152) — tambah 1 route BARU `GET /program-subscription` name `perusahaan.program-subscription` setelah route wellbeing. Nav hr_admin: label "Program & Subscription", icon 'receipt'.
- `AggregatePrivacy` (R10/K14): ubah signature `safe($counts, int $min = 5)` — semua caller existing TIDAK diubah (tetap 5, `AggregatePrivacySuppressionTest` tetap hijau); report/agregat terkait program wellbeing (mis. summary komponen halaman ini bila menampilkan grup) pass `$min = 10`.

## CONSTRAINTS
- Files: `app/Http/Controllers/Perusahaan/ProgramSubscriptionController.php` (index — read-only, tenant scope via pola controller Perusahaan existing), `resources/views/perusahaan/program-subscription.blade.php`, edit `app/Support/AggregatePrivacy.php` (signature + default 5 — MINIMAL diff), route 1 baris + nav entry hr_admin section `layouts/app.blade.php` (BARIS BARU SAJA; T4 merge serial kemudian — no reformat).
- Read-only: tidak ada POST di halaman ini; HR TIDAK bisa assign (test C4: GET 200, POST assign (admin route) → 403 sudah tercakup T4 — di sini cukup assert GET saja + role gate: employee role → 403).
- Empty state saat tidak ada program aktif. Near-expiry ≤30 hari. Tidak menampilkan data individual employee (aggregate only).
- HR tidak melihat harga karyawan-lain/isi chat — halaman ini hanya snapshot program + saldo bucket.
- No touch file wb-T4 (`Admin/Wellbeing*`, `admin/wellbeing-*` views, `ExpireWellbeingPrograms`, `Console/Kernel.php`, nav admin section).
- File baru `chgrp hermes` + `g+r`. Blade flat + copy-paste style block (pola codebase — no component extraction, ponytail ux §6).

## ACCEPTANCE
1. `ProgramSubscriptionPageTest` baru (RefreshDatabase, pola `PerusahaanWellbeingTest`):
   - (C4) hr_admin GET /perusahaan/program-subscription 200 dengan program aktif growth → tampil nama paket, periode, 3 kartu kuota `used / total` benar (mis. 12/30 chat), label "sisa kuota program"; employee role → 403.
   - Empty state: tanpa program → 200, teks "Program belum aktif" + "Hubungi tim Soluusi".
   - Near-expiry: `ends_at` = today+26 → alert strip muncul, kata "26 hari" + "tidak dipindahtuangkan".
   - Saldo tidak reset bulan: assert angka sama setelah `travelTo` bulan berikut (kontrak-level, D1 via UI data).
2. `AggregatePrivacy` test baru (atau extend file test baru sendiri — JANGAN edit `AggregatePrivacySuppressionTest`): grup 7 peserta → `safe($counts, 10)` false "data belum cukup"; `safe($counts)` default → true (legacy 5 tetap); grup 11 → keduanya true.
3. Full suite `/usr/local/bin/php artisan test` hijau (termasuk `AggregatePrivacySuppressionTest` lama untouched), 0 edit test existing.

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil / deviations / blockers
