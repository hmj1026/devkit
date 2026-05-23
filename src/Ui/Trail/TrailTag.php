<?php

namespace Devkit\Ui\Trail;

use ArrayAccess;

/**
 * Value object representing a single breadcrumb segment. Implements
 * ArrayAccess so legacy Blade views written against array shapes
 * (`{{ $crumb['text'] }}`) keep working alongside the object accessors.
 *
 * #[\ReturnTypeWillChange] is parsed as a line comment in PHP 7.x and as a
 * native attribute in PHP 8.x. It suppresses the PHP 8.1+ tentative-return-type
 * deprecation warning without forcing PHP-8-only `mixed` declarations, keeping
 * the class loadable on the PHP 7.3 floor.
 */
class TrailTag implements ArrayAccess
{
    /**
     * @var array
     */
    protected $attributes = array();

    public function __construct(array $attributes = array())
    {
        $this->attributes = $attributes;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return isset($this->attributes['text']) ? $this->attributes['text'] : null;
    }

    /**
     * @return mixed
     */
    public function getHref()
    {
        return isset($this->attributes['href']) ? $this->attributes['href'] : null;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
