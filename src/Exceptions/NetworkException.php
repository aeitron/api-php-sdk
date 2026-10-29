<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class NetworkException extends AeitronException
{
    private int $curlErrorCode;

    public function __construct(
        string $message,
        int $curlErrorCode = 0,
        string $errorCode = 'NETWORK_ERROR'
    ) {
        parent::__construct(0, $message, $errorCode);
        $this->curlErrorCode = $curlErrorCode;
    }

    public function getCurlErrorCode(): int
    {
        return $this->curlErrorCode;
    }
}
