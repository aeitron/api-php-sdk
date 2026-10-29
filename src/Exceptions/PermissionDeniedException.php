<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class PermissionDeniedException extends AeitronException
{
    public function __construct(
        string $message = 'Access denied. The authenticated principal lacks the required permission scope.',
        string $errorCode = 'FORBIDDEN',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(403, $message, $errorCode, $requestId, $errorDetails);
    }
}
