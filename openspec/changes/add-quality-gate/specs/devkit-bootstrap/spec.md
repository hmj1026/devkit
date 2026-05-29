# devkit-bootstrap Specification (delta: add-quality-gate)

## ADDED Requirements

### Requirement: Static Analysis Gate
The package SHALL ship a PHPStan configuration (`phpstan.neon.dist`) at level 5 with
`phpVersion: 70300`, analysing `src/` while excluding `src/Laravel` (framework magic,
deferred to larastan) and `src/Logging/GoogleChat/Internal` (version-pinned Monolog
concretes whose cross-major `write()` signature is a non-ignorable LSP artifact). Current
residual errors SHALL be frozen in `phpstan-baseline.neon`; analysis of new code SHALL
report no new errors. PHPStan SHALL run in CI on the PHP 8.2 cell via a PHAR tool (PHPStan
2.x cannot install on the PHP 7.3 matrix cells).

#### Scenario: PHPStan passes on the committed baseline
- **WHEN** running `composer stan` on the PHP 8.2 / Laravel 11 cell
- **THEN** PHPStan reports `[OK] No errors` because residual cross-version artifacts are
  absorbed by `phpstan-baseline.neon`

#### Scenario: New analysis errors fail the gate
- **WHEN** a change introduces a new PHPStan-detectable error in an analysed path
- **THEN** the CI `quality` job fails until the error is fixed or explicitly baselined with
  a documented rationale

### Requirement: Coverage Reporting
The CI workflow SHALL produce line coverage on a single cell (PHP 8.2 / Laravel 11 /
PHPUnit 11, pcov driver) using a dedicated PHPUnit 11 (`<source>`-schema)
`phpunit.coverage.xml.dist` (the primary 9.x `phpunit.xml.dist` `<coverage><include>` element
is ignored by PHPUnit 11, which would otherwise process no coverage), upload a Clover
artifact, and push the report to Codecov (non-blocking on upload failure). A `codecov.yml`
SHALL configure a regression gate (`project: auto`, threshold 1%; `patch: 80%`) rather than a
high absolute floor, because the single-cell number excludes the Monolog 2 / Flysystem 1
polyfill branches that only execute on older matrix cells.

#### Scenario: Coverage report is produced and uploaded
- **WHEN** the `quality` job runs on push
- **THEN** a `coverage.xml` Clover report is generated and uploaded as an artifact, and a
  Codecov upload is attempted

### Requirement: Dev-tooling Constraint Parity
The `require-dev` constraints SHALL cover every tooling version the CI matrix exercises:
`phpunit/phpunit ^8.5 || ^9.6 || ^10.5 || ^11.0` and `mockery/mockery ^1.3 || ^1.5 ||
^1.6`. The PHPUnit configuration SHALL be distributed as `phpunit.xml.dist` (9.x schema —
the runnable common denominator across PHPUnit 8.5 → 11), with `phpunit.xml` gitignored as a
local override.

#### Scenario: Root composer install resolves the PHPUnit 11 cell
- **WHEN** installing dev dependencies on PHP 8.2 with `phpunit/phpunit ^11.0`
- **THEN** Composer resolves without conflict (Mockery `^1.6` satisfies the PHPUnit 11
  requirement)

#### Scenario: One config runs across the PHPUnit range
- **WHEN** running `vendor/bin/phpunit` with the committed `phpunit.xml.dist` on any matrix
  cell (PHPUnit 8.5 through 11)
- **THEN** the suite executes (PHPUnit 10/11 emit a benign schema deprecation but run)

## MODIFIED Requirements

### Requirement: CI Matrix
The package SHALL include a GitHub Actions workflow exercising **every** valid PHP × Laravel
combination admitted by the `php: ^7.3 || ^8.0` runtime constraint (which includes PHP 8.3
and 8.4), so the supported-runtime claim is honoured for a v1.0.0 SemVer tag:
- PHP 7.3 × Laravel 6 / 7 / 8
- PHP 7.4 × Laravel 6 / 7 / 8
- PHP 8.0 × Laravel 6 / 7 / 8 / 9
- PHP 8.1 × Laravel 8 / 9 / 10
- PHP 8.2 × Laravel 9 / 10 / 11
- PHP 8.3 × Laravel 10 / 11
- PHP 8.4 × Laravel 11

Incompatible cells are explicitly excluded:
- PHP 7.2 × any Laravel — `elasticsearch/elasticsearch ^7.17` floors at PHP 7.3
- PHP 7.3 × Laravel 9+ — Laravel 9 floors at PHP 8.0
- PHP 8.1+ × Laravel 6 / 7 — Laravel 6/7 top out at PHP 8.0
- PHP 8.3+ × Laravel 9 — Laravel 9 tops out at PHP 8.2
- Other combinations Composer's resolver rejects

For Laravel 10+ cells the matrix step pins `monolog/monolog ^3.0`; earlier cells stay on
`monolog/monolog ^2.9`. For Laravel 11 cells the matrix step pins `butschster/meta-tags
^3.0`; earlier cells stay on `butschster/meta-tags ^2.1`. PHP 8.4 cells pin `phpunit ^11.0`
(PHPUnit 11 is the first major to support PHP 8.4).

#### Scenario: CI runs on push
- **WHEN** a commit is pushed to the main branch
- **THEN** the CI workflow runs every valid matrix cell (19 in total) and reports green for
  all, plus the `quality` job

#### Scenario: PHP 8.4 cell is exercised
- **WHEN** the matrix runs the PHP 8.4 × Laravel 11 cell
- **THEN** the suite passes under PHP 8.4 (where implicit-nullable parameters are a hard
  error), proving the claimed PHP 8.x support extends to the latest minor
