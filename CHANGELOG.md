# Changelog

All notable changes to `hmj1026/devkit` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> No release has been tagged yet. The first tagged release will be `1.0.0`; the entries
> below describe its scope. On tagging, rename the `[Unreleased]` heading to
> `[1.0.0] - <date>`.

## [Unreleased]

### Added

- **Initial toolkit — 14 capabilities.** Framework-agnostic core (`Devkit\Core\*`,
  `Devkit\Http\*`, `Devkit\Storage\*`, `Devkit\Search\*`, `Devkit\Messaging\*`,
  `Devkit\Logging\*`, `Devkit\Ui\*`) plus Laravel glue (`Devkit\Laravel\*`):
  - `devkit-enum` — reflection-based `AbstractEnum` for the PHP 7.3 floor.
  - `devkit-http-foundation` — `AbstractHttpException` + PSR-7 JSON/Web envelopes.
  - `devkit-http-gateway` — single-class Guzzle `Gateway` with retry/backoff + log observers.
  - `devkit-asset-versioning` — PSR-16-cached asset URL versioning (`HttpUri`).
  - `devkit-file-uploader` — director pattern over Flysystem 1/2/3 with visibility mapping.
  - `devkit-elasticsearch` — ES 7.17 manager, `Index`/`Alias` bases, optional AWS SigV4 handler.
  - `devkit-sms-dispatch` — driver contract + manager + `NullSmsDriver` + `AbstractHttpSmsDriver`.
  - `devkit-sqs-fifo-queue` — Laravel SQS FIFO connector with four deduplicators.
  - `devkit-googlechat-logger` — dual Monolog 2.9 / 3.x Google Chat handler.
  - `devkit-blade-helpers` — `Trail` breadcrumbs + weight-sorted `Meta` (butschster 2/3).
  - `devkit-eloquent-helpers` — `HasUuid` / `HasStatus` / `HasAuditLog` traits, `Criteria`, casts.
  - `devkit-audit-logging` — strategy-based entity-change logger (Eloquent + Elasticsearch targets).
  - `devkit-laravel-integration` — root `DevkitServiceProvider`, 5 opt-in generators, `devkit:install`.
- **Quality gate.** PHPStan (level 5) with a baseline for cross-version polyfill artifacts,
  line-coverage reporting to Codecov, and a `quality` CI job. See
  [`openspec/changes/add-quality-gate`](./openspec/changes/add-quality-gate/).
- **Full PHP × Laravel CI matrix (19 cells)** spanning PHP 7.3 → 8.4 × Laravel 6 → 11,
  closing the previously-untested PHP 8.3 / 8.4 cells.
- **Polyfill branch tests.** Skip-guarded tests proving both the Monolog 2/3 and
  Flysystem 1/2-3 branches across the matrix; direct `FilesystemBridge` coverage.
- Release documentation: `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md`, and a
  tag-triggered GitHub Release job gated behind the full matrix + `quality` job.

### Fixed

- **Laravel 6 cast polyfill hardening** (`devkit-eloquent-helpers`). `EncryptedCast` /
  `HashedCast` on Laravel 6 now serialize through `toArray()`/`toJson()`, compute dirty
  state by decoded value, memoize per-instance cast reads, and detect native class-cast
  support robustly. Tracked by the archived `harden-laravel-cast-polyfill` OpenSpec change
  (the `openspec/changes/archive/` directory is local-only / gitignored).

[Unreleased]: https://github.com/hmj1026/devkit/commits/develop
