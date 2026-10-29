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
     */
    public function list(string $domain): array
    {
        return $this->client->request('GET', '/v1/dns/' . rawurlencode($domain) . '/records');
    }

    /**
     * Creates a new DNS record.
     *
     * @param array{
     *     type: string,
     *     name: string,
     *     value: string,
     *     ttl?: int
     * } $record
     */
    public function create(string $domain, array $record): array
    {
        return $this->client->request('POST', '/v1/dns/' . rawurlencode($domain) . '/records', $record);
    }

    /**
     * Deletes a DNS record.
     *
     * @param string $domain Domain name
     * @param string $recordId Record ID
     * @param string|null $approvalToken Required in high-security environments for destructive deletion
     */
    public function delete(string $domain, string $recordId, ?string $approvalToken = null): array
    {
        $headers = [];
        if ($approvalToken !== null) {
            $headers['X-Aeitron-Approval-Token'] = $approvalToken;
        }
        return $this->client->request(
            'DELETE',
            '/v1/dns/' . rawurlencode($domain) . '/records/' . rawurlencode($recordId),
            null,
            $headers
        );
    }
}
