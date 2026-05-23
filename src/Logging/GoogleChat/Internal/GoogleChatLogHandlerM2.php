<?php

namespace Devkit\Logging\GoogleChat\Internal;

use Devkit\Logging\GoogleChat\Concerns\HandlesGoogleChatCard;
use Devkit\Logging\GoogleChat\Exception\GoogleChatLogWebHookUrlNotSettingException;
use GuzzleHttp\Client as GuzzleClient;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;

/**
 * Monolog 2.x concrete handler. Selected by the dispatcher stub
 * (GoogleChatLogHandler.php) when `class_exists('Monolog\LogRecord')`
 * returns false. Never references Monolog\LogRecord (which doesn't
 * exist in Monolog 2), keeping this file parseable on PHP 7.3+.
 *
 * The `protected function write(array $record): void` signature matches
 * Monolog 2.9's AbstractProcessingHandler::write declaration; the `:void`
 * return type is REQUIRED by LSP since Monolog 2.9 declares it on the
 * abstract parent. This is a documented LSP exception to the
 * openspec/config.yaml no-return-types convention.
 */
class GoogleChatLogHandlerM2 extends AbstractProcessingHandler
{
    use HandlesGoogleChatCard;

    /**
     * @param  string  $webhookUrl
     * @param  string  $appName
     * @param  string  $env
     * @param  array  $mentionMap  level → mention literal (e.g. ['error' => 'users/12345']).
     * @param  Psr18ClientInterface|null  $httpClient  Defaults to a fresh Guzzle 7 client.
     * @param  int  $level  Monolog level threshold (default DEBUG so all levels pass).
     * @param  bool  $bubble  Pass-to-next-handler flag.
     *
     * @throws GoogleChatLogWebHookUrlNotSettingException  When $webhookUrl is empty.
     */
    public function __construct(
        $webhookUrl,
        $appName = '',
        $env = '',
        array $mentionMap = array(),
        Psr18ClientInterface $httpClient = null,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        if ($webhookUrl === null || $webhookUrl === '') {
            throw new GoogleChatLogWebHookUrlNotSettingException(
                'GoogleChat webhook URL is required but was not provided to GoogleChatLogHandler.'
            );
        }
        parent::__construct($level, $bubble);
        $this->initGoogleChatCard(
            (string) $webhookUrl,
            (string) $appName,
            (string) $env,
            $mentionMap,
            $httpClient !== null ? $httpClient : new GuzzleClient()
        );
    }

    /**
     * @param  array  $record
     * @return void
     */
    protected function write(array $record): void
    {
        $payload = $this->buildPayload(
            isset($record['level_name']) ? $record['level_name'] : 'INFO',
            isset($record['message']) ? $record['message'] : '',
            isset($record['context']) && is_array($record['context']) ? $record['context'] : array(),
            isset($record['extra']) && is_array($record['extra']) ? $record['extra'] : array(),
            isset($record['datetime']) ? $record['datetime'] : null
        );
        $this->dispatchWebhook($payload);
    }
}
