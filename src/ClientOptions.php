<?php

declare(strict_types=1);

namespace Aeitron;

use Aeitron\Http\TransportInterface;

class ClientOptions
{
    private string $baseUrl;
    private string $apiVersion;
    private float $timeoutSeconds;
    private int $maxRetries;
    private ?TransportInterface $transport;
    /** @var array<string, string> */
    private array $defaultHeaders;

    /**
     * @param string $baseUrl
     * @param string $apiVersion
     * @param float $timeoutSeconds
     * @param int $maxRetries
     * @param TransportInterface|null $transport
     * @param array<string, string> $defaultHeaders
     */
    public function __construct(
        string $baseUrl = 'https://dashboard.aeitron.com',
        string $apiVersion = '2026-11-01',
        float $timeoutSeconds = 30.0,
        int $maxRetries = 2,
        ?TransportInterface $transport = null,
        array $defaultHeaders = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiVersion = $apiVersion;
        $this->timeoutSeconds = max(0.1, $timeoutSeconds);
        $this->maxRetries = max(0, $maxRetries);
        $this->transport = $transport;
        $this->defaultHeaders = $defaultHeaders;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    public function getTimeoutSeconds(): float
    {
        return $this->timeoutSeconds;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getTransport(): ?TransportInterface
    {
        return $this->transport;
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }
}
