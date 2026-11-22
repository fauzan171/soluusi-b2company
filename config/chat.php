<?php

// W5 (K11): polling chat — interval adaptif klien (3s fokus / 10s blur) + retensi purgable.
return [
    'retention_days' => env('CHAT_RETENTION_DAYS', 90),
];
