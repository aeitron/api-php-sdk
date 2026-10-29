<?php

declare(strict_types=1);

namespace Aeitron\Security;

use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\ValidationException;

class WebhookSignature
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Verifies an incoming webhook payload using the Aeitron HMAC-SHA256 signature scheme.
     *
     * @param string $payload The raw HTTP request body string
     * @param string $signatureHeader The value of the Aeitron-Signature header (e.g. "t=1730227200,v1=abcdef...")
     * @param string $secret The shared webhook signing secret (whsec_...)
     * @param int $tolerance Maximum allowed age of the event in seconds to prevent replay attacks
     * @return bool True if valid
     * @throws AuthenticationException If signature is invalid or forged
     * @throws ValidationException If signature header is malformed or timestamp has expired
     */
    public static function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        $trimmedSecret = trim($secret);
        if ($trimmedSecret === '') {
            throw new ValidationException('Webhook secret cannot be empty');
        }

        $items = explode(',', $signatureHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($items as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = (int) $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if ($timestamp === null || empty($signatures)) {
            throw new ValidationException(
                'Invalid webhook signature header format. Expected "t=<timestamp>,v1=<signature>"'
            );
        }

        $now = time();
        if ($tolerance > 0 && abs($now - $timestamp) > $tolerance) {
            throw new ValidationException(
                'Webhook signature timestamp outside acceptable tolerance (' . $tolerance . 's). Potential replay attack.'
            );
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $trimmedSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        throw new AuthenticationException('Webhook signature verification failed. Computed signature does not match header.');
    }
}
