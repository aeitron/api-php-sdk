<?php

declare(strict_types=1);

namespace Aeitron\Tests;

use Aeitron\AeitronClient;
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\ValidationException;
use Aeitron\Security\WebhookSignature;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private string $secret = 'whsec_test_secret_key_abcdef1234567890';

    public function testValidWebhookSignatureVerification(): void
    {
        $payload = '{"id":"evt_01","type":"domain.verified","data":{"domain":"aeitron.com"}}';
        $timestamp = time();
        $sig = hash_hmac('sha256', $timestamp . '.' . $payload, $this->secret);
        $header = 't=' . $timestamp . ',v1=' . $sig;

        $client = new AeitronClient('atk_live_test');
        $event = $client->webhooks->constructEvent($payload, $header, $this->secret);

        $this->assertSame('evt_01', $event['id']);
        $this->assertSame('domain.verified', $event['type']);
        $this->assertSame('aeitron.com', $event['data']['domain']);
    }

    public function testForgedSignatureThrowsAuthenticationException(): void
    {
        $payload = '{"id":"evt_forged","type":"order.created"}';
        $timestamp = time();
        $header = 't=' . $timestamp . ',v1=bad_hex_signature_1234567890abcdef';

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Webhook signature verification failed');

        WebhookSignature::verify($payload, $header, $this->secret);
    }

    public function testExpiredTimestampThrowsValidationException(): void
    {
        $payload = '{"id":"evt_replayed","type":"domain.deleted"}';
        // Timestamp 10 minutes ago
        $timestamp = time() - 600;
        $sig = hash_hmac('sha256', $timestamp . '.' . $payload, $this->secret);
        $header = 't=' . $timestamp . ',v1=' . $sig;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('outside acceptable tolerance');

        // Tolerance is 300s (5 minutes)
        WebhookSignature::verify($payload, $header, $this->secret, 300);
    }

    public function testMalformedHeaderThrowsValidationException(): void
    {
        $payload = '{"id":"evt_01"}';
        $header = 'invalid_format_header_without_timestamp';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid webhook signature header format');

        WebhookSignature::verify($payload, $header, $this->secret);
    }
}
