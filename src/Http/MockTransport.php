<?php

declare(strict_types=1);

namespace Aeitron\Http;

use Aeitron\Exceptions\NetworkException;
use RuntimeException;

class MockTransport implements TransportInterface
{
    /** @var array<array{type: 'response'|'error', response?: Response, error?: NetworkException}> */
    private array $queue = [];

    /** @var array<array{method: string, url: string, headers: array<string, string>, body: ?string, timeoutSeconds: float}> */
    private array $recordedRequests = [];

    /**
     * @param int $statusCode
     * @param array<string, string> $headers
     * @param string|array<string, mixed> $body
     */
    public function appendResponse(int $statusCode, array $headers = [], string|array $body = ''): self
    {
        $bodyString = is_array($body) ? (string) json_encode($body) : (string) $body;
        $this->queue[] = [
            'type' => 'response',
            'response' => new Response($statusCode, $headers, $bodyString),
        ];
        return $this;
    }

    public function appendNetworkError(string $message = 'Connection timeout', int $curlCode = 28): self
    {
        $this->queue[] = [
            'type' => 'error',
            'error' => new NetworkException($message, $curlCode),
        ];
        return $this;
    }

    public function send(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        float $timeoutSeconds
    ): Response {
        $this->recordedRequests[] = [
            'method' => strtoupper($method),
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'timeoutSeconds' => $timeoutSeconds,
        ];

        if (empty($this->queue)) {
            throw new RuntimeException('MockTransport queue empty. No mock response provided for request: ' . $method . ' ' . $url);
        }

        $next = array_shift($this->queue);
        if ($next['type'] === 'error') {
            throw $next['error'];
        }

        return $next['response'];
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: ?string, timeoutSeconds: float}|null
     */
    public function getLastRequest(): ?array
    {
        return empty($this->recordedRequests) ? null : $this->recordedRequests[count($this->recordedRequests) - 1];
    }

    /**
     * @return array<array{method: string, url: string, headers: array<string, string>, body: ?string, timeoutSeconds: float}>
     */
    public function getRecordedRequests(): array
    {
        return $this->recordedRequests;
    }

    public function reset(): void
    {
        $this->queue = [];
        $this->recordedRequests = [];
    }
}
