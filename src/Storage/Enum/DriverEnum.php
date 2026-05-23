<?php

namespace Devkit\Storage\Enum;

use Devkit\Core\Enum\AbstractEnum;

/**
 * Director driver keys understood by storage managers. Values are
 * lowercase to match the conventional config key style.
 */
class DriverEnum extends AbstractEnum
{
    const FILE = 'file';
    const IMAGE = 'image';

    /**
     * @var array<string, string>
     */
    protected static $contents = array(
        'FILE' => 'Generic file',
        'IMAGE' => 'Image (width/height extracted)',
    );
}
