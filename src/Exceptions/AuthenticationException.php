<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class AuthenticationException extends AeitronException
{
    public function __construct(
        string $message = 'Authentication failed. Please check your API key or bearer token.',
        string $errorCode = 'UNAUTHORIZED',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(401, $message, $errorCode, $requestId, $errorDetails);
    }
}
