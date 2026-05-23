<?php

namespace Devkit\Database\Contract\Entity;

/**
 * Marks a model as carrying a UUID identifier (typically v4) on a dedicated
 * column. Application code SHALL type-hint this interface instead of the
 * concrete Laravel trait when accepting "any UUID-bearing entity".
 *
 * Pure PHP — no Illuminate imports.
 */
interface HasUuidContract
{
    /**
     * Return the entity's UUID, or null when not yet assigned.
     *
     * @return string|null
     */
    public function getUuid();
}
