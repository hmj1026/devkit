## ADDED Requirements

### Requirement: Single-Class Gateway over Guzzle
`Devkit\Http\Client\Gateway` SHALL be a single, subclassable class wrapping `GuzzleHttp\Client` with built-in retry, exponential backoff, and log observer; consumers integrate with third-party APIs by subclassing `Gateway` and overriding configuration (base URI, default headers, auth) — not by authoring separate Request/Response class hierarchies.

#### Scenario: Default Guzzle setup
- **WHEN** code instantiates `new Gateway()` without arguments
- **THEN** an internal `GuzzleHttp\Client` is constructed and used for HTTP dispatch

#### Scenario: Subclass binds upstream
- **WHEN** a subclass `MyApiClient extends Gateway` declares `protected string $baseUri = 'https://api.example.com'` and calls `$this->request('GET', '/v1/foo')`
- **THEN** the dispatched request hits `https://api.example.com/v1/foo`

#### Scenario: PSR-18 client injection
- **WHEN** code injects a custom PSR-18 client (e.g. `Symfony\Component\HttpClient\Psr18Client`) via constructor
- **THEN** the Gateway dispatches requests through that client instead of Guzzle

### Requirement: Retry Decider with Exponential Backoff
The Gateway SHALL retry requests on 5xx responses and connection errors using exponential backoff, configurable for max attempts and initial delay via constructor arguments.

#### Scenario: 503 retries up to limit
- **WHEN** the upstream returns 503 three times then 200, with `maxAttempts = 4`
- **THEN** the Gateway returns the eventual 200 response without raising

#### Scenario: Exhausted retries raise exception
- **WHEN** the upstream returns 503 on every attempt up to `maxAttempts`
- **THEN** the Gateway raises `Devkit\Http\Client\Exception\RetryExhaustedException` containing the last response

### Requirement: Log Observer Hook
The Gateway SHALL allow attaching `Devkit\Http\Client\Contract\LogObserverContract` implementations notified on each request and response, decoupling logging from the request lifecycle.

#### Scenario: Observer captures request and response
- **WHEN** a `LogObserverContract` mock is attached and code dispatches one request
- **THEN** the observer's `onRequest($request)` and `onResponse($response)` methods are each invoked exactly once

#### Scenario: Multiple observers
- **WHEN** two observers are attached
- **THEN** both receive each request/response in registration order

### Requirement: No Separate Request/Response Class Hierarchy
This package SHALL NOT ship `AbstractRequest` / `AbstractResponse` abstract classes. The previous 4-layer abstraction (`Client` + `AbstractGateway` + `AbstractRequest` + `AbstractResponse`) is collapsed into a single `Gateway` class; per-API typed wrappers, if desired, are the consumer's responsibility.

#### Scenario: Audit src/ has no AbstractRequest
- **WHEN** a maintainer greps `src/Http/Client/` for `abstract class AbstractRequest`
- **THEN** no such class is found

### Requirement: Framework Independence
The Gateway SHALL NOT depend on `Illuminate\*` or any framework-specific container.

#### Scenario: Use in pure-PHP CLI script
- **WHEN** the Gateway is instantiated in a pure-PHP CLI script
- **THEN** it dispatches requests successfully without Laravel bootstrapping
