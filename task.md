# Task: Sekolah Flow Parity + Mode Khusus

## Goal
Samakan flow konsultasi sekolah dengan perusahaan (parity 11/12) sambil menambah mode khusus sekolah: BK bisa request untuk siswa, siswa self-request, auto-provision akun siswa, chat + AI summary + advice card, pervorma SAP trigger saat sesi done, role `sekolah_staff` (mirror employee), dan mode orangtua email-only (tanpa akun login).

## Success Criteria
- [ ] Siswa bisa self-request konsultasi (`role:student` → form + submit, route ada).
- [ ] BK bisa request atas nama siswa (refer flow → session `pending`, request_notes tersimpan).
- [ ] Akun siswa auto-provision saat pertama kali di-request (lewat `RosterInvitation`, user_id terisi).
- [ ] Orangtua menerima email (no user account dibuat) — verifiable via `Mail::fake()` di test.
- [ ] Chat siswa ↔ konsultan berfungsi (start, end, message, rate) — route `sekolah.chat*`.
- [ ] AI summary + advice card muncul untuk sesi sekolah yang done (SessionSummary record ada dengan `sekolah_session_id` terisi).
- [ ] `PervormaService::evaluate('sap', …)` ter-call otomatis saat sesi sekolah → done.
- [ ] `BkReviewController` parity dengan `HrReviewController` (index/approve/reject).
- [ ] `SekolahSessionPolicy` authorize seluruh aksi BK + siswa + konsultan.
- [ ] Role `sekolah_staff` mirror employee (UI booking reuse komponen PR #4).
- [ ] Semua test pass: `php artisan test --testsuite=Feature --filter=Sekolah`.
- [ ] `vendor/bin/pint --test` clean.

## Phases

### Phase A: Schema & Foundation
**Depends on:** none

#### Task A.1: Migration — kolom polymorphic + field parity (S, parallel_group: schema)
- **Status:** DONE
- **Files:**
  - `database/migrations/2026_11_17_000001_add_sekolah_support_to_chat_tables.php` (new)
  - `database/migrations/2026_11_17_000002_add_parity_fields_to_sekolah_sessions_table.php` (new)
- **Action:**
  - Tambah `sekolah_session_id` nullable + index di `chat_messages`, `session_summaries`, `session_slot_proposals`. `perusahaan_session_id` diubah jadi nullable. CHECK constraint: exactly one of the two non-null (SQLite-compatible via raw `DB::statement` guard di migrate up, skip di test sqlite jika perlu — ponytail: pakai conditional driver check).
  - Tambah `request_notes` (text nullable), `schedule_version` (unsignedInteger default 1), `requested_by_user_id` (FK users nullable), `requested_for_role` (enum-ish string: 'self'|'bk'|'parent', default 'self') di `sekolah_sessions`.
- **Verify:** `php artisan migrate:fresh --seed --env=testing` exit 0
- **Acceptance:** Kolom ada di kedua DB sqlite+mysql schema; rollback sukses.

#### Task A.2: Update model relations & fillable (S, parallel_group: schema)
- **Status:** DONE
- **Files:**
  - `app/Models/ChatMessage.php`
  - `app/Models/SessionSummary.php`
  - `app/Models/SessionSlotProposal.php`
  - `app/Models/Sekolah/SekolahSession.php`
- **Action:**
  - Tambah `sekolah_session_id` ke fillable + relasi `sekolahSession()` belongsTo di 3 model pertama. Mutator: set salah satu FK harus null-kan yang lain.
  - `SekolahSession`: tambah `request_notes`, `schedule_version`, `requested_by_user_id`, `requested_for_role` ke fillable; relasi `chatMessages()`, `sessionSummaries()`, `slotProposals()`; relasi `requestedBy()` → User.
- **Verify:** `php artisan tinker --execute="dd((new \App\Models\Sekolah\SekolahSession)->getFillable());"` exit 0
- **Acceptance:** Tinker print menunjukkan 4 field baru.

#### Task A.3: SekolahSessionPolicy (M, parallel_group: policy) [x]
- **Files:**
  - `app/Policies/SekolahSessionPolicy.php` (new)
  - `app/Providers/AuthServiceProvider.php` (register policy)
- **Action:** Mirror `PerusahaanSessionPolicy`. Method: `view`, `update`, `cancel`, `reschedule`, `chat`, `rate`, `bkReview` (untuk approve/reject oleh BK), `requestFor` (BK request atas nama siswa). Scope: BK hanya tenant-nya, siswa hanya sesi miliknya, konsultan hanya sesi yang di-assign ke dia, `sekolah_staff` seperti employee.
- **Verify:** `php artisan test --filter=SekolahSessionPolicyTest`
- **Acceptance:** Policy test (TDD, ditulis dulu) hijau untuk 7 method × 4 role (bk/student/sekolah_staff/consultant) happy + negative path.

---

### Phase B: Request Flow (BK + Self-Request + Auto-Provision)
**Depends on:** Phase A

#### Task B.1: Student self-request endpoint (M, parallel_group: request)
- **Status:** DONE (da57ca0)
- **Files:**
  - `app/Http/Controllers/Sekolah/StudentController.php` (extend)
  - `resources/views/sekolah/siswa/request-form.blade.php` (new, reuse komponen PR #4)
  - `routes/web.php` (tambah 2 route di group `role:student`)
- **Action:** Tambah `requestConsultation` form + store. Validasi: konsultan exists, slot future, throttle 10/1. Buat `SekolahSession` status `pending`, `requested_by_user_id=auth`, `requested_for_role='self'`. Reuse komponen booking UI dari PR #4.
- **Verify:** `php artisan test --filter=SekolahStudentRequestTest`
- **Acceptance:** Test: student POST → session pending dibuat; unauthorized role → 403; invalid payload → 422.

#### Task B.2: BK refer/request atas nama siswa + auto-provision (M, parallel_group: request)
- **Status:** DONE (fbc9acf)
- **Files:**
  - `app/Http/Controllers/Sekolah/RosterController.php` (extend `refer`)
  - `app/Services/Sekolah/StudentProvisioner.php` (new)
- **Action:**
  - `StudentProvisioner::ensure(SekolahStudent $student): User` — jika `student.user_id` null, buat `User` role `student` + kirim `RosterInvitation` (audit trail), link `user_id`. Idempotent.
  - `RosterController::refer` panggil `StudentProvisioner::ensure()` lalu buat `SekolahSession` dengan `requested_for_role='bk'`, `requested_by_user_id=bk_user_id`.
- **Verify:** `php artisan test --filter=SekolahBkReferTest`
- **Acceptance:** Test: BK refer siswa baru → user dibuat, invitation queued, session pending; refer siswa existing → reuse user.

#### Task B.3: Orangtua email-only (no login) (S, parallel_group: request)
- **Status:** DONE (fbc9acf)
- **Files:**
  - `app/Http/Controllers/Sekolah/RosterController.php` (extend `refer` — parent mode)
  - `app/Mail/Sekolah/ParentConsultationRequested.php` (new)
  - `resources/views/emails/sekolah/parent-consultation-requested.blade.php` (new)
- **Action:** Tambah field `parent_email` di form refer. Jika diisi: JANGAN buat user untuk parent. Kirim mail ke parent berisi detail sesi + link publik read-only (signed URL → show sesi summary). Audit `requested_for_role='bk'` + `parent_notified_at` timestamp.
- **Verify:** `php artisan test --filter=SekolahParentEmailTest`
- **Acceptance:** `Mail::fake()` assert mail terkirim ke parent; tidak ada user baru dengan email parent.

---

### Phase C: Chat + AI Summary
**Depends on:** Phase A

#### Task C.1: SekolahChatController (L, parallel_group: chat)
- **Files:**
  - `app/Http/Controllers/Sekolah/ChatController.php` (new)
  - `resources/views/sekolah/chat/*.blade.php` (new — reuse komponen PR #4 chat partials)
  - `routes/web.php` (tambah routes di group `role:student,sekolah_staff` + consultant side)
- **Action:** Mirror `App\Http\Controllers\ChatController`. Method: `student`, `studentStore`, `studentEnd`, `studentRate`, `consultant`, `consultantStore`, `consultantStart`, `consultantEnd`. Semua type-hint `SekolahSession`. Authorize via `SekolahSessionPolicy`. Throttle: store 60/1, lainnya 10/1.
- **Verify:** `php artisan test --filter=SekolahChatTest`
- **Acceptance:** Test: student kirim pesan → `chat_messages.sekolah_session_id` terisi; consultant start/end; rate 1-5 tersimpan.

#### Task C.2: Extract GenerateSessionSummary → polymorphic (M, parallel_group: chat)
- **Status:** DONE (2771057)
- **Files:**
  - `app/Jobs/GenerateSessionSummary.php` (refactor)
  - `app/Services/SessionSummaryGenerator.php` (new, logic dipindah)
- **Action:** Job terima `sessionId` + `vertical` (`perusahaan|sekolah`). Service handle kedua model. SessionSummary polymorphic via `perusahaan_session_id` XOR `sekolah_session_id`. Update semua call site (ChatController lama).
- **Verify:** `php artisan test --filter=GenerateSessionSummaryTest`
- **Acceptance:** Test existing perusahaan tetap hijau + test baru sekolah hijau. Kedua job produce SessionSummary dengan FK yang benar.

#### Task C.3: Advice card UI untuk siswa/BK (S, parallel_group: chat)
- **Files:**
  - `resources/views/sekolah/siswa/session-detail.blade.php` (extend/new)
  - `app/Http/Controllers/Sekolah/StudentController.php` (extend show)
- **Action:** Render advice card dari SessionSummary (reuse partial dari perusahaan PR #4). Tampil di detail sesi siswa + dashboard BK (read-only).
- **Verify:** `php artisan test --filter=SekolahAdviceCardTest` (HTTP test assert see text)
- **Acceptance:** Sesi done → advice card muncul di UI siswa + BK.

---

### Phase D: Pervorma SAP + BK Review
**Depends on:** Phase B, C

#### Task D.1: SAP pervorma trigger saat sesi done (S, parallel_group: pervorma)
- **Files:**
  - `app/Http/Controllers/Sekolah/ChatController.php` (consultantEnd hook)
  - `app/Observers/SekolahSessionObserver.php` (new — alternative cleaner hook)
- **Action:** Saat `SekolahSession.status` berubah → `done`, dispatch `PervormaService::evaluate('sap', $session->sekolah)`. Pakai observer untuk single-source trigger.
- **Verify:** `php artisan test --filter=SekolahPervormaTriggerTest`
- **Acceptance:** Test: tandai sesi done → `sekolah_pervormas` row updated (stage count +1).

#### Task D.2: BkReviewController (M, parallel_group: review)
- **Files:**
  - `app/Http/Controllers/Sekolah/BkReviewController.php` (new)
  - `resources/views/sekolah/review/*.blade.php` (new — mirror `perusahaan/review`)
  - `routes/web.php` (tambah di group `role:bk`)
- **Action:** Method `index`, `approve`, `reject`. Mirror HrReviewController. Approve → status `approved`; Reject → `rejected` + catat reason. Authorize `bkReview` policy.
- **Verify:** `php artisan test --filter=SekolahBkReviewTest`
- **Acceptance:** Test: BK approve/reject; non-BK 403; tenant isolation (BK A tidak bisa review sesi sekolah B).

---

### Phase E: Role sekolah_staff + Polish
**Depends on:** Phase B, C

#### Task E.1: Role sekolah_staff + seeder + middleware (S, parallel_group: role)
- **Files:**
  - `database/seeders/RoleSeeder.php` (extend)
  - `database/migrations/2026_11_17_000003_add_sekolah_staff_role.php` (new — insert role)
  - `app/Http/Middleware/EnsureUserHasRole.php` (verify sudah support)
- **Action:** Tambah role `sekolah_staff`. Migration insert role record. Seeder update. Staff bisa booking atas nama diri sendiri (mirror employee flow). UI: reuse komponen booking PR #4.
- **Verify:** `php artisan test --filter=SekolahStaffRoleTest`
- **Acceptance:** User dengan role `sekolah_staff` bisa akses `/sekolah-portal/staff/*` mirror `/perusahaan/employee/*`.

#### Task E.2: StaffController + routes (S, parallel_group: role)
- **Files:**
  - `app/Http/Controllers/Sekolah/StaffController.php` (new — mirror `Perusahaan/EmployeeController`)
  - `routes/web.php`
- **Action:** Method: `index`, `assessmentForm`, `assessmentStore`, `bookingForm`, `bookingStore`, `requestConsultation`, `bookingCancel`, `bookingReschedule`. Reuse `BookingService::book(..., type: 'sekolah')` yang sudah support.
- **Verify:** `php artisan test --filter=SekolahStaffFlowTest`
- **Acceptance:** Staff bisa self-booking end-to-end.

#### Task E.3: UX parity pass — reuse komponen PR #4 (S, parallel_group: ux)
- **Files:** `resources/views/sekolah/**/*.blade.php`
- **Action:** Audit visual: dashboard BK, daftar students, form request, chat, advice card. Reuse partials kelas/tokens dari PR #4 booking UI redesign. Tidak ada style inline baru.
- **Verify:** `vendor/bin/pint --test` exit 0 + manual screenshot diff (tooling existing `shot-pages.mjs`)
- **Acceptance:** Pint clean; visual diff vs PR #4 baseline konsisten (no inline style baru).

---

### Phase F: Tests + Lint + Docs
**Depends on:** Phase A–E

#### Task F.1: Full feature suite + edge cases (M, parallel_group: qa)
- **Files:** `tests/Feature/Sekolah/*` (semua file test baru + existing update)
- **Action:** Pastikan coverage: parity matrix (11 flow perusahaan × sekolah), tenant isolation, role-based access, pervorma trigger, auto-provision idempotent, parent email-only no user created, race condition (concurrent refer).
- **Verify:** `php artisan test --testsuite=Feature` exit 0
- **Acceptance:** Seluruh suite hijau.

#### Task F.2: Pint + static analysis (S, parallel_group: qa)
- **Files:** all touched
- **Action:** `vendor/bin/pint` + pastikan tidak ada `@phpstan-ignore` baru.
- **Verify:** `vendor/bin/pint --test` exit 0
- **Acceptance:** Clean.

#### Task F.3: Update docs (S, parallel_group: qa)
- **Files:** `docs/flows/sekolah.md` (new) + `README.md` (link)
- **Action:** Dokumentasi flow parity, role matrix, pervorma trigger, auto-provision logic.
- **Verify:** file exists + markdown lint
- **Acceptance:** Dokumen lengkap.

## Status: READY-FOR-KULI
