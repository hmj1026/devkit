<?php

namespace Devkit\Tests\Core\Enum\Fixture;

use Devkit\Core\Enum\AbstractEnum;

/**
 * Fixture with no $aliases / $contents declared so the AbstractEnum
 * defaults (empty arrays) are exercised.
 */
class SparseEnum extends AbstractEnum
{
    const RED = 'r';
    const GREEN = 'g';
    const BLUE = 'b';
}
