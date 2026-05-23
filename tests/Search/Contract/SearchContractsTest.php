<?php

namespace Devkit\Tests\Search\Contract;

use PHPUnit\Framework\TestCase;

class SearchContractsTest extends TestCase
{
    public function testConnectionContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Search\Contract\ConnectionContract::class));
    }

    public function testIndexContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Search\Contract\IndexContract::class));
    }

    public function testIndexContractDeclaresExpectedMethods()
    {
        $reflection = new \ReflectionClass(\Devkit\Search\Contract\IndexContract::class);
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        sort($methods);
        $this->assertSame(array('getIndex', 'getMapping', 'getPartition', 'save'), $methods);
    }
}
