<?php

namespace Devkit\Storage\Contract;

/**
 * Represents a stored file after a successful upload through the director
 * pipeline. Exposes per-disk path / URL accessors so consumers can reference
 * a single logical file replicated across multiple disks (e.g. local + S3).
 *
 * Pure PHP — no Illuminate imports.
 */
interface FileContract
{
    /**
     * Return the storage path for the file on the named disk, or on the
     * default disk when $disk is null.
     *
     * @param  string|null  $disk
     * @return string
     */
    public function getPath($disk = null);

    /**
     * Return the publicly addressable URL for the file on the named disk,
     * or on the default disk when $disk is null. For private disks an
     * adapter may return a signed URL; implementations document which.
     *
     * @param  string|null  $disk
     * @return string
     */
    public function getUrl($disk = null);

    /**
     * Return the original uploaded filename (pre-rename), useful for
     * Content-Disposition headers and audit logs.
     *
     * @return string
     */
    public function getOriginalName();

    /**
     * Return the storage filename (post-rename / hash-bucketed), distinct
     * from the original name returned by getOriginalName().
     *
     * @return string
     */
    public function getStoredName();
}
