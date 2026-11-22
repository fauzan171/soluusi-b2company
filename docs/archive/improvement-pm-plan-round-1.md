# PM Plan — Round 1 (Top 10 Prioritas)

Tanggal: 2026-11-05. Berdasarkan analysis-round-1.md (23 temuan, 10 prioritas).

## Task List

### T1: Navigasi Utama per Role [A-01, critical]
**Effort:** L  
**Dependency:** none  
**Acceptance Criteria:**
- Layout `app.blade.php` punya top-nav/sidebar permanen
- Menu berbeda per role: HR (Dashboard, Karyawan, Laporan), Konsultan (Sesi Saya), BK (Dashboard, Siswa, Laporan)
- State aktif ter-highlight
- Responsive (hamburger di mobile)
- Test: akses tiap halaman dari nav, back button berfungsi

### T2: Back Button Chat Room [A-02, critical]
**Effort:** S  
**Dependency:** none  
**Acceptance Criteria:**
- Link "← Kembali ke riwayat sesi" di `chat/room.blade.php`
- Route sesuai konteks: employee → `perusahaan.employee`, consultant → `consultant.sessions`
- Test: dari chat room, klik back → kembali ke halaman sebelumnya

### T3: Styling Laporan HR [A-05, high]
**Effort:** M  
**Dependency:** T1  
**Acceptance Criteria:**
- `perusahaan/report.blade.php` pakai `portal-card`/`dash-card`
- Tabel terformat (thead styling, zebra rows)
- Tombol "Cetak / PDF" pakai `btn-pine`
- Form filter styling konsisten
- Test: visual inspection, tidak ada HTML mentah

### T4: Roster Karyawan — Styling + Aksi [A-06, high]
**Effort:** L  
**Dependency:** T1  
**Acceptance Criteria:**
- Form tambah karyawan styling konsisten (input, label, tombol)
- `<details>` consent styling
- Empty-state dengan CTA "Tambah karyawan pertama"
- Aksi per baris: edit, nonaktifkan
- Test: tambah/edit/nonaktif karyawan berhasil, validasi form

### T5: Copy Dashboard Sekolah [A-08, high]
**Effort:** S  
**Dependency:** none  
**Acceptance Criteria:**
- Semua "HR" di `sekolah/dashboard.blade.php` → "guru BK"
- Variabel `$activeEmployeesMonth` → `$activeStudentsMonth` (jika ada)
- Copy konsisten untuk konteks sekolah
- Test: grep tidak ada "HR" di blade sekolah

### T6: Dashboard Konsultan + Flow Scheduling/Complete [A-03+A-04, high]
**Effort:** L  
**Dependency:** T1, T2  
**Acceptance Criteria:**
- Route `/consultant` redirect ke dashboard konsultan baru
- Dashboard: sesi hari ini, pending scheduling, link chat
- `consultant/sessions.blade.php` pisah tabel CAP vs klinik dengan heading
- Scheduling pakai modal konfirmasi (bukan select inline)
- Aksi "Tandai selesai" untuk sesi CAP
- Test: konsultan bisa jadwalkan → chat → selesai; sesi CAP bisa di-complete

### T7: Login Error + Lupa Password + Aktivasi [A-11, high]
**Effort:** M  
**Dependency:** none  
**Acceptance Criteria:**
- `auth/login.blade.php` tampilkan error auth (session('error') / @error)
- Link "Lupa password" (route password.request)
- Link aktivasi undangan (jika ada token)
- Disable-on-submit di semua form (prevent double-submit)
- Test: login salah → error muncul; submit form → tombol disabled

### T8: Konsistensi Bahasa [A-07, high]
**Effort:** M  
**Dependency:** none  
**Acceptance Criteria:**
- UI utama Bahasa Indonesia
- Istilah teknis (WHO-5, NPS) tetap Inggris + padanan Indonesia
- Ganti: "Aggregate only" → "Agregat saja", "Next 7 Days" → "7 Hari Ke Depan", dst
- Test: review copy di 5 halaman utama

### T9: Filter Slot per Jenis Konsultan [A-09, medium]
**Effort:** M  
**Dependency:** none  
**Acceptance Criteria:**
- Dropdown jenis konsultan filter daftar slot (server-side atau JS)
- Modal reschedule hanya tampilkan slot sesuai `konsultan_type` sesi
- Test: booking dengan filter jenis konsultan → slot sesuai

### T10: Komponen Desain Terstandar [A-13, medium]
**Effort:** L  
**Dependency:** T1, T3, T4  
**Acceptance Criteria:**
- Blade components: `<x-button>`, `<x-badge>`, `<x-card>`
- Radius konsisten: `rounded-lg` untuk card/button, `rounded-full` untuk badge
- Warna auth `bg-emerald-700` → `bg-pine-700`
- Halaman 419/error pakai brand pine
- Test: komponen terpakai di minimal 3 halaman

## Urutan Eksekusi

```
T1 (L) ─┬─→ T3 (M) ─┬─→ T10 (L)
        ├─→ T4 (L) ─┤
        └─→ T6 (L) ─┘
T2 (S) ─┘
T5 (S) ──── independent
T7 (M) ──── independent
T8 (M) ──── independent
T9 (M) ──── independent
```

**Critical path:** T1 → T3/T4/T6 → T10  
**Quick wins:** T2, T5, T8 (bisa paralel)

## Estimasi Total

- L: 3 task (T1, T4, T6, T10)
- M: 5 task (T3, T7, T8, T9)
- S: 2 task (T2, T5)

Total: ~3L + 5M + 2S

## Notes

- Semua task harus lewat QA sebelum merge
- Branch: `laravel-clinic` (jangan push ke `main`)
- Style: ponytail — smallest diff, no over-engineering
