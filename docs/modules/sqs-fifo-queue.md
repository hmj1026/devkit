# Devkit SQS FIFO Queue

## Use Case

Use the Laravel-only `sqs-fifo` queue connector when jobs need SQS FIFO message group IDs and deduplication IDs.

## Laravel Configuration

```php
'modules' => array(
    'queue' => array('enabled' => true),
),
```

Example queue connection:

```php
'connections' => array(
    'orders-fifo' => array(
        'driver' => 'sqs-fifo',
        'queue' => 'orders.fifo',
        'group' => 'orders',
        'deduplicator' => Devkit\Laravel\Queue\SqsFifo\Deduplicator\Content::class,
        'allow_delay' => false,
    ),
),
```

## Pure PHP Usage

This module depends on `Illuminate\Queue\SqsQueue` and is intentionally Laravel-only. Non-Laravel applications should use the AWS SDK directly or their queue framework's SQS FIFO integration.
