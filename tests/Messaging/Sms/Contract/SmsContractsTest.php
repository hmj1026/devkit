<?php

namespace Devkit\Tests\Messaging\Sms\Contract;

use PHPUnit\Framework\TestCase;

class SmsContractsTest extends TestCase
{
    public function testSmsMessageContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Messaging\Sms\Contract\SmsMessageContract::class));
    }

    public function testSmsResultContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Messaging\Sms\Contract\SmsResultContract::class));
    }

    public function testSmsDriverContractLoads()
    {
        $this->assertTrue(interface_exists(\Devkit\Messaging\Sms\Contract\SmsDriverContract::class));
    }

    public function testSmsDriverContractDeclaresSendSms()
    {
        $reflection = new \ReflectionClass(\Devkit\Messaging\Sms\Contract\SmsDriverContract::class);
        $this->assertTrue($reflection->hasMethod('sendSms'));
        $params = $reflection->getMethod('sendSms')->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(
            \Devkit\Messaging\Sms\Contract\SmsMessageContract::class,
            $params[0]->getType() ? $params[0]->getType()->getName() : null
        );
    }
}
