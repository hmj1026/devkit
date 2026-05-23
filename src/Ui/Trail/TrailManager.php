<?php

namespace Devkit\Ui\Trail;

/**
 * Singleton registry of named Trail instances. `register('main')` returns the
 * same Trail object on every call within a process so different layers of an
 * application can append segments without passing the Trail through every
 * controller / middleware.
 */
class TrailManager
{
    /**
     * Trail instances keyed by namespace.
     *
     * @var Trail[]
     */
    private static $instances = array();

    /**
     * Return the singleton Trail for the given namespace, creating it on
     * first access.
     *
     * @param  string  $namespace
     * @return Trail
     */
    public static function register($namespace = 'default')
    {
        if (!isset(self::$instances[$namespace])) {
            self::$instances[$namespace] = new Trail();
        }

        return self::$instances[$namespace];
    }

    /**
     * Drop one named instance, or every instance when called with no arg.
     * Provided primarily so tests can reset state between cases.
     *
     * @param  string|null  $namespace
     * @return void
     */
    public static function forget($namespace = null)
    {
        if ($namespace === null) {
            self::$instances = array();
            return;
        }

        unset(self::$instances[$namespace]);
    }
}
