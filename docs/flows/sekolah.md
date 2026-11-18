# Sekolah Portal — Flow Parity

How the Sekolah vertical's three end-user flows work, and how the staff
flow (added in `goal/sekolah-flow-parity`) mirrors the student flow.

## Role matrix

| Role | Tenant binding | Portal | Can do |
|---|---|---|---|
| `admin` | none (picks tenant via `?sekolah_id=`) | any | everything, read-only preview for staff/student portals |
| `bk` | `sekolah_admins.role = 'bk'` | `/sekolah-portal/dashboard` | dashboard, roster, report, BK review, request on behalf of a student |
| `student` | `sekolah_students.user_id` | `/sekolah-portal/siswa` | assessment, booking, request consultation, chat, rating |
| `sekolah_staff` | `sekolah_admins.role = 'sekolah_staff'` **and** `sekolah_students.type = 'staff'` | `/sekolah-portal/staff` | same self-service flows as `student`, no guardian consent |

Tenant resolution is `App\Support\SekolahContext::current()`: super admin picks
explicitly, everyone else resolves through `SekolahAdmin::where('user_id')`.
More than one membership aborts 403 (ambiguous), an inactive tenant aborts 403
except on `sekolah.dashboard`, and a `suspended` tenant is blocked at login by
`AuthController::tenantSuspended()`.

## Flow parity: student ↔ staff

| Flow | Student route | Staff route | Shared code |
|---|---|---|---|
| Portal home | `sekolah.student` | `sekolah.staff` | — (separate views) |
| WHO-5 assessment form | `sekolah.assessment.form` | `sekolah.staff.assessment.form` | `sekolah/assessment.blade.php`, `StudentController::WHO5_ITEMS` |
| Assessment submit | `sekolah.assessment.store` | `sekolah.staff.assessment.store` | — (each controller writes `SekolahAssessment`) |
| Booking form | `sekolah.booking` | `sekolah.staff.booking` | `sekolah/booking.blade.php`, `BookingService::available()` |
| Booking submit | `sekolah.booking.store` | `sekolah.staff.booking.store` | `BookingService::book('sekolah', $person, $data)` |
| Request consultation | `sekolah.siswa.request` | `sekolah.staff.request` | pending-review convention (below) |
| Cancel pending request | `sekolah.siswa.request.cancel` | `sekolah.staff.request.cancel` | `SessionStateMachine::cancel()` |
| Cancel booked session | `sekolah.booking.cancel` | `sekolah.staff.booking.cancel` | `BookingService::cancel()` |
| Reschedule | `sekolah.booking.reschedule` | `sekolah.staff.booking.reschedule` | `BookingService::reschedule()` |
| Chat | `sekolah.chat` | `sekolah.staff.chat` | `ChatController::student*` + `SekolahSessionPolicy` |

Both shared Blade views take an optional `$action` variable; it defaults to the
student store route, and `StaffController` passes its own. That keeps the two
views byte-identical in output while letting each portal post to its own route.

## The `type` discriminator

`sekolah_students` is the person table for everyone who consumes the program —
students *and* staff. A `type` column (`'student'` default, `'staff'`) separates
them so the BK-facing aggregates keep counting only real students:

- `Sekolah::students()` — filtered to `type = 'student'`. Used by
  `DashboardMetrics::build()`, `ProgramAggregate::monthly()`, and
  `AiInsightService::buildContext()` for `registered`, `quotaTotal`, and
  per-kelas grouping.
- `Sekolah::staff()` — the same rows filtered to `type = 'staff'`.
- `Sekolah::sessions()` — excludes sessions whose person row is staff, so the
  "sessions this month" aggregate is unchanged by staff activity.
- `SekolahAssessment::scopeStudentRoster()` — applied to the assessment
  aggregates in `ProgramAggregate` and `AiInsightService`.

Existing rows default to `'student'`, so the migration needs no data backfill.

## Consent

| Person | Gate |
|---|---|
| `student` | `parent_consent_at` must be set — `BookingService::book()` aborts 403 otherwise |
| `sekolah_staff` | `consent_at` (self-consent; staff are adults) |

`BookingService::book()` branches on `$row->isStaff()` so the two gates never
cross. Staff booking also requires `sekolahs.is_active`.

## Pending review convention

There is no `pending` status value. A consultation request is a
`sekolah_sessions` row with:

- `status = 'scheduled'`
- `scheduled_at = NULL`
- `consultant_id = NULL`
- `requested_by_user_id`, `requested_for_role = 'self'`

BK sees these in `/sekolah-portal/review` and approves (assigns a consultant and
slot) or rejects (records a reason). Rejecting goes through
`SessionStateMachine`, not a raw update.

## Chat authorization

`SekolahSessionPolicy::chat()` allows the session's owner person row
(`SekolahStudent.user_id`) or the assigned consultant, and requires
`status = 'scheduled'`. Staff reach their own sessions through the same arm —
the person row they own is theirs — so no policy change was needed for the
staff chat routes. `rate()` additionally requires `status = 'done'`.

## Tenant isolation

`TenantScope('sekolah')` is a global scope, inert unless a user is
authenticated and the vertical resolves to a concrete tenant. Cross-tenant rows
are hidden, so route model binding returns 404 rather than leaking existence —
fail-closed by construction, and stricter than a 403.