<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\Exceptions\AeitronException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientInitializationWithValidKey(): void
    {
        $client = new AeitronClient('atk_live_test_key_123');
        $this->assertSame('atk_live_test_key_123', $client->getApiKey());
        $this->assertSame('https://dashboard.aeitron.com', $client->getBaseUrl());
        $this->assertNotNull($client->inference);
        $this->assertNotNull($client->domains);
        $this->assertNotNull($client->telemetry);
        $this->assertNotNull($client->dns);
    }

    public function testClientRejectsEmptyKey(): void
    {
        $this->expectException(AeitronException::class);
        $this->expectExceptionMessage('INVALID_API_KEY');
        new AeitronClient('   ');
    }

    public function testCustomBaseUrlTrailingSlashStripped(): void
    {
        $client = new AeitronClient('atk_live_test_key_123', 'https://api.aeitron.com/');
        $this->assertSame('https://api.aeitron.com', $client->getBaseUrl());
    }
}
