# Aeitron PHP SDK

Official zero-dependency PHP client library for Aeitron intelligent edge services, network routing, DNS, compute placement, and AI orchestration.

## Requirements

- PHP 8.1 or higher
- `ext-curl` and `ext-json`

## Installation

Install via Composer:

```bash
composer require aeitron/api-sdk
```

## Quickstart

```php
use Aeitron\AeitronClient;

$client = new AeitronClient('atk_live_your_api_key_here');

// Multi-objective AI inference placement
$response = $client->inference->complete([
    'model' => 'llama-3.3-70b-instruct',
    'messages' => [
        ['role' => 'user', 'content' => 'Optimize globally distributed edge routing.']
    ],
    'strategy' => 'LATENCY_OPTIMAL',
    'jurisdiction' => 'GLOBAL'
]);

// Query edge PoP health
$pops = $client->telemetry->getPopHealth();

// List authoritative DNS records
$records = $client->dns->list('example.com');
```

## Supported Namespaces

- `client->inference`: Multi-objective AI model execution and routing
- `client->domains`: Custom domain onboarding, verification, and deletion
- `client->dns`: Authoritative DNS record management
- `client->telemetry`: Edge traffic metrics and PoP operational status
- `client->compute`: MicroVM and compute instance inspection
- `client->storage`: Edge object store and bucket inspection
- `client->network`: Latency matrix and subsea cable routing topology

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
