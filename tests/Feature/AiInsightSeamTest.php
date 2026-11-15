<?php

namespace Tests\Feature;

use App\Contracts\AIService;
use App\Models\Perusahaan\Perusahaan;
use App\Services\AI\NullAIService;
use App\Services\AiInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * T6.1 — AI service seam: interface + provider via env + per-tenant flag.
 */
class AiInsightSeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_interface_bound_and_resolves_null_by_default(): void
    {
        config()->set('ai.provider', 'null');
        $this->assertTrue(interface_exists(AIService::class));
        $this->assertInstanceOf(NullAIService::class, app(AIService::class));
    }

    public function test_ai_disabled_tenant_short_circuits_without_http_call(): void
    {
        Http::fake();
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('ai.provider', 'tokengateway');

        $tenant = Perusahaan::create([
            'name' => 'PT Disabled', 'brand' => 'T',
            'sessions_per_employee_month' => 1, 'employee_count' => 1,
            'is_active' => true, 'ai_enabled' => false,
        ]);

        $state = AiInsightService::get($tenant, 'perusahaan', '{}', true);

        $this->assertSame('not_configured', $state['status']);
        Http::assertNothingSent();
    }

    public function test_provider_null_short_circuits_without_http_call(): void
    {
        Http::fake();
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('ai.provider', 'null');

        $tenant = Perusahaan::create([
            'name' => 'PT NullProv', 'brand' => 'T',
            'sessions_per_employee_month' => 1, 'employee_count' => 1,
            'is_active' => true, 'ai_enabled' => true,
        ]);

        $state = AiInsightService::get($tenant, 'perusahaan', '{}', true);

        $this->assertSame('not_configured', $state['status']);
        Http::assertNothingSent();
    }

    public function test_ai_enabled_tenant_calls_tokengateway_provider(): void
    {
        config()->set('services.ai_insight.key', 'test-key');
        config()->set('services.ai_insight.base_url', 'https://tokengateway.dev/v1');
        config()->set('ai.provider', 'tokengateway');

        Http::fake([
            'tokengateway.dev/*' => Http::response(json_encode([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'summary' => 'ok',
                        'findings' => [[
                            'title' => 't', 'detail' => 'd',
                            'priority' => 'pemantauan', 'hrAction' => 'a',
                        ]],
                        'dataGaps' => [],
                    ])],
                ]],
            ]), 200),
        ]);

        $tenant = Perusahaan::create([
            'name' => 'PT Enabled', 'brand' => 'T',
            'sessions_per_employee_month' => 1, 'employee_count' => 1,
            'is_active' => true, 'ai_enabled' => true,
        ]);

        $state = AiInsightService::get($tenant, 'perusahaan', '{}', true);

        $this->assertSame('completed', $state['status']);
        Http::assertSentCount(1);
    }
}
