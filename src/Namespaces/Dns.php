<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Dns
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    /**
     * Lists DNS records for an authoritative zone.
     *
     * @return array<string, mixed>
     */
    public function list(string $domain): array
    {
        return $this->client->request('GET', '/v1/dns/' . rawurlencode($domain) . '/records');
    }

    /**
     * Creates a new DNS record.
     *
     * @param string $domain Authoritative domain name
     * @param array{
     *     type: string,
     *     name: string,
     *     value: string,
     *     ttl?: int
     * } $record
     * @return array<string, mixed>
     */
    public function create(string $domain, array $record): array
    {
        return $this->client->request('POST', '/v1/dns/' . rawurlencode($domain) . '/records', $record);
    }

    /**
     * Submits a pending DNS deletion request for dual-control human operator authorization.
     * Returns a unique request ID (del_req_...) without mutating the DNS zone.
     *
     * @return array{requestId: string, domain: string, recordId: string, expiresAt: int}
     */
    public function requestDeletion(string $domain, string $recordId): array
    {
        /** @var array{requestId: string, domain: string, recordId: string, expiresAt: int} */
        return $this->client->request(
            'POST',
            '/v1/dns/' . rawurlencode($domain) . '/records/' . rawurlencode($recordId) . '/deletion-request'
        );
    }

    /**
     * Authorizes a pending DNS deletion request by a verified human operator.
     * Requires the specialized 'dns:delete:approve' permission scope.
     * Independent dual-control rule: The operator cannot be the same principal who initiated the deletion.
     *
     * @param string $requestId Pending deletion request ID (del_req_...)
     * @return array{success: bool, approvalToken: string, requestId: string, expiresAt: int}
     */
    public function approveDeletion(string $requestId): array
    {
        /** @var array{success: bool, approvalToken: string, requestId: string, expiresAt: int} */
        return $this->client->request(
            'POST',
            '/v1/dns/approve-deletion',
            ['requestId' => $requestId]
        );
    }

    /**
     * Permanently deletes a DNS record.
     * In high-security environments, requires a valid cryptographic approvalToken and confirm: true.
     *
     * @param string $domain Domain name
     * @param string $recordId Record ID
     * @param string|null $approvalToken Single-use challenge token issued by operator approval
     * @param bool $confirm Safety guard: must be explicitly true to confirm destructive deletion
     * @return array<string, mixed>
     */
    public function delete(string $domain, string $recordId, ?string $approvalToken = null, bool $confirm = false): array
    {
        $headers = [];
        if ($approvalToken !== null) {
            $headers['X-Aeitron-Approval-Token'] = $approvalToken;
        }

        $query = $confirm ? '?confirm=true' : '';

        return $this->client->request(
            'DELETE',
            '/v1/dns/' . rawurlencode($domain) . '/records/' . rawurlencode($recordId) . $query,
            null,
            $headers
        );
    }
}
