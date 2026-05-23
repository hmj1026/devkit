# devkit-laravel-integration Specification

## Purpose
Single root service provider with conditional module registration, facade index, five opt-in Artisan generators, stub publication, and one-shot install command.

## Requirements

### Requirement: Single Root Service Provider
`Devkit\Laravel\DevkitServiceProvider` SHALL be the only service provider declared in `composer.json` `extra.laravel.providers`, conditionally registering module service providers based on `config('devkit.modules.<name>.enabled')`.

#### Scenario: Disabled module skips registration
- **WHEN** `config('devkit.modules.sms.enabled')` is `false` and Laravel boots
- **THEN** the SMS module service provider is NOT registered and the `Sms` binding is absent from the container

#### Scenario: Enabled modules register
- **WHEN** all module flags are `true` (default)
- **THEN** every module's service provider is registered exactly once

### Requirement: Laravel Sub-Namespace (No "Bridge" Prefix)
All Laravel-specific code SHALL live directly under `Devkit\Laravel\*` (e.g. `Devkit\Laravel\Database\Entity\HasUuid`, `Devkit\Laravel\Queue\SqsFifo\SqsFifoQueue`). The earlier `Devkit\Laravel\Bridge\*` layer name SHALL NOT be used — the Laravel sub-namespace is the primary Laravel implementation, not a bridge to another implementation.

#### Scenario: No Bridge namespace
- **WHEN** a maintainer greps `src/Laravel/` for the substring `Bridge\`
- **THEN** no matches are found

### Requirement: Default Config Publication
The package SHALL publish a `config/devkit.php` containing module on/off flags, disks, SMS driver registry, HTTP gateway retry settings, ES connections, GoogleChat webhook, and command opt-in.

#### Scenario: Publish via artisan
- **WHEN** an operator runs `php artisan vendor:publish --tag=devkit-config`
- **THEN** `config/devkit.php` is copied into the application's `config/` directory

### Requirement: Facade Index
The package SHALL register facades for each user-facing manager: `Trail`, `MetaTags`, `HttpUri`, `Sms`, `FileUploader`, `Elasticsearch`.

#### Scenario: Facade returns manager
- **WHEN** code calls `\Trail::register('admin')`
- **THEN** the call resolves to `Devkit\Ui\Trail\TrailManager::register('admin')`

### Requirement: Exactly Five Artisan Generators (Opt-in)
The package SHALL ship exactly five Artisan generators under the `devkit:make:*` namespace, registered only when `config('devkit.commands.generators.enabled')` is `true` (default `false`):
1. `devkit:make:service` — single-responsibility service class
2. `devkit:make:action` — invokable single-action class
3. `devkit:make:enum` — AbstractEnum subclass (v1) / native enum scaffold (v2)
4. `devkit:make:audit-log-target` — LogTargetContract implementation skeleton
5. `devkit:make:http-client` — Gateway subclass scaffold

The earlier set of 12 generators (which included Repository, RequestContract, Cache, Format, LogEntity scaffolds) SHALL NOT be reproduced — most generators tied to the dropped repository / repository-cache / format / log-entity capabilities are removed.

#### Scenario: Generators hidden by default
- **WHEN** the config flag is false and an operator runs `php artisan list devkit:make`
- **THEN** no generator commands appear

#### Scenario: Service generator creates file
- **WHEN** the config flag is true and an operator runs `php artisan devkit:make:service Account/Register/RegisterAccountService`
- **THEN** a class file is generated at `app/Services/Account/Register/RegisterAccountService.php` using the published stub

#### Scenario: Audit src/ has no dropped generators
- **WHEN** a maintainer greps `src/Laravel/Command/Generators/` for `make:repository` or `make:request-contract`
- **THEN** no such command classes are found

### Requirement: Stub Publication
Generator stubs SHALL be publishable via `vendor:publish --tag=devkit-stubs`, allowing consumers to override the templates.

#### Scenario: Custom stub overrides default
- **WHEN** a published stub `service.stub` is modified and `devkit:make:service` is invoked
- **THEN** the generated file uses the modified template

### Requirement: One-Shot Install Command
`Devkit\Laravel\Command\InstallCommand` (`devkit:install`) SHALL publish config, stubs, and any required migrations in one command, with idempotent behaviour.

#### Scenario: Install command sets up everything
- **WHEN** an operator runs `php artisan devkit:install`
- **THEN** the config file, stubs, and migrations are present in the application without duplicating already-published files

### Requirement: Module Registration Order
The root service provider SHALL register module SPs in a deterministic order: Core → Logging → Http → Storage → Search → Database → Messaging → Ui → Queue → Commands.

#### Scenario: Database SP boots before Messaging
- **WHEN** Laravel boots with all modules enabled
- **THEN** the database module SP registers before the messaging module SP so that any messaging job needing entity helpers can resolve them
