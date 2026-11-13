# Soluusi B2B — Production Foundation Plan

Baseline: `b5170cf` on `main`. Branch kerja: `goal/production-foundation`.

## Constraint aktual (terverifikasi 2026-11-14)
- PHP/Composer TIDAK di PATH host. Runtime test/build lokal diblokir sampai diinstall.
- Git Windows 2.56.0 OK. Worktree manual berfungsi; `Agent isolation:worktree` TIDAK dipakai.
- SSH alias `app-server` dan `soluusi-server` ter-resolve sebagai literal hostname (bukan IP). Server read-only sampai owner konfirmasi identitas.
- Laravel 12 di composer.json TAPI skeleton masih Laravel ≤10 (`bootstrap/app.php` + `app/Http/Kernel.php`).
- Stack UI: Blade + Tailwind (CDN) + Alpine.js (CDN) + Chart.js (CDN). Tidak ada Livewire/Inertia.
- Realtime chat = HTTP polling 5 detik; broadcast scaffolding setengah-terinstal (channels.php, BroadcastServiceProvider) tapi tidak dipakai.

## North Star recap
Multi-tenant B2B: superadmin / HR perusahaan / consultant / employee / klinik admin. Tenant isolation di data + Policy + test negatif lintas perusahaan. Waktu UTC → Asia/Jakarta. AI seam via `AIService` interface, provider env, feature flag tenant — belum integrasi AI aktif. `MAX_SCHEDULES_PER_SESSION = 3` (config).

## Gap matrix (dari 3 audit + route walk)

| # | Area | Kondisi aktual (bukti) | Gap | P | Acceptance |
|---|------|------------------------|-----|---|------------|
| G1 | Alpine bug `requestConsult` | `employee.blade.php:16` tidak deklarasi state | Modal "Minta Konsultasi" bisa stuck/gagal | P0 | State dideklarasikan, modal buka/tutup via klik+Escape, manual test OK |
| G2 | Dead `POST /lead` | `routes/web.php:38`, tidak ada form yang POST | Route yatim; CTAs arah `#kontak` | P1 | Route terpakai ATAU dihapus |
| G3 | Login page misbrand | `auth/login.blade.php:38` tulis "Portal B2B Klinik" | Salah framing untuk 7 role non-klinik | P1 | Copy netral "Portal Soluusi Mitra" |
| G4 | Placeholder logo/stats | `landing.blade.php:139-148` 12 "Logo N", "300+ konsultan", "5.000+ kasus" | Tidak boleh ship | P0 | Diganti data real ATAU dihapus |
| G5 | Public `/progress` leak | `routes/web.php:66-67` tidak ada auth | Bocor metrik internal | P0 | Auth+role:admin ATAU dihapus |
| G6 | Dummy payment public | `routes/web.php:18-19` tidak gated env | Bisa dipakai bayar palsu di prod | P0 | Gate `APP_ENV != production` ATAU hapus |
| G7 | Mass-assignment tidak konsisten | `ConsultationSession`, `ChatMessage`, `ConsultantRating`, `ConsultantPool` pakai `$guarded=['id']`; `Payment`, `SocialAccount`, `ConsultantCompany` pakai `$guarded=[]` | Risiko privilege/amount escalation | P0 | `$fillable` eksplisit di semua money/PII model |
| G8 | Inline admin closures mutasi finansial | `routes/web.php:153-211` 6 closures | Tidak bisa route:cache, tidak ada audit log, tidak testable | P0 | Pindah ke `Admin\*Controller` + Form Request |
| G9 | Policy belum ada, authZ inline duplikat | `ChatController` 5× `abort_unless`, `Klinik\SessionController:98-102` hand-rolled | Drift, rawan miss di endpoint baru | P0 | `PerusahaanSessionPolicy` + `ConsultationSessionPolicy`; test negatif lintas tenant |
| G10 | Laravel 12 skeleton belum selesai | `bootstrap/app.php` bind Kernel manual | Upgrade setengah jadi | P1 | Middleware pindah ke `bootstrap/app.php` `withMiddleware()`; hapus `app/Http/Kernel.php`+`Console\Kernel.php` |
| G11 | CheckoutController god class 629 baris | `app/Http/Controllers/CheckoutController.php` | Semua perubahan checkout menyentuh 1 file riskan | P1 | Extract `CheckoutFlow` service + `CustomerDashboardState` support class |
| G12 | Session state machine 3 tempat | `BookingService` + `ChatController` + `Klinik\SessionController` | Inconsistent guard, sulit tambah status | P1 | Satu `SessionStateMachine` service |
| G13 | DashboardMetrics N+1 | `app/Services/DashboardMetrics.php:90-96` `departement` tidak eager-loaded; `$tenant->sessions()` dihitung 7× | Dashboard paling sering diakses | P2 | Eager load + agregasi query-level |
| G14 | Chat polling 5s + scaffold broadcast mati | `ChatController.php:12`, `BroadcastServiceProvider.php:17-19` | Infrastructure menyesatkan | P2 | Hapus scaffolding ATAU commit Reverb (pilih hapus untuk MVP) |
| G15 | Tenancy test negatif belum ada | Cek `tests/Feature/` — tidak ada test cross-tenant isolation | Klaim "tenant isolation" belum terbukti | P0 | Test: employee A tidak bisa lihat session employee B perusahaan lain |
| G16 | HR "Sesi aktif" miss overnight | `Perusahaan\DashboardController.php:31` filter `>= today 00:00` | Sesi jalan lewat tengah malam hilang | P2 | Filter `status = in_progress` ATAU `scheduled_at >= today - 12 jam` |
| G17 | Pending-ACC request invisible | `EmployeeController.php:289-301` tolak duplikat tapi tidak tampilkan pending | User tidak bisa cancel/lihat status | P1 | Card "Menunggu ACC" dengan cancel |
| G18 | "Jadi Mitra" CTA salah rute | `landing.blade.php:53`, `verticals/*.blade.php` ke `#kontak` | Form signup tidak terjangkau | P1 | CTA → `/daftar-mitra` |
| G19 | Tidak ada password reset | `routes/web.php:24-31` hanya login/logout | User terkunci = ops manual | P1 | Standard Laravel `Password::sendResetLink` flow |
| G20 | Tidak ada email verification / welcome | `PartnerRegisterController.php:82` flash "cek email" tapi tidak kirim | Janji tidak ditepati | P2 | Mailable `WelcomePartner` queued |
| G21 | `lang/id/` hilang, semua string hardcoded | `lang/` hanya `en/` default | Localization terkunci | P2 | `lang/id/*.php` per portal (top 20 strings dulu) |
| G22 | Employee nav tidak link ke consultant picker | `layouts/app.blade.php:40-44` | Fitur tidak discoverable | P2 | Tambah link sidebar |
| G23 | Associate dashboard "Rule demo" hardcoded | `associate/dashboard.blade.php:20-22` | Tidak production-ready | P2 | Pindah ke config |
| G24 | Custom error pages hanya 419 | `errors/` cuma 419 | 403/404/500 default Laravel | P2 | Tambah 403/404/500 brand-neutral |
| G25 | Statis service classes | `BookingService`, `DashboardMetrics`, dll. semua static | Tidak bisa di-mock/DI | P2 | Instance class + container binding (refactor incremental) |
| G26 | ~~`app/Models/Model.php` kosong~~ **CANCELLED** | Masih dipakai 18 model | Bukan dead code | — | Tidak ada action |
| G27 | Duplikasi Perusahaan/Sekolah controller/model | `Perusahaan\*Controller` vs `Sekolah\*Controller` hampir copy-paste | Vertical ke-3 = copy ke-3 | P2 | Parameterize atau extract base (deferred — bukan blocker) |
| G28 | AI seam belum ada | Cek `app/Services/AiInsightService.php` — static, tidak ada interface/flag | Klaim "AI seam" belum terbukti | P1 | Interface `AIService`, env provider, tenant feature flag, modul `ai` |
| G29 | `MAX_SCHEDULES_PER_SESSION` belum di-config | Belum ada config; reschedule tidak di-limit | Kontrak belum ada | P1 | `config/session.php` key + enforcement: **total 3 jadwal, reschedule ke-3 ditolak** (ADR 001) |
| G30 | Rate limiter hanya di login/lead/daftar | Tidak ada throttle di chat send, booking, reschedule | Spam/abuse surface | P1 | `throttle:` di endpoint chat/store/reschedule |

## Coverage Status Legend

| Status | Arti |
|--------|------|
| **Planned** | Ada task spec, belum diimplementasikan |
| **Implemented** | Kode sudah ditulis, belum diverifikasi |
| **Verified** | Test hijau + review independen + browser QA (jika UI) |

## North Star Coverage (accurate)

| Requirement | Status | Evidence |
|-------------|--------|----------|
| Tenant isolation di data layer | **Planned** | T2.4 (baru) — kolom tenant_id ada tapi belum ada enforcement |
| Tenant isolation di Policy layer | **Planned** | T2.2 — policies belum dibuat |
| Tenant isolation test negatif | **Planned** | T2.3 — test belum ditulis |
| View-as dengan audit log | **Planned** | T3.1 + F2.2 — audit log belum ada |
| HR review request queue | **Planned** | F1.1 (baru) — belum ada UI |
| Consultant slot proposal | **Planned** | F1.2 (baru) — belum ada flow |
| Employee slot confirmation | **Planned** | F1.3 (baru) — belum ada flow |
| Session expiry sweep | **Planned** | F1.4 (baru) — belum ada scheduler |
| AI seam | **Planned** | T6.1 — interface belum dibuat |
| MAX_SCHEDULES_PER_SESSION | **Planned** | T6.2 — ADR 001 decided, belum diimplementasikan |
| UTC storage | **Implemented** | Laravel default, verified di migrations |
| Asia/Jakarta display | **Not planned** | Belum ada task |

**Catatan:** Kolom `perusahaan_id` di tabel BUKAN bukti enforcement tenant. Hanya menunjukkan relasi. Enforcement memerlukan global scope, policy, dan test negatif.

## Milestones (vertikal slice)

- **M0 — Env & hygiene**: PHP/Composer terinstal, `vendor` di-`composer install`, `php artisan test` jalan hijau di lokal. Verify: `php -v` + `php artisan test --testsuite=Feature` OK.
- **M1 — P0 security/correctness** (G1, G4, G5, G6, G7, G8, G9, G15, G29, G30): tutup exploit + klaim palsu + bug demo. Verify: semua test baru hijau + manual browser pass 5 flow.
- **M2 — Tenancy/RBAC/audit** (G9 lanjutan + audit log untuk admin actions): Policy terpusat, audit log table, view-as superadmin. Verify: test negatif lintas tenant + audit log terisi saat view-as.
- **M3 — Foundation refactor** (G10, G11, G12): selesaikan Laravel 12 skeleton, pecah CheckoutController, satukan state machine. Verify: test tetap hijau, `php artisan route:cache` OK.
- **M4 — UX P1** (G2, G3, G17, G18, G19): onboarding + discoverability + recovery. Verify: manual browser pass signup→login→reset→booking→chat.
- **M5 — AI seam + hardening** (G28, G13, G14, G16, G20): interface + flag + queue + eager load. Verify: AI provider fake mengembalikan insight di dashboard saat flag on; flag off tidak manggil provider.
- **M6 — Cleanup P2** (G21–G27): localization, nav, dead code, dedup.

## Batas
- Tidak push, tidak deploy, tidak migrate:fresh/refresh/reset, tidak akses production lama Hostinger.
- Server: read-only recon; mutasi butuh baseline + backup terverifikasi + rollback plan + owner approval.
- Kuli model: `tg/glm5.3`. Smoke test headless tanpa tools wajib sebelum dispatch.
- Reviewer terpisah dari implementer. QA independent di akhir M1/M3/M6.
