<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;

class Storage
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    public function listBuckets(): array
    {
        return $this->client->request('GET', '/v1/storage/buckets');
    }

    public function listObjects(string $bucket): array
    {
        return $this->client->request('GET', '/v1/storage/buckets/' . rawurlencode($bucket) . '/objects');
    }
}
