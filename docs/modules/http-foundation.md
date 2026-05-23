# Devkit HTTP Foundation

## Use Case

Use the HTTP foundation classes for framework-neutral exceptions and PSR-7 response envelopes. They provide common JSON response shape and redirect helpers without requiring Laravel response objects.

## Laravel Configuration

Laravel applications can keep returning native responses from controllers, or adapt PSR-7 responses at the edge. Enable the HTTP module for asset helper bindings:

```php
'modules' => array(
    'http' => array('enabled' => true),
),
```

## Pure PHP Usage

```php
use Devkit\Core\Response\JsonEnvelope;

$envelope = new JsonEnvelope($responseFactory);
$response = $envelope->success(array('id' => 1));
```

`AbstractHttpException` can be extended by domain exceptions that need status codes and report control.
