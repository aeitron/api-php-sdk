<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class ExpiredException extends AeitronException
{
    public function __construct(
        string $message = 'The requested entity, challenge, or token has expired or already been consumed.',
        string $errorCode = 'GONE',
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(410, $message, $errorCode, $requestId, $errorDetails);
    }
}
