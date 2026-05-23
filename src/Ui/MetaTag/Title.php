<?php

namespace Devkit\Ui\MetaTag;

/**
 * Title segment manager. Holds an ordered list of title parts and renders
 * them joined by a configurable separator. Pure PHP — Meta delegates title
 * composition to this class so callers can mutate the separator independently
 * from segment registration order.
 */
class Title
{
    /**
     * @var string[]
     */
    protected $segments = array();

    /**
     * @var string
     */
    protected $separator = ' - ';

    /**
     * @param  string  $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @param  string  $text
     * @return $this
     */
    public function append($text)
    {
        $this->segments[] = $text;

        return $this;
    }

    /**
     * @param  string  $text
     * @return $this
     */
    public function prepend($text)
    {
        array_unshift($this->segments, $text);

        return $this;
    }

    /**
     * @return string[]
     */
    public function segments()
    {
        return $this->segments;
    }

    /**
     * @return string
     */
    public function render()
    {
        return implode($this->separator, $this->segments);
    }
}
