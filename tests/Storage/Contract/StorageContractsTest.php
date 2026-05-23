<?php

namespace Devkit\Tests\Storage\Contract;

use PHPUnit\Framework\TestCase;

class StorageContractsTest extends TestCase
{
    public function testFileContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Storage\Contract\FileContract::class));
    }

    public function testDirectorContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Storage\Contract\DirectorContract::class));
    }

    public function testFileContractMethods()
    {
        $reflection = new \ReflectionClass(\Devkit\Storage\Contract\FileContract::class);
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        sort($methods);
        $this->assertSame(array('getOriginalName', 'getPath', 'getStoredName', 'getUrl'), $methods);
    }
}
