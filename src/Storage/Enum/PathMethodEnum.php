<?php

namespace Devkit\Storage\Enum;

use Devkit\Core\Enum\AbstractEnum;

/**
 * Strategies for generating a storage path prefix during upload.
 *
 *   MD5  → first 2 + next 2 hex chars of the stored filename
 *          (e.g. abcdef1234 → "ab/cd/abcdef1234.ext")
 *   DATE → Y/m/d folder bucketing
 */
class PathMethodEnum extends AbstractEnum
{
    const MD5 = 'md5';
    const DATE = 'date';

    /**
     * @var array<string, string>
     */
    protected static $contents = array(
        'MD5' => 'MD5 hash bucket',
        'DATE' => 'Y/m/d date bucket',
    );
}
