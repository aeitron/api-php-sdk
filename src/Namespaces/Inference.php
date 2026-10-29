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
     *     strategy?: 'LATENCY_OPTIMAL'|'COST_MINIMAL'|'CARBON_NEUTRAL'|'SOVEREIGN_PINNED'|string,
     *     jurisdiction?: 'EU'|'US'|'APAC'|'GLOBAL'|'BD'|string,
     *     max_tokens?: int,
     *     temperature?: float,
     *     stream?: bool
     * } $params
     * @param string|null $idempotencyKey Optional unique key to prevent duplicate completions on network retry
     * @return array<string, mixed>
     */
    public function complete(array $params, ?string $idempotencyKey = null): array
    {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->client->request('POST', '/v1/inference/chat/completions', $params, $headers);
    }
}
