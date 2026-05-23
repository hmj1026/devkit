<?php

namespace Devkit\Tests\Logging\Contract;

use PHPUnit\Framework\TestCase;

class LogTargetContractTest extends TestCase
{
    public function testLogTargetContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Logging\Contract\LogTargetContract::class));
    }

    public function testSaveAcceptsArrayParameter()
    {
        $reflection = new \ReflectionClass(\Devkit\Logging\Contract\LogTargetContract::class);
        $this->assertTrue($reflection->hasMethod('save'));
        $params = $reflection->getMethod('save')->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->getType() !== null);
        $this->assertSame('array', $params[0]->getType()->getName());
    }
}
