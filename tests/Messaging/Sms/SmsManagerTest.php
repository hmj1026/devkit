<?php

namespace Devkit\Tests\Messaging\Sms;

use Devkit\Messaging\Sms\Contract\SmsDriverContract;
use Devkit\Messaging\Sms\Contract\SmsMessageContract;
use Devkit\Messaging\Sms\Contract\SmsResultContract;
use Devkit\Messaging\Sms\Driver\NullSmsDriver;
use Devkit\Messaging\Sms\Exception\SmsDriverNotRegisteredException;
use Devkit\Messaging\Sms\SmsManager;
use Devkit\Messaging\Sms\SmsResult;
use PHPUnit\Framework\TestCase;

class SmsManagerTest extends TestCase
{
    public function testNullDriverIsAutoRegistered()
    {
        $manager = new SmsManager();

        $this->assertInstanceOf(NullSmsDriver::class, $manager->driver('null'));
    }

    public function testDefaultDriverNameDefaultsToNull()
    {
        $this->assertSame('null', (new SmsManager())->getDefaultDriverName());
    }

    public function testCustomDefaultDriverIsHonoured()
    {
        $manager = new SmsManager('custom');
        $manager->extend('custom', function () {
            return new NullSmsDriver();
        });

        $this->assertSame('custom', $manager->getDefaultDriverName());
        $this->assertInstanceOf(NullSmsDriver::class, $manager->driver());
    }

    public function testUnknownDriverThrows()
    {
        $this->expectException(SmsDriverNotRegisteredException::class);

        (new SmsManager())->driver('nonexistent');
    }

    public function testExtendReplacesFactory()
    {
        $manager = new SmsManager();
        $manager->extend('null', function () {
            $driver = new NullSmsDriver();
            // Pre-flag this instance so we can prove the new factory ran.
            $driver->sendSms(new \Devkit\Messaging\Sms\SmsMessage('+886900000000', 'marker'));
            return $driver;
        });

        $driver = $manager->driver('null');
        $this->assertCount(1, $driver->sentMessages());
        $this->assertSame('marker', $driver->sentMessages()[0]->body());
    }

    public function testDriverCachesInstance()
    {
        $manager = new SmsManager();

        $first = $manager->driver('null');
        $second = $manager->driver('null');

        $this->assertSame($first, $second);
    }

    public function testForgetForcesFactoryRerun()
    {
        $manager = new SmsManager();

        $first = $manager->driver('null');
        $manager->forget('null');
        $second = $manager->driver('null');

        $this->assertNotSame($first, $second);
    }

    public function testLazyFactoryNotCalledUntilDriverRequested()
    {
        $callCount = 0;
        $manager = new SmsManager('custom');
        $manager->extend('custom', function () use (&$callCount) {
            $callCount++;
            return new NullSmsDriver();
        });

        $this->assertSame(0, $callCount, 'factory must not run at registration time');

        $manager->driver('custom');
        $this->assertSame(1, $callCount);

        $manager->driver('custom');
        $this->assertSame(1, $callCount, 'cached instance — factory does not re-run');
    }

    public function testSendSmsShorthandProxiesToDefaultDriver()
    {
        $manager = new SmsManager();
        $result = $manager->sendSms('+886912345678', 'hello');

        $this->assertInstanceOf(SmsResultContract::class, $result);
        $this->assertTrue($result->isSuccessful());

        $driver = $manager->driver('null');
        $this->assertCount(1, $driver->sentMessages());
        $sent = $driver->sentMessages()[0];
        $this->assertSame('+886912345678', $sent->cellPhone());
        $this->assertSame('hello', $sent->body());
        $this->assertSame(array(), $sent->options());
    }

    public function testSendSmsShorthandPassesOptionsThrough()
    {
        $manager = new SmsManager();
        $manager->sendSms('+886912345678', 'hi', array('sender_id' => 'DEVKIT'));

        $sent = $manager->driver('null')->sentMessages()[0];
        $this->assertSame(array('sender_id' => 'DEVKIT'), $sent->options());
    }

    public function testCustomDriverThroughExtendIsCallable()
    {
        $captured = null;
        $manager = new SmsManager();
        $fake = new class($captured) implements SmsDriverContract {
            private $captured;
            public function __construct(&$captured) { $this->captured = &$captured; }
            public function sendSms(SmsMessageContract $message)
            {
                $this->captured = $message;
                return new SmsResult(true, 'fake-1', 'ACCEPTED');
            }
        };
        $manager->extend('fake', function () use ($fake) { return $fake; });

        $result = $manager->driver('fake')->sendSms(
            new \Devkit\Messaging\Sms\SmsMessage('+886912345678', 'via fake')
        );

        $this->assertSame('fake-1', $result->getMessageId());
        $this->assertSame('+886912345678', $captured->cellPhone());
    }
}
