<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Enums\Jurisdiction;
use Aeitron\Enums\RoutingStrategy;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class InferenceTest extends TestCase
{
    public function testInferenceCompletionWithStrategyAndJurisdiction(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(200, [], [
            'id' => 'chatcmpl_test_123',
            'choices' => [
                ['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'Aeitron is an intelligent edge platform.']]
            ],
            'telemetry' => [
                'provider' => 'bedrock',
                'region' => 'ap-southeast-1',
                'cost' => 0.00012,
                'ttftMs' => 45.2
            ]
        ]);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock));

        $res = $client->inference->complete([
            'model' => 'anthropic.claude-3-5-sonnet',
            'messages' => [
                ['role' => 'user', 'content' => 'What is Aeitron?']
            ],
            'strategy' => RoutingStrategy::LATENCY_OPTIMAL,
            'jurisdiction' => Jurisdiction::GLOBAL
        ], 'idemp_key_unique_001');

        $this->assertSame('chatcmpl_test_123', $res['id']);
        $this->assertSame('Aeitron is an intelligent edge platform.', $res['choices'][0]['message']['content']);
        $this->assertSame(45.2, $res['telemetry']['ttftMs']);

        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('idemp_key_unique_001', $req['headers']['Idempotency-Key']);

        $payload = json_decode((string) $req['body'], true);
        $this->assertSame('LATENCY_OPTIMAL', $payload['strategy']);
        $this->assertSame('GLOBAL', $payload['jurisdiction']);
    }
}
