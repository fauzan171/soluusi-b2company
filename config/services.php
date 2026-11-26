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

    // AI Insight (HR/BK) — Tokengateway OpenAI-compatible, model glm-5.3-flash
    // (satu-satunya model yang gateway expose; diverifikasi live via /v1/models).
    // Key kosong = panel menampilkan fallback rule-based (InsightEngine).
    // SSO konsultan dari dashboard B2C (HMAC shared secret, sama di .env B2C).
    // Kosong = endpoint /sso/b2c mati (404).
    'b2c_sso' => [
        'secret' => env('B2C_SSO_SECRET'),
    ],

    'ai_insight' => [
        'base_url' => env('AI_INSIGHT_BASE_URL', 'https://tokengateway.dev/v1'),
        'key' => env('AI_INSIGHT_API_KEY'),
        'model' => env('AI_INSIGHT_MODEL', 'glm-5.3-flash'),
    ],

];
