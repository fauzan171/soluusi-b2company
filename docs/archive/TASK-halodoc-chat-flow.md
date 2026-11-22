# Task: Halodoc-style Chat Flow

**Baseline:** 52/52 tests pass. Branch `main`. Push target `main:laravel-clinic`.

## Scope

Ubah flow chat konsultan jadi ala Halodoc: picker → chat langsung (bukan lewat booking form). Tiga fase, tiap fase bisa ship terpisah.

---

## Phase 1 — Consultant Picker + Instant Chat (MVP)

**Goal:** Employee bisa pilih konsultan dari list, langsung masuk chat room, tanpa lewat booking form.

### 1.1 Route & Controller
- [ ] `GET /perusahaan/consultants` — list konsultan aktif, card UI (foto, nama, kategori, rating, pengalaman)
- [ ] `POST /perusahaan/consultants/{consultant}/start-chat` — buat `PerusahaanSession` baru (`scheduled_at=null`, `status=scheduled`, `consultant_id=X`), redirect ke chat room
- [ ] Employee dengan sesi aktif (un-completed) ke konsultan yang sama → redirect ke sesi lama, bukan duplikat

### 1.2 View
- [ ] `resources/views/perusahaan/consultants.blade.php` — grid card, search box, filter kategori
- [ ] Ambassador pola existing: pakai `portal-card`, `pine-*` color tokens, responsive grid

### 1.3 Guard & Edge Case
- [ ] Konsultan tanpa user aktif → hidden dari list
- [ ] Employee belum consent → redirect ke consent flow
- [ ] Session created tapi konsultan belum accept → chat room tampil "Menunggu konsultan bergabung"

**Test file:** `tests/Feature/ConsultantPickerTest.php`

---

## Phase 2 — Chat Inbox (Semua Role)

**Goal:** Setiap role punya halaman `/chats` yang list semua sesi aktif + unread count.

### 2.1 Route & Controller
- [ ] `GET /perusahaan/chats` — employee inbox
- [ ] `GET /consultant/chats` — konsultan inbox
- [ ] `GET /klinik/chats` — klinik admin (monitor semua sesi kliniknya, read-only)

### 2.2 PerusahaanSession Enhancement
- [ ] Tambah kolom `last_message_at` (datetime, nullable) — update otomatis saat `ChatMessage` created
- [ ] Tambah helper `unreadCountFor(User $user)` — hitung `ChatMessage` where `sender_id != $user->id` and `read_at` null
- [ ] Migration + model update

### 2.3 View
- [ ] `resources/views/perusahaan/chats.blade.php` — list style WhatsApp: avatar, nama, last message preview, unread badge, timestamp
- [ ] `resources/views/consultant/chats.blade.php` — sama, tapi dari sisi konsultan
- [ ] `resources/views/klinik/chats.blade.php` — read-only monitor, group by perusahaan

### 2.4 Sidebar Update
- [ ] Badge unread di sidebar nav (employee, consultant, klinik) — pakai existing `$unreadCount` pattern kalau ada, atau query langsung

**Test file:** `tests/Feature/ChatInboxTest.php`

---

## Phase 3 — Session Lifecycle + Presence

**Goal:** Chat bisa diakhiri, rating muncul, indikator online ada.

### 3.1 End Session
- [ ] `POST /perusahaan/sessions/{session}/end` — employee/konsultan akhiri sesi, status jadi `completed`, `ended_at` set
- [ ] Setelah end, redirect ke rating form (existing `ConsultantRating` model)

### 3.2 Online Presence
- [ ] Tambah `last_seen_at` ke `users` table
- [ ] Middleware update `last_seen_at` tiap request
- [ ] Di chat room + picker tampil indikator: dot hijau (online < 5 menit), abu (offline)

### 3.3 Auto-end
- [ ] Console command `sessions:auto-end` — auto complete sesi yang `scheduled_at` < now - 24 jam dan masih `scheduled`
- [ ] Schedule di Kernel: tiap jam

**Test file:** `tests/Feature/ChatLifecycleTest.php`

---

## QA Gates (Harus Lolos Tiap Phase)

1. **Unit/Feature:** `php artisan test` — 52 baseline + test baru per phase
2. **Route smoke:** Semua route baru return 200 untuk role yang benar, 302 untuk guest
3. **Guard test:** User lain (bukan owner sesi) tetap 403
4. **UI check:** Buka `/perusahaan/consultants`, `/perusahaan/chats`, `/consultant/chats` — render tanpa error
5. **Regression:** Existing `/perusahaan/employee`, `/consultant/sessions`, `/klinik/dashboard` tetap 200

## Out of Scope

- Push notification (Telegram/email) — butuh queue worker
- Attachment file — butuh storage + virus scan
- WebSocket real-time — PHP-FPM polling 5s cukup
- Video call — terlalu besar untuk fase ini

## Estimasi

| Phase | Effort | Deliverable |
|-------|--------|-------------|
| 1 | 0.5 hari | Picker page + start-chat route |
| 2 | 1 hari | Inbox untuk 3 role + unread badge |
| 3 | 0.5 hari | End session + presence + auto-end |

**Total: 2 hari kerja**

---

## File Changes Summary

| File | Action | Phase |
|------|--------|-------|
| `routes/web.php` | EDIT — tambah route | 1,2,3 |
| `app/Http/Controllers/Perusahaan/EmployeeController.php` | EDIT — tambah method `consultants()`, `startChat()` | 1 |
| `app/Http/Controllers/ChatController.php` | EDIT — tambah method `inbox()` per role | 2 |
| `app/Models/Perusahaan/PerusahaanSession.php` | EDIT — tambah `last_message_at`, `unreadCountFor()` | 2 |
| `app/Models/ChatMessage.php` | EDIT — booted event update `last_message_at` | 2 |
| `database/migrations/` | CREATE — add `last_message_at` to sessions, `last_seen_at` to users | 2,3 |
| `resources/views/perusahaan/consultants.blade.php` | CREATE | 1 |
| `resources/views/perusahaan/chats.blade.php` | CREATE | 2 |
| `resources/views/consultant/chats.blade.php` | CREATE | 2 |
| `resources/views/klinik/chats.blade.php` | CREATE | 2 |
| `resources/views/layouts/app.blade.php` | EDIT — sidebar badge | 2 |
| `app/Http/Middleware/UpdateLastSeen.php` | CREATE | 3 |
| `app/Console/Commands/AutoEndSessions.php` | CREATE | 3 |
| `tests/Feature/ConsultantPickerTest.php` | CREATE | 1 |
| `tests/Feature/ChatInboxTest.php` | CREATE | 2 |
| `tests/Feature/ChatLifecycleTest.php` | CREATE | 3 |

---

## QA Swarm

Setelah tiap phase selesai, dispatch **QA agent** untuk:
1. Verify semua test pass (52 baseline + N baru)
2. Smoke test semua route yang terpengaruh
3. Check edge case: foreign access, empty state, validation
4. Report: pass/fail per criterion dengan evidence

**QA Agent goal template:**
```
As QA, verify acceptance criteria for Phase N in /home/hermes/workspace/projects/soluusi-b2b-klinik.
Run: php artisan test, smoke test routes via curl, check guard behavior.
Return: pass/fail per criterion with command output.
```
