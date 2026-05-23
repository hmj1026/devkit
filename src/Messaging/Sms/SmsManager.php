<?php

namespace Devkit\Messaging\Sms;

use Closure;
use Devkit\Messaging\Sms\Contract\SmsDriverContract;
use Devkit\Messaging\Sms\Driver\NullSmsDriver;
use Devkit\Messaging\Sms\Exception\SmsDriverNotRegisteredException;

/**
 * SMS driver registry + dispatcher. Resolves driver instances by name,
 * caches them within the manager's lifetime, and proxies `sendSms()`
 * to the default driver as a convenience for callers that don't care
 * which provider is active.
 *
 * No Illuminate\Foundation\Application dependency — pure PHP so it
 * works in non-Laravel contexts (the Laravel bridge in Wave 5 wires
 * this into the service container as a singleton).
 *
 * The 'null' driver is auto-registered at construction unless the
 * caller supplies their own factory under the same name. This keeps
 * test and local-dev paths zero-config while letting production wire
 * a real provider via extend().
 */
class SmsManager
{
    /**
     * @var string
     */
    protected $defaultDriver;

    /**
     * @var array<string, Closure>
     */
    protected $factories = array();

    /**
     * @var array<string, SmsDriverContract>
     */
    protected $resolved = array();

    /**
     * @param  string  $defaultDriver  Name resolved by driver() when no
     *                                 explicit name is passed.
     * @param  array<string, Closure>  $factories  Initial driver factory map.
     */
    public function __construct($defaultDriver = 'null', array $factories = array())
    {
        $this->defaultDriver = (string) $defaultDriver;

        foreach ($factories as $name => $factory) {
            $this->extend((string) $name, $factory);
        }

        // Auto-register the in-memory null driver when the consumer hasn't
        // overridden it. The factory closure defers class instantiation
        // until driver('null') is actually requested — lazy by design.
        if (!isset($this->factories['null'])) {
            $this->factories['null'] = function () {
                return new NullSmsDriver();
            };
        }
    }

    /**
     * Register or replace a driver factory. The factory is called the
     * first time `driver($name)` resolves the name; subsequent calls
     * return the cached instance until `forget()` drops it.
     *
     * @param  string  $name
     * @param  Closure  $factory  Returns a SmsDriverContract.
     * @return $this
     */
    public function extend($name, Closure $factory)
    {
        $this->factories[$name] = $factory;
        // Drop any previously-resolved instance for this name so the new
        // factory is honoured on the next driver() call.
        unset($this->resolved[$name]);

        return $this;
    }

    /**
     * Resolve a driver instance by name. Caches instances per-name; the
     * second call returns the same object the first call constructed.
     *
     * @param  string|null  $name  Defaults to the configured default driver.
     * @return SmsDriverContract
     *
     * @throws SmsDriverNotRegisteredException  When the name has no factory.
     */
    public function driver($name = null)
    {
        $name = $name === null || $name === '' ? $this->defaultDriver : (string) $name;

        if (!isset($this->resolved[$name])) {
            if (!isset($this->factories[$name])) {
                throw new SmsDriverNotRegisteredException(
                    'SMS driver [' . $name . '] is not registered. Use extend() to add it.'
                );
            }
            $instance = call_user_func($this->factories[$name]);
            $this->resolved[$name] = $instance;
        }

        return $this->resolved[$name];
    }

    /**
     * Dispatch an SMS through the default driver. Convenience shorthand
     * equivalent to `$manager->driver()->sendSms(new SmsMessage($phone, $body, $options))`.
     *
     * @param  string  $cellPhone
     * @param  string  $body
     * @param  array  $options
     * @return \Devkit\Messaging\Sms\Contract\SmsResultContract
     */
    public function sendSms($cellPhone, $body, array $options = array())
    {
        return $this->driver()->sendSms(new SmsMessage($cellPhone, $body, $options));
    }

    /**
     * Drop a resolved instance so the factory runs again on next access.
     * Provided primarily for tests that need a fresh driver between cases.
     *
     * @param  string|null  $name  Null forgets every resolved instance.
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
    public function getDefaultDriverName()
    {
        return $this->defaultDriver;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setDefaultDriverName($name)
    {
        $this->defaultDriver = (string) $name;

        return $this;
    }
}
