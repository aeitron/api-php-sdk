<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class NotFoundException extends AeitronException
{
    public function __construct(
        string $message = 'The requested resource was not found.',
        string $errorCode = 'NOT_FOUND',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(404, $message, $errorCode, $requestId, $errorDetails);
    }
}
