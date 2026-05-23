# Devkit SMS Dispatch

## Use Case

Use `SmsManager` to register named SMS drivers while keeping concrete provider integrations in the consuming application. Devkit ships a no-op `NullSmsDriver` and an `AbstractHttpSmsDriver` base.

## Laravel Configuration

```php
'modules' => array(
    'messaging' => array('enabled' => true),
),
'sms' => array(
    'default' => 'null',
    'drivers' => array(),
    'queue' => array('connection' => null, 'queue' => null),
),
```

Notifications can route through `Devkit\Laravel\Messaging\Sms\SmsChannel`.

## Pure PHP Usage

```php
$manager = new SmsManager('null');
$result = $manager->sendSms('+886912345678', 'hello');
```

Register a provider driver with `extend()` when integrating a real gateway.
