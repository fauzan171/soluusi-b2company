<?php

return [
    /*
    | Provider AI aktif. 'null' = tidak ada provider (fitur AI off,
    | dashboard jatuh ke InsightEngine rule-based).
    */
    'provider' => env('AI_PROVIDER', 'null'),

    'providers' => [
        'null' => \App\Services\AI\NullAIService::class,
        'tokengateway' => \App\Services\AI\TokengatewayAIService::class,
        // 'openai' => \App\Services\AI\OpenAIService::class, // future
    ],
];
