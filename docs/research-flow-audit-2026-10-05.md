# Audit source alur HR/Perusahaan, Sekolah, Klinik

Tanggal: 2026-10-05. Root source: `/Users/mekari/soluusi-b2b-klinik`.

## Metode dan batas

Review lokal, read-only kode aplikasi. Membaca routes, middleware, tenant contexts, controller tiap vertikal, model/schema terkait, services pembayaran/calendar/komisi, template portal, dan regression tests. Dokumen acuan: `docs/Soluusi B2B - Dev Preparation.md`, tiga `Vertical Spec.md`, `docs/PRD-Soluusi-B2B-Reframed.md`, dan `docs/mom-client-updates.md`. Dokumen mencampur MVP, expansion, dan keputusan terbuka; source menjadi bukti implementasi, bukan checklist dokumen lama.

Semua path sumber di bawah relatif terhadap root di atas, dengan nomor baris source 1-based. Tidak membaca nilai `.env`, credential, database pelanggan, atau mengunggah data. Tidak menjalankan browser, gateway, SMTP, Calendar, scheduler, migration, seeder, ataupun feature tests yang menulis DB. Ini **source review, bukan observasi browser atau sertifikasi produksi**. Test assertions dibaca; hasil test lama tidak diperlakukan sebagai hasil run sekarang.

Check nonmutating: `php -l` pada 245 file PHP di `app`, `routes`, `database/migrations`, `tests`: 0 syntax errors; PHP CLI 8.4.22. Check compiler Blade vendor membuktikan `@old(...)` tetap literal, bukan directive. Check Carbon tanpa aplikasi/DB membuktikan parsing `Y-m` pada tanggal 31 dapat bergeser ke bulan berikutnya.

Prioritas: P0 = batas keamanan/privasi sebelum penggunaan data nyata; P1 = perjalanan utama buntu atau integritas booking/uang; P2 = akurasi, navigasi, kejelasan tugas. “Terkonfirmasi source” berarti kondisi ada pada kode; bukan klaim exploit/insiden sudah terjadi di produksi.

## Yang sudah ada — jangan dianggap belum dibuat

- Laravel 9, Eloquent, Socialite Google dan password login, role routing, CSRF web; webhook API memverifikasi signature (`composer.json:10-23`, `app/Http/Controllers/AuthController.php:25-90`, `app/Http/Kernel.php:32-44`, `app/Http/Controllers/MidtransController.php:24-49`).
- Tenant contexts HR/BK berasal dari membership, bukan ID arbitrary; hanya super admin boleh switch tenant. Cancel sesi mengecek pemilik, pilihan konsultan memeriksa pool/type (`app/Support/PerusahaanContext.php:16-41`, `app/Support/SekolahContext.php:16-41`, `app/Support/KlinikContext.php:17-44`, `app/Models/ConsultantPool.php:49-69`, controller Employee/Student cancel).
- HR: dashboard agregat, roster/invite, laporan bulanan print/PDF browser, CSV, pool konsultan, WHO-5, employee self-register, booking/cancel/reschedule. Sekolah: dashboard BK/InsightEngine, roster tambah/invite/referral, portal siswa, WHO-5, self-register, booking/cancel dan guard parent consent. Bukan “landing saja” seperti catatan kickoff lama (`routes/web.php:60-101`; `docs/mom-client-updates.md:69-74` sudah tidak sesuai source).
- Klinik: partner registration dengan approval, katalog/profil, schedule online/offline, markup admin, checkout link dan public clinic booking, guest status/payment, lokal dummy payment, signature callback, ledger split unik per sesi, dual-confirm dan flag ops setelah 24 jam, HPA milestone (`routes/web.php:12-20,40-58,103-180`; `app/Console/Kernel.php:18-30`; `database/migrations/2026_08_05_000013_create_split_settlements_table.php:17-25`).

## Peta perjalanan role

| Role | Jalur yang tersedia | Titik putus penting |
|---|---|---|
| HR | login → dashboard → roster/invite → laporan/CSV | tidak bisa onboard roster/consent lewat portal; roster membocorkan pemakaian individu; laporan belum konsisten |
| Karyawan | roster → daftar → dashboard/WHO-5 → pilih konsultan/waktu → scheduled → cancel/reschedule | form waktu tidak berfungsi; setelah booking tidak ada delivery/completion CAP |
| BK | login → dashboard → siswa → tambah/invite → referral | tambah/referral bergantung Alpine yang tidak dimuat; consent tidak punya jalur pencatatan |
| Siswa | daftar → dashboard/WHO-5 → booking → scheduled/cancel | checkbox consent tidak disimpan; form waktu rusak; sesi tidak masuk portal konsultan |
| Admin klinik | daftar mitra → pending → approval → katalog/profil → schedule → generate/share link → dashboard | pembuatan schedule menempel handler ke form logout; katalog menjadikan schedule prasyarat untuk menemukan konsultan |
| Customer klinik | guest link → isi data/consent → gateway → status → konsultasi → confirm | slot paid bisa dijual ulang; link public terpakai sebelum payment berhasil |
| Konsultan | login → daftar sesi klinik → check-in → mark-done → customer confirm | hanya ConsultationSession klinik; CAP/Sekolah tidak muncul; tidak ada guard waktu selesai |
| Ops | approval → review dual-confirm → settle margin/komisi manual | tidak ada tenant setup/pool/completion CAP/Sekolah di routes; actual transfer bukan fungsi tombol settle |

## Temuan bersama terkonfirmasi

### S1 — P1 — Booking karyawan/siswa dan tambah/referral BK kehilangan runtime interaktif

**Skenario:** karyawan buka booking dari dashboard. Chip jam tidak terlihat; tombol hari tidak melakukan apa-apa. Siswa/BK menghadapi pola sama; “Tambah Siswa” tidak membuka modal.

`resources/js/app.js:1` hanya komentar; `resources/views/layouts/app.blade.php:7,155` tidak memuat Alpine; dependency `package.json:8-15` tidak memuat Alpine. Import CDN Alpine hanya ada pada **halaman** `resources/views/perusahaan/dashboard.blade.php:657` dan `resources/views/perusahaan/employee.blade.php:262`; full-page navigation tidak membawa script itu ke halaman berikutnya. CSS `resources/css/app.css:44-46` menyembunyikan `x-cloak` permanen tanpa Alpine. Form menggunakan `x-data`, `@click`, `x-show`, `x-cloak` pada `resources/views/perusahaan/booking.blade.php:22,38,45-64`, `resources/views/sekolah/booking.blade.php:12,29,45-64`, `resources/views/sekolah/roster.blade.php:5,11,17-40,92-107`.

**Akar kedua:** `@old(...)` bukan Blade directive; compiler vendor meninggalkan literal tersebut. Bahkan setelah runtime tersedia, ekspresi JS `type: @old(...)` dan `day: @old(...)` invalid. Kedua booking view memakai pola ini; pencarian tidak menemukan custom directive di providers. **Saran:** satu runtime layout bila masih memakai Alpine, lalu nilai PHP di-encode dengan directive/escaping yang benar; cek perjalanan browser, bukan hanya HTTP 200.

### S2 — P0 — Self-register bukan bukti kepemilikan email

**Skenario:** orang yang mengetahui nama lengkap dan email roster dapat membuat akun dengan password sendiri, lalu mengakses WHO-5 dan riwayat pemilik. Tenant isolation setelah login tidak menolong jika akun awal diambil orang lain.

`app/Http/Controllers/SelfRegisterController.php:43-59` mencari roster global berdasarkan email lalu hanya mencocokkan nama; `:61-78` membuat user aktif, menautkan roster, login. Tidak ada signed invitation, OTP atau email verification pada routes `routes/web.php:60-62`. Nama lengkap bukan rahasia/autentikator. Roster email tidak unik per tenant atau global (`database/migrations/2026_08_13_000003_create_perusahaan_employees_table.php:21-27`, `2026_09_19_000003_create_sekolah_students_table.php:19-28`), sementara user email unik (`2014_10_12_000000_create_users_table.php:19`): email yang ada di dua tenant dipilih `first()`, tenant pilihan tidak eksplisit; user existing bisa menabrak unique constraint tanpa jalur linking. **Saran:** verifikasi inbox melalui invitation bertoken; ikat tenant/row pada invitation dan tangani existing account eksplisit.

### S3 — P1 — Tenant nonaktif tetap bisa menjalankan program

**Skenario:** perusahaan/sekolah dinonaktifkan. HR/BK melihat pending di dashboard, tetapi membuka URL roster/report langsung tetap bekerja; employee/student tetap assessment dan booking.

Guard `is_active` hanya pada `app/Http/Controllers/Perusahaan/DashboardController.php:21-26` dan `app/Http/Controllers/Sekolah/DashboardController.php:24-29`. `routes/web.php:65-101` hanya auth/role; resolver Employee `:35-51` dan Student `:23-38` tidak memeriksa tenant aktif atau consent program; roster/report tidak memeriksa aktif. Klinik memiliki `klinik.active` (`routes/web.php:48-57`), jadi bukan masalah di semua vertikal. **Saran:** status program ditegakkan pada shared boundary CAP/Sekolah, dengan pending experience konsisten.

### S4 — P1 — Booking CAP/Sekolah bukan slot nyata dan bisa double-book

**Skenario:** dua karyawan/siswa memilih konsultan sama pada jam sama, atau POST waktu Minggu jam 02:00. Semua diterima selama future/type/pool cocok. Satu konsultan juga bisa terbooking bersamaan di perusahaan, sekolah dan klinik.

Employee `app/Http/Controllers/Perusahaan/EmployeeController.php:124-138,200-220`, Student `app/Http/Controllers/Sekolah/StudentController.php:133-143,159-182`, BK `app/Http/Controllers/Sekolah/RosterController.php:70-98`: pilihan jam dihasilkan dari kalender generik; server hanya `date|after:now`, tidak memilih Schedule, durasi, interval atau capacity. Pool mengecek identitas/type, bukan availability. Schema sesi tidak memiliki uniqueness/slot reservation (`database/migrations/2026_08_13_000004_create_perusahaan_sessions_table.php:16-30`, `2026_09_19_000004_create_sekolah_sessions_table.php:16-30`). Employee reschedule `:182-183` juga langsung mengganti waktu tanpa capacity. Spec perusahaan `:265-267,411` dan sekolah `:193-194,325` mengharapkan availability/slot lock. **Saran:** gunakan sumber availability bersama, validasi interval server dan reservasi atomik; jangan tampilkan jam generik sebagai ketersediaan nyata.

### S5 — P1 — Kuota bulanan memakai bulan sekarang tanpa batas akhir atau atomicity

**Skenario:** sesi bulan November sudah dibuat ketika Oktober; ikut mengurangi “kuota Oktober”. Employee dapat memindahkan sesi Oktober ke bulan lain tanpa pengecekan kuota bulan tujuan. Dua request paralel ketika sisa kuota satu sama-sama lolos count lalu create.

Employee `:147-150,175-184,194-220`, Student `:154-182`, roster HR `app/Http/Controllers/Perusahaan/RosterController.php:23-29`, roster BK `app/Http/Controllers/Sekolah/RosterController.php:25-31`: filter hanya `scheduled_at >= startOfMonth()`, bukan interval bulan tanggal booking. Tidak ada transaction/lock di count+create. Dashboard employee/student menghitung done dari `completed_at`, booking dari `scheduled_at`, membuat sisa kuota berbeda untuk sesi lintas bulan (`EmployeeController.php:60-64`, `StudentController.php:47-51`). **Saran:** satu aturan periode terpilih + interval tertutup-terbuka; cek kuota reschedule dan atomik per penerima/periode.

### S6 — P1 — Sesi CAP/Sekolah berhenti di scheduled; janji delivery belum punya caller

**Skenario:** setelah sukses booking, karyawan/siswa menunggu detail konsultan/link email. Konsultan login tetapi tidak melihat booking itu, tidak bisa menandai selesai. HR/BK laporan tetap kosong atau bergantung demo/manual DB.

Routes consultant `routes/web.php:104-108` memakai `app/Http/Controllers/Klinik/SessionController.php:17-25,31-46`, hanya `ConsultationSession`. CAP/Sekolah memakai model/tabel berbeda, `PerusahaanSession`/`SekolahSession`; pencarian semua callers aplikasi menemukan create/cancel/reschedule dan read aggregate, bukan completion, rating, topic input, assignment null-consultant, atau dispatch link. Calendar `app/Services/GoogleCalendarService.php:71` hanya menerima `ConsultationSession`, dipanggil webhook klinik `MidtransController.php:76-87`; scheduler `app/Console/Kernel.php:18-30` juga hanya klinik. Pesan sukses menjanjikan detail akan dikirim (`EmployeeController.php:222-223`, `StudentController.php:184-185`); portal mengulang janji link (`resources/views/perusahaan/employee.blade.php:193`, `resources/views/sekolah/student.blade.php:85`). GCalLink yang ada adalah **add-to-calendar link**, bukan pembuatan meeting/penugasan.

**Saran:** sebelum menyebut end-to-end, sediakan jalur ops/konsultan untuk assign → join → completion/no-show → topic/rating. Jika sementara manual eksternal, jelaskan pemilik/SLA dan cara sinkronisasi; tidak ada bukti SOP eksternal berjalan dari source.

### S7 — P1 — Mobile tidak memiliki menu portal atau logout

**Skenario:** HR di ponsel sudah membuka laporan atau roster; tidak ada menu Karyawan/Laporan/Dashboard. Employee/student tidak dapat pindah ke Asesmen/Booking melalui nav. Role dengan nav tidak memiliki tombol Keluar di header.

`resources/views/layouts/app.blade.php:56-81` sidebar `hidden md:flex`; tidak ada mobile replacement. `:123-130` header hanya memberi logout ketika `!count($navItems)`, selain itu email. Ini hilangnya aksi dari markup pada breakpoint, bukan dugaan estetika. **Saran:** navigasi responsive minimum dan logout selalu tersedia.

### S8 — P2 — White-label/page context tidak mengikuti end-user

Context HR/BK hanya membership admin (`app/Support/PerusahaanContext.php:35`, `app/Support/SekolahContext.php:35`); employee/student resolver punya tenant tetapi layout mencari context admin (`resources/views/layouts/app.blade.php:14-16`). Akun end-user biasa tanpa admin membership jatuh ke default brand. Klinik layout bahkan tidak memanggil KlinikContext untuk brand. `:6,90` memakai `$title`, sementara beberapa view memakai `@section('title', ...)` yang tidak di-yield. Header banyak halaman tetap “Dashboard”. Booking CAP/Sekolah memuat “Soluusi pilihkan” meski comment controller menyebut zero Soluusi (`resources/views/perusahaan/booking.blade.php:34-36`, `resources/views/sekolah/booking.blade.php:25-27`). **Saran:** brand/page title dari tenant resolved yang sama dengan controller; putuskan secara produk apakah supplier boleh terlihat, jangan janjikan white-label absolut sekarang.

## HR / Perusahaan

### H1 — P0 — HR melihat siapa yang menggunakan konseling

**Skenario:** HR buka Karyawan: nama orang ditampilkan bersama pemakaian kuota `1 / 2`. Tanpa melihat notes pun HR tahu individu itu terjadwal/pernah konseling.

`app/Http/Controllers/Perusahaan/RosterController.php:24-29` menghitung sesi setiap employee scheduled/done; `resources/views/perusahaan/roster.blade.php:49` menampilkan `used_month` pada baris identitas. Ini bertentangan eksplisit dengan `docs/Soluusi B2B - Perusahaan Vertical Spec.md:92,419` dan pesan invite `RosterController.php:57`. **Saran:** roster hanya administrasi akun/consent; pemakaian tetap aggregate, tidak per nama.

### H2 — P0 — Laporan “sel <5 tersembunyi” tidak menegakkan janji itu

**Skenario:** perusahaan kecil atau departemen satu orang memiliki satu sesi; laporan/CSV menunjukkan departemen dan topik count satu. Lima orang assessment dengan satu berisiko tinggi tetap memunculkan count/percent satu orang.

Janji view `resources/views/perusahaan/report.blade.php:36`; controller `app/Http/Controllers/Perusahaan/ReportController.php:37-57` hanya suppress WHO-5 jika **total** assessment <5. View `:86-110,127-150` mengeluarkan semua sel. Dashboard `app/Http/Controllers/Perusahaan/DashboardController.php:79-98,116-119,180-183` dan export `:325-345` tidak suppress department/topic; risk categories memakai total n, bukan per sel. Sekolah juga memakai pola sama di `DashboardController.php:62-95`, sehingga aggregate bukan otomatis anonim. Skenario reidentifikasi memerlukan pengetahuan roster/konteks; bukan bukti nama sesi keluar dari dashboard. **Saran:** aturan ukuran kelompok/sel berdasarkan orang unik, bukan jumlah sesi; terapkan konsisten di UI, insights, report, CSV dan secondary suppression bila diperlukan.

### H3 — P1 — HR onboarding buntu sebelum invite

**Skenario:** HR perusahaan baru membuka roster kosong. Tidak ada tambah/import/update karyawan; employee signup meminta consent yang HR tidak bisa catat melalui portal.

Routes HR `routes/web.php:85-91` hanya roster index/invite. `RosterController.php:18-64` tidak punya create/import/consent action. SelfRegister `:45-47` mensyaratkan roster dan `consent_at`. Roster menampilkan “Belum consent” (`resources/views/perusahaan/roster.blade.php:31-35`) tanpa aksi. Onboarding via ops/external import mungkin disengaja, tetapi tidak tercatat sebagai langkah operasional di UI. **Kategori:** gap perjalanan terkonfirmasi; bukan menyimpulkan tidak ada proses manual di organisasi. **Saran:** owner/SLA/link bantuan untuk onboarding manual, atau minimal create/import+consent yang memang dibutuhkan pengguna; tidak perlu dashboard baru.

### H4 — P2 — Partisipasi bisa >100%; departemen tanpa sesi hilang dari evaluasi

`app/Http/Controllers/Perusahaan/DashboardController.php:79-98` membagi **jumlah sesi sepanjang waktu** dengan jumlah employee, diberi label partisipasi. Sepuluh employee masing-masing dua sesi menghasilkan 200%; departemen nol sesi tidak masuk deptRows sehingga tidak bisa terpilih “partisipasi terendah” (`:105-112`). Mirror sekolah `app/Http/Controllers/Sekolah/DashboardController.php:60-72`. **Saran:** orang unik/periode yang jelas, tampilkan seluruh roster groups termasuk nol. Ini bukan tuntutan chart baru.

### H5 — P1/P2 — Pemilihan bulan laporan dapat salah periode atau error 500

`app/Http/Controllers/Perusahaan/ReportController.php:20-22` menerima query mentah lalu `Carbon::createFromFormat('Y-m', $month)->startOfMonth()`, tanpa validation dan tanpa reset day. Check mandiri: freeze `2026-10-31`; parse `2026-02` menghasilkan `2026-03-01`. Input malformed dapat melempar exception, bukan validation response. **Saran:** validasi exact Y-m, parse dengan reset fields, gunakan interval bulan konsisten. Tingkat P1 jika dipakai keputusan billing, P2 untuk laporan wellbeing saat ini.

### H6 — P2 — CSV bukan serializer CSV dan periodenya campur

`app/Http/Controllers/Perusahaan/DashboardController.php:323-346` menamai done/no-show “bulan ini” tetapi departemen/topik sepanjang waktu; nilai brand/department/topic disisipkan tanpa quoting. Nama dengan koma/newline merusak kolom; formula-prefix dari data yang bisa diedit perlu mitigation jika dikonsumsi spreadsheet. **Saran:** serializer CSV standar, periode tertulis konsisten; mitigasi formula pada trust boundary export. Ini bukan klaim payload sudah ada.

## Sekolah

### E1 — P1 — Parent consent diterima di form lalu hilang; siswa baru tidak dapat booking

**Skenario:** BK menambah siswa → undang → siswa centang persetujuan orang tua → akun berhasil dibuat → booking ditolak “Izin orang tua belum tercatat”. BK juga tidak bisa refer karena consent belum ada.

`app/Http/Controllers/Sekolah/RosterController.php:46-59` membuat roster tanpa consent. `app/Http/Controllers/SelfRegisterController.php:37-40,50-75` memvalidasi checkbox namun hanya menyimpan `user_id`; tidak mengisi `parent_consent_at`. Student `:151-153` dan BK `RosterController.php:78-80` mewajibkan timestamp. Search semua source aplikasi hanya menemukan pembacaan/cast/guard, tidak writer consent selain demo/test data. **Saran:** jalur pencatatan consent wali yang dapat diaudit; sekadar menyimpan checkbox siswa bukan bukti identitas wali. Spec sekolah `:262-263,312-317` mengharapkan form informed consent/signature.

### E2 — P1 — BK referral melewati kuota

BK `app/Http/Controllers/Sekolah/RosterController.php:65-98` mengecek tenant/consent/pool tetapi tidak quota count. Siswa penuh kuota tetap dapat menerima unlimited referral, sementara self-book ditolak `StudentController.php:154-157`; sesi referral masuk count yang sama. **Saran:** bila referral memang boleh override, tunjukkan override/owner/reason; bila tidak, pakai guard kuota yang sama. Tidak menganggap override policy sudah diputuskan.

### E3 — P2 — Invite siswa tidak menyertakan tautan registrasi

`app/Http/Controllers/Sekolah/RosterController.php:115-122` berkata “Daftarkan akun di halaman registrasi” tanpa URL. HR invite sudah memiliki route self-register (`app/Http/Controllers/Perusahaan/RosterController.php:51-55`). Siswa penerima email harus mencari sendiri `/daftar/siswa`, tidak tersedia di email. **Saran:** sertakan invitation aman dari S2, bukan hanya URL generic.

### E4 — P2 — Consent diketahui terlalu terlambat; tidak ada reschedule siswa

BookingForm `StudentController.php:111-130` tidak menampilkan state consent, tetapi submit `:153` menolak. CTA siswa `resources/views/sekolah/student.blade.php:52-60` tetap mengajak booking. Tindakan “hubungi BK” tidak memberi jalur pencatatan consent E1. Routes siswa `routes/web.php:73-80` menyediakan cancel tetapi tidak reschedule; portal `student.blade.php:108-109` cancel langsung tanpa konfirmasi. Spec `docs/Soluusi B2B - Sekolah Vertical Spec.md:319-320` mengharapkan cutoff. **Saran:** status prasyarat dan next action sebelum isi form; putuskan reschedule/cutoff sebagai aturan produk, bukan diam-diam menambah flow.

## Klinik

### K1 — P1 — Form schedule memasang submit handler pada form logout

`resources/views/klinik/schedules/create.blade.php:65-76` memakai `document.querySelector('form')`. Layout klinik memasukkan form logout terlebih dahulu (`resources/views/layouts/app.blade.php:76-79`). Form schedule `create.blade.php:17-58` hanya mengirim date/time_start/time_end; `started_at`/`ended_at` diciptakan JS tetapi handler terpasang pada form salah. Submit schedule tidak memiliki field wajib controller `app/Http/Controllers/Klinik/ScheduleController.php:46-49`; klik logout menjalankan handler yang mencari input date null. **Saran:** target form schedule eksplisit atau composisi server; cek dari browser halaman dengan layout lengkap. Tes POST controller tidak menangkap masalah ini.

### K2 — P1 — Slot berbayar dilepas setelah expired_at pembayaran

`CheckoutController.php:288-307` menyimpan `expired_at = now()+1h`. Settlement `MidtransController.php:60-67` mengubah verified/scheduled tetapi tidak membersihkan expired_at. Semua availability menambahkan expired filter pada **verified maupun waiting** (`CheckoutController.php:185-190,544-560`; `Klinik/CheckoutLinkController.php:64-68,115-119`; `Klinik/ConsultantController.php:40-44,148-152`; `MidtransController.php:193-198`).

**Skenario:** customer bayar hari ini untuk sesi besok. Setelah satu jam, sesi verified dianggap tidak memblokir slot; public clinic booking atau admin bisa membuat booking kedua. `Schedule::consultationSession()` hasOne (`app/Models/Schedule.php:57-60`) tidak menciptakan unique DB constraint; schema consultation sessions hanya FK (`database/migrations/2023_11_09_155601_create_consultation_sessions_table.php:18`). **Saran:** verified tetap reserved; expiry hanya untuk waiting. Satukan seluruh caller availability agar sibling tidak tertinggal.

### K3 — P1 — “Race guard” checkout tidak atomik

`CheckoutController.php:185-193,233-245` check exists lalu request gateway lalu insert; CheckoutLink store `:114-158` juga check lalu insert tanpa lock/transaction. Dua POST bersamaan melewati check; unique token tidak menjamin unique active schedule. Order ID bertumpu timestamp detik (`CheckoutController.php:196`) tidak idempotent retry yang dijanjikan comment. **Saran:** reservation atomik per schedule sebelum external API, stable idempotency key dan handling retry; schema harus mendukung reservasi/expired attempts tanpa memblokir booking valid selamanya.

### K4 — P1 — Public booking mengonsumsi link sebelum voucher/gateway berhasil

`CheckoutController.php:117-131` membuat link dengan `used_at=now()`, lalu startPayment memeriksa voucher `:171-178`, konfigurasi/gateway `:198-203,233-236`. Voucher invalid atau gateway error meninggalkan used link tanpa sesi. Jalur public selanjutnya tidak melihat link sebagai active karena `slotHasActiveLink` mensyaratkan used_at null (`:565-569`), jadi retry menciptakan link baru; bukan recovery transaksi yang sama. Jalur admin pay baru mengonsumsi setelah create session (`:239-246`). **Saran:** consume setelah booking/payment reservation benar-benar berhasil; error return menjelaskan recovery yang konsisten. Catatan: ini orphan/inconsistent state, bukan klaim slot terblokir permanen.

### K5 — P1 — Ledger voucher melebihkan hak net Soluusi

`CheckoutController.php:180-182` mengurangi gross dan menyatakan voucher ditanggung net. `app/Services/Klinik/SplitSettlementService.php:31-40` tetap mencatat net penuh dari snapshot tanpa `voucher_amount`. Contoh net 200.000 + margin 50.000, voucher 50.000: received 200.000; ledger net 200.000 + margin 50.000 = 250.000. **Saran:** ledger reconcile dengan received dan discount snapshot; margin tetap jika itu aturan bisnis. Tests `tests/Feature/KlinikMoneyFlowTest.php:69` menguji idempotence/milestone, bukan diskon ini.

### K6 — P1 — Callback lama bisa menurunkan pembayaran verified

Idempotence guard `MidtransController.php:42-49` hanya repeated settlement/capture. Signed pending yang datang setelah settlement menjalankan `:101-103` lalu `:119`, mengubah verified menjadi waiting; expire/cancel/failure juga menurunkan status, sementara split ledger tidak reversed. Endpoint signed cancel `:124-141` selalu overwrite. Signature verification memang ada; masalah **ordering/state transition**, bukan callback tanpa signature. **Saran:** status monoton untuk notification lama, reconcile ke provider bila perlu, refund/reversal ledger explicit. Jangan menganggap refund tercakup oleh tombol manual settle.

### K7 — P1/P2 — New-clinic schedule discovery membentuk lingkaran

`Klinik/ScheduleController.php:24-26` create tanpa consultant_id mengarahkan ke picker. Picker `Klinik/ConsultantController.php:35-47,70-72` hanya memuat konsultan yang sudah memiliki available schedule di klinik itu. Index schedule CTA menuju picker (`resources/views/klinik/schedules/index.blade.php:9`); empty picker menyuruh “tambahkan slot” tetapi hanya menyediakan Reset (`resources/views/klinik/consultants/index.blade.php:127-131`).

Ada escape path melalui nearby consultant di dashboard (`Klinik/DashboardController.php:86-97`, `resources/views/klinik/dashboard.blade.php:322-339`) lalu profil/schedule; **jadi bukan tidak mungkin sama sekali**. Tetapi nearby terfilter kota dan limit enam; klinik tanpa konsultan lokal/di luar enam tidak dapat menemukan melalui flow utama. **Saran:** bedakan picker “booking tersedia” dengan “pilih konsultan untuk membuat jadwal”; jangan minta pengguna menebak URL consultant_id.

### K8 — P1 — Completion bisa sebelum konsultasi; ops verify tidak punya guard pembayaran

Check-in/markDone `Klinik/SessionController.php:31-55` hanya status scheduled/owner, tanpa window waktu. Customer `CheckoutController.php:395-398` hanya paid/not verified; dapat confirm sebelum konsultan done walau tombol UI baru muncul sesudah done (`resources/views/checkout/status.blade.php:160`). tryVerify `SessionController.php:75-86` tidak cek jadwal telah lewat/payment/needs_ops_review, lalu memicu HPA. Ops verify `routes/web.php:173-179` tidak cek paid/scheduled/konsultan_done_at; tanpa done, ia memberi pesan sukses tetapi belum benar verified. **Saran:** definisikan state/time guards dan manual override reason/audit; jangan menjadikan dua timestamp bukti sesi berlangsung.

### K9 — P2 — Calendar fallback tidak dipulihkan oleh retry settlement

Settlement tetap scheduled saat Calendar/mail gagal, itu **sudah benar mencegah data-loss status** (`MidtransController.php:64-99`). Tetapi verified retry langsung record split dan return (`:42-49`), tidak retry meeting/mail. Schedule UI tidak memberi input meeting_url (`resources/views/klinik/schedules/create.blade.php:44-54`), tidak ada update schedule route (`routes/web.php:51-53`). Customer status menunggu link disiapkan (`resources/views/checkout/status.blade.php:123-128`) tanpa aksi ops repair di portal. Calendar membuat Meet juga pada offline schedule tanpa branch mode (`GoogleCalendarService.php:152-185`). **Saran:** manual repair/retry terpisah dan mode-aware; eksternal failure tidak boleh dianggap pulih otomatis.

## Friction dan gap produk yang perlu diputuskan, bukan otomatis bug baru

1. **CAP/Sekolah onboarding dan pembayaran B2B belum tersedia sebagai workflow portal.** Routes tidak punya tenant creation/membership/pool administration, contract/quota editor, invoice subscription, atau MHCU project. Models/seeder menyediakan data demo; aktivasi admin tersedia. Spec perusahaan `:91,126,136` meminta invoice; sekolah `:85,131-137` annual bundle. Kemungkinan operasi manual/phase rollout perlu owner/SLA; jangan menyebut Midtrans customer checkout solusi pembayaran subscription CAP.
2. **HR referral, MHCU battery, workshop/webinar, high-risk escalation, crisis SOP, retention/delete/access audit** masih gap terhadap full specs. WHO-5 self-assessment **sudah ada**, bukan pengganti MMCI/MMPI/MHCU massal. InsightEngine **sudah ada**, tetapi kartu rekomendasi bukan task assignment/completion tracking. Spec punya out-of-scope/open decisions; validasi scope sebelum membangun. Acuan: spec perusahaan `:60-78,369-378,491-494`; sekolah `:69,277,284,602`.
3. **Transfer uang aktual belum dibuktikan.** Split service hanya ledger DB; comment `SplitSettlementService.php:12-14` mengklaim config split.on tetapi tidak ada implementasi gateway split di service/caller. Admin settle `routes/web.php:163-170` hanya status/timestamp. Manual payout boleh sah untuk MVP, tetapi UI/operasional harus menyebut “dicatat sudah ditransfer”, bukan transfer otomatis. Refund/cancel klinik customer dan no-show operational flow tidak memiliki routes lengkap.
4. **Cutoff cancel/reschedule tidak sesuai full spec.** Employee cancel/reschedule hanya cek scheduled; Student cancel sama; tanpa cutoff/reason. Perusahaan spec `:403-407` H-2, sekolah `:319-320` H-24. Tentukan kebijakan/biaya/quota dahulu. Ini berbeda dari kepemilikan cancel, yang sudah dijaga.
5. **KlinikPackage vs ConsultantPackage membingungkan.** Paket klinik read-only di dashboard (`Klinik/DashboardController.php:125-126`), tetapi checkout membeli ConsultantPackage (`CheckoutController.php:78-93`, `Klinik/CheckoutLinkController.php:86-107`). Tidak ada mapping booking untuk paket program/custom yang tampil. Putuskan apakah paket program hanya sales collateral atau dapat dipesan; label next action jelas, jangan menduplikasi engine paket.
6. **Status dan istilah membentuk ekspektasi yang belum terpenuhi:** “Soluusi pilihkan” menyimpan consultant_id null tanpa assignment flow (S6); “terjadwal” belum berarti slot dikonfirmasi konsultan (S4); “kuota bulan ini” bukan batas bulan nyata (S5); “data anonim” bukan suppress semua sel (H2); “preview super admin” dapat menulis assessment/booking sungguhan karena memakai method yang sama (`EmployeeController.php:45-51,101-118`; `StudentController.php:33-38,88-105`). Preview sebaiknya diberi warning atau dibuat read-only bila produk bermaksud simulasi.

## Urutan kerja disarankan

1. **Sebelum data nyata:** S2 self-register ownership, H1 kebocoran pemakaian per nama, H2 anonimitas laporan. Pastikan consent wali dapat dibuktikan, bukan checkbox siswa saja.
2. **Buka jalan tugas utama:** S1 interaksi form, K1 schedule form, E1 consent pipeline, H3 onboarding; lalu S6 assignment/delivery/completion CAP/Sekolah. Jangan mempercantik dashboard sebelum sesi bisa benar-benar diselesaikan.
3. **Integritas transaksi:** K2/K3 slot reservation seluruh callers, S4 availability lintas vertikal, S5/E2 quota; K5 ledger diskon, K6 state callback, K4 retry/error recovery.
4. **Kejelasan harian:** S7 mobile/logout, S8 brand/title, K7 empty-state discovery, E3 invite, K9 repair meeting; H4/H5/H6 metrik/periode/export; putuskan cutoff dan proses manual.

## Coverage check yang masih diperlukan

- Browser nyata desktop/mobile: setiap role login → menu → form → validation retry → next step, termasuk input consent, keyboard/modal, dropdown/type filtering, empty tenant, CDN gagal. HTTP 200 bukan bukti form usable.
- Regression test tanpa menyentuh DB nyata: identity invitation, privacy n/sel, quota lintas bulan/paralel, referral override, paid slot setelah expiry, webhook out-of-order, voucher reconciliation, schedule form payload, CAP/Sekolah completion. Test sekarang dominan POST/render; tidak mencakup JavaScript browser atau monetary cases tersebut (`tests/Feature/HpaKlinikPortalTest.php`, `PerusahaanWellbeingTest.php`, `SekolahWellbeingTest.php`, `KlinikMoneyFlowTest.php`).
- Database target produksi dan migration parity: audit menemukan raw `CAST(... AS INTEGER)` pada dashboard Klinik/Admin (`Klinik/DashboardController.php:35`, `AdminDashboardController.php:49,87,118`); jangan menganggap SQLite lint/test membuktikan kompatibilitas MySQL. Tidak dieksekusi ke MySQL, sehingga dicatat sebagai risiko kompatibilitas, bukan runtime error terobservasi.
- Live payment/gcal/mail/scheduler, actual split payout, domain/receipt white-label, deployment access/security logging, retention policy: tidak terverifikasi. Audit PDF bisnis dan setiap mail template/migration B2C historis tidak menyeluruh; fokus alur executable tiga vertikal dan schema terkait. Tidak ada perubahan kode aplikasi.
