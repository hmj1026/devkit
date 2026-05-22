## ADDED Requirements

### Requirement: SMS Driver Contract
`Devkit\Messaging\Sms\Contract\SmsDriverContract` SHALL define `sendSms(SmsMessageContract $message): SmsResultContract`, with the message exposing `cellPhone()` / `body()` / `options()`.

#### Scenario: Custom driver implements contract
- **WHEN** a consumer-authored driver implements `SmsDriverContract` and is registered with the manager
- **THEN** `$manager->driver('custom')->sendSms($msg)` is callable and returns an `SmsResultContract`

### Requirement: Manager with Named Drivers
`Devkit\Messaging\Sms\SmsManager` SHALL resolve drivers by name, cache instances within the manager's lifetime, and accept new driver factories via `extend(string $name, Closure $factory)`. The manager SHALL expose a configurable default driver name.

#### Scenario: Default driver resolution
- **WHEN** config declares default driver `'null'` and code calls `$manager->sendSms('+886912345678', 'hello')`
- **THEN** the call is proxied to the `NullSmsDriver`

#### Scenario: Lazy driver instantiation
- **WHEN** the manager is constructed but no driver is requested
- **THEN** no driver class is loaded into memory

#### Scenario: Runtime driver registration
- **WHEN** code calls `$manager->extend('myProvider', fn () => new MyProviderDriver(...))` and then `$manager->driver('myProvider')`
- **THEN** the manager returns the `MyProviderDriver` instance produced by the factory

### Requirement: Null Driver for Development
The package SHALL ship a `Devkit\Messaging\Sms\Driver\NullSmsDriver` that records calls in-memory but never makes network requests, intended for local development and automated tests.

#### Scenario: Null driver records call
- **WHEN** `NullSmsDriver` is the active driver and code calls `sendSms('+886912345678', 'hi')`
- **THEN** the driver's internal log contains one entry with phone `+886912345678` and body `hi`, and no HTTP request is made

#### Scenario: Test inspection
- **WHEN** test code calls `$nullDriver->sentMessages()`
- **THEN** the test receives an array of every message dispatched during the test, in order

### Requirement: Abstract HTTP SMS Driver Base
`Devkit\Messaging\Sms\Driver\AbstractHttpSmsDriver` SHALL extend `Devkit\Http\Client\Gateway` and provide a subclassable base for HTTP-backed SMS providers, leaving three abstract hook methods: `endpointFor(SmsMessageContract $message): string`, `payloadFor(SmsMessageContract $message): array`, `parseResponse(ResponseInterface $response): SmsResultContract`.

#### Scenario: Consumer subclass dispatches HTTP
- **WHEN** a consumer subclass declares the three hook methods and code calls `$myDriver->sendSms($msg)`
- **THEN** the base class assembles a PSR-7 request from the subclass-provided endpoint + payload, dispatches via the Gateway (inheriting retry / backoff / log observer), and returns the subclass-parsed `SmsResultContract`

#### Scenario: Retry inherits from Gateway
- **WHEN** the subclass driver's upstream returns 503 then 200 and `maxAttempts = 2`
- **THEN** the driver returns the 200 result without raising, because the inherited Gateway retry logic handled the 503

### Requirement: No Concrete Provider Drivers Shipped
This package SHALL NOT ship any concrete SMS provider driver implementation (no Twilio, no AWS SNS, no Every8d, no in-house providers). Consumers SHALL author their own drivers by extending `AbstractHttpSmsDriver` or implementing `SmsDriverContract` directly.

#### Scenario: Audit src/ has no provider driver
- **WHEN** a maintainer greps `src/Messaging/Sms/Driver/` for class files
- **THEN** the only files found are `NullSmsDriver` and `AbstractHttpSmsDriver` (plus contract directory) — no provider-named subdirectories

### Requirement: Driver-Specific Enums Inherit AbstractEnum
Driver enums authored by consumers (status codes, account types, project IDs, etc.) SHALL extend `Devkit\Core\Enum\AbstractEnum` so they inherit the package's enum helper surface.

#### Scenario: Consumer-defined status enum
- **WHEN** a consumer-defined `MyProviderStatusEnum extends AbstractEnum` declares `SUCCESS = '0000'` and code calls `MyProviderStatusEnum::content(MyProviderStatusEnum::SUCCESS)`
- **THEN** the result equals the configured human-readable label for success

### Requirement: Laravel Bridge — Notification Channel + Queueable Job
A Laravel bridge SHALL provide `SmsChannel` (Notification channel) and `SendSmsJob` (queueable), with `sendSmsQueue()` on the manager dispatching the job onto a named Laravel queue.

#### Scenario: Notification dispatch
- **WHEN** a Laravel Notification implements `via()` returning `[SmsChannel::class]`
- **THEN** sending the notification routes through `SmsChannel` and invokes the configured SMS driver

#### Scenario: Queueable job
- **WHEN** code calls `$manager->sendSmsQueue('+886912345678', 'hi')`
- **THEN** a `SendSmsJob` is dispatched onto the configured queue connection and queue name, and a queue worker later invokes the driver
