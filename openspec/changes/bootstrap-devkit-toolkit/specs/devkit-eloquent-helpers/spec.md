## ADDED Requirements

### Requirement: Three Core Entity Traits (UUID / Status / AuditLog)
The Laravel sub-namespace SHALL ship exactly three high-value Eloquent traits, plus their corresponding contracts in core: `HasUuid`, `HasStatus`, `HasAuditLog`.

#### Scenario: HasUuid auto-generates on create
- **WHEN** a new model with `HasUuid` is saved without a `uuid` attribute
- **THEN** the persisted row has a valid UUID v4 in the `uuid` column

#### Scenario: HasUuid lookup helper
- **WHEN** code calls `Model::findByUuid('abc-123')` matching an existing row
- **THEN** the returned instance is the matching model

#### Scenario: HasStatus activate/deactivate
- **WHEN** code calls `$model->activate()` on an instance with `status = 0`
- **THEN** after save, the row's `status` is the configured "active" value (default `1`) and `$model->isActive()` returns `true`

#### Scenario: HasAuditLog delegates to logger
- **WHEN** a model with `HasAuditLog` is updated
- **THEN** the configured audit logger receives the diff exactly once via `LogTargetContract` (see `devkit-audit-logging`)

### Requirement: No Repository Pattern
This package SHALL NOT ship an `AbstractRepository` or `EloquentRepository` class. Consumers SHALL use Eloquent directly (with Scopes, Actions, or Service classes). The package only ships an optional `Criteria` helper for complex query reuse.

#### Scenario: Audit src/ has no Repository class
- **WHEN** a maintainer greps `src/` for `class.*Repository`
- **THEN** no Repository abstract class is found

### Requirement: Optional Criteria Query Builder
`Devkit\Laravel\Database\Criteria` SHALL provide a chainable query-composition helper that can be applied to an Eloquent builder, primarily for cases where the same query shape is reused across services.

#### Scenario: Apply criteria to query
- **WHEN** code chains `Criteria::create()->where('status', 'active')->orderBy('created_at', 'desc')->limit(10)` and applies it via `$model->newQuery()->withCriteria($criteria)`
- **THEN** the resulting SQL contains the matching WHERE / ORDER BY / LIMIT clauses

### Requirement: Encrypted and Hashed Casts
The Laravel sub-namespace SHALL ship `EncryptedCast` and `HashedCast` Eloquent casts (implementing `Illuminate\Contracts\Database\Eloquent\CastsAttributes`) for transparent column-level encryption and one-way hashing of sensitive columns.

#### Scenario: Encrypted cast round-trip
- **WHEN** a model declares `'ssn' => EncryptedCast::class` and sets `$model->ssn = 'A123456789'`
- **THEN** the DB row stores an encrypted blob; subsequent `$model->ssn` reads decrypt back to `'A123456789'`

#### Scenario: Hashed cast is one-way
- **WHEN** a model declares `'password' => HashedCast::class` and sets `$model->password = 'plain'`
- **THEN** the DB row stores a bcrypt hash, and reads return the hash (not the plain text)

### Requirement: Contracts in Core
`Devkit\Database\Contract\Entity\HasUuidContract`, `HasStatusContract`, `HasAuditLogContract` SHALL live in the core namespace as pure-PHP interfaces (no Eloquent imports), so application code can type-hint them.

#### Scenario: Contracts load without Eloquent
- **WHEN** the contracts are autoloaded in a non-Eloquent project
- **THEN** no missing-class errors are raised

### Requirement: All Concrete Traits and Casts in Laravel Namespace
All concrete trait and cast classes SHALL live under `Devkit\Laravel\Database\*` (no longer `Devkit\Laravel\Bridge\*`) because their behavior depends on Eloquent's `bootXxxTrait` mechanism and `CastsAttributes` contract.

#### Scenario: Traits available only in Laravel namespace
- **WHEN** code attempts to `use Devkit\Database\HasUuid` (wrong namespace)
- **THEN** autoload fails; the correct namespace is `Devkit\Laravel\Database\Entity\HasUuid`
