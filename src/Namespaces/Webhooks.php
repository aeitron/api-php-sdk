<?php

declare(strict_types=1);

namespace Aeitron\Namespaces;

use Aeitron\AeitronClient;
use Aeitron\Exceptions\ValidationException;
use Aeitron\Security\WebhookSignature;

class Webhooks
{
    private AeitronClient $client;

    public function __construct(AeitronClient $client)
    {
        $this->client = $client;
    }

    /**
     * Validates an incoming webhook notification and constructs the parsed event array.
     * Enforces HMAC-SHA256 signature verification and replay tolerance checks.
     *
     * @param string $payload Raw request body string
     * @param string $signatureHeader Header string from Aeitron-Signature or X-Aeitron-Signature
     * @param string $secret Webhook signing secret (e.g. whsec_...)
     * @param int $tolerance Maximum allowed age in seconds
     * @return array<string, mixed> Parsed event payload
     * @throws \Aeitron\Exceptions\AuthenticationException If signature does not match
     * @throws ValidationException If signature is expired or malformed
     */
    public function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = WebhookSignature::DEFAULT_TOLERANCE_SECONDS
    ): array {
        WebhookSignature::verify($payload, $signatureHeader, $secret, $tolerance);

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new ValidationException('Webhook payload could not be parsed as valid JSON');
        }

        return $decoded;
    }

    /**
     * Verifies the authenticity of a webhook signature header without parsing the JSON.
     */
    public function verifySignature(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = WebhookSignature::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        return WebhookSignature::verify($payload, $signatureHeader, $secret, $tolerance);
    }

    /**
     * Lists registered webhook endpoints for the authenticated tenant.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->request('GET', '/v1/webhooks');
    }

    /**
     * Registers a new webhook subscription endpoint.
     *
     * @param array{
     *     url: string,
     *     events: array<string>,
     *     secret?: string,
     *     enabled?: bool
     * } $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->request('POST', '/v1/webhooks', $params);
    }

    /**
     * Deletes a registered webhook endpoint by ID.
     */
    public function delete(string $id): array
    {
        return $this->client->request('DELETE', '/v1/webhooks/' . rawurlencode($id));
    }
}
