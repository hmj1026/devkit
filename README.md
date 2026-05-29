# hmj1026/devkit

[![Tests](https://github.com/hmj1026/devkit/actions/workflows/tests.yml/badge.svg)](https://github.com/hmj1026/devkit/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/hmj1026/devkit.svg)](https://packagist.org/packages/hmj1026/devkit)
[![codecov](https://codecov.io/gh/hmj1026/devkit/branch/master/graph/badge.svg)](https://codecov.io/gh/hmj1026/devkit)
[![PHP Version](https://img.shields.io/packagist/php-v/hmj1026/devkit.svg)](https://packagist.org/packages/hmj1026/devkit)
[![License](https://img.shields.io/packagist/l/hmj1026/devkit.svg)](./LICENSE)

A generic, framework-agnostic PHP toolkit with optional Laravel integration. Bundles common building blocks for backend services — HTTP gateway, Elasticsearch toolkit, SMS dispatch, file uploader, audit logging, Google Chat error logger, meta tags, breadcrumb trail — into a single Composer package.

All 14 capabilities are implemented, tested across a 19-cell PHP × Laravel CI matrix, and documented. See the [CHANGELOG](./CHANGELOG.md) for release history and [CONTRIBUTING](./CONTRIBUTING.md) for the development workflow.

## Supported runtimes

| PHP | Laravel | Monolog | Notes |
|-----|---------|---------|-------|
| 7.3 | 6.x / 7.x / 8.x | 2.9 | Lowest supported floor (PHP 7.2 excluded — `elasticsearch/elasticsearch ^7.17` requires PHP 7.3+) |
| 7.4 | 6.x / 7.x / 8.x | 2.9 | Most common legacy target |
| 8.0 | 6.x / 7.x / 8.x / 9.x | 2.9 | |
| 8.1 | 8.x / 9.x / 10.x | 2.9 (L8/9) / 3.x (L10) | |
| 8.2 | 9.x / 10.x / 11.x | 2.9 (L9) / 3.x (L10/11) | L11 also requires `butschster/meta-tags ^3.0` |
| 8.3 | 10.x / 11.x | 3.x | |
| 8.4 | 11.x | 3.x | Newest cell; requires PHPUnit `^11.0` |

Incompatible cells (e.g. PHP 7.3 + Laravel 9, PHP 8.1+ + Laravel 6/7, PHP 7.2 + anything) are excluded by Composer's resolver and by the CI matrix in [`.github/workflows/tests.yml`](./.github/workflows/tests.yml). Every valid cell admitted by the `php: ^7.3 || ^8.0` constraint (19 in total) is exercised. The package declares `monolog/monolog ^2.9 || ^3.0` and `butschster/meta-tags ^2.1 || ^3.0`; Laravel 10+ forces monolog 3, Laravel 11 forces meta-tags 3, and the GoogleChat handler + Meta wrapper detect the installed major at autoload time.

A v2 with PHP `^8.1` floor will swap Monolog → 3.x, Flysystem → 3-only, and consider [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) as the audit-log engine.

## Install

```bash
composer require hmj1026/devkit
```

On Laravel projects, the root `Devkit\Laravel\DevkitServiceProvider` is registered via `extra.laravel.providers` package auto-discovery. Module sub-providers are enabled by default and can be disabled per module via `config/devkit.php`.

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

$assetUrl = HttpUri::url('/images/logo.png');
```

Module-level usage lives in [`docs/`](./docs/) and the capability specs under [`openspec/specs/*/spec.md`](./openspec/specs/).

## Module map (14 capabilities)

Framework-agnostic core (`Devkit\Core\*`, `Devkit\Database\*`, `Devkit\Http\*`, `Devkit\Storage\*`, `Devkit\Search\*`, `Devkit\Messaging\*`, `Devkit\Logging\*`, `Devkit\Ui\*`):

- `devkit-enum` — Reflection-based PHP enum base class for the PHP 7.3+ compatibility floor.
- `devkit-http-foundation` — `AbstractHttpException` + JSON/Web envelope returns PSR-7 `ResponseInterface`.
- `devkit-http-gateway` — Single-class Gateway around Guzzle with retry/backoff + log observer.
- `devkit-asset-versioning` — PSR-16-cached asset URL versioning.
- `devkit-file-uploader` — Director pattern over Flysystem 1/2/3 with cross-version visibility mapping.
- `devkit-elasticsearch` — ES 7.17 client with Index/Alias bases and raw array DSL (no Query Builder).
- `devkit-sms-dispatch` — Driver contract + Manager + NullDriver + `AbstractHttpSmsDriver`.
- `devkit-googlechat-logger` — Dual Monolog 2.9 / 3.x handler for Google Chat webhooks (version selected per Laravel cell).
- `devkit-blade-helpers` — Trail (breadcrumb) + dual butschster/meta-tags 2.x / 3.x wrapper with weight-sorted ordering.
- `devkit-eloquent-helpers` — `HasUuid` / `HasStatus` / `HasAuditLog` traits + Criteria + Casts. Laravel 6 consumers must `use UsesClassCastCompatibility` on models with `EncryptedCast` / `HashedCast`.
- `devkit-audit-logging` — Strategy-based entity change logger with Eloquent + Elasticsearch targets.
- `devkit-sqs-fifo-queue` — Laravel-only SQS FIFO queue connector.

Laravel glue (`Devkit\Laravel\*`):

- `devkit-laravel-integration` — Root `DevkitServiceProvider`, 5 opt-in Artisan generators (`devkit:make:service`, `:action`, `:enum`, `:audit-log-target`, `:http-client`), publishable stubs, `devkit:install`.

## Local development

Composer scripts drive the test, lint, and static-analysis tooling:

```bash
composer test:core      # phpunit --testsuite=core (pure PHP, no Laravel)
composer test:laravel   # phpunit --testsuite=laravel (Orchestra Testbench)
composer test:unit      # both testsuites
composer lint           # php-cs-fixer --dry-run --diff
composer lint:fix       # php-cs-fixer
composer stan           # phpstan (level 5; requires phpstan on PATH — see below)
```

Static analysis runs on PHP 8.2 via a PHPStan PHAR (PHPStan 2.x does not install on the
PHP 7.3 cells), so `composer stan` needs `phpstan` available — install it globally or use
the CI `quality` job. The baseline (`phpstan-baseline.neon`) freezes the cross-version
polyfill artifacts; new code must analyse clean.

Reproduce a single CI cell locally (cleanup with `git checkout -- composer.json && composer install`):

```bash
composer matrix:list                          # list every (php, laravel) cell
composer matrix:test -- 8.2 11                # install that cell's deps + run both suites
```

The full CI matrix (PHP 7.3 → 8.4 × Laravel 6 → 11, 19 cells) plus a `quality` job
(PHPStan + coverage) runs on GitHub Actions; see [`.github/workflows/tests.yml`](./.github/workflows/tests.yml).

## Versioning

This package follows [Semantic Versioning](https://semver.org). The public contracts under
`Devkit\Core\*`, `Devkit\Http\*`, `Devkit\Storage\*`, `Devkit\Search\*`, `Devkit\Messaging\*`,
`Devkit\Logging\*`, `Devkit\Ui\*`, and the documented Laravel facades are stable within the
`1.x` line. Breaking changes — bumping the PHP floor to `^8.1`, dropping Monolog 2 /
Flysystem 1 support, or scaffolding output requiring newer PHP syntax — are reserved for
`2.0` (see [`docs/v2-roadmap.md`](./docs/v2-roadmap.md)). Release history: [CHANGELOG](./CHANGELOG.md).

## Contributing & security

- [CONTRIBUTING.md](./CONTRIBUTING.md) — branch flow, OpenSpec workflow, polyfill discipline, local commands.
- [SECURITY.md](./SECURITY.md) — supported versions and private vulnerability reporting.

## OpenSpec workflow

This package is managed with [OpenSpec](https://github.com/Fission-AI/OpenSpec). The canonical
capability specs live under [`openspec/specs/`](./openspec/specs/); in-flight changes (each a
proposal + design + spec delta) live under [`openspec/changes/`](./openspec/changes/), and
completed changes are archived under `openspec/changes/archive/`.

## License

Released under the [MIT License](./LICENSE). Copyright (c) 2026 Paul.
