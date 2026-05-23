<?php

namespace Devkit\Tests\Laravel\Messaging;

use Devkit\Messaging\Sms\Driver\NullSmsDriver;
use Devkit\Messaging\Sms\SmsManager;
use Devkit\Laravel\Messaging\Sms\SendSmsJob;
use Devkit\Laravel\Messaging\Sms\SmsChannel;
use Devkit\Tests\Laravel\Messaging\Fixture\SmsNotifiable;
use Devkit\Tests\Laravel\Messaging\Fixture\SmsNotification;
use Devkit\Tests\Laravel\TestCase;

class SmsChannelAndJobTest extends TestCase
{
    public function testSmsChannelDispatchesNotificationMessageThroughManager()
    {
        $manager = new SmsManager();
        $channel = new SmsChannel($manager);

        $channel->send(new SmsNotifiable(), new SmsNotification());

        $sent = $manager->driver('null')->sentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('+886912345678', $sent[0]->cellPhone());
        $this->assertSame('hello from notification', $sent[0]->body());
    }

    public function testSendSmsJobDispatchesThroughManager()
    {
        $manager = new SmsManager();
        $job = new SendSmsJob('+886900000000', 'queued body', array('driver' => 'null'));

        $job->handle($manager);

        /** @var NullSmsDriver $driver */
        $driver = $manager->driver('null');
        $this->assertCount(1, $driver->sentMessages());
        $this->assertSame('queued body', $driver->sentMessages()[0]->body());
    }
}
