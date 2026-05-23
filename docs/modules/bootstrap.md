# Devkit Bootstrap

## Use Case

`hmj1026/devkit` is a library package that provides a framework-agnostic core plus Laravel adapters. It is intended for projects that need common enum, HTTP, storage, logging, search, SMS, UI, audit, and queue tooling without copying internal package code between repositories.

## Laravel Configuration

Install through Composer and let Laravel package discovery load the root provider:

```php
// config/devkit.php
'modules' => array(
    'logging' => array('enabled' => true),
    'http' => array('enabled' => true),
    'storage' => array('enabled' => true),
    'search' => array('enabled' => true),
    'database' => array('enabled' => true),
    'messaging' => array('enabled' => true),
    'ui' => array('enabled' => true),
    'queue' => array('enabled' => true),
    'commands' => array('enabled' => true),
),
```

Run tests separately when isolating framework-independent code:

```bash
./vendor/bin/phpunit --testsuite=core
./vendor/bin/phpunit --testsuite=laravel
```

## Pure PHP Usage

Use Composer autoload and instantiate core classes directly. Core namespaces avoid `Illuminate\*` dependencies, so non-Laravel applications can use `Devkit\Core`, `Devkit\Http`, `Devkit\Storage`, `Devkit\Search`, `Devkit\Messaging`, `Devkit\Logging`, and `Devkit\Ui` classes directly.
