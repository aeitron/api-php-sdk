<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Telemetry
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieves aggregated traffic and edge telemetry summary.
     */
    public function getSummary(int $rangeHours = 24): array
    {
        return $this->client->request('GET', '/v1/telemetry/summary?range_hours=' . $rangeHours);
    }

    /**
     * Retrieves real-time edge PoP operational health across all simulated metros.
     */
    public function getPopHealth(): array
    {
        return $this->client->request('GET', '/v1/telemetry/pops');
    }
}
