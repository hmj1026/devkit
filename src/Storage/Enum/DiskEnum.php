<?php

namespace Devkit\Storage\Enum;

use Devkit\Core\Enum\AbstractEnum;

/**
 * Conventional disk names. Framework-agnostic strings; the Laravel
 * bridge maps these to filesystems.disks.* entries.
 */
class DiskEnum extends AbstractEnum
{
    const LOCAL = 'local';
    const S3 = 's3';

    /**
     * @var array<string, string>
     */
    protected static $contents = array(
        'LOCAL' => 'Local filesystem',
        'S3' => 'Amazon S3 (or compatible)',
    );
}
