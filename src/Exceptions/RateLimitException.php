<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class RateLimitException extends AeitronException
{
    private ?int $retryAfterSeconds;

    public function __construct(
        string $message = 'Rate limit exceeded. Please back off and retry later.',
        ?int $retryAfterSeconds = null,
        string $errorCode = 'RATE_LIMIT_EXCEEDED',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(429, $message, $errorCode, $requestId, $errorDetails);
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
