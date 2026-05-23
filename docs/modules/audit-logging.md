# Devkit Audit Logging

## Use Case

Use audit logging to capture Eloquent created, updated, deleting, and login events once, then route entries to different targets such as Eloquent tables or Elasticsearch indexes.

## Laravel Configuration

```php
'audit' => array(
    'target' => App\Audit\OrderLogTarget::class,
    'login_target' => App\Audit\LoginLogTarget::class,
),
```

Models can use `Devkit\Laravel\Audit\AbstractEntityChangeLogger` through the `HasAuditLog` trait, then provide or configure a target implementing `LogTargetContract`.

## Pure PHP Usage

Change capture depends on Eloquent model events, so this module is Laravel-only. The target contract is pure PHP:

```php
class ArrayLogTarget implements LogTargetContract
{
    public function save(array $entry)
    {
        // Persist or forward the entry.
    }
}
```
