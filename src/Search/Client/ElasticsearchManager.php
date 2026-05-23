<?php

namespace Devkit\Search\Client;

use Closure;
use Devkit\Search\Client\Exception\ConnectionNotRegisteredException;

/**
 * Elasticsearch connection registry. Resolves named `Elasticsearch\Client`
 * instances by name, caches them within the manager's lifetime. Mirrors
 * the registry pattern used by SmsManager — pure PHP, no Laravel
 * container dependency, so non-Laravel consumers can wire it directly.
 *
 * The Laravel bridge in Wave 5 registers this as a singleton and
 * pre-loads factories from `config('devkit.modules.search.connections')`.
 */
class ElasticsearchManager
{
    /** @var string */
    protected $defaultConnection;

    /**
     * @var array<string, Closure>
     */
    protected $factories = array();

    /**
     * @var array<string, \Elasticsearch\Client>
     */
    protected $resolved = array();

    /**
     * @param  string  $defaultConnection  Name returned by connection() when no name is passed.
     * @param  array<string, Closure>  $factories  Initial name → factory closure map.
     */
    public function __construct($defaultConnection = 'default', array $factories = array())
    {
        $this->defaultConnection = (string) $defaultConnection;

        foreach ($factories as $name => $factory) {
            $this->extend((string) $name, $factory);
        }
    }

    /**
     * Register or replace a factory. The factory is called the first
     * time `connection($name)` resolves the name; subsequent calls
     * return the cached instance until `forget()` drops it.
     *
     * @param  string  $name
     * @param  Closure  $factory  Returns an `\Elasticsearch\Client`.
     * @return $this
     */
    public function extend($name, Closure $factory)
    {
        $this->factories[$name] = $factory;
        unset($this->resolved[$name]);

        return $this;
    }

    /**
     * Resolve a client by connection name; caches per name.
     *
     * @param  string|null  $name  Null/empty → default connection.
     * @return \Elasticsearch\Client
     *
     * @throws ConnectionNotRegisteredException  When the name has no factory.
     */
    public function connection($name = null)
    {
        $name = $name === null || $name === '' ? $this->defaultConnection : (string) $name;

        if (!isset($this->resolved[$name])) {
            if (!isset($this->factories[$name])) {
                throw new ConnectionNotRegisteredException(
                    'Elasticsearch connection [' . $name . '] is not registered. Use extend() to add it.'
                );
            }
            $this->resolved[$name] = call_user_func($this->factories[$name]);
        }

        return $this->resolved[$name];
    }

    /**
     * Drop one or all cached client instances so the next connection()
     * call rebuilds them via the factory. Useful in tests that need a
     * fresh client between cases.
     *
     * @param  string|null  $name  Null forgets every resolved client.
     * @return $this
     */
    public function forget($name = null)
    {
        if ($name === null) {
            $this->resolved = array();
        } else {
            unset($this->resolved[$name]);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setDefaultConnection($name)
    {
        $this->defaultConnection = (string) $name;

        return $this;
    }

    /**
     * @return array<string>  All registered connection names.
     */
    public function getConnectionNames()
    {
        return array_keys($this->factories);
    }
}
