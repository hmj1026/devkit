# hmj1026/devkit

A generic, framework-agnostic PHP toolkit with optional Laravel integration. Bundles common building blocks for backend services — HTTP gateway, Elasticsearch toolkit, SMS dispatch, file uploader, audit logging, Google Chat error logger, meta tags, breadcrumb trail — into a single Composer package.

> **Status: Wave 0 bootstrap.** The package skeleton (composer manifest, PHPUnit config, CI matrix) is live. Module implementation lands in Waves 1–6 per [`openspec/changes/bootstrap-devkit-toolkit/tasks.md`](./openspec/changes/bootstrap-devkit-toolkit/tasks.md).

## Supported runtimes

| PHP | Laravel | Monolog | Notes |
|-----|---------|---------|-------|
| 7.3 | 6.x / 7.x | 2.9 | Lowest supported floor (PHP 7.2 excluded — `elasticsearch/elasticsearch ^7.17` requires PHP 7.3+) |
| 7.4 | 6.x / 7.x / 8.x | 2.9 | Most common legacy target |
| 8.0 | 8.x / 9.x | 2.9 | |
| 8.1 | 8.x / 9.x / 10.x | 2.9 (L8/9) / 3.x (L10) | |
| 8.2 | 9.x / 10.x / 11.x | 2.9 (L9) / 3.x (L10/11) | Most modern cell |

Incompatible cells (e.g. PHP 7.3 + Laravel 9, PHP 7.2 + anything) are excluded by Composer's resolver and by the CI matrix in [`.github/workflows/tests.yml`](./.github/workflows/tests.yml). The package declares `monolog/monolog ^2.9 || ^3.0`; Laravel 10+ forces monolog 3, earlier Laravel keeps monolog 2.9.

A v2 with PHP `^8.1` floor will swap Monolog → 3.x, Flysystem → 3-only, and consider [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) as the audit-log engine.

## Install

```bash
composer require hmj1026/devkit
```

On Laravel projects, the root `Devkit\Laravel\DevkitServiceProvider` is registered via `extra.laravel.providers` package auto-discovery. Module sub-providers are off by default; opt in per module via `config/devkit.php` once it's published in Wave 5.

## Usage

### Framework-agnostic (no Laravel)

```php
use Devkit\Http\Client\Gateway;
use GuzzleHttp\Client;

$gateway = new Gateway(new Client(['base_uri' => 'https://api.example.com']));
$response = $gateway->request('GET', '/health');
```

### Laravel

```php
use Devkit\Laravel\Http\Facades\HttpUri;

$cdnUrl = HttpUri::cdn('images/logo.png');
```

Full module-level usage will land alongside each Wave's spec; see `openspec/changes/bootstrap-devkit-toolkit/specs/*/spec.md` for the design.

## Module map (14 capabilities)

Framework-agnostic core (`Devkit\Core\*`, `Devkit\Database\*`, `Devkit\Http\*`, `Devkit\Storage\*`, `Devkit\Search\*`, `Devkit\Messaging\*`, `Devkit\Logging\*`, `Devkit\Ui\*`):

- `devkit-enum` — Reflection-based PHP enum base class (PHP 5.6+ syntax-compatible).
- `devkit-http-foundation` — `AbstractHttpException` + JSON/Web envelope returns PSR-7 `ResponseInterface`.
- `devkit-http-gateway` — Single-class Gateway around Guzzle with retry/backoff + log observer.
- `devkit-asset-versioning` — PSR-16-cached asset URL versioning.
- `devkit-file-uploader` — Director pattern over Flysystem 2/3 with dual visibility mapping.
- `devkit-elasticsearch` — ES 7.17 client with Index/Alias bases and raw array DSL (no Query Builder).
- `devkit-sms-dispatch` — Driver contract + Manager + NullDriver + `AbstractHttpSmsDriver`.
- `devkit-googlechat-logger` — Dual Monolog 2.9 / 3.x handler for Google Chat webhooks (version selected per Laravel cell).
- `devkit-blade-helpers` — Trail (breadcrumb) + butschster/meta-tags v2 with weight-sorted ordering.
- `devkit-eloquent-helpers` — `HasUuid` / `HasStatus` / `HasAuditLog` traits + Criteria + Casts.
- `devkit-audit-logging` — Strategy-based entity change logger with Eloquent + Elasticsearch targets.
- `devkit-sqs-fifo-queue` — Laravel-only SQS FIFO queue connector.

Laravel glue (`Devkit\Laravel\*`):

- `devkit-laravel-integration` — Root `DevkitServiceProvider`, 5 opt-in Artisan generators (`devkit:make:service`, `:action`, `:enum`, `:audit-log-target`, `:http-client`), publishable stubs, `devkit:install`.

## Local development

Run tests inside the docker_run PHP 7.4 container (bind-mounted at `/var/www/devkit`):

```bash
docker exec -w /var/www/devkit posdev_php composer install
docker exec -w /var/www/devkit posdev_php vendor/bin/phpunit --testsuite=core
docker exec -w /var/www/devkit posdev_php vendor/bin/phpunit --testsuite=laravel
```

Full CI matrix (PHP 7.3 → 8.2 × Laravel 6 → 11) runs on GitHub Actions; see [`.github/workflows/tests.yml`](./.github/workflows/tests.yml).

## OpenSpec workflow

This package is managed with [OpenSpec](https://github.com/Fission-AI/OpenSpec). Active change:

- [`openspec/changes/bootstrap-devkit-toolkit/`](./openspec/changes/bootstrap-devkit-toolkit/) — proposal, design, 14 module specs, and the wave-based task list driving implementation.

## License

Released under the [MIT License](./LICENSE). Copyright (c) 2026 Paul.
