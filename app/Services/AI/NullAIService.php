<?php

namespace App\Services\AI;

use App\Contracts\AIService;

/**
 * No-op provider — fitur AI off. generateInsight tidak pernah dipanggil
 * (AiInsightService short-circuit ke not_configured sebelum resolve).
 */
class NullAIService implements AIService
{
    public function generateInsight(array $payload): array
    {
        return [];
    }
}
