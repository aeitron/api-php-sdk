<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientInitializationWithValidKey(): void
    {
        $client = new AeitronClient('atk_live_test_key_123');
        $this->assertSame('atk_live_test_key_123', $client->getApiKey());
        $this->assertSame('https://dashboard.aeitron.com', $client->getBaseUrl());
        $this->assertSame('2026-11-01', $client->getApiVersion());
        $this->assertSame(30.0, $client->getTimeoutSeconds());
        $this->assertSame(2, $client->getMaxRetries());

        $this->assertNotNull($client->inference);
        $this->assertNotNull($client->domains);
        $this->assertNotNull($client->telemetry);
        $this->assertNotNull($client->dns);
        $this->assertNotNull($client->compute);
        $this->assertNotNull($client->storage);
        $this->assertNotNull($client->network);
        $this->assertNotNull($client->webhooks);
    }

    public function testClientRejectsEmptyOrWhitespaceKey(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('INVALID_API_KEY');
        new AeitronClient("   \n\t  ");
    }

    public function testCustomBaseUrlTrailingSlashStripped(): void
    {
        $client = new AeitronClient('atk_live_test_key_123', 'https://api.aeitron.com///');
        $this->assertSame('https://api.aeitron.com', $client->getBaseUrl());
    }

    public function testClientOptionsConfig(): void
    {
        $mock = new MockTransport();
        $options = new ClientOptions(
            baseUrl: 'https://staging.aeitron.io',
            apiVersion: '2026-12-01',
            timeoutSeconds: 12.5,
            maxRetries: 4,
            transport: $mock,
            defaultHeaders: ['X-Custom-Tenant' => 'tenant_test_42']
        );

        $client = new AeitronClient('atk_live_test_key_123', $options);
        $this->assertSame('https://staging.aeitron.io', $client->getBaseUrl());
        $this->assertSame('2026-12-01', $client->getApiVersion());
        $this->assertSame(12.5, $client->getTimeoutSeconds());
        $this->assertSame(4, $client->getMaxRetries());
        $this->assertSame($mock, $client->getTransport());
    }
}
