<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class ConflictException extends AeitronException
{
    public function __construct(
        string $message = 'Conflict detected with current state of resource.',
        string $errorCode = 'CONFLICT',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(409, $message, $errorCode, $requestId, $errorDetails);
    }
}
