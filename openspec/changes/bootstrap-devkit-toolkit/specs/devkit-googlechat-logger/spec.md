## ADDED Requirements

### Requirement: Dual Monolog 2.x / 3.x Handler Support
`Devkit\Logging\GoogleChat\GoogleChatLogHandler` SHALL be installable and loadable on both Monolog 2.9 and Monolog 3.x to match the package's dependency range `monolog/monolog ^2.9 || ^3.0`. The implementation SHALL detect the installed Monolog major at autoload time (e.g. via `class_exists('Monolog\\LogRecord')`) and dispatch to a version-specific concrete class extending `Monolog\Handler\AbstractProcessingHandler` with the correct `write()` signature:

- Under Monolog 2.9: `protected function write(array $record): void` — `Monolog\LogRecord` MUST NOT be imported (it does not exist).
- Under Monolog 3.x: `protected function write(\Monolog\LogRecord $record): void` — the record is treated as the immutable `LogRecord` value object.

Both concrete implementations SHALL share formatting/dispatch logic via a common trait or helper class so colour-coding, mention maps, and webhook semantics stay identical.

#### Scenario: Loads on PHP 7.3 + Monolog 2.9
- **WHEN** the package is installed on PHP 7.3 + Monolog 2.9
- **THEN** the handler autoloads without referencing the non-existent `Monolog\LogRecord` class, and Monolog 2.9 dispatching a record invokes `write(array $record)` without TypeError

#### Scenario: Loads on PHP 8.2 + Monolog 3.x
- **WHEN** the package is installed on PHP 8.2 + Monolog 3.x (e.g. via Laravel 10/11 forcing Monolog 3)
- **THEN** the handler autoloads without a Liskov-substitution (LSP) violation against the Monolog 3 abstract base, and Monolog 3 dispatching a record invokes `write(LogRecord $record)` without TypeError

#### Scenario: Fixes original mixed-signature bug
- **WHEN** maintainers grep the v1 GoogleChat handler source for the literal `use Monolog\LogRecord`
- **THEN** that import appears only in the Monolog 3 concrete class, never in the Monolog 2 concrete class

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
