<?php

namespace Devkit\Logging\GoogleChat\Concerns;

use DateTimeInterface;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;

/**
 * Shared formatting + dispatch logic for the Monolog-2 and Monolog-3
 * concrete handlers. Lives in a trait so the two write(...) implementations
 * (which must differ to satisfy each Monolog major's parent signature) can
 * use identical card-rendering, mention-substitution, and webhook-POST code.
 *
 * Designed to be use'd inside a Monolog\Handler\AbstractProcessingHandler
 * subclass; the subclass owns the constructor and the write() signature.
 */
trait HandlesGoogleChatCard
{
    /**
     * @var string
     */
    protected $webhookUrl;

    /**
     * @var string
     */
    protected $appName;

    /**
     * @var string
     */
    protected $env;

    /**
     * Map of lower-cased level name to mention literal (e.g. 'users/12345'
     * or '@all'). Levels absent from the map produce no mention.
     *
     * @var array<string, string>
     */
    protected $mentionMap = array();

    /**
     * @var Psr18ClientInterface
     */
    protected $httpClient;

    /**
     * Severity → hex colour band per spec
     * (devkit-googlechat-logger / Color-Coded Severity Cards).
     *
     * @var array<string, string>
     */
    protected static $colorMap = array(
        'emergency' => '#d32f2f',
        'alert'     => '#d32f2f',
        'critical'  => '#d32f2f',
        'error'     => '#d32f2f',
        'warning'   => '#fbc02d',
        'notice'    => '#388e3c',
        'info'      => '#388e3c',
        'debug'     => '#212121',
    );

    /**
     * Common construction. Subclass __construct calls this after invoking
     * parent::__construct() for the Monolog AbstractProcessingHandler.
     *
     * @param  string  $webhookUrl
     * @param  string  $appName
     * @param  string  $env
     * @param  array  $mentionMap
     * @param  Psr18ClientInterface  $httpClient
     * @return void
     */
    protected function initGoogleChatCard($webhookUrl, $appName, $env, array $mentionMap, Psr18ClientInterface $httpClient)
    {
        $this->webhookUrl = $webhookUrl;
        $this->appName = $appName;
        $this->env = $env;
        // Normalise level keys to lower-case so callers can pass any casing.
        $normalised = array();
        foreach ($mentionMap as $level => $mention) {
            $normalised[strtolower((string) $level)] = (string) $mention;
        }
        $this->mentionMap = $normalised;
        $this->httpClient = $httpClient;
    }

    /**
     * @param  string  $levelName  e.g. 'ERROR', 'warning', 'INFO'.
     * @return string  Hex colour (#RRGGBB).
     */
    protected function colorFor($levelName)
    {
        $key = strtolower((string) $levelName);
        if (isset(self::$colorMap[$key])) {
            return self::$colorMap[$key];
        }
        // Unknown levels fall back to black (matches debug treatment).
        return '#212121';
    }

    /**
     * Translate a raw mention literal into Google Chat's wire format.
     *   'users/12345' -> '<users/12345>'
     *   '@all'        -> '<users/all>'
     * Other values pass through wrapped in angle brackets unchanged.
     *
     * @param  string  $levelName
     * @return string  Empty string when no mention is configured for this level.
     */
    protected function mentionFor($levelName)
    {
        $key = strtolower((string) $levelName);
        if (!isset($this->mentionMap[$key])) {
            return '';
        }
        $raw = $this->mentionMap[$key];
        if ($raw === '@all') {
            return '<users/all>';
        }
        return '<' . ltrim($raw, '<') . (substr($raw, -1) === '>' ? '' : '>');
    }

    /**
     * Build the Google Chat cardsV2 payload for a log record.
     *
     * @param  string  $levelName
     * @param  string  $message
     * @param  array  $context
     * @param  array  $extra
     * @param  DateTimeInterface|null  $datetime
     * @return array
     */
    protected function buildPayload($levelName, $message, array $context, array $extra, $datetime)
    {
        $level = strtoupper((string) $levelName);
        $color = $this->colorFor($levelName);
        $mention = $this->mentionFor($levelName);
        $stamp = ($datetime instanceof DateTimeInterface)
            ? $datetime->format('Y-m-d H:i:s')
            : '';

        $headline = trim($mention . ' *' . $level . '* @ ' . $this->appName . '@' . $this->env);

        // Inline coloured text via Google Chat's <font color=""> markup;
        // the header carries the bare metadata (level / app / env / time).
        $coloredBody = '<font color="' . $color . '">' . $this->escapeText($message) . '</font>';

        return array(
            'text' => $headline . "\n" . $message,
            'cardsV2' => array(array(
                'cardId' => 'devkit-googlechat-log',
                'card' => array(
                    'header' => array(
                        'title' => $level . ' — ' . $this->appName . '@' . $this->env,
                        'subtitle' => $stamp,
                    ),
                    'sections' => array(array(
                        'widgets' => array(array(
                            'textParagraph' => array('text' => $coloredBody),
                        )),
                    )),
                ),
            )),
            'context' => $context,
            'extra' => $extra,
        );
    }

    /**
     * POST the payload as JSON to the configured webhook URL.
     *
     * @param  array  $payload
     * @return void
     */
    protected function dispatchWebhook(array $payload)
    {
        $factory = new HttpFactory();
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stream = $factory->createStream($body === false ? '{}' : $body);
        $request = $factory->createRequest('POST', $this->webhookUrl)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($stream);

        $this->httpClient->sendRequest($request);
    }

    /**
     * Strip control characters that Google Chat's card renderer rejects.
     *
     * @param  string  $text
     * @return string
     */
    protected function escapeText($text)
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $text);
    }
}
