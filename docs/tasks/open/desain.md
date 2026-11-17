# Desain — Employee Booking UI Redesign (Vertikal Perusahaan)

Status: READY-FOR-KULI
Tanggal: 2026-11-17
Branch kerja: `goal/superadmin-control-center` (worktree `/home/seva-ojan/wt/soluusib2b-production-foundation`)
Model eksekutor kuli: **glm-5.3-flash** (via ANTHROPIC_DEFAULT_SONNET_MODEL=9inf/glm-5.3-flash)

## 1. Masalah

Halaman booking karyawan `resources/views/perusahaan/booking.blade.php` saat ini:
- Pilihan jenis konsultan = `<select>` generik (psikolog/nutrisionis/fisioterapis/karir) tanpa konteks.
- Pilihan slot = list radio polos dikelompokkan per tanggal; konsultan hanya tampil sebagai nama di tiap baris — tidak ada kartu konsultan, tidak ada filter per konsultan, tidak ada mode badge yang jelas.
- Halaman katalog `perusahaan/consultants.blade.php` punya tombol "Lihat Jadwal & Booking" tapi TIDAK membawa konsultan terpilih ke form booking (link polos ke `perusahaan.booking`) — karyawan memilih konsultan dua kali.
- Bandingkan dengan `resources/views/checkout/klinik.blade.php` (flow klinik): hero section, grid kartu konsultan (avatar inisial, kategori chip, jumlah slot), panel samping sticky berisi jadwal konsultan terpilih. Itu benchmark kualitas yang diminta.

## 2. Keputusan Arsitektur (WAJIB diikuti kuli)

1. **Tidak ada library JS baru** (no Alpine, no npm package). Interaksi via GET param + server render — pattern yang sama dengan `checkout/klinik.blade.php` (`?consultant_id=`) dan sesuai ponytail smallest-diff codebase.
2. **Satu halaman booking, dua langkah visual** (bukan SPA):
   - `GET /employee/booking` → grid kartu konsultan pool (filter `?q=` nama + `?category=`), kuota badge di hero.
   - `GET /employee/booking?consultant_id={id}` → kartu terpilih (ring pine) + panel samping sticky menampilkan slot konsultan itu, dikelompokkan per tanggal, radio `schedule_id` per slot (pertahankan input existing — BookingService tidak berubah).
   - Tanpa `consultant_id`: panel samping menampilkan SEMUA slot pool (dikelompokkan per tanggal) — perilaku sekarang, tetap berfungsi.
3. **Backend minimal**: `EmployeeController::bookingForm` tambah `?consultant_id` filter (validasi consultant ada di pool perusahaan) + `?q`/`?category` opsional untuk grid. `bookingStore` TIDAK berubah (tetap `schedule_id` + `konsultan_type`; `konsultan_type` di-derive dari konsultan terpilih via hidden input, fallback select bila tanpa consultant_id).
4. **Katalog → booking deep-link**: tombol di `perusahaan/consultants.blade.php` berubah dari link polos ke `route('perusahaan.booking', ['consultant_id' => ...])`. Sudah diverifikasi: `ConsultantPool::poolFor` return array `['id' => consultant_id, 'name', 'category', 'type']` (`app/Models/ConsultantPool.php:53-70`) — pakai `$c['id']` langsung. Validasi `consultant_id` di bookingForm bisa pakai `ConsultantPool::validChoice` atau contains() pada pool.
5. **Tetap dalam design system existing**: Tailwind + token warna `pine-*`, `terra-*`, `ink`, `paper`, komponen `portal-card`, layout `layouts.app`. Tiru pola visual `checkout/klinik.blade.php` (hero gradient `pine-950→pine-700`, kartu konsultan `rounded-2xl border hover:-translate-y-0.5`, aside `lg:sticky lg:top-5`) TETAPI tanpa field pembayaran/paket/data customer (employee sudah login — tidak perlu nama/email/HP/voucher).
6. **A11y**: radio slot tetap `<input type="radio" name="schedule_id" required>`, label terasosiasi, error `role="alert"` — pertahankan dari form existing.
7. **Kuota habis**: tetap disable submit + alert box (behavior existing).
8. **View-as superadmin**: halaman harus tetap render saat `isPreview` (bookingForm sudah handle; jangan pecahkan).

## 3. File yang Disentuh (surgical, jangan melebar)

| File | Perubahan |
|---|---|
| `app/Http/Controllers/Perusahaan/EmployeeController.php` | `bookingForm`: tambah filter `consultant_id` (validated ada di pool), `q`, `category`; pass `selectedConsultant`, `categories`, `q`, `activeCategory` ke view. |
| `resources/views/perusahaan/booking.blade.php` | REDESIGN: hero (judul + badge kuota), grid kartu konsultan dengan filter, aside sticky slot picker (per tanggal, radio), hidden `konsultan_type` saat konsultan terpilih. |
| `resources/views/perusahaan/consultants.blade.php` | Tombol kartu → `perusahaan.booking?consultant_id=` (deep-link). |
| `tests/Feature/EmployeeBookingUiTest.php` | BARU — lihat acceptance. |

JANGAN sentuh: `BookingService`, `bookingStore` logic, ChatController, model apapun, routes (route sudah cukup), halaman klinik.

## 4. Acceptance Criteria (verify kuli)

1. `php artisan test --filter=EmployeeBookingUiTest` hijau:
   - GET booking tanpa param → 200, render nama minimal 2 konsultan pool + semua slot.
   - GET booking `?consultant_id=X` → 200, hanya slot konsultan X tampil; slot konsultan lain TIDAK tampil; hidden input `konsultan_type` terisi kategori konsultan X.
   - GET booking `?consultant_id=999999` (bukan pool) → 404/302 dengan error (bukan 500).
   - GET booking `?q=nama` → grid terfilter.
   - POST booking.store existing tetap jalan (reuse 1 test booking existing kalau ada — cek `tests/Feature` untuk booking store test; TIDAK boleh merah).
2. Regression: `php artisan test --filter='BookingServiceTest|KlinikMoneyFlowTest|HpaKlinikPortalTest'` hijau (kalau suite itu ada; kalau nama beda, pakai suite booking yang ada).
3. Full `php artisan test` hijau (0 failures; warning `/tmp/soluusi-tests-no-env/.env` = pre-existing, abaikan).
4. `php artisan route:cache` exit 0.
5. Visual: grid konsultan 2 kolom md, aside sticky lg, mobile single column. (Kuli tidak perlu screenshot; mandor yang verifikasi visual.)

## 5. Instruksi Eksekusi Kuli

- Kerja HANYA di worktree `/home/seva-ojan/wt/soluusib2b-production-foundation`, branch `goal/superadmin-control-center`.
- Commit: `git -C /home/seva-ojan/wt/soluusib2b-production-foundation commit` (hook guard.sh salah resolve cwd bila pakai `cd` + `git commit`). Conventional Commits, English, akhiri `Co-Authored-By: [CC] <noreply@anthropic.com>`.
- TDD: tulis `EmployeeBookingUiTest` dulu (merah), baru implementasi (hijau).
- Jangan disable/skip test, jangan tambah `@ts-ignore`/sejenisnya, jangan baca/print isi `.env`.
- Data test = fake, tanpa PII.
- Bila ada ketidakcocokan data, baca implementasi `app/Models/ConsultantPool.php` dulu, jangan tebak.
- Selesai → laporkan: STATUS / branch+sha / files changed / tests run + hasil / deviations / blockers.

## 6. Out of Scope (YAGNI)

- Filter tanggal multi-bulan / kalender interaktif JS.
- Paket/harga (perusahaan pakai kuota, bukan checkout).
- Perubahan flow HR review-gated (path A) — ini hanya path booking langsung (path B).
- Redesign halaman lain (dashboard, chat, katalog selain tombol deep-link).
- Alpine.js / komponen JS framework apapun.

## 7. Catatan PRD

Setelah implementasi selesai & QA hijau, mandor update `docs/PRD-2026-11-16-current-state.md` bagian flow booking employee perusahaan: UI kini kartu-konsultan + slot panel (sebelumnya dropdown + radio list polos), deep-link dari katalog konsultan.
