# Source Package Capability Checklist

This checklist maps the 11 source package concepts into the 14 Devkit capabilities defined by `bootstrap-devkit-toolkit`.

| Source package concept | Devkit capability | Implemented module |
| --- | --- | --- |
| framework helpers | `devkit-bootstrap` | Composer package, PHPUnit suites, CI workflow, README, license |
| framework enum helpers | `devkit-enum` | `Devkit\Core\Enum\AbstractEnum` |
| framework exceptions/responses | `devkit-http-foundation` | `AbstractHttpException`, `JsonEnvelope`, `WebEnvelope` |
| HTTP API client | `devkit-http-gateway` | `Devkit\Http\Client\Gateway` |
| HTTP URI/cache-busting | `devkit-asset-versioning` | `Devkit\Http\Asset\HttpUri` and Laravel cache adapter |
| file uploader | `devkit-file-uploader` | Storage foundations, Flysystem bridge, directors |
| SMS package | `devkit-sms-dispatch` | SMS contracts, manager, null driver, HTTP driver base, Laravel channel/job |
| SQS FIFO queue package | `devkit-sqs-fifo-queue` | Laravel FIFO connector, queue, deduplicators, queueable trait |
| Elasticsearch package | `devkit-elasticsearch` | Manager, index, alias, optional AWS signed handler |
| Elasticsearch log package | `devkit-audit-logging` | Shared audit logger with Eloquent and Elasticsearch targets |
| Google Chat log package | `devkit-googlechat-logger` | Dual Monolog handler, formatter, factory, Laravel log provider |
| breadcrumb trail package | `devkit-blade-helpers` | `Trail`, `TrailManager`, `TrailTag`, Laravel facade/helper |
| meta-tag package | `devkit-blade-helpers` | `Meta`, renderer, Laravel facade/directive |
| entity traits from framework/repository packages | `devkit-eloquent-helpers` | `HasUuid`, `HasStatus`, `HasAuditLog`, Criteria, casts |
| Laravel package glue and DX | `devkit-laravel-integration` | Root provider, facades, config, stubs, five generators, install command |

## Deliberately Not Ported

- Repository pattern classes are omitted by design. Consumers should use Eloquent scopes, services, and actions.
- Elasticsearch query builders and grammar translators are omitted by design. Consumers should use native Elasticsearch array DSL.
- Concrete SMS provider drivers are omitted to avoid leaking business-specific provider behavior.
- Legacy namespace aliases are deferred to a separate `add-legacy-shim` change.
