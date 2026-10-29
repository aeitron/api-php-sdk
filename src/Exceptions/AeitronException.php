<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

use Exception;

class AeitronException extends Exception
{
    private int $statusCode;
    private string $errorCode;

    public function __construct(int $statusCode, string $message, string $errorCode = 'AEITRON_ERROR')
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
