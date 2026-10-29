<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Exceptions\ServerException;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class RetryTest extends TestCase
{
    public function testAutomaticRetryOnTransientServerErrorForIdempotentMethod(): void
    {
        $mock = new MockTransport();
        // First attempt fails with 503
        $mock->appendResponse(503, [], ['error' => 'Service Unavailable', 'message' => 'Origin temporary overload']);
        // Second attempt succeeds with 200
        $mock->appendResponse(200, [], ['healthy' => true, 'pops' => ['sin-01', 'dhk-01']]);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock, maxRetries: 2));

        $res = $client->request('GET', '/v1/telemetry/pops');
        $this->assertTrue($res['healthy']);
        $this->assertCount(2, $mock->getRecordedRequests());
    }

    public function testRetriesExhaustedThrowsServerException(): void
    {
        $mock = new MockTransport();
        // 3 consecutive 500s (initial attempt + 2 retries)
        $mock->appendResponse(500, [], ['error' => 'Internal Server Error']);
        $mock->appendResponse(500, [], ['error' => 'Internal Server Error']);
        $mock->appendResponse(500, [], ['error' => 'Internal Server Error']);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock, maxRetries: 2));

        $this->expectException(ServerException::class);
        try {
            $client->request('GET', '/v1/account');
        } finally {
            $this->assertCount(3, $mock->getRecordedRequests());
        }
    }

    public function testPostWithoutIdempotencyKeyIsNotRetriedOn500(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(500, [], ['error' => 'Internal Server Error']);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock, maxRetries: 2));

        $this->expectException(ServerException::class);
        try {
            $client->request('POST', '/v1/orders', ['plan' => 'enterprise']);
        } finally {
            $this->assertCount(1, $mock->getRecordedRequests(), 'Non-idempotent POST must not be retried without Idempotency-Key');
        }
    }

    public function testPostWithIdempotencyKeyIsRetriedOn500(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(500, [], ['error' => 'Internal Server Error']);
        $mock->appendResponse(200, [], ['orderId' => 'ord_9900']);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock, maxRetries: 2));

        $res = $client->request('POST', '/v1/orders', ['plan' => 'enterprise'], ['Idempotency-Key' => 'idemp_unique_order_1']);
        $this->assertSame('ord_9900', $res['orderId']);
        $this->assertCount(2, $mock->getRecordedRequests(), 'POST with Idempotency-Key must safely retry transient 500');
    }
}
