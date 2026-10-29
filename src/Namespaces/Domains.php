<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Domains
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    /**
     * Lists all onboarded domains.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->request('GET', '/v1/domains');
    }

    /**
     * Retrieves domain configuration and verification state.
     */
    public function get(string $domain): array
    {
        return $this->client->request('GET', '/v1/domains/' . rawurlencode($domain));
    }

    /**
     * Onboards a new custom domain onto the edge network.
     *
     * @param array{
     *     domain: string,
     *     origin_pool?: array<string>,
     *     routing_policy?: string
     * } $params
     */
    public function onboard(array $params): array
    {
        return $this->client->request('POST', '/v1/domains', $params);
    }

    /**
     * Triggers TLS and DNS ownership verification for a domain.
     */
    public function verify(string $domain): array
    {
        return $this->client->request('POST', '/v1/domains/' . rawurlencode($domain) . '/verify');
    }

    /**
     * Deletes a domain and purges edge routing.
     */
    public function delete(string $domain): array
    {
        return $this->client->request('DELETE', '/v1/domains/' . rawurlencode($domain));
    }
}
