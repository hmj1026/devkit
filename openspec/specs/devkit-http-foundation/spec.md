# devkit-http-foundation Specification

## Purpose
HTTP-aware exception base (Symfony HttpExceptionInterface) plus JSON/Web PSR-7 response envelopes, with a Laravel JsonResponse adapter.

## Requirements

### Requirement: HTTP-Aware Exception Base
`Devkit\Core\Exception\AbstractHttpException` SHALL implement `Symfony\Component\HttpKernel\Exception\HttpExceptionInterface` and `Devkit\Core\Exception\ReportExceptionContract`, exposing `getStatusCode()`, `getHeaders()`, and `shouldReport()`.

#### Scenario: Subclass sets 404 status
- **WHEN** a subclass declares `protected $statusCode = 404` and is thrown
- **THEN** `$exception->getStatusCode()` returns `404`

#### Scenario: Subclass opts out of reporting
- **WHEN** a subclass overrides `shouldReport()` to return `false`
- **THEN** the consuming framework's exception handler may skip log writing for this exception type

#### Scenario: Headers passthrough
- **WHEN** a subclass returns `['X-Custom' => 'value']` from `getHeaders()`
- **THEN** the value is observable by the surrounding HTTP framework's exception handler

### Requirement: JSON Envelope Shape
`Devkit\Core\Response\JsonEnvelope` SHALL produce a body conforming to `{"code": <int>, "message": <string>, "data": <mixed>}` regardless of input shape, returned as a PSR-7 `ResponseInterface` constructed via an injected `Psr\Http\Message\ResponseFactoryInterface`.

#### Scenario: Success envelope
- **WHEN** code calls `JsonEnvelope::success(['id' => 1])`
- **THEN** the resulting PSR-7 response body decodes to `{"code": 0, "message": "OK", "data": {"id": 1}}` and the status code is 200

#### Scenario: Failure envelope with custom code
- **WHEN** code calls `JsonEnvelope::failure('invalid input', 422, ['field' => 'email'])`
- **THEN** the body decodes to `{"code": 422, "message": "invalid input", "data": {"field": "email"}}` and the status code is 422

### Requirement: Web Envelope for Non-JSON
`Devkit\Core\Response\WebEnvelope` SHALL provide redirect and view-name helpers returning PSR-7 responses, distinct from JSON envelope semantics.

#### Scenario: Redirect response
- **WHEN** code calls `WebEnvelope::redirect('/home', 302)`
- **THEN** the response has status 302 and `Location: /home` header

### Requirement: Laravel Bridge Adapter
A Laravel adapter SHALL convert `Devkit\Core\Response\JsonEnvelope` output to `Illuminate\Http\JsonResponse` for return from Laravel controllers, preserving body and headers.

#### Scenario: Controller returns envelope
- **WHEN** a Laravel controller returns the adapter-converted response from `JsonEnvelope::success(...)`
- **THEN** the HTTP response delivered to the client retains both the envelope body and headers

### Requirement: Framework Independence
The core foundation classes (`AbstractHttpException`, `JsonEnvelope`, `WebEnvelope`) SHALL NOT depend on `Illuminate\*`; they MAY depend on `symfony/http-kernel` (for the exception interface) and PSR-7/17 contracts.

#### Scenario: Pure-PHP usage
- **WHEN** the classes are used in a Slim or pure-PHP project
- **THEN** they load and operate without booting Laravel
