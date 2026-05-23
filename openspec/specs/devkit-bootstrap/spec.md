# devkit-bootstrap Specification

## Purpose
Composer package identity, dependency pinning, dual PHPUnit testsuites, and the GitHub Actions PHP×Laravel test matrix.

## Requirements

### Requirement: Composer Package Identity
The package SHALL be named `hmj1026/devkit` with PSR-4 autoload mapping `Devkit\` → `src/`, MIT license, and PHP version constraint `^7.3 || ^8.0`. This range pairs with a wide Laravel range below so consumers on either legacy PHP 7.3 + Laravel 6/7/8 or modern PHP 8.x + Laravel 9/10/11 can install. PHP 7.2 is excluded because `elasticsearch/elasticsearch ^7.17` requires PHP 7.3+.

#### Scenario: Composer install succeeds on PHP 7.3 + Laravel 6
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 7.3 and Laravel 6
- **THEN** the install completes without platform-requirement errors and the `Devkit\` namespace is autoloaded

#### Scenario: Composer install succeeds on PHP 8.2 + Laravel 11
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 8.2 and Laravel 11
- **THEN** the install completes without platform-requirement errors

#### Scenario: Composer install rejects PHP 7.2
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 7.2.x
- **THEN** Composer reports an unsatisfied platform requirement

### Requirement: Hard Dependency Pinning
The package SHALL pin runtime dependencies to versions compatible with both ends of the PHP range (7.3 → 8.2):
- `monolog/monolog ^2.9 || ^3.0` (Monolog 3 requires PHP 8.1 — Laravel 10+ forces Monolog 3, earlier Laravel keeps Monolog 2.9; the GoogleChat handler implementation adapts per-version)
- `guzzlehttp/guzzle ^7.0`
- `league/flysystem ^1.1 || ^2.0 || ^3.0` (v1 for Laravel 6/7/8 consumers, v2 for PHP 7.3+, v3 for PHP 7.4+ and Laravel 9+; composer resolves per consumer). The file-uploader module abstracts over all three API generations.
- `elasticsearch/elasticsearch ^7.17` (requires PHP 7.3+; 8.x requires PHP 7.4 and Elastic License v2)
- `butschster/meta-tags ^2.1 || ^3.0` (v3 floors at PHP 8.0; Laravel 11 forces v3, earlier Laravel can resolve v2 or v3 — the Meta wrapper adapts per-version)
- `jenssegers/agent ^2.0`
- PSR-3/7/16/17/18 contract packages
- Laravel range: `illuminate/support ^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0` (composer resolves per-PHP)

NOTE: v2 of devkit (when PHP floor is bumped to ^8.1) will drop Monolog 2.9 support, drop `league/flysystem ^1.1 || ^2.0`, and consider adding `spatie/laravel-activitylog ^4.0` as the audit-logging engine.

#### Scenario: Composer resolves without conflicts
- **WHEN** running `composer install` in a PHP 8.0 + Laravel 9 environment
- **THEN** all locked dependencies resolve without version conflicts

### Requirement: Dual Test Suites
The package SHALL ship a PHPUnit configuration with two testsuites: `core` (no Laravel) and `laravel` (Orchestra Testbench), both runnable independently.

#### Scenario: Core suite runs without Laravel
- **WHEN** running `./vendor/bin/phpunit --testsuite=core`
- **THEN** tests pass without requiring any `Illuminate\*` class to be loaded

#### Scenario: Laravel suite uses Orchestra Testbench
- **WHEN** running `./vendor/bin/phpunit --testsuite=laravel`
- **THEN** tests boot a Laravel application via Orchestra Testbench and pass

### Requirement: CI Matrix
The package SHALL include a GitHub Actions workflow exercising the supported PHP × Laravel matrix:
- PHP 7.3 × Laravel 6 / 7
- PHP 7.4 × Laravel 6 / 7 / 8
- PHP 8.0 × Laravel 8 / 9
- PHP 8.1 × Laravel 8 / 9 / 10
- PHP 8.2 × Laravel 9 / 10 / 11

Incompatible cells are explicitly excluded:
- PHP 7.2 × any Laravel — `elasticsearch/elasticsearch ^7.17` floors at PHP 7.3
- PHP 7.3 × Laravel 9+ — Laravel 9 floors at PHP 8.0
- Other combinations Composer's resolver rejects

For Laravel 10+ cells the matrix step pins `monolog/monolog ^3.0`; earlier cells stay on `monolog/monolog ^2.9`. For Laravel 11 cells the matrix step pins `butschster/meta-tags ^3.0`; earlier cells stay on `butschster/meta-tags ^2.1`.

#### Scenario: CI runs on push
- **WHEN** a commit is pushed to the main branch
- **THEN** the CI workflow runs every valid matrix cell and reports green for all

### Requirement: Laravel Auto-Discovery
The package SHALL declare `Devkit\Laravel\DevkitServiceProvider` and selected facades via `extra.laravel.providers` and `extra.laravel.aliases` in `composer.json`.

#### Scenario: Auto-discovery on Laravel install
- **WHEN** Laravel boots after `composer require hmj1026/devkit`
- **THEN** `DevkitServiceProvider` is auto-registered without manual `config/app.php` edits
