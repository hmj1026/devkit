<?php

namespace Devkit\Database\Contract\Entity;

/**
 * Marks a model as carrying an active/inactive status column and exposes the
 * canonical toggle surface (activate / deactivate / isActive). Concrete
 * status values (default 1 = active, 0 = inactive) and the column name are
 * implementation details left to the Laravel-side trait in Wave 5.
 *
 * Pure PHP — no Illuminate imports.
 */
interface HasStatusContract
{
    /**
     * @return bool
     */
    public function isActive();

    /**
     * Flip status to "active" and persist. Implementations return $this
     * to support fluent chaining.
     *
     * @return $this
     */
    public function activate();

    /**
     * Flip status to "inactive" and persist.
     *
     * @return $this
     */
    public function deactivate();
}
