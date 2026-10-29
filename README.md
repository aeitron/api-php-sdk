# Aeitron Official PHP SDK

[![CI](https://github.com/aeitron/api-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/aeitron/api-php-sdk/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP: >=8.1](https://img.shields.io/badge/PHP-%3E%3D8.1-777bb4.svg)](https://www.php.net/)

The official, zero-dependency, enterprise-grade PHP client library for **Aeitron** — the intelligent global edge, distributed security fabric, multi-objective AI placement engine, and workload orchestration platform.

---

## Key Features

- **Zero External Runtime Dependencies:** Operates natively using modern PHP (>= 8.1) with `ext-curl` and `ext-json`.
- **Strictly Typed & PSR Compliant:** Full PSR-4 autoloading, strict scalar typing (`declare(strict_types=1);`), and typed domain exceptions.
- **Enterprise Error Handling:** Fine-grained exception hierarchy mapping HTTP status codes and domain errors (`AuthenticationException`, `PermissionDeniedException`, `ApprovalRequiredException`, `RateLimitException`, `ValidationException`, `ServerException`, `NetworkException`).
- **Resilient Transport & Retries:** Automatic exponential backoff with full jitter for transient 5xx and 429 errors on idempotent requests.
- **Dual-Control Human Operator Approval:** Native support for Aeitron's dual-control DNS deletion protocol and single-use challenge tokens.
- **Webhook Security:** Cryptographic HMAC-SHA256 signature verification with configurable timestamp tolerance to prevent replay attacks.
- **Unit Testing First:** Pluggable `TransportInterface` with built-in `MockTransport` for deterministic, zero-network unit testing in customer applications.

---

## Requirements

- PHP 8.1 or higher
- `ext-curl`
- `ext-json`

---

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require aeitron/api-sdk
```

---

## Quickstart

### 1. Initialize the Client

```php
use Aeitron\AeitronClient;
use Aeitron\ClientOptions;

// Standard initialization
$client = new AeitronClient('atk_live_your_api_key_here');

// Or configure advanced client options
$options = new ClientOptions(
    baseUrl: 'https://dashboard.aeitron.com',
    apiVersion: '2026-11-01',
    timeoutSeconds: 30.0,
    maxRetries: 2,
    defaultHeaders: ['X-Tenant-Tag' => 'production-edge']
);
$client = new AeitronClient('atk_live_your_api_key_here', $options);
```

---

### 2. Multi-Objective AI Inference Placement

Route LLM inference across global edge regions optimizing for latency, cost, carbon footprint, KV-cache locality, or sovereign jurisdictions:

```php
use Aeitron\Enums\Jurisdiction;
use Aeitron\Enums\RoutingStrategy;

$response = $client->inference->complete([
    'model' => 'anthropic.claude-3-5-sonnet',
    'messages' => [
        ['role' => 'system', 'content' => 'You are an edge infrastructure copilot.'],
        ['role' => 'user', 'content' => 'Summarize real-time edge telemetry.']
    ],
    'strategy' => RoutingStrategy::LATENCY_OPTIMAL,
    'jurisdiction' => Jurisdiction::GLOBAL,
    'temperature' => 0.2
], 'idemp_key_unique_001'); // Optional Idempotency-Key

echo $response['choices'][0]['message']['content'];
echo "Routed Region: " . $response['telemetry']['region'];
echo "Time-to-First-Token: " . $response['telemetry']['ttftMs'] . "ms";
```

---

### 3. Dual-Control DNS Deletion (Human Operator Approval)

Destructive DNS record deletions require an independent human operator approval workflow to prevent catastrophic routing outages:

```php
use Aeitron\Exceptions\ApprovalRequiredException;

try {
    // Attempting direct deletion in protected zones triggers the approval requirement
    $client->dns->delete('example.com', 'rec_dns_cname_003');
} catch (ApprovalRequiredException $e) {
    echo "Deletion blocked: " . $e->getMessage() . "\n";
    $requestId = $e->getChallengeRequestId(); // e.g. "del_req_abc12345"

    // 1. Submit for operator review (or request explicit approval challenge)
    echo "Pending Request ID: " . $requestId . "\n";

    // 2. An authorized operator (with scope 'dns:delete:approve') reviews and approves:
    $operatorClient = new AeitronClient('atk_live_operator_token_with_approval_scope');
    $approval = $operatorClient->dns->approveDeletion($requestId);
    $approvalToken = $approval['approvalToken']; // e.g. "del_tok_eyJh..."

    // 3. Complete the permanent deletion with token and explicit confirmation
    $result = $client->dns->delete('example.com', 'rec_dns_cname_003', $approvalToken, confirm: true);
    echo "Record deleted successfully.\n";
}
```

---

### 4. Webhook Signature Verification

Secure your webhook endpoints against forgery and replay attacks using timing-safe HMAC-SHA256 verification:

```php
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\ValidationException;

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_AEITRON_SIGNATURE'] ?? '';
$webhookSecret = getenv('AEITRON_WEBHOOK_SECRET'); // e.g. "whsec_..."

try {
    // Validates signature, verifies timestamp within 300s tolerance, and parses event
    $event = $client->webhooks->constructEvent($payload, $signatureHeader, $webhookSecret);

    if ($event['type'] === 'domain.verified') {
        $domain = $event['data']['domain'];
        // Handle domain activation...
    }

    http_response_code(200);
    echo json_encode(['received' => true]);
} catch (AuthenticationException $e) {
    // Forged signature
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
} catch (ValidationException $e) {
    // Expired timestamp (replay attempt) or malformed payload
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

### 5. Enterprise Error Handling

Catch specific exception types to implement precise application fallback logic:

```php
use Aeitron\Exceptions\AuthenticationException;
use Aeitron\Exceptions\PermissionDeniedException;
use Aeitron\Exceptions\NotFoundException;
use Aeitron\Exceptions\RateLimitException;
use Aeitron\Exceptions\ValidationException;
use Aeitron\Exceptions\ServerException;
use Aeitron\Exceptions\NetworkException;
use Aeitron\Exceptions\AeitronException;

try {
    $zones = $client->domains->list();
} catch (AuthenticationException $e) {
    // 401 Unauthorized: Invalid API key
} catch (PermissionDeniedException $e) {
    // 403 Forbidden: Missing scope or cross-tenant access violation
} catch (RateLimitException $e) {
    // 429 Too Many Requests: Back off and retry
    $retryAfterSeconds = $e->getRetryAfterSeconds();
    sleep($retryAfterSeconds ?? 5);
} catch (ValidationException $e) {
    // 400/422 Bad Request: Inspect field validation errors
    $errors = $e->getValidationErrors();
} catch (ServerException $e) {
    // 5xx Platform Error
} catch (NetworkException $e) {
    // cURL timeout or connection refusal
} catch (AeitronException $e) {
    // Catch-all Aeitron API error
}
```

---

### 6. Testing with MockTransport

Mock API responses in your test suite without making network calls:

```php
use Aeitron\AeitronClient;
use Aeitron\ClientOptions;
use Aeitron\Http\MockTransport;
use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase
{
    public function testEdgeRouting(): void
    {
        $mock = new MockTransport();
        $mock->appendResponse(200, [], [
            'status' => 'active',
            'domain' => 'mybrand.com'
        ]);

        $client = new AeitronClient('atk_test_dummy', new ClientOptions(transport: $mock));
        $domain = $client->domains->get('mybrand.com');

        $this->assertSame('active', $domain['status']);
        $this->assertSame('GET', $mock->getLastRequest()['method']);
    }
}
```

---

## Supported Namespaces

| Namespace | Description | Key Methods |
|---|---|---|
| `client->inference` | Multi-objective AI model execution and routing | `complete()` |
| `client->domains` | Custom edge domain onboarding & TLS verification | `list()`, `get()`, `onboard()`, `verify()`, `delete()` |
| `client->dns` | Authoritative DNS zones & Dual-Control challenge approvals | `list()`, `create()`, `delete()`, `requestDeletion()`, `approveDeletion()` |
| `client->telemetry` | Edge traffic metrics, summary, and PoP health | `getSummary()`, `getPopHealth()` |
| `client->webhooks` | Signature verification & subscription management | `constructEvent()`, `verifySignature()`, `list()`, `create()`, `delete()` |
| `client->compute` | Edge microVMs and serverless instances | `listInstances()`, `getInstance()` |
| `client->storage` | Distributed edge object storage and buckets | `listBuckets()`, `listObjects()` |
| `client->network` | Global subsea cable topology & latency matrix | `getLatencyMatrix()`, `getCables()` |

---

## License

This SDK is open-source software licensed under the [MIT License](LICENSE).
