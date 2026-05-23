<?php

namespace Devkit\Tests\Messaging\Sms\Driver;

use Devkit\Http\Client\Exception\RetryExhaustedException;
use Devkit\Messaging\Sms\SmsMessage;
use Devkit\Tests\Messaging\Sms\Fixture\FakeProviderDriver;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AbstractHttpSmsDriverTest extends TestCase
{
    public function testSubclassDispatchesHttpAndParsesResponse()
    {
        $mock = new MockHandler(array(
            new Response(200, array(), json_encode(array('ok' => true, 'id' => 'msg-42'))),
        ));
        $guzzle = new GuzzleClient(array(
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ));

        $driver = new FakeProviderDriver(null, $guzzle, 1, 0);
        $result = $driver->sendSms(new SmsMessage('+886912345678', 'hello'));

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('msg-42', $result->getMessageId());
        $this->assertSame('200', $result->getStatusCode());
        $this->assertSame(array('ok' => true, 'id' => 'msg-42'), $result->getRawResponse());
    }

    public function testRetryInheritedFromGateway503ThenSuccess()
    {
        $mock = new MockHandler(array(
            new Response(503, array(), ''),
            new Response(200, array(), json_encode(array('ok' => true, 'id' => 'msg-retry'))),
        ));
        $guzzle = new GuzzleClient(array(
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ));

        $driver = new FakeProviderDriver(null, $guzzle, 2, 0);
        $result = $driver->sendSms(new SmsMessage('+886912345678', 'try'));

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('msg-retry', $result->getMessageId());
        $this->assertSame(0, $mock->count(), 'both queued responses must be consumed');
    }

    public function testRetryExhaustedBubblesAsException()
    {
        $mock = new MockHandler(array(
            new Response(503),
            new Response(503),
        ));
        $guzzle = new GuzzleClient(array(
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ));

        $driver = new FakeProviderDriver(null, $guzzle, 2, 0);

        $this->expectException(RetryExhaustedException::class);
        $driver->sendSms(new SmsMessage('+886912345678', 'doomed'));
    }

    public function testSendsJsonBodyToConfiguredEndpoint()
    {
        $captured = array();
        $mock = new MockHandler(array(
            new Response(200, array(), json_encode(array('ok' => true, 'id' => 'i'))),
        ));
        $stack = HandlerStack::create($mock);
        // Insert a middleware that captures the outgoing request before MockHandler emits its response.
        $stack->push(function (callable $handler) use (&$captured) {
            return function ($request, array $options) use ($handler, &$captured) {
                $captured[] = $request;
                return $handler($request, $options);
            };
        });
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $driver = new FakeProviderDriver(null, $guzzle, 1, 0);
        $driver->sendSms(new SmsMessage('+886912345678', 'hi', array('sender_id' => 'DEVKIT')));

        $this->assertCount(1, $captured);
        $request = $captured[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://fake-provider.test/v1/send', (string) $request->getUri());
        $this->assertSame('application/json; charset=utf-8', $request->getHeaderLine('Content-Type'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('+886912345678', $body['phone']);
        $this->assertSame('hi', $body['text']);
        $this->assertSame(array('sender_id' => 'DEVKIT'), $body['opts']);
    }
}
