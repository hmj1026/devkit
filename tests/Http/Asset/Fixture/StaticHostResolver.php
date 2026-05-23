<?php

namespace Devkit\Tests\Http\Asset\Fixture;

use Devkit\Http\Asset\Contract\HostResolverInterface;

/**
 * Returns a fixed origin string. Used by tests to verify host prepending
 * without booting Laravel or pulling request data.
 */
class StaticHostResolver implements HostResolverInterface
{
    /**
     * @var string
     */
    protected $origin;

    public function __construct($origin)
    {
        $this->origin = $origin;
    }

    public function resolve()
    {
        return $this->origin;
    }
}
