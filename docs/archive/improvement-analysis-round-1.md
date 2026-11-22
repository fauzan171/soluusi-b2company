# Audit Profesionalisme B2B — Soluusi B2B Klinik (Round 1)

Tanggal: 2026-11-05. Auditor: UX/product analyst (read-only). Cakupan: seluruh blade di `resources/views`, `routes/web.php`, dan empat user journey (HR perusahaan, karyawan, konsultan, BK sekolah).

Ringkasan: fondasi desain (token pine/terra, portal-card, dash-card) kuat di dashboard utama, tapi menurun tajam di halaman sekunder (laporan, roster, sesi konsultan) yang masih bergaya prototipe. Beberapa flow punya dead-end nyata (konsultan tanpa dashboard, chat tanpa navigasi balik).

---

## Temuan

### A-01 — Area: Layout/navigasi · Severity: critical
Tidak ada navigasi utama sama sekali. `layouts/app.blade.php` hanya render logo + `@yield('content')` — tanpa menu, tanpa link ke halaman lain, tanpa indikator posisi. Sidebar "NAVIGASI" hanya muncul saat `$impersonator` (mode super admin). Pengguna normal nyangkut: dari dashboard HR tidak ada jalan ke Roster Karyawan, Laporan, dll kecuali lewat tombol "Quick actions" di bawah halaman.
Saran: tambahkan top-nav/sidebar permanen per role (Dashboard, Karyawan, Laporan, dst) dengan state aktif.

### A-02 — Area: Chat · Severity: critical
`chat/room.blade.php` tidak punya link kembali. Setelah masuk chat, satu-satunya jalan keluar adalah tombol back browser. Judul "Chat {peer}" tanpa breadcrumb.
Saran: tambah link "← Kembali ke riwayat sesi" (route employee/consultant sesuai konteks).

### A-03 — Area: Flow konsultan · Severity: high
Konsultan tidak punya dashboard. Route `/consultant` hanya `sessions`; `/consultant/sessions` (`consultant/sessions.blade.php`) adalah tabel mentah tanpa header portal-card, tanpa tombol kembali, tanpa filter/search, tanpa empty-state design. Judul "Sesi Saya" + paragraf soal "rate paket konsultan" bercampur dengan tabel sesi CAP perusahaan yang ditumpuk di atas tabel sesi klinik — dua tabel berbeda konteks dalam satu halaman tanpa pemisah visual jelas.
Saran: buat dashboard konsultan minimal (sesi hari ini, pending scheduling, link chat) + pisahkan tabel CAP vs klinik dengan heading section + styling portal-card.

### A-04 — Area: Flow konsultan · Severity: high
Penjadwalan sesi perusahaan dilakukan lewat `<select onchange="this.form.submit()">` inline di dalam sel tabel — tanpa konfirmasi, tanpa feedback loading, mudah salah pilih slot. Tidak ada aksi "tandai selesai" untuk sesi perusahaan (hanya sesi klinik), jadi flow "terima sesi → jadwalkan → chat → selesai" patah di ujung.
Saran: modal konfirmasi pilih slot; tambah aksi complete untuk sesi CAP.

### A-05 — Area: Laporan HR · Severity: high
`perusahaan/report.blade.php` adalah HTML mentah tanpa class sama sekali: `<h1>`, `<dl>`, `<ul>`, `<button>` default browser, form filter tanpa styling, tombol "Cetak / PDF" default. Kontras total dengan dashboard yang polished — terlihat "belum jadi". Link ke halaman ini justru ditonjolkan dari dashboard ("Laporan bulanan →").
Saran: styling dengan portal-card/dash-card, tabel terformat, tombol pine konsisten.

### A-06 — Area: Roster karyawan · Severity: high
`perusahaan/roster.blade.php` (dirujuk sebagai "Kelola karyawan") setengah mentah: form tambah karyawan tanpa styling input (label+input default), tombol "Simpan" default, `<details>` consent tanpa styling. Empty-state "Belum ada karyawan terdaftar" tanpa CTA padahal form tambah tepat di atasnya. Tidak ada aksi edit/hapus/nonaktifkan karyawan — HR tidak bisa koreksi typo email atau offboard karyawan resign.
Saran: styling form konsisten, tambah aksi edit/nonaktif, empty-state dengan CTA.

### A-07 — Area: Copy · Severity: high
Campuran bahasa Indonesia–Inggris tidak konsisten di seluruh portal: "Aggregate only", "Aggregate after-session", "Data-driven", "Operational snapshot", "Next 7 Days", "Engagement", "Capacity", "Leading topic", "Line trend", "Weekly bar", "Quick actions", "Executive pulse", "command center", "out of 5" (label chart NPS). Untuk pengguna HR/BK Indonesia, label label Inggris teknis ini menurunkan kesan profesional dan membingungkan.
Saran: tetapkan panduan bahasa: UI utama Bahasa Indonesia, istilah teknis (WHO-5, NPS) tetap Inggris tapi diberi padanan.

### A-08 — Area: Copy sekolah · Severity: high
`sekolah/dashboard.blade.php` hasil copy-paste dashboard perusahaan tanpa penyesuaian: baris 118 "…command center yang rapi untuk evaluasi **HR** mingguan", baris 564 "Operasional **HR** mingguan", komentar "kondisi yang butuh perhatian **HR**". Variabel juga masih `$activeEmployeesMonth` untuk konteks siswa. Badge konsultan menggunakan teks "Konsultan dari Soluusi" sama persis.
Saran: ganti HR → guru BK, audit seluruh copy sekolah per baris, rename variabel.

### A-09 — Area: Karyawan portal · Severity: medium
`perusahaan/employee.blade.php`: modal reschedule memuat SEMUA slot tersedia lintas jenis konsultan (`BookingService::available('perusahaan', ...)`) tanpa filter sesuai `konsultan_type` sesi yang diubah — label sendiri bilang "sesuai jenis konsultan" tapi daftar tidak difilter. Hal yang sama di booking: dropdown jenis konsultan tidak memfilter daftar slot (select statis, tidak ada JS cascading).
Saran: filter slot per jenis konsultan (server-side query param atau JS).

### A-10 — Area: Karyawan portal · Severity: medium
Copy tidak konsisten register bahasa: "Butuh cerita? Konsultan siap dengerin", "Yuk mulai dengan pilih jadwal", "booking pertamamu", "Booking lagi bulan depan ya" (sangat kasual) bercampur dengan copy formal B2B di dashboard HR ("Executive pulse wellbeing", "command center"). Untuk produk yang dibeli perusahaan, register kasual boleh untuk karyawan, tapi inkonsisten dalam satu halaman (mis. "bagus, terus jaga ritmenya" vs tabel riwayat formal).
Saran: tetapkan tone per persona: HR = formal-profesional; karyawan = hangat tapi konsisten.

### A-11 — Area: Form & feedback · Severity: medium
Banyak aksi tanpa feedback/validasi UX: login page tidak menampilkan error apapun (tidak ada `@error`/session error di `auth/login.blade.php`) — salah password hanya redirect tanpa pesan tampak. Tidak ada "lupa password". Tidak ada link ke halaman register/self-register dari login (dead-end bagi user baru yang dapat undangan). Tombol submit umumnya tanpa state loading/disable (risiko double-submit booking).
Saran: tampilkan error auth, link lupa password, link aktivasi undangan, disable-on-submit.

### A-12 — Area: Aksesibilitas · Severity: medium
Hampir semua form input tidak terasosiasi dengan `<label for>` (login, roster, assessment pakai label-wrap sebagian, roster tidak sama sekali). Tabel riwayat karyawan: seluruh `<tr>` jadi clickable via Alpine tanpa `role="button"`/`tabindex` — tidak bisa diakses keyboard. Modal pakai div + x-show tanpa `role="dialog"`/`aria-modal`, fokus tidak di-trap, meski Escape sudah ditangani. Kontras `text-gray-400` di atas putih untuk teks 11px borderline. `x-cloak` dipakai tapi tidak terlihat didefinisikan di CSS layout (risiko flash konten modal — perlu `[x-cloak]{display:none}` global).
Saran: label/for, role+keyboard untuk row clickable, aria-modal + focus trap, cek kontras, pastikan rule x-cloak ada.

### A-13 — Area: Desain sistem · Severity: medium
Inkonsistensi komponen: tombol primary kadang `rounded` (booking), kadang `rounded-lg` (employee modal), kadang `rounded-xl` (sekolah); warna primary di auth login `bg-emerald-700` sedangkan seluruh portal `bg-pine-700`; badge status kadang `rounded` (roster) kadang `rounded-lg` (employee) kadang `rounded-full` (dashboard). Border color kadang `border` default, kadang `border-[#e7e2d9]`. Halaman 419 pakai palet slate, berbeda total dari brand pine.
Saran: definisikan komponen Blade `<x-button>`, `<x-badge>`, `<x-card>`; samakan radius token; styling ulang halaman error dengan brand.

### A-14 — Area: Empty state · Severity: medium
Kualitas empty-state timpang: dashboard & chat punya copy empty-state yang baik, tapi booking ("Belum ada slot tersedia. Hubungi pengelola program." — tanpa info kontak/cara menghubungi), konsultan ("Belum ada sesi."), roster sekolah ("Belum ada siswa. Tambah lewat form." — form tersembunyi di tab lain) minimalis tanpa CTA yang jelas.
Saran: standar empty-state = ilustrasi/teks + CTA actionable.

### A-15 — Area: Flow siswa · Severity: medium
Siswa yang sesinya dijadwalkan guru BK (`bk_referral`) bisa membatalkan sesi sendiri (tombol "Batalkan" tampil untuk semua status scheduled) tanpa peringatan bahwa sesi dijadwalkan sekolah — kontra-intuitif dan berisiko konflik dengan kebijakan sekolah. Tidak ada konfirmasi pada tombol Batalkan di `sekolah/student.blade.php` (beda dengan perusahaan yang pakai `confirm()` + alasan wajib).
Saran: blokir/beri peringatan pembatalan sesi referral; tambah konfirmasi konsisten.

### A-16 — Area: Roster sekolah · Severity: medium
Form consent ortu di dalam `<details>` per baris tanpa styling, tombol "undang" lowercase tidak konsisten dengan perusahaan ("Undang via email"). Kolom tabel header pakai `border-b` tanpa styling seragam dengan roster perusahaan. Tidak ada tombol kembali ke dashboard dari roster sekolah (perusahaan punya "← Dashboard").
Saran: samakan komponen tabel/form lintas vertikal; tambah link kembali.

### A-17 — Area: Konsultan · Severity: medium
`consultant/sessions.blade.php` menampilkan "Anonim #{{ employee_id }}" — mengekspos ID internal database ke UI sebagai identitas. Fungsional ok, tapi "Anonim #1234" terlihat developer-ish, bukan copy produk. Juga tabel tanpa `portal-card`, header tanpa styling.
Saran: label "Karyawan (anonim)" tanpa ID, atau ID diformat sebagai kode sesi.

### A-18 — Area: Login/branding · Severity: low
Halaman login polos tanpa logo Soluusi, tanpa nama produk, tanpa konteks multi-tenant (user tidak tahu ini portal perusahaan/sekolah/klinik yang mana). `max-w-sm` card putih di atas bg-paper tanpa elemen brand.
Saran: tambah logo + tagline + konteks brand tenant jika tersedia.

### A-19 — Area: Booking · Severity: low
Info mode sesi (online/offline) hanya muncul sebagai teks mentah `{{ $slot->mode }}` di opsi dropdown. Tidak ada penjelasan perbedaan mode, tidak ada info durasi sesi, tidak ada info konsultan selain firstname. Untuk layanan kesehatan mental, pilihan konsultan sebaiknya menampilkan kredensial singkat.
Saran: tampilkan durasi, mode sebagai badge, nama+spesialisasi konsultan.

### A-20 — Area: Karyawan · Severity: low
Modal pembatalan sesi karyawan: form "Alasan pembatalan" + tombol "Batalkan" dirender inline dalam grid 2 kolom bersebelahan dengan tombol "Ubah jadwal" — layout sempit, input alasan tanpa styling (`class="w-full border rounded"`), dan form tidak full-width sehingga tombol Batalkan tidak sejajar. Secara visual terlihat rusak.
Saran: jadikan pembatalan langkah/modal terpisah dengan form full-width.

### A-21 — Area: Teknis UI · Severity: low
Chart.js + Alpine di-load via CDN di banyak halaman (duplikasi tag script per halaman). Inline `<style>` block besar di dashboard perusahaan & sekolah (duplikat identik ~60 baris). Menandakan belum ada ekstraksi ke asset build.
Saran: pindahkan ke `resources/css` + Vite; load CDN sekali di layout.

### A-22 — Area: Copy · Severity: low
Inkonsistensi istilah: "konsultan" vs "konselor" vs "psikolog" dipakai campur; "Minta Konsultasi" vs "Booking Sesi Konsultasi" vs "Booking konseling" untuk aksi yang sama; "pervorma" (typo "performa"?) dipakai sebagai istilah produk di admin/associate tanpa penjelasan — terlihat seperti typo bagi pembaca luar.
Saran: glosarium istilah produk; pastikan "pervorma" memang branding (jika ya, jelaskan; jika tidak, koreksi).

### A-23 — Area: Pending pages · Severity: low
Halaman pending perusahaan/sekolah tidak konsisten satu sama lain (perusahaan: card amber dengan ikon jam; sekolah: teks polos `py-20`). Pesan berbeda padahal situasi sama.
Saran: satu komponen halaman pending dipakai lintas vertikal.

---

## Verdict — Top 10 Prioritas

1. **A-01** Navigasi utama per role (tanpa ini produk terasa belum jadi).
2. **A-02** Tombol kembali di chat room.
3. **A-05** Styling halaman laporan HR (halaman yang justru dipromosikan dari dashboard).
4. **A-06** Roster karyawan: styling form + aksi edit/nonaktif karyawan.
5. **A-08** Bersihkan copy hasil copy-paste "HR" di dashboard sekolah.
6. **A-03+A-04** Dashboard konsultan + flow penjadwalan/penyelesaian sesi CAP.
7. **A-11** Error login, lupa password, link aktivasi undangan, disable-on-submit.
8. **A-07** Konsistensi bahasa (standarisasi Bahasa Indonesia di UI).
9. **A-09** Filter slot booking/reschedule per jenis konsultan.
10. **A-13** Komponen desain terstandar (button/badge/card) + samakan warna auth dengan brand.

Total temuan: 23 (critical 2, high 6, medium 9, low 6).
