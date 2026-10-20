<?php

namespace App\Services;

use App\Models\ConsultationSession;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventAttendee;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_ConferenceSolutionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    protected Google_Client $client;
    protected Google_Service_Calendar $calendarService;
    protected string $calendarId;

    public function __construct(?string $calendarId = null)
    {
        $this->client = new Google_Client();
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');

        // B2B CHANGE (§15.11): service account + Workspace Delegation jika JSON
        // ada; fallback OAuth-user (dev / match B2C).
        $saJson = config('services.google_calendar.service_account_json');
        if ($saJson && file_exists($saJson)) {
            $this->client->setAuthConfig($saJson);
            $subject = config('services.google_calendar.service_account_subject');
            if ($subject) {
                $this->client->setSubject($subject);
            }
        } else {
            $this->client->setClientId(config('services.google_calendar.client_id'));
            $this->client->setClientSecret(config('services.google_calendar.client_secret'));

            // Fetch a fresh access token using the refresh token
            $refreshToken = config('services.google_calendar.refresh_token');

            if (empty($refreshToken)) {
                Log::error('GoogleCalendar: GOOGLE_CALENDAR_REFRESH_TOKEN is not set in .env');
                throw new \Exception('Google Calendar refresh token is not configured.');
            }

            $accessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($accessToken['error'])) {
                Log::error('GoogleCalendar: Token refresh failed', $accessToken);
                throw new \Exception('Google Calendar token refresh failed: ' . ($accessToken['error_description'] ?? $accessToken['error']));
            }

            $this->client->setAccessToken($accessToken);
        }

        $this->calendarService = new Google_Service_Calendar($this->client);
        $this->calendarId = $calendarId ?: config('services.google_calendar.calendar_id', 'primary');
    }

    /**
     * Create a Google Calendar event with Google Meet link for a consultation session.
     *
     * @param ConsultationSession $session
     * @return array|null Returns event data or null on failure
     */
    public function createEvent(ConsultationSession $session): ?array
    {
        try {
            // Load relationships
            $session->load(['schedule', 'user.regency', 'consultant.user', 'consultantPackage', 'klinik', 'customerRegency']);

            if (!$session->schedule) {
                Log::warning('GoogleCalendar: Session #' . $session->id . ' has no schedule.');
                return null;
            }

            $consultantName = trim(
                ($session->consultant->user->firstname ?? '') . ' ' .
                ($session->consultant->user->lastname ?? '')
            );
            // B2B CHANGE: customer = guest (Model C) — data dari kolom customer_*,
            // fallback user B2C-style.
            $clientName = $session->customer_display_name;
            $packageName = $session->consultantPackage->name ?? 'Konsultasi';

            // B2B CHANGE (white-label §10.8): brand dari klinik, BUKAN "Soluusi".
            $brand = \App\Support\Brand::for($session);

            // Build the event
            $event = new Google_Service_Calendar_Event();

            $event->setSummary("{$brand} - {$packageName}");
            // Calculate client details
            $clientAge = '-';
            $dob = $session->customer_dob ?? $session->user?->date_of_birth;
            if (!empty($dob)) {
                $clientAge = \Carbon\Carbon::parse($dob)->age . ' Tahun';
            }
            $clientGender = $session->customer_gender ?? $session->user?->gender ?? '-';
            $clientRegency = $session->customerRegency->name ?? $session->user?->regency?->name ?? '-';
            $clientComplaint = $session->complaint ?? '-';

            $event->setDescription(
                "Nama : {$clientName}\n" .
                "Umur : {$clientAge}\n" .
                "Jenis Kelamin : {$clientGender}\n" .
                "Kota Domisili : {$clientRegency}\n" .
                "Mohon jelaskan kondisi, kebutuhan utama, serta tujuan yang ingin kamu capai melalui layanan ini, termasuk konteks aktivitas harian atau situasi yang relevan (isian ini membantu konsultan memahami kebutuhan kamu sebelum sesi dimulai) : {$clientComplaint}\n\n" .
                "Konsultan: {$consultantName}\n" .
                "Paket: {$packageName}\n" .
                "No. Order: {$session->order_number}\n\n" .
                "Sesi konsultasi ini telah dibayar dan dijadwalkan secara otomatis oleh {$brand}."
            );

            // Start time
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($session->schedule->started_at->toRfc3339String());
            $start->setTimeZone('Asia/Jakarta');
            $event->setStart($start);

            // End time
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($session->schedule->ended_at->toRfc3339String());
            $end->setTimeZone('Asia/Jakarta');
            $event->setEnd($end);

            // Attendees: client + consultant
            $attendees = [];

            $clientEmail = $session->customer_email;
            if ($clientEmail) {
                $clientAttendee = new Google_Service_Calendar_EventAttendee();
                $clientAttendee->setEmail($clientEmail);
                $clientAttendee->setDisplayName($clientName);
                $attendees[] = $clientAttendee;
            }

            if ($session->consultant->user->email) {
                $consultantAttendee = new Google_Service_Calendar_EventAttendee();
                $consultantAttendee->setEmail($session->consultant->user->email);
                $consultantAttendee->setDisplayName($consultantName);
                $attendees[] = $consultantAttendee;
            }

            $event->setAttendees($attendees);

            // Conference data (Google Meet)
            $conferenceData = new Google_Service_Calendar_ConferenceData();
            $createRequest = new Google_Service_Calendar_CreateConferenceRequest();
            $createRequest->setRequestId(Str::uuid()->toString());

            $solutionKey = new Google_Service_Calendar_ConferenceSolutionKey();
            $solutionKey->setType('hangoutsMeet');
            $createRequest->setConferenceSolutionKey($solutionKey);

            $conferenceData->setCreateRequest($createRequest);
            $event->setConferenceData($conferenceData);

            // Send email notifications to attendees
            $event->setGuestsCanModify(false);
            $event->setGuestsCanInviteOthers(false);

            // Reminders
            $event->setReminders(new \Google_Service_Calendar_EventReminders([
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 60],
                    ['method' => 'popup', 'minutes' => 30],
                ],
            ]));

            // Insert the event
            $createdEvent = $this->calendarService->events->insert(
                $this->calendarId,
                $event,
                [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => 'all', // Send email invites to attendees
                ]
            );

            $meetLink = null;
            if ($createdEvent->getConferenceData() && $createdEvent->getConferenceData()->getEntryPoints()) {
                foreach ($createdEvent->getConferenceData()->getEntryPoints() as $entryPoint) {
                    if ($entryPoint->getEntryPointType() === 'video') {
                        $meetLink = $entryPoint->getUri();
                        break;
                    }
                }
            }

            Log::info('GoogleCalendar: Event created for session #' . $session->id, [
                'event_id' => $createdEvent->getId(),
                'event_link' => $createdEvent->getHtmlLink(),
                'meet_link' => $meetLink,
            ]);

            return [
                'event_id' => $createdEvent->getId(),
                'event_link' => $createdEvent->getHtmlLink(),
                'meet_link' => $meetLink,
            ];
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: Failed to create event for session #' . $session->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
