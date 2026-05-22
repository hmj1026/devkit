## ADDED Requirements

### Requirement: Composer Package Identity
The package SHALL be named `hmj1026/devkit` with PSR-4 autoload mapping `Devkit\` → `src/`, MIT license, and PHP version constraint `^7.2.5 || ^8.0`. This wide PHP range pairs with a wide Laravel range below so consumers on either legacy PHP 7.2.5 + Laravel 6/7/8 or modern PHP 8.x + Laravel 9/10/11 can install.

#### Scenario: Composer install succeeds on PHP 7.2.5 + Laravel 6
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 7.2.5 and Laravel 6
- **THEN** the install completes without platform-requirement errors and the `Devkit\` namespace is autoloaded

#### Scenario: Composer install succeeds on PHP 8.2 + Laravel 11
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 8.2 and Laravel 11
- **THEN** the install completes without platform-requirement errors

#### Scenario: Composer install rejects PHP 7.1
- **WHEN** running `composer require hmj1026/devkit` in a project with PHP 7.1.x
- **THEN** Composer reports an unsatisfied platform requirement

### Requirement: Hard Dependency Pinning
The package SHALL pin runtime dependencies to versions compatible with both ends of the PHP range (7.2.5 → 8.2):
- `monolog/monolog ^2.9` (3.x requires PHP 8.1; deferred to v2)
- `guzzlehttp/guzzle ^7.0`
- `league/flysystem ^2.0 || ^3.0` (v2 supports PHP 7.2, v3 requires 7.4+; consumer-side composer resolves the right one)
- `elasticsearch/elasticsearch ^7.17` (8.x requires PHP 7.4 and Elastic License v2)
- `butschster/meta-tags ^2.1`
- `jenssegers/agent ^2.0`
- PSR-3/7/16/17/18 contract packages
- Laravel range: `illuminate/support ^6.0 || ^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0` (composer resolves per-PHP)

NOTE: v2 of devkit (when PHP floor is bumped to ^8.1) will swap in `monolog/monolog ^3.0`, `league/flysystem ^3.0` only, and consider adding `spatie/laravel-activitylog ^4.0` as the audit-logging engine.

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
- PHP 7.2.5 / 7.3 × Laravel 6 / 7
- PHP 7.4 × Laravel 6 / 7 / 8
- PHP 8.0 × Laravel 8 / 9
- PHP 8.1 × Laravel 8 / 9 / 10
- PHP 8.2 × Laravel 9 / 10 / 11

Incompatible PHP × Laravel cells (e.g. PHP 7.2 + Laravel 9) are explicitly excluded.

#### Scenario: CI runs on push
- **WHEN** a commit is pushed to the main branch
- **THEN** the CI workflow runs every valid matrix cell and reports green for all

### Requirement: Laravel Auto-Discovery
The package SHALL declare `Devkit\Laravel\DevkitServiceProvider` and selected facades via `extra.laravel.providers` and `extra.laravel.aliases` in `composer.json`.

#### Scenario: Auto-discovery on Laravel install
- **WHEN** Laravel boots after `composer require hmj1026/devkit`
- **THEN** `DevkitServiceProvider` is auto-registered without manual `config/app.php` edits
