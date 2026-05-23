<?php

namespace Devkit\Tests\Http\Contract;

use PHPUnit\Framework\TestCase;

class LogObserverContractTest extends TestCase
{
    public function testLogObserverContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Http\Client\Contract\LogObserverContract::class));
    }

    public function testOnRequestAcceptsPsr7Request()
    {
        $reflection = new \ReflectionClass(\Devkit\Http\Client\Contract\LogObserverContract::class);
        $this->assertTrue($reflection->hasMethod('onRequest'));
        $params = $reflection->getMethod('onRequest')->getParameters();
        $this->assertSame(
            \Psr\Http\Message\RequestInterface::class,
            $params[0]->getType() ? $params[0]->getType()->getName() : null
        );
    }

    public function testOnResponseAcceptsPsr7Response()
    {
        $reflection = new \ReflectionClass(\Devkit\Http\Client\Contract\LogObserverContract::class);
        $params = $reflection->getMethod('onResponse')->getParameters();
        $this->assertSame(
            \Psr\Http\Message\ResponseInterface::class,
            $params[0]->getType() ? $params[0]->getType()->getName() : null
        );
    }
}
