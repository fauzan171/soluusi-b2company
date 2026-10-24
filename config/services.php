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

    // AI Insight (HR/BK) — gateway OpenAI-compatible, model fixed glm-5.3-flash
    // (paritas TVS kiosk). Key kosong = panel menampilkan fallback rule-based.
    'ai_insight' => [
        'base_url' => env('AI_INSIGHT_BASE_URL', 'http://127.0.0.1:20128/v1'),
        'key' => env('AI_INSIGHT_API_KEY'),
        'model' => env('AI_INSIGHT_MODEL', 'tg/glm-5.3-flash'),
    ],

];
