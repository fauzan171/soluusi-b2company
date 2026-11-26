# wb-T6 — Test suite lengkap 18 scenario + full regression

Status: READY
Depends: wb-T1..wb-T5 ( semua task selesai & merged ke integration branch).

## GOAL
Tutup gap scenario dari `research-tests.md` §3 yang belum tercakup test per-task, + full regression gate 712 baseline + semua test wb baru.

## CONTEXT
- Test plan 18 scenario: `docs/plan/research-tests.md` §3 (grup A superadmin katalog, B pricing band, C assign program, D kuota kontrak-level, E deep support option, F regression gate). Sebagian sudah tercakup di test per-task (wb-T2..wb-T5 acceptance) — T6 mengisi SISA scenario, bukan duplikasi. Mapping kosong vs sudah → cek test file existing di branch integration sebelum menulis.
- Prioritas tulis (research-tests §3): sisa gate keamanan & validasi dulu.
- House style: `research-tests.md` §2 (RefreshDatabase, fixture inline `fixture()`/`user()` private method, `actingAs`, role `firstOrCreate(['name'=>'admin'])`, `assertDatabaseHas`, `assertForbidden`, counter `static $n` untuk uniqueness). No factory baru selain yang perlu.
- QuotaService public API TIDAK boleh berubah; `W1FoundationSchemaTest` hard-assert MissionPackageSeeder — jangan sentuh.

## CONSTRAINTS
- Files: maksimal 4–6 file test baru di `tests/Feature/` (usulan nama dari research-tests §3: `SuperadminPackageCatalogTest`, `PricingBandTest`, `ProgramAssignmentTest`, `ContractQuotaTest`, `DeepSupportOptionTest` — skip yang sudah ada dari task lain, atau tambah method di file yang sudah dibuat task sebelumnya dengan MENAMBAH method, TIDAK mengedit test lama dari baseline).
- 0 EDIT file test existing 712-baseline (menambah method ke test file wb-* buatan task wb-T1..T5 diperbolehkan).
- Scenario yang belum ada di acceptance per-task, antara lain: C2 (assign paket sama ke 2 perusahaan paralel — tenant isolation: HR A tidak lihat program B), C3 (program expired → booking employee abort 403), D3 (sisa kuota tidak dipindahtuangkan antar employee bila relevan — note: bucket = kontrak-level company, D3 diartikan employee di luar `selected_employee_ids` discovery ditolak), E2 (expert chat Foundation=1, Growth/Impact=2 — tampilan/gate jumlah expert), B4 (estimasi band di luar range), A4 (discovery gratis tidak bisa di-custom harga).
- Fixture fake data only, no PII.
- File baru `chgrp hermes` + `g+r`.

## ACCEPTANCE
1. Semua 18 scenario research-tests §3 ter-cover (mapping file:scenario ditulis di RETURN — gap diisi T6, sisanya refer test per-task): setiap scenario punya assert eksak, semua hijau.
2. Full regression: `/usr/local/bin/php artisan test` → **712 baseline + semua test wb baru passed, exit 0**, TANPA satu pun edit ke file test baseline (verify: `git diff --stat <base> -- tests/` hanya file baru / method tambahan di file wb).
3. Negative test tenant isolation + authz matrix (A3/C2/C4) hijau.
4. Laporan mapping scenario → file test di return message (bukan file .md baru).

## RETURN FORMAT
STATUS(done|blocked) / branch+sha / files changed / commands run + hasil (angka test final + assertions) / mapping scenario→test / deviations / blockers
