<?php

namespace Tests\Feature;

use App\Services\AiInsightService;
use Tests\TestCase;

/**
 * T6.1 wiring — model & endpoint AI insight resolve dari config, bukan hardcode.
 * Memastikan gateway Tokengateway (env AI_INSIGHT_*) benar-benar dipakai.
 */
class AiInsightConfigTest extends TestCase
{
    public function test_model_resolves_from_config(): void
    {
        config()->set('services.ai_insight.model', 'glm-5.3-flash');
        $this->assertSame('glm-5.3-flash', $this->invokeModel());
    }

    public function test_model_falls_back_to_const_when_config_blank(): void
    {
        config()->set('services.ai_insight.model', '');
        $this->assertSame(AiInsightService::MODEL, $this->invokeModel());
    }

    public function test_gateway_base_url_is_tokengateway(): void
    {
        $this->assertSame('https://tokengateway.dev/v1', config('services.ai_insight.base_url'));
    }

    private function invokeModel(): string
    {
        $m = new \ReflectionMethod(AiInsightService::class, 'model');
        $m->setAccessible(true);
        return $m->invoke(null);
    }
}
