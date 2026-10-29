<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class DnsTest extends TestCase
{
    public function testListDnsRecords(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(200, [], [
            'records' => [
                ['id' => 'rec_01', 'name' => '@', 'type' => 'A', 'value' => '1.2.3.4']
            ]
        ]);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock));
        $res = $client->dns->list('aeitron.com');

        $this->assertCount(1, $res['records']);
        $req = $mock->getLastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertStringEndsWith('/v1/dns/aeitron.com/records', $req['url']);
    }

    public function testCreateDnsRecord(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(201, [], [
            'id' => 'rec_cname_99',
            'name' => 'api',
            'type' => 'CNAME',
            'value' => 'edge.aeitron.io'
        ]);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock));
        $res = $client->dns->create('aeitron.com', [
            'type' => 'CNAME',
            'name' => 'api',
            'value' => 'edge.aeitron.io',
            'ttl' => 300
        ]);

        $this->assertSame('rec_cname_99', $res['id']);
        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertStringContainsString('api.aeitron.io', (string) $req['body']);
    }

    public function testDualControlDeletionWorkflow(): void
    {
        $mock = new MockTransport();
        
        // 1. Request deletion (Initiator step)
        $mock->appendResponse(200, [], [
            'requestId' => 'del_req_99887766',
            'domain' => 'aeitron.com',
            'recordId' => 'rec_cname_99',
            'expiresAt' => time() + 300
        ]);

        // 2. Operator approves deletion (Dual-control operator step)
        $mock->appendResponse(200, [], [
            'success' => true,
            'approvalToken' => 'del_tok_signed_payload_123',
            'requestId' => 'del_req_99887766',
            'expiresAt' => time() + 300
        ]);

        // 3. Execute permanent deletion with token & confirm
        $mock->appendResponse(200, [], [
            'success' => true,
            'message' => 'DNS Record Deleted Successfully'
        ]);

        $client = new AeitronClient('atk_live_test', new ClientOptions(transport: $mock));

        // Step 1: Request Deletion
        $reqResult = $client->dns->requestDeletion('aeitron.com', 'rec_cname_99');
        $this->assertSame('del_req_99887766', $reqResult['requestId']);

        // Step 2: Operator Approves
        $apprResult = $client->dns->approveDeletion($reqResult['requestId']);
        $this->assertTrue($apprResult['success']);
        $this->assertSame('del_tok_signed_payload_123', $apprResult['approvalToken']);

        // Step 3: Delete with approval token and confirm: true
        $delResult = $client->dns->delete('aeitron.com', 'rec_cname_99', $apprResult['approvalToken'], true);
        $this->assertTrue($delResult['success']);

        $req = $mock->getLastRequest();
        $this->assertSame('DELETE', $req['method']);
        $this->assertStringContainsString('confirm=true', $req['url']);
        $this->assertSame('del_tok_signed_payload_123', $req['headers']['X-Aeitron-Approval-Token']);
    }
}
