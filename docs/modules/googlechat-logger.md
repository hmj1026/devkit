# Devkit Google Chat Logger

## Use Case

Use the Google Chat handler to send Monolog records to a Chat webhook with severity colors and optional per-level mentions. The handler adapts to Monolog 2 and 3 at runtime.

## Laravel Configuration

Enable the logging module:

```php
'modules' => array(
    'logging' => array('enabled' => true),
),
'googlechat' => array(
    'url' => env('GOOGLECHAT_WEBHOOK_URL'),
    'mentions' => array(
        'error' => 'users/12345',
    ),
),
```

Configure a Laravel logging channel using the service provider's custom driver registration.

## Pure PHP Usage

```php
$handler = new GoogleChatLogHandler(
    $httpClient,
    $requestFactory,
    $streamFactory,
    $webhookUrl,
    'my-app',
    'production'
);

$logger = new \Monolog\Logger('app');
$logger->pushHandler($handler);
```
