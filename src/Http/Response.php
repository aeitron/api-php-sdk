<?php

declare(strict_types=1);

namespace Aeitron\Http;

use Aeitron\Exceptions\AeitronException;

class Response
{
    private int $statusCode;
    /** @var array<string, string> */
    private array $headers;
    private string $body;
    private ?string $requestId;

    /**
     * @param int $statusCode
     * @param array<string, string> $headers
     * @param string $body
     */
    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        
        // Normalize header keys to lowercase for case-insensitive lookup
        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            $normalizedHeaders[strtolower((string) $k)] = (string) $v;
        }
        $this->headers = $normalizedHeaders;
        $this->requestId = $this->headers['x-request-id'] ?? $this->headers['aeitron-request-id'] ?? null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Decodes response body into array or throws AeitronException on invalid JSON.
     *
     * @return array<string, mixed>
     * @throws AeitronException
     */
    public function json(): array
    {
        if (trim($this->body) === '') {
            return [];
        }

        $decoded = json_decode($this->body, true);
        if (!is_array($decoded)) {
            if ($this->statusCode >= 200 && $this->statusCode < 300) {
                return ['raw' => $this->body, 'status' => $this->statusCode];
            }
            throw new AeitronException(
                $this->statusCode,
                'Malformed JSON response from Aeitron API: ' . substr($this->body, 0, 200),
                'MALFORMED_RESPONSE',
                $this->requestId
            );
        }

        return $decoded;
    }
}
