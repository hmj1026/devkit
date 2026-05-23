# Devkit HTTP Gateway

## Use Case

Use `Devkit\Http\Client\Gateway` for small third-party API clients that need retry, exponential backoff, and request/response observer hooks without creating request and response class hierarchies.

## Laravel Configuration

The default retry settings live in config:

```php
'http' => array(
    'gateway' => array(
        'max_attempts' => 3,
        'base_delay_ms' => 100,
    ),
),
```

Generate a client scaffold when generators are enabled:

```bash
php artisan devkit:make:http-client Billing/BillingClient
```

## Pure PHP Usage

```php
use Devkit\Http\Client\Gateway;

class BillingClient extends Gateway
{
    protected $baseUri = 'https://api.example.com';
}

$client = new BillingClient();
$response = $client->request('GET', '/v1/accounts');
```
