# devkit-sqs-fifo-queue Specification

## Purpose
Laravel-only SQS FIFO queue driver with MessageGroupId/Deduplication on send, four built-in deduplicators, and delay/queue-name safeguards.

## Requirements

### Requirement: Laravel-Only Queue Driver
`Devkit\Laravel\Queue\SqsFifo\SqsFifoQueue` SHALL extend `Illuminate\Queue\SqsQueue` and be registered as the `sqs-fifo` queue driver via Laravel's QueueManager.

#### Scenario: Queue config triggers driver
- **WHEN** `config/queue.php` declares a connection with `driver: 'sqs-fifo'`
- **THEN** dispatching jobs through that connection uses `SqsFifoQueue` to send messages

### Requirement: Message Group ID and Deduplication on Send
On `pushRaw()`, the queue SHALL include `MessageGroupId` and `MessageDeduplicationId` in the SQS SendMessage parameters, derived from job payload metadata or default config.

#### Scenario: Default group from config
- **WHEN** the queue connection's `group` config is `'orders'` and code dispatches a job without group override
- **THEN** the outgoing SQS message has `MessageGroupId = 'orders'`

#### Scenario: Per-job group override
- **WHEN** a job uses the `SqsFifoQueueable` trait and calls `->onMessageGroup('tenant-42')` before dispatch
- **THEN** the outgoing SQS message has `MessageGroupId = 'tenant-42'`

### Requirement: Four Built-in Deduplicators
The package SHALL ship `Unique` (UUID4), `Content` (SHA-256 of payload), `Sqs` (return false — defer to AWS Content-Based Deduplication), and `Callback` (closure-based) deduplicators, all implementing `Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator`.

#### Scenario: Unique generates UUID
- **WHEN** the active deduplicator is `Unique` and two identical payloads are dispatched
- **THEN** both messages get distinct `MessageDeduplicationId` UUIDs and are not deduplicated by AWS

#### Scenario: Content collapses duplicates
- **WHEN** the active deduplicator is `Content` and two identical payloads are dispatched within 5 minutes
- **THEN** both messages get the same `MessageDeduplicationId`, and AWS deduplicates the second within the 5-minute window

### Requirement: Disallow Per-Message Delay by Default
The queue SHALL throw `BadMethodCallException` when `later()` is called unless `allow_delay` config is true.

#### Scenario: later() rejects by default
- **WHEN** `allow_delay` is false (default) and code calls `Queue::later(60, $job)`
- **THEN** a `BadMethodCallException` is thrown explaining FIFO queues do not support per-message delay

#### Scenario: allow_delay swallows the delay
- **WHEN** `allow_delay` is true and code calls `Queue::later(60, $job)`
- **THEN** the message is sent without per-message delay and a deprecation-style log warning is emitted

### Requirement: Queue Name Suffix Handling
The connector SHALL validate the queue name ends with `.fifo` and optionally insert a `suffix` segment from config (e.g. `-staging`) before the `.fifo` suffix.

#### Scenario: Queue name reshaping
- **WHEN** config has `queue: 'orders.fifo'` and `suffix: '-staging'`
- **THEN** the resolved queue URL targets `orders-staging.fifo`

#### Scenario: Non-FIFO queue rejected
- **WHEN** config declares `queue: 'orders'` (no `.fifo`)
- **THEN** the connector raises an exception at boot
