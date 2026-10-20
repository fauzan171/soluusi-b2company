<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | B2B: hanya block google (Socialite SSO admin klinik + konsultan) +
    | google_calendar. Drop postmark/resend/ses/slack/zhipu_ai/deepseek/
    | gemini/ga4/gsc — mesin content/AI/SEO soluusi.com, irrelevant klinik
    | (Dev Preparation §4.6).
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'google_calendar' => [
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_CALENDAR_REFRESH_TOKEN'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        // B2B migration path (§15.11): service account + Workspace Delegation.
        'service_account_json' => env('GOOGLE_CALENDAR_SERVICE_ACCOUNT_JSON', storage_path('app/gcal-service-account.json')),
        'service_account_subject' => env('GOOGLE_CALENDAR_SERVICE_ACCOUNT_SUBJECT'),
    ],

];
