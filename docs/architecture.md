# Devkit Architecture

## Layers

```text
Consumer Application
        |
Devkit\Laravel\*      Laravel adapters, service providers, facades, commands
        |
Devkit core modules   Core, Http, Storage, Search, Messaging, Logging, Ui
        |
PSR contracts         PSR-3, PSR-7, PSR-15/16/17/18 where applicable
```

Core modules avoid `Illuminate\*` imports. Laravel code lives directly under `Devkit\Laravel\*`; there is no `Bridge` namespace because the Laravel sub-namespace is the primary Laravel implementation.

## Module Dependencies

```text
bootstrap
enum
http-foundation
http-gateway -> logging contracts
asset-versioning -> PSR-16
file-uploader -> Flysystem adapter
elasticsearch -> elasticsearch-php, optional AWS signing
sms-dispatch -> http-gateway for HTTP drivers
googlechat-logger -> PSR-18, Monolog
blade-helpers -> butschster/meta-tags
eloquent-helpers -> audit-logging contracts
audit-logging -> logging contract, search index target
sqs-fifo-queue -> Laravel queue, AWS SDK
laravel-integration -> all Laravel module providers
```

## Service Provider Order

The root provider registers module providers in deterministic order:

```text
Logging -> Http -> Storage -> Search -> Database -> Messaging -> Ui -> Queue -> Commands
```

Pure core classes are available through Composer autoload and do not require a core service provider.
