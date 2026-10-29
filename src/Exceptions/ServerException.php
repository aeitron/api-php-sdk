<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class ServerException extends AeitronException
{
    public function __construct(
        int $statusCode = 500,
        string $message = 'An unexpected server error occurred on the Aeitron edge platform.',
        string $errorCode = 'SERVER_ERROR',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct($statusCode, $message, $errorCode, $requestId, $errorDetails);
    }
}
