<?php

namespace Devkit\Laravel\Ui\MetaTag;

use Devkit\Ui\MetaTag\Meta;

class MetaRenderer
{
    public static function render(Meta $meta, $placement = 'head')
    {
        $html = array();

        foreach ($meta->tagsAt($placement) as $entry) {
            $html[] = '<meta ' . static::attributes($entry['attributes']) . '>';
        }

        foreach ($meta->stylesAt($placement) as $entry) {
            $html[] = '<link rel="stylesheet" href="' . e($entry['src']) . '"' . static::prefixedAttributes($entry['attributes']) . '>';
        }

        foreach ($meta->scriptsAt($placement) as $entry) {
            $html[] = '<script src="' . e($entry['src']) . '"' . static::prefixedAttributes($entry['attributes']) . '></script>';
        }

        return implode("\n", $html);
    }

    protected static function prefixedAttributes(array $attributes)
    {
        $rendered = static::attributes($attributes);

        return $rendered === '' ? '' : ' ' . $rendered;
    }

    protected static function attributes(array $attributes)
    {
        $parts = array();

        foreach ($attributes as $key => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $parts[] = e($key);
            } else {
                $parts[] = e($key) . '="' . e($value) . '"';
            }
        }

        return implode(' ', $parts);
    }
}
