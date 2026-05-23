<?php

namespace Devkit\Logging\GoogleChat\Internal;

use Devkit\Logging\GoogleChat\Concerns\HandlesGoogleChatCard;
use Devkit\Logging\GoogleChat\Exception\GoogleChatLogWebHookUrlNotSettingException;
use GuzzleHttp\Client as GuzzleClient;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;

/**
 * Monolog 3.x concrete handler. Selected by the dispatcher stub
 * (GoogleChatLogHandler.php) when `class_exists('Monolog\LogRecord')`
 * returns true. References Monolog\LogRecord + Monolog\Level which
 * only exist in Monolog 3.x (PHP 8.1+); this file is therefore only
 * SAFE to parse on PHP 8.1+ AND only LOADED by the dispatcher when
 * Monolog 3 is installed. On Monolog 2 / PHP 7.x the dispatcher
 * routes to GoogleChatLogHandlerM2 instead and this file is never
 * required.
 *
 * The `protected function write(LogRecord $record): void` signature
 * matches Monolog 3.x's AbstractProcessingHandler::write declaration;
 * deviation would trigger an LSP fatal at autoload.
 */
class GoogleChatLogHandlerM3 extends AbstractProcessingHandler
{
    use HandlesGoogleChatCard;

    /**
     * @throws GoogleChatLogWebHookUrlNotSettingException  When $webhookUrl is empty.
     */
    public function __construct(
        string $webhookUrl,
        string $appName = '',
        string $env = '',
        array $mentionMap = [],
        ?Psr18ClientInterface $httpClient = null,
        $level = null,
        bool $bubble = true
    ) {
        if ($webhookUrl === '') {
            throw new GoogleChatLogWebHookUrlNotSettingException(
                'GoogleChat webhook URL is required but was not provided to GoogleChatLogHandler.'
            );
        }
        parent::__construct($level === null ? \Monolog\Level::Debug : $level, $bubble);
        $this->initGoogleChatCard(
            $webhookUrl,
            $appName,
            $env,
            $mentionMap,
            $httpClient ?? new GuzzleClient()
        );
    }

    protected function write(LogRecord $record): void
    {
        $payload = $this->buildPayload(
            $record->level->getName(),
            $record->message,
            $record->context,
            $record->extra,
            $record->datetime
        );
        $this->dispatchWebhook($payload);
    }
}
