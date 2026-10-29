<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class ValidationException extends AeitronException
{
    /** @var array<string, mixed> */
    private array $validationErrors;

    /**
     * @param string $message
     * @param string $errorCode
     * @param array<string, mixed> $validationErrors
     * @param string|null $requestId
     */
    public function __construct(
        string $message = 'Validation failed for request parameters.',
        string $errorCode = 'VALIDATION_ERROR',
        array $validationErrors = [],
        ?string $requestId = null
    ) {
        parent::__construct(422, $message, $errorCode, $requestId, $validationErrors);
        $this->validationErrors = $validationErrors;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
