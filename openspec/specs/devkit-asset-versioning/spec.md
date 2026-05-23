# devkit-asset-versioning Specification

## Purpose
Cache-busted asset URL generation with PSR-16 backing and pluggable host resolver, plus a Laravel facade/helper bridge.

## Requirements

### Requirement: Cache-Busted URL Generation
`Devkit\Http\Asset\HttpUri::url(string $path): string` SHALL return the input path with a timestamp query parameter appended (e.g. `/logo.png?v=1737686800`).

#### Scenario: Relative path
- **WHEN** code calls `$httpUri->url('/images/logo.png')` and the cached timestamp is `1737686800`
- **THEN** the result is `/images/logo.png?v=1737686800`

#### Scenario: Absolute URL preserved
- **WHEN** code calls `$httpUri->url('https://cdn.example.com/logo.png')`
- **THEN** the timestamp query is appended to that absolute URL with the host preserved

### Requirement: PSR-16 Cache Backing
The timestamp SHALL be persisted in a `Psr\SimpleCache\CacheInterface` implementation injected via constructor, with a configurable TTL.

#### Scenario: Fresh cache populates and reads back
- **WHEN** the cache is empty and `$httpUri->url('/x')` is called twice
- **THEN** the first call writes a timestamp and the second call returns the same cached value

#### Scenario: Clear resets timestamp
- **WHEN** code calls `$httpUri->clear()`
- **THEN** the next call to `url()` writes a new timestamp into cache

### Requirement: Configurable Host Resolver
The class SHALL accept an injectable `HostResolverInterface` for deriving the scheme + host part of generated URLs when no host is present in the input.

#### Scenario: Custom resolver
- **WHEN** a resolver returns `https://cdn.example.com` and code calls `$httpUri->url('/x.png')`
- **THEN** the result is `https://cdn.example.com/x.png?v=<ts>`

### Requirement: Framework Independence
The core `HttpUri` SHALL NOT depend on `Illuminate\Cache\*`; a Laravel bridge wires `Illuminate\Cache\Repository` (which implements PSR-16 since Laravel 5.8) to fulfil the contract.

#### Scenario: Pure PHP usage
- **WHEN** the class is instantiated with a non-Laravel PSR-16 cache
- **THEN** URL versioning works without any Laravel boot

### Requirement: Laravel Facade and Blade Helper
A Laravel bridge SHALL register a `HttpUri` facade and an `http_url($path)` helper function, both returning the same versioned URL.

#### Scenario: Blade template usage
- **WHEN** a Blade template renders `<img src="{{ http_url('/logo.png') }}">`
- **THEN** the rendered HTML contains a versioned URL identical to facade output
