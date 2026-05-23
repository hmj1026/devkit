<?php

namespace Devkit\Core\Enum;

use ReflectionClass;

abstract class AbstractEnum
{
    /**
     * Map of constant name to alias string. Subclasses override.
     *
     * @var array
     */
    protected static $aliases = array();

    /**
     * Map of constant name to human-readable label. Subclasses override.
     *
     * @var array
     */
    protected static $contents = array();

    /**
     * Memoised reflection result keyed by called class name.
     *
     * @var array
     */
    private static $constantsCache = array();

    /**
     * Return every public constant declared on the called subclass as
     * an associative array of name => value.
     *
     * @return array
     */
    public static function toArray()
    {
        $class = get_called_class();

        if (!isset(self::$constantsCache[$class])) {
            $reflection = new ReflectionClass($class);
            self::$constantsCache[$class] = $reflection->getConstants();
        }

        return self::$constantsCache[$class];
    }

    /**
     * Return the declared constant values in declaration order.
     *
     * @return array
     */
    public static function values()
    {
        return array_values(static::toArray());
    }

    /**
     * Return the declared constant names in declaration order.
     *
     * @return array
     */
    public static function keys()
    {
        return array_keys(static::toArray());
    }

    /**
     * Resolve a constant value by its declared alias. Returns null when the
     * alias is not registered or its constant cannot be found.
     *
     * @param  string  $alias
     * @return mixed|null
     */
    public static function getByAlias($alias)
    {
        $aliases = static::$aliases;
        $constantName = array_search($alias, $aliases, true);

        if ($constantName === false) {
            return null;
        }

        $constants = static::toArray();

        if (!array_key_exists($constantName, $constants)) {
            return null;
        }

        return $constants[$constantName];
    }

    /**
     * Return the constant name to label map declared via $contents.
     *
     * @return array
     */
    public static function mapping()
    {
        return static::$contents;
    }

    /**
     * Resolve the human-readable label for a constant name, falling back to
     * the constant name itself when no entry exists in $contents.
     *
     * @param  string  $constantName
     * @return string
     */
    public static function content($constantName)
    {
        $contents = static::$contents;

        if (isset($contents[$constantName])) {
            return $contents[$constantName];
        }

        return $constantName;
    }
}
