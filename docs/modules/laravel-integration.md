# Devkit Laravel Integration

## Use Case

Use the root service provider to register Devkit modules through config flags, publish config and stubs, and opt in to exactly five generators.

## Laravel Configuration

Publish package assets:

```bash
php artisan vendor:publish --tag=devkit-config
php artisan vendor:publish --tag=devkit-stubs
php artisan devkit:install
```

Enable generators explicitly:

```php
'commands' => array(
    'generators' => array('enabled' => true),
),
```

Available generator commands:

```text
devkit:make:service
devkit:make:action
devkit:make:enum
devkit:make:audit-log-target
devkit:make:http-client
```

## Pure PHP Usage

The root service provider, facades, publish tags, and Artisan commands are Laravel-only. Pure PHP consumers can use the core modules directly through Composer autoload.
