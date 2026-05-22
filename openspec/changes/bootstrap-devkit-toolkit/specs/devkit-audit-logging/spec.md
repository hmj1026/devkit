## ADDED Requirements

### Requirement: Strategy-Based Change Logger
`Devkit\Laravel\Audit\AbstractEntityChangeLogger` (trait) SHALL capture Eloquent `created` / `updated` / `deleting` events, compute attribute diffs, and persist them via an injectable `Devkit\Logging\Contract\LogTargetContract`.

NOTE: For v2 (PHP 8.1+ floor), the implementation MAY pivot to wrapping `spatie/laravel-activitylog` ^4.0 as the underlying change-capture engine while keeping `LogTargetContract` strategy. v1 keeps a self-contained implementation because Spatie's v4 requires PHP 8.0+.

#### Scenario: Update writes diff to log target
- **WHEN** a model with the trait updates `email` from `a@x.com` to `b@x.com` and saves
- **THEN** the log target receives `{'entity_id': <id>, 'action': 'updated', 'changes': {'email': {'from': 'a@x.com', 'to': 'b@x.com'}}, 'user_id': <auth_user>, 'created_at': <timestamp>}`

#### Scenario: Created action records full snapshot
- **WHEN** a model with the trait is created
- **THEN** the log target receives `action: 'created'` with the full attribute snapshot in `changes`

### Requirement: LogTargetContract
`Devkit\Logging\Contract\LogTargetContract` SHALL declare `save(array $entry): void` so any storage backend can serve as a log target.

#### Scenario: Custom target
- **WHEN** a class implementing `LogTargetContract` is bound to the trait via config
- **THEN** the trait writes diffs to that target instead of the default

### Requirement: Eloquent Log Target
`Devkit\Laravel\Audit\EloquentLogTarget` SHALL persist change entries to a configurable Eloquent model (default: `<model_table>_logs`).

#### Scenario: Writes to log table
- **WHEN** a model `App\Models\Order` uses the trait with `EloquentLogTarget` and saves
- **THEN** a row is inserted into `orders_logs` carrying the diff

### Requirement: Elasticsearch Log Target
`Devkit\Laravel\Audit\ElasticsearchLogTarget` SHALL persist change entries to an `Devkit\Search\Index\Index` subclass, with mapping for `entity_id`, `action`, `changes`, `user_id`, `comment`, `created_at`.

#### Scenario: Writes to ES index
- **WHEN** a model uses the trait with `ElasticsearchLogTarget` and saves
- **THEN** a document is indexed into the configured ES log index carrying the diff

### Requirement: Login Audit
`Devkit\Laravel\Audit\LoginLoggerTrait` SHALL be applied to User models to record login events with user_id, guard, device, browser, platform, IP, and sanitized headers, dispatching to a `LoginLogTargetContract`.

#### Scenario: Login captures device info
- **WHEN** a user logs in via the `web` guard from Chrome on macOS
- **THEN** the login log target receives an entry with `guard: 'web'`, `browser: 'Chrome'`, `platform: 'OS X'`, and the request IP

### Requirement: User-Agent Parsing
`Devkit\Laravel\Audit\AgentSupport` SHALL wrap `jenssegers/agent` v2 and expose `device()`, `browser()`, `platform()`, plus a method to sanitize HTTP headers (masking cookie/authorization values).

#### Scenario: Authorization header masked
- **WHEN** `AgentSupport::sanitizeHeaders($request->headers->all())` is called with `Authorization: Bearer xyz`
- **THEN** the returned headers contain `Authorization` with a masked value (e.g. `[redacted]`)

### Requirement: Consolidation of Duplicated Loggers
The audit logging capability SHALL replace the ~600 lines duplicated between two parallel entity logger traits in the source codebase (one writing to a DB log table, one writing to an Elasticsearch index) with a single trait plus two log target strategies.

#### Scenario: Single source of truth
- **WHEN** auditing diff logic needs change
- **THEN** the change is made once in `AbstractEntityChangeLogger`, not in two parallel implementations
