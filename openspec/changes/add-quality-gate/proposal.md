## Why

All 14 declared capabilities are implemented, tested (221 methods, dual suites), and
archived. The library is feature-complete but not yet *ship-ready*: there is no static
analysis, no coverage reporting, and `composer.json` `require-dev` lies about its test
tooling — it pins `phpunit/phpunit ^8.5 || ^9.6` while the CI matrix actually runs
`^10.5 || ^11.0` on the PHP 8.2 + Laravel 11 cell, and Mockery `^1.3 || ^1.5` cannot
resolve against PHPUnit 11. Before tagging v1.0.0 (a SemVer commitment) the project needs a
deterministic quality gate so the first stable commit is the first commit through it.

This change adds a single-cell static-analysis + coverage gate that respects the
multi-major polyfill shape: PHPStan analyses the framework-agnostic core against the PHP
7.3 floor, with the version-specific Monolog/Flysystem branches frozen in a baseline
(their correctness is proven by the matrix, not by single-version analysis).

## What Changes

### MODIFIED Capability: `devkit-bootstrap`

Three requirements are added to the bootstrap capability:

1. **Static Analysis Gate** — the package SHALL ship `phpstan.neon.dist` (level 5,
   `phpVersion: 70300`) analysing `src/` excluding `src/Laravel` (deferred to larastan) and
   `src/Logging/GoogleChat/Internal` (version-pinned Monolog concretes whose cross-major
   `write()` signature is a non-ignorable LSP artifact). Current residual errors are frozen
   in `phpstan-baseline.neon`; new code must analyse clean. A CI job runs PHPStan on the
   PHP 8.2 cell using a PHAR tool (PHPStan 2.x cannot install on the 7.3 cells).

2. **Coverage Reporting** — the CI workflow SHALL produce line coverage on one cell
   (PHP 8.2 / Laravel 11 / PHPUnit 11, pcov driver), upload a Clover artifact, and push to
   Codecov (non-blocking). `codecov.yml` configures a regression gate (`project: auto`,
   threshold 1%; `patch: 80%`) rather than a high absolute floor, because the single-cell
   number understates the Monolog 2 / Flysystem 1 branches that only run on older cells.

3. **Dev-tooling Constraint Parity** — `require-dev` SHALL declare PHPUnit and Mockery
   ranges that cover every cell the CI matrix exercises (`phpunit ^8.5 || ^9.6 || ^10.5 ||
   ^11.0`, `mockery ^1.3 || ^1.5 || ^1.6`).

The single `phpunit.xml` is renamed to `phpunit.xml.dist` (9.x schema — the runnable common
denominator: native on 8.5/9.6, deprecated-but-functional on 10/11, verified empirically);
`phpunit.xml` is gitignored as a local override.

Additionally, the **CI Matrix** requirement is MODIFIED to close a pre-existing gap: the
matrix topped out at PHP 8.2 while `php: ^8.0` admits PHP 8.3 and 8.4. Six cells are added
(7.3×L8, 8.0×L6, 8.0×L7, 8.3×L10, 8.3×L11, 8.4×L11) for a full 19-cell matrix, so the
supported-runtime claim is actually exercised before the v1.0.0 SemVer tag.

### Out of Scope (Deferred)

- **larastan** for `src/Laravel` analysis — follow-up change.
- **Mutation testing (Infection)** — low signal against a `class_alias` dispatcher at v1;
  candidate for v1.1.
- **Raising PHPStan above level 5** — ratchet in a later change once the baseline shrinks.

## Capabilities

### Modified Capabilities

- `devkit-bootstrap` — see `specs/devkit-bootstrap/spec.md` delta.

### New Capabilities

None.

## Impact

- **Config/CI**: `phpstan.neon.dist` (new), `phpstan-baseline.neon` (new, 13 frozen
  errors), `codecov.yml` (new), `phpunit.xml` → `phpunit.xml.dist` (renamed),
  `phpunit.coverage.xml.dist` (new — PHPUnit 11 `<source>`-schema config used only by the
  coverage run), `composer.json` (dev constraints widened + `stan` scripts), `.gitignore`
  (`phpunit.xml`), `.github/workflows/tests.yml` (new `quality` job + tag-gated `release`).
- **Source**: 0 files modified — the gate observes, it does not change behaviour.
- **Risk**: the polyfill `Internal` concretes are excluded from analysis; their per-major
  correctness remains covered by the CI matrix tests (see the `add-coverage-gaps` work).
