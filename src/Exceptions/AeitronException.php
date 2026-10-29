<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

use Exception;

class AeitronException extends Exception
{
    protected int $statusCode;
    protected string $errorCode;
    protected ?string $requestId;
    /** @var array<string, mixed>|null */
    protected ?array $errorDetails;

    /**
     * @param int $statusCode HTTP status code or 0 for client-side errors
     * @param string $message Descriptive error message
     * @param string $errorCode Machine-readable error code
     * @param string|null $requestId Unique request identifier for audit tracing
     * @param array<string, mixed>|null $errorDetails Optional contextual details
     */
    public function __construct(
        int $statusCode,
        string $message,
        string $errorCode = 'AEITRON_ERROR',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->errorDetails = $errorDetails;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}
