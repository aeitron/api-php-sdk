<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Exceptions\ApprovalRequiredException;
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\ConflictException;
use Aeitron\Exceptions\ExpiredException;
use Aeitron\Exceptions\NotFoundException;
use Aeitron\Exceptions\PermissionDeniedException;
use Aeitron\Exceptions\RateLimitException;
use Aeitron\Exceptions\ServerException;
use Aeitron\Exceptions\ValidationException;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    private function createClientWithMock(MockTransport $mock): AeitronClient
    {
        return new AeitronClient(
            'atk_live_test_key_123',
            new ClientOptions(transport: $mock, maxRetries: 0)
        );
    }

    public function test401ThrowsAuthenticationException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(401, ['x-request-id' => 'req_401'], [
            'error' => 'UNAUTHORIZED',
            'message' => 'Invalid or expired API token',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired API token');
        $client->request('GET', '/v1/account');
    }

    public function test403ThrowsPermissionDeniedException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(403, ['x-request-id' => 'req_403'], [
            'error' => 'FORBIDDEN',
            'message' => 'Missing required scope dns:write:delete',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(PermissionDeniedException::class);
        $this->expectExceptionMessage('Missing required scope dns:write:delete');
        $client->request('DELETE', '/v1/dns/records/rec_01');
    }

    public function testDualControlChallengeThrowsApprovalRequiredException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(403, ['x-request-id' => 'req_challenge_01'], [
            'error' => 'APPROVAL_REQUIRED',
            'message' => 'Destructive DNS deletion requires human operator approval. Request ID: del_req_abc987654321',
            'requestId' => 'del_req_abc987654321',
            'domain' => 'aeitron.com',
            'recordId' => 'rec_cname_003'
        ]);

        $client = $this->createClientWithMock($mock);

        try {
            $client->request('DELETE', '/v1/dns/records/rec_cname_003');
            $this->fail('Expected ApprovalRequiredException was not thrown');
        } catch (ApprovalRequiredException $e) {
            $this->assertSame('del_req_abc987654321', $e->getChallengeRequestId());
            $this->assertSame('aeitron.com', $e->getDomain());
            $this->assertSame('rec_cname_003', $e->getRecordId());
            $this->assertSame('req_challenge_01', $e->getRequestId());
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test404ThrowsNotFoundException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(404, ['x-request-id' => 'req_404'], [
            'error' => 'NOT_FOUND',
            'message' => 'Domain not registered',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Domain not registered');
        $client->request('GET', '/v1/domains/missing.com');
    }

    public function test409ThrowsConflictException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(409, ['x-request-id' => 'req_409'], [
            'error' => 'CONFLICT',
            'message' => 'Domain already active in different workspace',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Domain already active');
        $client->request('POST', '/v1/domains', ['domain' => 'active.com']);
    }

    public function test410ThrowsExpiredException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(410, ['x-request-id' => 'req_410'], [
            'error' => 'GONE',
            'message' => 'Pending deletion request has expired',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ExpiredException::class);
        $this->expectExceptionMessage('expired');
        $client->request('POST', '/v1/dns/approve-deletion', ['requestId' => 'del_req_old']);
    }

    public function test422ThrowsValidationException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(422, ['x-request-id' => 'req_422'], [
            'error' => 'UNPROCESSABLE_ENTITY',
            'message' => 'Invalid IPv4 address in A record',
            'fields' => ['value' => 'not-an-ip'],
        ]);

        $client = $this->createClientWithMock($mock);

        try {
            $client->request('POST', '/v1/dns/aeitron.com/records', ['type' => 'A', 'value' => 'not-an-ip']);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertArrayHasKey('fields', $e->getValidationErrors());
        }
    }

    public function test429ThrowsRateLimitExceptionWithRetryAfter(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(429, [
            'retry-after' => '15',
            'x-request-id' => 'req_429'
        ], [
            'error' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Too many requests',
        ]);

        $client = $this->createClientWithMock($mock);

        try {
            $client->request('GET', '/v1/usage');
            $this->fail('Expected RateLimitException was not thrown');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame(15, $e->getRetryAfterSeconds());
            $this->assertSame('req_429', $e->getRequestId());
        }
    }

    public function test500ThrowsServerException(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(502, ['x-request-id' => 'req_502'], [
            'error' => 'BAD_GATEWAY',
            'message' => 'Edge origin unreachable',
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Edge origin unreachable');
        $client->request('GET', '/v1/health');
    }
}
