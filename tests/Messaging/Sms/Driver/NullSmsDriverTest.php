<?php

namespace Devkit\Tests\Messaging\Sms\Driver;

use Devkit\Messaging\Sms\Driver\NullSmsDriver;
use Devkit\Messaging\Sms\SmsMessage;
use PHPUnit\Framework\TestCase;

class NullSmsDriverTest extends TestCase
{
    public function testSendSmsRecordsMessageAndReturnsSuccess()
    {
        $driver = new NullSmsDriver();
        $message = new SmsMessage('+886912345678', 'hi', array('sender_id' => 'X'));

        $result = $driver->sendSms($message);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('null-1', $result->getMessageId());
        $this->assertSame('OK', $result->getStatusCode());
        $this->assertSame(array('recorded' => true), $result->getRawResponse());
    }

    public function testSentMessagesReturnsListInOrder()
    {
        $driver = new NullSmsDriver();
        $driver->sendSms(new SmsMessage('+886900000001', 'first'));
        $driver->sendSms(new SmsMessage('+886900000002', 'second'));
        $driver->sendSms(new SmsMessage('+886900000003', 'third'));

        $sent = $driver->sentMessages();
        $this->assertCount(3, $sent);
        $this->assertSame('first', $sent[0]->body());
        $this->assertSame('second', $sent[1]->body());
        $this->assertSame('third', $sent[2]->body());
    }

    public function testIncrementalMessageIds()
    {
        $driver = new NullSmsDriver();

        $first = $driver->sendSms(new SmsMessage('+886900000001', 'a'));
        $second = $driver->sendSms(new SmsMessage('+886900000002', 'b'));

        $this->assertSame('null-1', $first->getMessageId());
        $this->assertSame('null-2', $second->getMessageId());
    }

    public function testResetClearsRecordedMessagesAndIdCounter()
    {
        $driver = new NullSmsDriver();
        $driver->sendSms(new SmsMessage('+886900000001', 'a'));
        $driver->sendSms(new SmsMessage('+886900000002', 'b'));
        $driver->reset();

        $this->assertSame(array(), $driver->sentMessages());
        $result = $driver->sendSms(new SmsMessage('+886900000003', 'c'));
        $this->assertSame('null-1', $result->getMessageId(), 'id counter must restart from 1 after reset');
    }
}
