<?php

declare(strict_types=1);

namespace Aeitron\Http;

use Aeitron\Exceptions\NetworkException;

interface TransportInterface
{
    /**
     * Sends an HTTP request to the target endpoint.
     *
     * @param string $method Uppercase HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $url Absolute URL
     * @param array<string, string> $headers Key-value header pairs
     * @param string|null $body Serialized body payload
     * @param float $timeoutSeconds Maximum total request duration in seconds
     * @return Response
     * @throws NetworkException
     */
    public function send(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        float $timeoutSeconds
    ): Response;
}
