# Research E — Online Meeting Links (GMeet/Zoom) for Consultation Sessions

## 0. Current state (codebase evidence)

| Fact | Location |
|---|---|
| `link_meeting` nullable column already on `consultation_sessions` | `database/migrations/2023_11_27_160617_..._link_meeting.php` |
| `google_calendar_event_id` nullable column already exists | `database/migrations/2026_02_15_232733_..._google_calendar_event_id...php` |
| `schedules.meeting_url` manual URL field exists (validated `nullable\|url`, editable by klinik) | `app/Http/Controllers/Klinik/ScheduleController.php:62,90` |
| `GoogleCalendarService::createEvent()` already creates a Calendar event **with Meet conferenceData** (`hangoutsMeet` + `conferenceDataVersion: 1`) | `app/Services/GoogleCalendarService.php:152-185` |
| On payment settle, Meet link is auto-saved best-effort: `link_meeting = meet_link ?? event_link` | `app/Http/Controllers/MidtransController.php:121-127` |
| Service account config exists: `services.google_calendar.service_account_json` (default `storage/app/gcal-service-account.json`) + `service_account_subject` (DWD) with OAuth-user refresh-token fallback | `config/services.php:23-31`, `GoogleCalendarService.php:32-59` |
| Per-klinik calendar `kliniks.gcal_id` (white-label) | `2026_08_05_000010_create_kliniks_table.php:23` |
| Join-button gating in checkout status: link shown only `payment_status === 'verified'`, fallback text "Link meeting akan tampil di sini setelah klinik/konsultan menyiapkan ruang sesi" | `resources/views/checkout/status.blade.php:123-129` |
| `link_meeting ?: schedule.meeting_url` fallback chain | `resources/views/checkout/status.blade.php:36` |
| Mailables `Client/LinkMeeting` + `Konsultan/LinkMeeting` exist but have **no sender** (dead B2C code); consultant sessions view has plain `Link Meet →` | `app/Mail/Klinik/*/LinkMeeting.php`, `resources/views/consultant/sessions.blade.php:140-141` |

Note: `storage/app/gcal-service-account.json` is NOT present in this checkout — service-account path is code-ready but the credential file (and Workspace/DWD setup) is a deployment prerequisite.

## 1. Manual field vs API-generated

**Manual** (consultant/klinik pastes GMeet/Zoom link): zero new dependencies, works with any provider, matches existing `schedules.meeting_url` + `link_meeting` columns and the existing "link akan tampil" empty-state UI. Cons: consultant effort per session, typo risk, no calendar invite to customer.

**API-generated**: link + calendar invite emitted automatically at payment-settle (the exact flow `MidtransController` already runs). Cons: provider-specific auth setup per klinik; failure modes need the manual field as backstop anyway.

The app is a hybrid already: GMeet link auto-generated at settle, `schedules.meeting_url` as manual fallback. The only true gap is **manual override/correction of a session's link** (no controller writes `link_meeting` from user input).

## 2. Zoom API — current state

- **Auth**: Server-to-Server OAuth (account-level token, no user interaction) — https://developers.zoom.us/docs/internal-apps/
- **Create meeting**: `POST /users/{userId}/meetings` with scope `meeting:write:admin` (S2S apps act on behalf of the account owner).
- **Quotas** (https://developers.zoom.us/docs/api/rate-limits/): free/basic plan = Light 4/sec 6,000/day; Medium 2/sec 2,000/day; Heavy 1/sec 1,000/day. Per-user cap: **100 create-or-update meeting/webinar calls per user per day (UTC), 429 on excess** — this is the "100 meetings/day" figure.
- **Free account blockers**: Basic (free) plan caps **group meetings at 40 minutes** (https://zoom.us/pricing) — unusable for paid consultations of 60+ min without a Pro license. API-created meetings are hosted by the account owner, so the *account* needs a paid license, not just quota. New dependency (no Zoom SDK installed; `composer.json` only has `google/apiclient`).
- **Verdict**: Zoom = extra paid account + new integration for the same outcome GMeet already delivers free. Skip for v1; add later only if klinik partners demand Zoom specifically.

## 3. Google Meet — feasibility with existing config

- Classic path: **Meet links are created via Google Calendar API** `conferenceData.createRequest` + `conferenceDataVersion=1`; `entryPoints[].uri` (type `video`) is the Meet URL — https://developers.google.com/calendar/api/guides/create-events. Exactly what `GoogleCalendarService` already implements.
- **Service account path**: requires Google Workspace + domain-wide delegation configured in Admin console (API Controls → Domain wide delegation; client ID + comma-delimited scopes), impersonating a Workspace user via `setSubject()` — https://developers.google.com/identity/protocols/oauth2/service-account. This matches `service_account_subject` in `config/services.php:30`. Propagation up to 24h; missing scopes → `access_denied`.
- **User-OAuth fallback path** (refresh token, already in config): works for consumer `primary` calendar; Google adds Meet conference data to events for consumer accounts too (Meet is free for personal Google accounts). Caveat: token expiry/re-auth is manual, and per-klinik white-label calendars (`kliniks.gcal_id`) only make sense on Workspace.
- The newer **Google Meet REST API v2** (`POST https://meet.googleapis.com/v2/spaces`, scope `https://www.googleapis.com/auth/meetings.space.created`) creates bare spaces without Calendar — https://developers.google.com/meet/api/reference/rest/v2/spaces/create — but it's OAuth-user-only (no service-account flow documented) and gives no invite/reminders, so it adds nothing over the existing Calendar path here.

## 4. Data model recommendation

- Keep `consultation_sessions.link_meeting` (string, nullable) as the single source of truth for the join URL. No enum column: provider is derivable (URL prefix `meet.google.com` / `zoom.us`) and nothing in the app branches on provider. YAGNI.
- Keep `google_calendar_event_id` for idempotent calendar updates.
- Keep `schedules.meeting_url` as klinik-level default.
- **Gating** (mostly already implemented): customer sees link only when `payment_status = verified` AND `status` in (`scheduled`, `done`-within-window is overkill). Current checkout view already gates on payment only — acceptable for v1 since links only get written at settle anyway. Do NOT expose link via API to unauthenticated/guest checkout (token-gated checkout status page already handles this).
- Visibility to employee vs consultant-only-until-start: unnecessary complexity for v1 — link lands only after payment, and the customer needs it before start (email `SudahBayar` + status page). Gate on payment, not on start time.

## 5. Final recommendation (v1)

1. **Keep the existing architecture as-is**: manual `schedules.meeting_url` default + auto GMeet generation at payment-settle via `GoogleCalendarService` (already coded, best-effort, non-fatal). It is the simplest robust path and requires zero new code for the happy path.
2. **Add one small gap-filler**: a klinik/consultant endpoint to **set/override `link_meeting`** on a session (validated `nullable|url`), so a failed GMeet call or a klinik that prefers Zoom can be fixed manually. Reuses existing column, existing empty-state UI text already covers this case.
3. **Prereq to verify at deploy**: `GOOGLE_CALENDAR_SERVICE_ACCOUNT_JSON` + Workspace DWD, or the refresh-token fallback; `kliniks.gcal_id` per white-label klinik.
4. **Zoom**: skip. Paid license required for >40-min meetings + 100 create/day/user cap + new dependency; the GMeet flow already covers the need. Add later only on explicit klinik demand.
5. **Meet REST API v2**: skip; user-OAuth-only, no calendar invite benefit.

Skipped: `meeting_provider` enum (derivable from URL), join-time gating (payment gating suffices), Zoom integration. Add provider enum only when provider-specific behavior (e.g. Zoom start-URL vs join-URL) actually lands.

## Sources

- Zoom S2S OAuth: https://developers.zoom.us/docs/internal-apps/
- Zoom rate limits (incl. 100 create/update per user/day, free-plan quotas): https://developers.zoom.us/docs/api/rate-limits/
- Zoom pricing (Basic = 40-min group meetings): https://zoom.us/pricing
- Google Calendar create events + Meet conferenceData: https://developers.google.com/calendar/api/guides/create-events
- Google service accounts / domain-wide delegation: https://developers.google.com/identity/protocols/oauth2/service-account
- Google Meet REST API v2 spaces.create (scope `meetings.space.created`): https://developers.google.com/meet/api/reference/rest/v2/spaces/create
- Meet API manage spaces guide: https://developers.google.com/meet/api/guides/manage-meeting-spaces
