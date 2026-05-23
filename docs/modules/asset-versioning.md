# Devkit Asset Versioning

## Use Case

Use `HttpUri` to append stable cache-busting timestamps to asset URLs. The timestamp is backed by PSR-16 cache and can be cleared on deploy.

## Laravel Configuration

Enable the HTTP module and configure the cache key:

```php
'modules' => array(
    'http' => array('enabled' => true),
),
'http' => array(
    'asset_version' => array(
        'cache_key' => 'devkit.asset_version',
        'ttl' => 3600,
    ),
),
```

In Blade:

```blade
<img src="{{ http_url('/images/logo.png') }}">
```

## Pure PHP Usage

```php
use Devkit\Http\Asset\HttpUri;

$uri = new HttpUri($psr16Cache);
echo $uri->url('/images/logo.png');
```
