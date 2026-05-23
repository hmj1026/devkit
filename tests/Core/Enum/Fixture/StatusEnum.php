<?php

namespace Devkit\Tests\Core\Enum\Fixture;

use Devkit\Core\Enum\AbstractEnum;

class StatusEnum extends AbstractEnum
{
    const ACTIVE = 1;
    const INACTIVE = 0;

    protected static $aliases = array(
        'ACTIVE' => 'enabled',
        'INACTIVE' => 'disabled',
    );

    protected static $contents = array(
        'ACTIVE' => '啟用',
        'INACTIVE' => '停用',
    );
}
