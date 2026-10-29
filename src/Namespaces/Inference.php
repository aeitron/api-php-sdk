<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Inference
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    /**
     * Executes a multi-objective optimized inference completion across the global edge fabric.
     *
     * @param array{
     *     model: string,
     *     messages: array<array{role: string, content: string}>,
     *     strategy?: 'LATENCY_OPTIMAL'|'COST_MINIMAL'|'CARBON_NEUTRAL'|'SOVEREIGN_PINNED',
     *     jurisdiction?: 'EU'|'US'|'APAC'|'GLOBAL',
     *     max_tokens?: int,
     *     temperature?: float
     * } $params
     * @return array<string, mixed>
     */
    public function complete(array $params): array
    {
        return $this->client->request('POST', '/v1/inference/chat/completions', $params);
    }
}
