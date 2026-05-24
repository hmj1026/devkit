<?php

namespace Devkit\Ui\Trail;

/**
 * Breadcrumb trail. Holds an ordered list of TrailTag items and renders them
 * either as the raw collection (via breadcrumb()->all()) or as a joined title
 * string (via title()). Pure PHP — the Laravel facade / SP glue lives under
 * the Devkit\Laravel\Ui\Trail namespace.
 */
class Trail
{
    /**
     * @var TrailTag[]
     */
    protected $items = array();

    /**
     * Separator used by title() to join segment texts.
     *
     * @var string
     */
    protected $separator = ' - ';

    /**
     * Append a segment to the end of the trail.
     *
     * @param  string  $text
     * @param  string|null  $href
     * @return $this
     */
    public function appendItem($text, $href = null)
    {
        $this->items[] = new TrailTag(array('text' => $text, 'href' => $href));

        return $this;
    }

    /**
     * Prepend a segment to the start of the trail.
     *
     * @param  string  $text
     * @param  string|null  $href
     * @return $this
     */
    public function prependItem($text, $href = null)
    {
        array_unshift(
            $this->items,
            new TrailTag(array('text' => $text, 'href' => $href))
        );

        return $this;
    }

    /**
     * Return the rendering helper for the current trail. In v1 this returns
     * the Trail itself so callers can chain ->all(); a dedicated collection
     * class may replace this in a future wave without breaking the contract.
     *
     * @return $this
     */
    public function breadcrumb()
    {
        return $this;
    }

    /**
     * Return all TrailTag items in registration order.
     *
     * @return TrailTag[]
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Set the separator used by title().
     *
     * @param  string  $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Return the trail rendered as a single title string, joining segment
     * texts with the configured separator.
     *
     * @return string
     */
    public function title()
    {
        $texts = array();
        foreach ($this->items as $item) {
            $texts[] = $item->getText();
        }

        return implode($this->separator, $texts);
    }

    /**
     * Drop every registered item. Useful between requests in long-running
     * workers and inside test teardown.
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = array();

        return $this;
    }
}
