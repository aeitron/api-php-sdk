<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Network
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    public function getLatencyMatrix(): array
    {
        return $this->client->request('GET', '/v1/network/latency-matrix');
    }

    public function getCables(): array
    {
        return $this->client->request('GET', '/v1/network/cables');
    }
}
