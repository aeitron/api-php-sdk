<?php

declare(strict_types=1);

namespace Aeitron\Exceptions;

class ApprovalRequiredException extends AeitronException
{
    private string $challengeRequestId;
    private ?string $domain;
    private ?string $recordId;

    public function __construct(
        string $message,
        string $challengeRequestId,
        ?string $domain = null,
        ?string $recordId = null,
        ?string $requestId = null,
        ?array $errorDetails = null
    ) {
        parent::__construct(403, $message, 'APPROVAL_REQUIRED', $requestId, $errorDetails);
        $this->challengeRequestId = $challengeRequestId;
        $this->domain = $domain;
        $this->recordId = $recordId;
    }

    public function getChallengeRequestId(): string
    {
        return $this->challengeRequestId;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getRecordId(): ?string
    {
        return $this->recordId;
    }
}
