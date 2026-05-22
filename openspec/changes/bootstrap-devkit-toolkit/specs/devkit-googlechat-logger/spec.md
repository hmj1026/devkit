## ADDED Requirements

### Requirement: Monolog 2.x Handler with Correct Signature
`Devkit\Logging\GoogleChat\GoogleChatLogHandler` SHALL extend `Monolog\Handler\AbstractProcessingHandler` and implement `protected function write(array $record): void`, without importing `Monolog\LogRecord` (which is Monolog 3+).

#### Scenario: Signature compatible with Monolog 2.9
- **WHEN** Monolog 2.9 dispatches a log record to the handler
- **THEN** the handler's `write(array $record)` is invoked without TypeError

#### Scenario: Fixes original mixed-signature bug
- **WHEN** the package is installed on PHP 7.2 + Monolog 2.9
- **THEN** the handler loads without referencing the non-existent `Monolog\LogRecord` class

### Requirement: Webhook Dispatch
On `write()`, the handler SHALL POST the formatted record to the configured Google Chat webhook URL via the injected PSR-18 HTTP client (Guzzle 7 by default).

#### Scenario: Successful dispatch
- **WHEN** the handler receives a record with level `error` and message `"db connection lost"`
- **THEN** an HTTP POST is issued to the webhook URL with JSON body containing the formatted message

#### Scenario: Webhook URL missing
- **WHEN** the handler is instantiated without a webhook URL
- **THEN** instantiation raises `Devkit\Logging\GoogleChat\Exception\GoogleChatLogWebHookUrlNotSettingException`

### Requirement: Color-Coded Severity Cards
The handler SHALL render log records as Google Chat cards with a color band keyed by severity: error/critical/alert/emergency → red, warning → yellow, info/notice → green, debug → black.

#### Scenario: Error level colors red
- **WHEN** a record with level `ERROR` is dispatched
- **THEN** the webhook payload contains a card with red color (or the documented HEX equivalent)

### Requirement: Per-Level User Mentions
The handler SHALL support per-level mention maps so that errors mention specific users while debug logs mention no one.

#### Scenario: Mention on error
- **WHEN** the mention map declares `error: 'users/12345'` and a record with level `ERROR` is dispatched
- **THEN** the webhook payload contains a mention element for user `12345`

#### Scenario: At-all broadcast
- **WHEN** the mention map declares `critical: '@all'` and a critical record is dispatched
- **THEN** the webhook payload contains an `@all` mention element

### Requirement: Constructor-Based Configuration
The handler SHALL accept webhook URL, app name, env, and mention map via constructor; it MUST NOT call `config()` or `app()` at construction time.

#### Scenario: Pure PHP usage
- **WHEN** the handler is instantiated in a non-Laravel script with constructor arguments only
- **THEN** logs flow to the webhook without any framework boot

### Requirement: Laravel Bridge Custom Log Driver
A Laravel bridge SHALL register the handler via `Log::extend('googlechat', ...)`, reading `config('logging.channels.googlechat')` and wiring the handler.

#### Scenario: Custom channel usage
- **WHEN** Laravel logging declares `'googlechat'` channel with `driver: 'custom'` and the bridge factory
- **THEN** `Log::channel('googlechat')->error('boom')` dispatches a webhook
