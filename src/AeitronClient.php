<?php

declare(strict_types=1);

namespace Aeitron;

use Aeitron\Exceptions\AeitronException;
use Aeitron\Namespaces\Compute;
use Aeitron\Namespaces\Dns;
use Aeitron\Namespaces\Domains;
use Aeitron\Namespaces\Inference;
use Aeitron\Namespaces\Network;
use Aeitron\Namespaces\Storage;
use Aeitron\Namespaces\Telemetry;

class AeitronClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $apiVersion;
    private float $timeoutSeconds;

    public Inference $inference;
    public Domains $domains;
    public Telemetry $telemetry;
    public Dns $dns;
    public Compute $compute;
    public Storage $storage;
    public Network $network;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://dashboard.aeitron.com',
        string $apiVersion = '2026-11-01',
        float $timeoutSeconds = 30.0
    ) {
        $trimmedKey = trim($apiKey);
        if ($trimmedKey === '') {
            throw new AeitronException(400, 'AeitronClient requires a non-empty API key (e.g., nke_live_... or atk_live_...)', 'INVALID_API_KEY');
        }

        $this->apiKey = $trimmedKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiVersion = $apiVersion;
        $this->timeoutSeconds = max(0.1, $timeoutSeconds);

        $this->inference = new Inference($this);
        $this->domains = new Domains($this);
        $this->telemetry = new Telemetry($this);
        $this->dns = new Dns($this);
        $this->compute = new Compute($this);
        $this->storage = new Storage($this);
        $this->network = new Network($this);
    }

    /**
     * Executes an authenticated HTTP request to the Aeitron API.
     *
     * @param string $method GET, POST, PUT, DELETE, etc.
     * @param string $path URL path e.g. /v1/domains
     * @param mixed $body Payload to encode as JSON
     * @param array<string, string> $extraHeaders Custom headers
     * @return array<string, mixed> Decoded JSON response
     * @throws AeitronException
     */
    public function request(string $method, string $path, mixed $body = null, array $extraHeaders = []): array
    {
        $url = $this->baseUrl . (str_starts_with($path, '/') ? $path : '/' . $path);
        
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Aeitron-Version' => $this->apiVersion,
            'User-Agent' => 'Aeitron-PHP-SDK/1.0.0 (PHP ' . PHP_VERSION . ')',
        ], $extraHeaders);

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) ceil($this->timeoutSeconds));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, (int) ceil($this->timeoutSeconds)));

        if ($body !== null) {
            $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        }

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new AeitronException(500, 'Network transport error: ' . $curlError, 'NETWORK_ERROR');
        }

        /** @var string $response */
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            if ($statusCode >= 200 && $statusCode < 300) {
                return ['raw' => $response, 'status' => $statusCode];
            }
            throw new AeitronException($statusCode, 'Malformed response from API: ' . substr($response, 0, 200), 'MALFORMED_RESPONSE');
        }

        if ($statusCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? ('HTTP ' . $statusCode);
            $code = $decoded['code'] ?? 'API_ERROR';
            throw new AeitronException($statusCode, (string) $msg, (string) $code);
        }

        return $decoded;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
