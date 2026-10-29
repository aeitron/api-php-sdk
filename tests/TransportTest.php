<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Exceptions\NetworkException;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testMockTransportRecordsHeadersAndMethod(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(200, ['x-request-id' => 'req_abc123'], '{"success":true,"pop":"fra-01"}');

        $options = new ClientOptions(
            baseUrl: 'https://api.aeitron.com',
            transport: $mock,
            defaultHeaders: ['X-Tenant-Tag' => 'edge_prod']
        );
        $client = new AeitronClient('atk_live_sec_key_99', $options);

        $res = $client->request('GET', '/v1/telemetry/pops', null, ['X-Extra' => 'test_extra']);
        $this->assertTrue($res['success']);
        $this->assertSame('fra-01', $res['pop']);

        $req = $mock->getLastRequest();
        $this->assertNotNull($req);
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://api.aeitron.com/v1/telemetry/pops', $req['url']);
        $this->assertSame('Bearer atk_live_sec_key_99', $req['headers']['Authorization']);
        $this->assertSame('edge_prod', $req['headers']['X-Tenant-Tag']);
        $this->assertSame('test_extra', $req['headers']['X-Extra']);
        $this->assertStringContainsString('Aeitron-PHP-SDK/', $req['headers']['User-Agent']);
    }

    public function testNetworkExceptionOnTransportFailure(): void
    {
        $mock = new MockTransport();
        $mock->appendNetworkError('Failed to connect to host', 7);

        $client = new AeitronClient('atk_live_sec_key_99', new ClientOptions(transport: $mock, maxRetries: 0));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to connect to host');
        $client->request('GET', '/v1/health');
    }
}
