<?php

namespace Devkit\Storage\Enum;

use Devkit\Core\Enum\AbstractEnum;

/**
 * Visibility strings shared across Flysystem 1/2/3. The static helpers
 * normalise between the textual constants we use everywhere and
 * Flysystem v3's `League\Flysystem\Visibility` class constants —
 * which happen to share the same string values, but routing them
 * through the helper keeps consumers insulated from version drift.
 *
 * `PUBLIC` and `PRIVATE` are permitted as class-constant names from
 * PHP 7.0 onward (the keyword reservation was relaxed for constants).
 */
class VisibilityEnum extends AbstractEnum
{
    const PUBLIC = 'public';
    const PRIVATE = 'private';

    /**
     * @var array<string, string>
     */
    protected static $contents = array(
        'PUBLIC' => 'Public (anonymous read)',
        'PRIVATE' => 'Private (signed access only)',
    );

    /**
     * Translate a devkit visibility string to the value Flysystem expects.
     * On v3 (when `League\Flysystem\Visibility` exists) returns the class
     * constant; on v1/v2 returns the plain string. The output value is the
     * same in both branches today — this exists to harden against future
     * drift and to give a single chokepoint for normalisation.
     *
     * @param  string  $visibility
     * @return string
     */
    public static function toFlysystemValue($visibility)
    {
        $normalised = strtolower((string) $visibility);

        if (class_exists('\\League\\Flysystem\\Visibility')) {
            if ($normalised === self::PRIVATE) {
                return \League\Flysystem\Visibility::PRIVATE;
            }

            return \League\Flysystem\Visibility::PUBLIC;
        }

        return $normalised === self::PRIVATE ? self::PRIVATE : self::PUBLIC;
    }

    /**
     * Inverse of {@see toFlysystemValue()} — coerce whatever Flysystem
     * hands back into one of our two canonical strings.
     *
     * @param  mixed  $raw
     * @return string
     */
    public static function fromFlysystemValue($raw)
    {
        $value = is_string($raw) ? strtolower($raw) : '';

        return $value === self::PRIVATE ? self::PRIVATE : self::PUBLIC;
    }
}
