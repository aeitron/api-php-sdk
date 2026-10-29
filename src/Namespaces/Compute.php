<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Compute
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    public function listInstances(): array
    {
        return $this->client->request('GET', '/v1/compute/instances');
    }

    public function getInstance(string $id): array
    {
        return $this->client->request('GET', '/v1/compute/instances/' . rawurlencode($id));
    }
}
