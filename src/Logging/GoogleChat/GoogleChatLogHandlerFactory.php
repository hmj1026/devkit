<?php

namespace Devkit\Logging\GoogleChat;

use Psr\Http\Client\ClientInterface as Psr18ClientInterface;

/**
 * Convenience factory for assembling a fully-configured
 * GoogleChatLogHandler from a single config array. The dispatcher stub
 * `Devkit\Logging\GoogleChat\GoogleChatLogHandler` resolves at autoload
 * to either the Monolog-2 or Monolog-3 concrete; this factory works
 * against the alias and so is itself version-agnostic.
 *
 * The Laravel custom-log-driver adapter uses this to construct
 * the handler from `config('logging.channels.googlechat')` without
 * caring about Monolog majors.
 */
class GoogleChatLogHandlerFactory
{
    /**
     * Build a handler from a config array. Recognised keys:
     *   - url (required)     — webhook URL
     *   - app_name (string)
     *   - env (string)
     *   - mentions (array)   — level => mention literal
     *   - http_client (Psr\Http\Client\ClientInterface)
     *   - level (int|string) — Monolog level threshold; passed straight through.
     *   - bubble (bool)
     *
     * @param  array  $config
     * @return GoogleChatLogHandler
     */
    public static function create(array $config)
    {
        $args = array(
            isset($config['url']) ? $config['url'] : '',
            isset($config['app_name']) ? (string) $config['app_name'] : '',
            isset($config['env']) ? (string) $config['env'] : '',
            isset($config['mentions']) && is_array($config['mentions']) ? $config['mentions'] : array(),
            isset($config['http_client']) && $config['http_client'] instanceof Psr18ClientInterface
                ? $config['http_client']
                : null,
        );

        if (array_key_exists('level', $config)) {
            $args[] = $config['level'];
            $args[] = isset($config['bubble']) ? (bool) $config['bubble'] : true;
        } elseif (array_key_exists('bubble', $config)) {
            // Skip $level (use handler default) — but we can't, since args are positional.
            // If bubble is set without level, callers should also pass level explicitly.
            // For typical config we leave both defaulted.
        }

        $class = GoogleChatLogHandler::class;

        switch (count($args)) {
            case 5:
                return new $class($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 7:
                return new $class($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
            default:
                return new $class($args[0], $args[1], $args[2], $args[3], $args[4]);
        }
    }
}
