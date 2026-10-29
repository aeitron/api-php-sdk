<?php

declare(strict_types=1);

namespace Aeitron;

use Aeitron\Exceptions\AeitronException;
use Aeitron\Exceptions\ApprovalRequiredException;
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\ConflictException;
use Aeitron\Exceptions\ExpiredException;
use Aeitron\Exceptions\NetworkException;
use Aeitron\Exceptions\NotFoundException;
use Aeitron\Exceptions\PermissionDeniedException;
use Aeitron\Exceptions\RateLimitException;
use Aeitron\Exceptions\ServerException;
use Aeitron\Exceptions\ValidationException;
use Aeitron\Http\CurlTransport;
use Aeitron\Http\Response;
use Aeitron\Http\TransportInterface;
use Aeitron\Namespaces\Compute;
use Aeitron\Namespaces\Dns;
use Aeitron\Namespaces\Domains;
use Aeitron\Namespaces\Inference;
use Aeitron\Namespaces\Network;
use Aeitron\Namespaces\Storage;
use Aeitron\Namespaces\Telemetry;
use Aeitron\Namespaces\Webhooks;

class AeitronClient
{
    public const VERSION = '1.0.0';
    public const DEFAULT_BASE_URL = 'https://dashboard.aeitron.com';
    public const DEFAULT_API_VERSION = '2026-11-01';

    private string $apiKey;
    private string $baseUrl;
    private string $apiVersion;
    private float $timeoutSeconds;
    private int $maxRetries;
    private TransportInterface $transport;
    /** @var array<string, string> */
    private array $defaultHeaders;

    public Inference $inference;
    public Domains $domains;
    public Telemetry $telemetry;
    public Dns $dns;
    public Compute $compute;
    public Storage $storage;
    public Network $network;
    public Webhooks $webhooks;

    /**
     * @param string $apiKey Authoritative Aeitron API Key (e.g. nke_live_..., atk_live_...)
     * @param string|ClientOptions $baseUrlOrOptions Base URL or ClientOptions configuration
     * @param string $apiVersion Target date-based API contract version
     * @param float $timeoutSeconds Maximum timeout in seconds
     * @param ClientOptions|null $options Optional ClientOptions instance
     */
    public function __construct(
        string $apiKey,
        string|ClientOptions $baseUrlOrOptions = self::DEFAULT_BASE_URL,
        string $apiVersion = self::DEFAULT_API_VERSION,
        float $timeoutSeconds = 30.0,
        ?ClientOptions $options = null
    ) {
        $trimmedKey = trim($apiKey);
        if ($trimmedKey === '') {
            throw new AuthenticationException(
                'AeitronClient requires a non-empty API key (e.g., nke_live_... or atk_live_...)',
                'INVALID_API_KEY'
            );
        }

        $this->apiKey = $trimmedKey;

        if ($baseUrlOrOptions instanceof ClientOptions) {
            $effectiveOptions = $baseUrlOrOptions;
        } elseif ($options !== null) {
            $effectiveOptions = $options;
        } else {
            $effectiveOptions = new ClientOptions($baseUrlOrOptions, $apiVersion, $timeoutSeconds);
        }

        $this->baseUrl = $effectiveOptions->getBaseUrl();
        $this->apiVersion = $effectiveOptions->getApiVersion();
        $this->timeoutSeconds = $effectiveOptions->getTimeoutSeconds();
        $this->maxRetries = $effectiveOptions->getMaxRetries();
        $this->transport = $effectiveOptions->getTransport() ?? new CurlTransport();
        $this->defaultHeaders = $effectiveOptions->getDefaultHeaders();

        // Initialize API namespaces
        $this->inference = new Inference($this);
        $this->domains = new Domains($this);
        $this->telemetry = new Telemetry($this);
        $this->dns = new Dns($this);
        $this->compute = new Compute($this);
        $this->storage = new Storage($this);
        $this->network = new Network($this);
        $this->webhooks = new Webhooks($this);
    }

    /**
     * Executes an authenticated HTTP request with automatic retries and typed exception mapping.
     *
     * @param string $method Uppercase HTTP method
     * @param string $path URL path e.g. /v1/domains
     * @param mixed $body Payload to encode as JSON
     * @param array<string, string> $extraHeaders Custom headers
     * @return array<string, mixed> Decoded JSON response
     * @throws AeitronException
     */
    public function request(string $method, string $path, mixed $body = null, array $extraHeaders = []): array
    {
        $normalizedMethod = strtoupper($method);
        $url = $this->baseUrl . (str_starts_with($path, '/') ? $path : '/' . $path);

        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Aeitron-Version' => $this->apiVersion,
            'User-Agent' => 'Aeitron-PHP-SDK/' . self::VERSION . ' (PHP ' . PHP_VERSION . ')',
        ], $this->defaultHeaders, $extraHeaders);

        $jsonPayload = null;
        if ($body !== null) {
            $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        $isIdempotent = in_array($normalizedMethod, ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'], true)
            || isset($headers['Idempotency-Key'])
            || isset($headers['idempotency-key']);

        $maxAttempts = ($isIdempotent ? $this->maxRetries : 0) + 1;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->transport->send($normalizedMethod, $url, $headers, $jsonPayload, $this->timeoutSeconds);
                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 300) {
                    return $response->json();
                }

                // Check for transient retryable HTTP errors
                if ($attempt < $maxAttempts && ($statusCode === 429 || ($statusCode >= 500 && $statusCode <= 504))) {
                    $retryAfter = (int) ($response->getHeader('retry-after') ?? 0);
                    $this->sleepWithBackoff($attempt, $retryAfter);
                    continue;
                }

                // Map status code to dedicated exception type
                $this->throwApiException($response);
            } catch (NetworkException $e) {
                $lastException = $e;
                if ($isIdempotent && $attempt < $maxAttempts) {
                    $this->sleepWithBackoff($attempt, 0);
                    continue;
                }
                throw $e;
            }
        }

        throw $lastException ?? new ServerException(500, 'Max retry limit reached without response');
    }

    /**
     * Maps an error HTTP response to a specialized AeitronException subtype.
     *
     * @param Response $response
     * @return never
     * @throws AeitronException
     */
    private function throwApiException(Response $response): void
    {
        $statusCode = $response->getStatusCode();
        $requestId = $response->getRequestId();
        $rawBody = $response->getBody();

        $decoded = null;
        try {
            $decoded = json_decode($rawBody, true);
        } catch (\Throwable) {
            // Ignore JSON decode failure
        }

        $message = 'HTTP request failed with status ' . $statusCode;
        $errorCode = 'API_ERROR';
        $details = null;

        if (is_array($decoded)) {
            $message = (string) ($decoded['message'] ?? $decoded['error'] ?? $decoded['error_description'] ?? $message);
            $errorCode = (string) ($decoded['code'] ?? $decoded['error'] ?? $errorCode);
            $details = $decoded;
        }

        // 1. Dual-Control DNS Deletion / Safety Challenge Guard Detection
        if (preg_match('/del_req_[a-f0-9]+/i', $message, $reqMatches) || isset($decoded['requestId'])) {
            $challengeReqId = $decoded['requestId'] ?? $reqMatches[0];
            $domain = $decoded['domain'] ?? null;
            $recordId = $decoded['recordId'] ?? null;
            throw new ApprovalRequiredException($message, (string) $challengeReqId, $domain, $recordId, $requestId, $details);
        }

        switch ($statusCode) {
            case 400:
                throw new ValidationException($message, $errorCode, is_array($details) ? $details : [], $requestId);
            case 401:
                throw new AuthenticationException($message, $errorCode, $requestId, $details);
            case 403:
                throw new PermissionDeniedException($message, $errorCode, $requestId, $details);
            case 404:
                throw new NotFoundException($message, $errorCode, $requestId, $details);
            case 409:
                throw new ConflictException($message, $errorCode, $requestId, $details);
            case 410:
                throw new ExpiredException($message, $errorCode, $requestId, $details);
            case 422:
                throw new ValidationException($message, $errorCode, is_array($details) ? $details : [], $requestId);
            case 429:
                $retryAfter = (int) ($response->getHeader('retry-after') ?? 0);
                throw new RateLimitException($message, $retryAfter > 0 ? $retryAfter : null, $errorCode, $requestId, $details);
            default:
                if ($statusCode >= 500) {
                    throw new ServerException($statusCode, $message, $errorCode, $requestId, $details);
                }
                throw new AeitronException($statusCode, $message, $errorCode, $requestId, $details);
        }
    }

    /**
     * Exponential backoff with full jitter to avoid thundering herd.
     */
    private function sleepWithBackoff(int $attempt, int $retryAfterSeconds): void
    {
        if ($retryAfterSeconds > 0) {
            usleep($retryAfterSeconds * 1000000);
            return;
        }

        // Base 50ms, max 1.5s exponential backoff with jitter
        $baseDelayMs = 50 * (2 ** ($attempt - 1));
        $maxDelayMs = 1500;
        $delayMs = min($maxDelayMs, $baseDelayMs);
        $jitterMs = random_int(0, (int) ($delayMs * 0.3));
        $totalSleepUs = ($delayMs + $jitterMs) * 1000;

        usleep($totalSleepUs);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    public function getTimeoutSeconds(): float
    {
        return $this->timeoutSeconds;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }
}
