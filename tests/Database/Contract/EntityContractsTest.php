<?php

namespace Devkit\Tests\Database\Contract;

use PHPUnit\Framework\TestCase;

class EntityContractsTest extends TestCase
{
    public function testHasUuidContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Database\Contract\Entity\HasUuidContract::class));
    }

    public function testHasStatusContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Database\Contract\Entity\HasStatusContract::class));
    }

    public function testHasAuditLogContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Database\Contract\Entity\HasAuditLogContract::class));
    }

    public function testHasStatusContractDeclaresExpectedMethods()
    {
        $reflection = new \ReflectionClass(\Devkit\Database\Contract\Entity\HasStatusContract::class);
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        sort($methods);
        $this->assertSame(array('activate', 'deactivate', 'isActive'), $methods);
    }
}
