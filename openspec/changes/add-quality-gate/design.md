# Design — add-quality-gate

## D1. PHPStan as a CI PHAR, not a require-dev dependency

PHPStan 2.x requires PHP 7.4+ to *run*. The CI matrix includes PHP 7.3 cells that execute
`composer update`; adding `phpstan/phpstan` to `require-dev` would make those cells fail to
resolve. Installing PHPStan as a `shivammathur/setup-php` PHAR tool in a dedicated `quality`
job (PHP 8.2 only) sidesteps the matrix install entirely. The `composer stan` script still
works locally for anyone with PHPStan on PATH (e.g. the `devkit-php82` image + global
install).

## D2. phpVersion 70300 + baseline, not phpVersion 80200

Two competing goals: (a) flag 7.4+ syntax that would break the 7.3 floor, and (b) avoid
noise from the Monolog 3 / Flysystem 3 branches that legitimately use 8.1+ symbols.

`phpVersion: 70300` satisfies (a) — the primary value, since the dhpk `php-7.4` reviewer is
only a proxy for the real 7.3 cells. The cost is that `Monolog\Level` (a PHP 8.1 enum) and
`Monolog\LogRecord` read as "unknown class". Rather than weaken the gate to 8.2, those
artifacts are frozen in `phpstan-baseline.neon`. The CI `quality` job pins the same cell
(Monolog 3 / Flysystem 3) the baseline was generated against, so the baseline stays
faithful.

## D3. Excluding `src/Logging/GoogleChat/Internal`

`GoogleChatLogHandlerM2::write(array $record)` matches the Monolog 2 parent signature;
`M3::write(LogRecord $record)` matches Monolog 3. Whichever major is installed, the *other*
concrete is a `method.childParameterType` Liskov-substitution (LSP) violation — which PHPStan marks
**non-ignorable** (cannot be suppressed via `ignoreErrors` or baseline; only by path
exclusion). Only one concrete is ever loaded at runtime (the dispatcher picks by
`class_exists('Monolog\\LogRecord')`). Their per-major correctness is asserted by the CI
matrix tests, so excluding the directory from static analysis loses no real safety.

`src/Laravel` is excluded for a softer reason: facade/container/Eloquent magic produces
false positives without larastan. Deferred so the v1 baseline contains zero noise.

## D4. One config for test runs, a second for coverage (verified empirically)

The initial plan assumed PHPUnit 11 hard-fails on the 9.x schema, requiring two config
files for *running tests*. Empirical test (PHPUnit 11.5.55 in the `devkit-php82` image)
disproved that for the test runs: `--list-tests` and a full `--testsuite=` run succeed with
exactly **one** benign schema deprecation. So a single `phpunit.xml.dist` (9.x schema) is the
runnable common denominator for the matrix `test` job across 8.5 → 11.

**Coverage is the exception.** PHPUnit 11 does *not* honour the legacy
`<coverage><include>` element — it reports `No filter is configured, code coverage will not
be processed`, emits no report, and exits non-zero (which would fail the `quality` job and
block the release gate). Coverage source scoping moved to the top-level `<source>` element in
PHPUnit 10+. Therefore the `quality` job's coverage run uses a dedicated
`phpunit.coverage.xml.dist` on the modern schema (`-c phpunit.coverage.xml.dist`), while the
matrix test job keeps the auto-discovered 9.x `phpunit.xml.dist`. This is narrower than the
plan's original two-config idea (the second file is coverage-only, not used for the L11 test
cells). Verified: the modern config yields a correctly-scoped report (119 `src/` files, 0
`vendor/`, `src/Laravel/Facades` excluded; ~66.8% line coverage).

## D5. Regression gate, not absolute floor

Coverage runs on one cell, so the Monolog 2 / Flysystem 1 / PHPUnit-8.5 branches are absent
from the number. A high absolute floor would either block the tag or force misleading
"coverage theatre". `codecov.yml` instead guards against regression (`project: auto`,
threshold 1%) and holds new code to a patch target (80%). Absolute floor ratchets later.
